<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Matchmaking\MemberSearchService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

final class SearchController extends BaseController
{
    public function index(): string
    {
        $userId =
            $this->authenticatedUserId();

        $input =
            $this->searchInput();

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
            return view(
                'Pages/Search/Index',
                [
                    'pageTitle' =>
                    'Search',

                    'mode' =>
                    $input['mode']
                        ?? 'basic',

                    'sort' =>
                    $input['sort']
                        ?? 'default',

                    'profiles' =>
                    [],

                    'total' =>
                    0,

                    'page' =>
                    1,

                    'totalPages' =>
                    1,

                    'filters' =>
                    $input,

                    'masterData' =>
                    service(
                        'memberSearchService'
                    )->search(
                        $userId,
                        []
                    )['masterData'],

                    'formAlert' => [
                        'type' =>
                        'danger',

                        'title' =>
                        'Search could not be completed',

                        'message' =>
                        $exception
                            ->getMessage(),
                    ],

                    'pageScripts' => [
                        'assets/js/pages/member-search.js',
                    ],
                ]
            );
        } catch (Throwable $exception) {
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

            return view(
                'Pages/Search/Index',
                [
                    'pageTitle' =>
                    'Search',

                    'mode' =>
                    'basic',

                    'profiles' =>
                    [],

                    'total' =>
                    0,

                    'page' =>
                    1,

                    'totalPages' =>
                    1,

                    'masterData' =>
                    [],

                    'filters' =>
                    [],

                    'formAlert' => [
                        'type' =>
                        'danger',

                        'title' =>
                        'Search unavailable',

                        'message' =>
                        'We could not complete the search. Please try again.',
                    ],

                    'pageScripts' => [
                        'assets/js/pages/member-search.js',
                    ],
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
