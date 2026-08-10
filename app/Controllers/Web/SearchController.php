<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Matchmaking\MemberSearchService;
use App\Validation\Search\MemberSearchValidation;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

final class SearchController extends BaseController
{
    /**
     * Display Basic or Advanced member Search.
     */
    public function index(): string
    {
        $userId =
            $this->authenticatedUserId();

        /*
     * Read only explicitly supported Search parameters.
     */
        $input =
            $this->searchInput();

        /*
     * Server-side validation is authoritative.
     *
     * Master-data arrays are subsequently validated by MemberSearchService.
     */
        $validation =
            service(
                'validation'
            );

        $validation->setRules(
            MemberSearchValidation::rules()
        );

        if (
            !$validation->run(
                $input
            )
        ) {
            return $this->searchView(
                $userId,
                $input,
                [
                    'type' =>
                    'danger',

                    'title' =>
                    'Search values need attention',

                    'message' =>
                    implode(
                        ' ',
                        $validation->getErrors()
                    ),
                ]
            );
        }

        try {
            /** @var MemberSearchService $service */
            $service = service(
                'memberSearchService'
            );

            $pageData =
                $service->search(
                    $userId,
                    $input
                );

            return view(
                'Pages/Search/Index',
                array_merge(
                    $pageData,
                    [
                        'pageTitle' =>
                        'Search',

                        'formAlert' =>
                        $this->readFormAlert(),

                        'pageScripts' => [
                            'assets/js/pages/member-search.js',
                        ],
                    ]
                )
            );
        } catch (
            DomainException $exception
        ) {
            return $this->searchView(
                $userId,
                $input,
                [
                    'type' =>
                    'danger',

                    'title' =>
                    'Search could not be completed',

                    'message' =>
                    $exception->getMessage(),
                ]
            );
        } catch (
            Throwable $exception
        ) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                [
                    'operation' =>
                    'member_search',

                    'controller' =>
                    self::class,

                    'method' =>
                    __FUNCTION__,

                    'member_id' =>
                    $userId,
                ]
            );

            return $this->searchView(
                $userId,
                [],
                [
                    'type' =>
                    'danger',

                    'title' =>
                    'Search unavailable',

                    'message' =>
                    'We could not complete the search. '
                        . 'Please try again.',
                ]
            );
        }
    }

    public function profile(): RedirectResponse
    {
        $userId =
            $this->authenticatedUserId();

        $reference =
            mb_strtoupper(
                trim(
                    (string)
                    $this->request
                        ->getGet(
                            'profile_id'
                        )
                )
            );

        if ($reference === '') {
            return redirect()
                ->to(
                    route_to(
                        'web.search'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'warning',

                        'title' =>
                        'Profile ID required',

                        'message' =>
                        'Please enter a Profile ID.',
                    ]
                );
        }

        /** @var MemberSearchService $service */
        $service = service(
            'memberSearchService'
        );

        $profile =
            $service
            ->profileByReference(
                $userId,
                $reference
            );

        if ($profile === null) {
            return redirect()
                ->to(
                    route_to(
                        'web.search'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'warning',

                        'title' =>
                        'Profile not available',

                        'message' =>
                        'The requested active profile could not be found.',
                    ]
                );
        }

        return redirect()
            ->to(
                route_to(
                    'web.members.view',
                    (string)
                    $profile['profile_ref_number']
                )
            );
    }

    public function cities(): ResponseInterface
    {
        $stateIds =
            $this->request
            ->getGet(
                'state_ids'
            );

        $stateIds =
            is_array($stateIds)
            ? $stateIds
            : [];

        $stateIds =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'intval',
                            $stateIds
                        ),
                        static fn(
                            int $id
                        ): bool =>
                        $id > 0
                    )
                )
            );

        $cities =
            service(
                'profileMasterDataService'
            )->citiesForStates(
                $stateIds
            );

        return $this->response
            ->setJSON(
                [
                    'cities' =>
                    $cities,
                ]
            );
    }

    /**
     * Render Search while retaining master data after a validation/business error.
     *
     * @param array<string, mixed>      $input
     * @param array<string, string>|null $formAlert
     */
    private function searchView(
        int $userId,
        array $input,
        ?array $formAlert = null
    ): string {
        /** @var MemberSearchService $service */
        $service = service(
            'memberSearchService'
        );

        /*
     * An empty Search is safe and also provides all currently active
     * master-data collections required by the form.
     */
        $pageData =
            $service->search(
                $userId,
                []
            );

        $requestedMode =
            mb_strtolower(
                trim(
                    (string) (
                        $input['mode']
                        ?? ''
                    )
                )
            );

        $pageData['mode'] =
            $requestedMode === 'advanced'
            ? 'advanced'
            : 'basic';

        /*
     * Preserve submitted filters so the user can correct only the invalid
     * value instead of rebuilding the complete search.
     */
        $pageData['filters'] =
            $input;

        return view(
            'Pages/Search/Index',
            array_merge(
                $pageData,
                [
                    'pageTitle' =>
                    'Search',

                    'formAlert' =>
                    $formAlert,

                    'pageScripts' => [
                        'assets/js/pages/member-search.js',
                    ],
                ]
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function searchInput(): array
    {
        $arrayFields = [
            'marital_status_ids',
            'state_ids',
            'city_ids',
            'photo_visibility',
            'community_ids',
            'managed_by',
            'education_ids',
            'occupation_ids',
            'employed_in',
            'lifestyle_option_ids',
        ];

        $input = [
            'mode' =>
            trim(
                (string)
                $this->request
                    ->getGet(
                        'mode'
                    )
            ),

            'age_min' =>
            trim(
                (string)
                $this->request
                    ->getGet(
                        'age_min'
                    )
            ),

            'age_max' =>
            trim(
                (string)
                $this->request
                    ->getGet(
                        'age_max'
                    )
            ),

            'height_min_id' =>
            trim(
                (string)
                $this->request
                    ->getGet(
                        'height_min_id'
                    )
            ),

            'height_max_id' =>
            trim(
                (string)
                $this->request
                    ->getGet(
                        'height_max_id'
                    )
            ),

            'annual_income_from_id' =>
            trim(
                (string)
                $this->request
                    ->getGet(
                        'annual_income_from_id'
                    )
            ),

            'annual_income_to_id' =>
            trim(
                (string)
                $this->request
                    ->getGet(
                        'annual_income_to_id'
                    )
            ),

            'sort' =>
            trim(
                (string)
                $this->request
                    ->getGet(
                        'sort'
                    )
            ),

            'page' =>
            trim(
                (string)
                $this->request
                    ->getGet(
                        'page'
                    )
            ),
        ];

        foreach (
            $arrayFields
            as $field
        ) {
            $submitted =
                $this->request
                ->getGet(
                    $field
                );

            $input[$field] =
                is_array($submitted)
                ? array_values(
                    $submitted
                )
                : [];
        }

        return $input;
    }
}
