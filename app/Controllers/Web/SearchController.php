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

/**
 * Handles authenticated matrimonial Search.
 *
 * Search form and Search results intentionally use separate pages while
 * retaining all criteria through the URL query string.
 */
final class SearchController extends BaseController
{
    /**
     * Display Basic / Advanced Search criteria page.
     */
    public function index(): string
    {
        $userId =
            $this->authenticatedUserId();

        /*
         * Read any criteria supplied by "Back to Search".
         *
         * These values are used only to repopulate the form. Search execution
         * happens exclusively through results().
         */
        $input =
            $this->searchInput();

        try {
            /** @var MemberSearchService $service */
            $service = service(
                'memberSearchService'
            );

            $pageData =
                $service->formData(
                    $userId,
                    $input
                );

            return view(
                'Pages/Search/Index',
                array_merge(
                    $pageData,
                    [
                        'pageTitle' =>
                        'Search Profiles',

                        'formAlert' =>
                        $this->readFormAlert(),

                        'pageScripts' => [
                            'assets/js/pages/member-search.js',
                        ],
                    ]
                )
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
                    'member_search_form',

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
                    'Search Profiles',

                    'mode' =>
                    'basic',

                    'filters' =>
                    [],

                    'masterData' =>
                    [],

                    'formAlert' => [
                        'type' =>
                        'danger',

                        'title' =>
                        'Search unavailable',

                        'message' =>
                        'Search options could not be loaded. '
                            . 'Please try again.',
                    ],

                    'pageScripts' => [
                        'assets/js/pages/member-search.js',
                    ],
                ]
            );
        }
    }

    /**
     * Execute Search and display matching profiles.
     */
    public function results(): string|RedirectResponse
    {
        $userId =
            $this->authenticatedUserId();

        $input =
            $this->searchInput();

        /*
         * Server-side scalar validation remains authoritative.
         *
         * Master IDs are additionally checked against active master data by
         * MemberSearchService.
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
            return redirect()
                ->to(
                    $this->searchUrl(
                        $input
                    )
                )
                ->with(
                    'formAlert',
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

            /*
             * Back-to-Search deliberately excludes page and sort because
             * they are result-view concerns rather than matching criteria.
             */
            $backToSearchUrl =
                $this->searchUrl(
                    $input
                );

            return view(
                'Pages/Search/Results',
                array_merge(
                    $pageData,
                    [
                        'pageTitle' =>
                        'Search Results',

                        'backToSearchUrl' =>
                        $backToSearchUrl,

                        'formAlert' =>
                        $this->readFormAlert(),
                    ]
                )
            );
        } catch (
            DomainException $exception
        ) {
            return redirect()
                ->to(
                    $this->searchUrl(
                        $input
                    )
                )
                ->with(
                    'formAlert',
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
                    'member_search_results',

                    'controller' =>
                    self::class,

                    'method' =>
                    __FUNCTION__,

                    'member_id' =>
                    $userId,
                ]
            );

            return redirect()
                ->to(
                    $this->searchUrl(
                        $input
                    )
                )
                ->with(
                    'formAlert',
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

    /**
     * Universal exact Profile-ID Search.
     *
     * Existing member-discovery restrictions are deliberately retained.
     */
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

        try {
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
                            'The requested active profile '
                                . 'could not be found.',
                        ]
                    );
            }

            return redirect()
                ->to(
                    route_to(
                        'web.members.view',
                        (string) (
                            $profile['profile_ref_number']
                            ?? ''
                        )
                    )
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
                    'member_profile_id_search',

                    'controller' =>
                    self::class,

                    'method' =>
                    __FUNCTION__,

                    'member_id' =>
                    $userId,
                ]
            );

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
                        'danger',

                        'title' =>
                        'Profile Search unavailable',

                        'message' =>
                        'The profile could not be opened. '
                            . 'Please try again.',
                    ]
                );
        }
    }

    /**
     * Return active cities for one or more selected states.
     */
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
                            int $stateId
                        ): bool =>
                        $stateId > 0
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
     * Read supported Search query parameters.
     *
     * Search criteria are explicitly allow-listed so arbitrary browser query
     * parameters never enter the Search service.
     *
     * @return array<string, mixed>
     */
    private function searchInput(): array
    {
        /*
     * Multi-value filters.
     *
     * Annual Income now follows the same multi-bracket approach already used
     * by Partner Preference.
     */
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
            'annual_income_ids',
            'lifestyle_option_ids',
        ];

        /*
     * Scalar filters.
     */
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

        /*
     * Normalize every multi-value field.
     */
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

    /**
     * Build the Search-form URL while preserving matching criteria.
     *
     * Result-only state such as pagination and sorting is removed.
     *
     * @param array<string, mixed> $input
     */
    private function searchUrl(
        array $input
    ): string {
        unset(
            $input['page'],
            $input['sort']
        );

        $query =
            http_build_query(
                array_filter(
                    $input,
                    static function (
                        mixed $value
                    ): bool {
                        if (is_array($value)) {
                            return $value !== [];
                        }

                        return trim(
                            (string) $value
                        ) !== '';
                    }
                )
            );

        $url =
            route_to(
                'web.search'
            );

        return $query !== ''
            ? $url . '?' . $query
            : $url;
    }
}
