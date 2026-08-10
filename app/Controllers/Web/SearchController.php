<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Matchmaking\MemberSearchService;
use App\Validation\Search\MemberSearchValidation;
use App\Services\Matchmaking\MemberMatchmakingService;
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

        /*
     * Read only allow-listed Search criteria.
     */
        $input =
            $this->searchInput();

        /*
     * Server-side scalar validation remains authoritative.
     *
     * Active master IDs and Search relationship rules are additionally
     * validated by MemberSearchService.
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
                            $validation
                                ->getErrors()
                        ),
                    ]
                );
        }

        try {
            /** @var MemberSearchService $service */
            $service =
                service(
                    'memberSearchService'
                );

            $pageData =
                $service->search(
                    $userId,
                    $input
                );

            /*
         * Configure the standard CI4 Pager so Search uses the same reusable
         * application Pagination component as other paginated screens.
         *
         * Use the default group because SearchController already consumes the
         * normal "page" query parameter.
         */
            $pager =
                service(
                    'pager'
                );

            $pager->setPath(
                route_to(
                    'web.search.results'
                ),
                'default'
            );

            $pager->store(
                'default',
                max(
                    1,
                    (int) (
                        $pageData['page']
                        ?? 1
                    )
                ),
                max(
                    1,
                    (int) (
                        $pageData['perPage']
                        ?? MemberSearchService::PER_PAGE
                    )
                ),
                max(
                    0,
                    (int) (
                        $pageData['total']
                        ?? 0
                    )
                )
            );

            /*
         * Back to Search keeps matching criteria but removes result-only state.
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

                        'pager' =>
                        $pager,

                        'pagerGroup' =>
                        'default',

                        /*
                     * Existing Profile View action JS handles the Interest
                     * loader on Search result cards as well.
                     */
                        'pageScripts' => [
                            'assets/js/pages/member-profile-actions.js',
                        ],
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
                        $exception
                            ->getMessage(),
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
     * Display one existing member-activity / matchmaking collection using the
     * normal Search profile-listing UI.
     *
     * No interaction or matchmaking logic is recreated here. All source
     * collections come from MemberMatchmakingService.
     */
    public function quick(
        string $type
    ): string|RedirectResponse {
        $userId =
            $this->authenticatedUserId();

        /*
     * Only known Quick Link collections may be requested.
     *
     * URL-controlled values never become arbitrary array keys or service
     * operations without first passing this allow-list.
     */
        $supportedTypes = [
            'shortlisted-by-you' => [
                'collection' =>
                'profilesShortlistedByYou',

                'title' =>
                'Shortlisted by You',

                'help' =>
                'Profiles you have added to your shortlist.',
            ],

            'shortlisted-you' => [
                'collection' =>
                'whoShortlistedYou',

                'title' =>
                'Shortlisted You',

                'help' =>
                'Members who have added your profile to their shortlist.',
            ],

            'viewed-you' => [
                'collection' =>
                'profileVisitors',

                'title' =>
                'Viewed You',

                'help' =>
                'Members who have viewed your profile.',
            ],

            'viewed-by-you' => [
                'collection' =>
                'profilesViewed',

                'title' =>
                'Viewed by You',

                'help' =>
                'Profiles you have viewed recently.',
            ],

            'new-profiles' => [
                'collection' =>
                'newMatches',

                'title' =>
                'New Profiles',

                'help' =>
                'Recently joined profiles matching your partner preferences.',
            ],
        ];

        $type =
            mb_strtolower(
                trim(
                    $type
                )
            );

        if (
            !isset(
                $supportedTypes[$type]
            )
        ) {
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
                        'Quick Search unavailable',

                        'message' =>
                        'The requested Quick Search could not be found.',
                    ]
                );
        }

        try {
            /** @var MemberMatchmakingService $matchmakingService */
            $matchmakingService =
                service(
                    'memberMatchmakingService'
                );

            /*
         * Reuse the existing matchmaking/activity collections.
         */
            $collections =
                $matchmakingService
                ->dashboardCollections(
                    $userId
                );

            $configuration =
                $supportedTypes[$type];

            $collectionKey =
                (string)
                $configuration['collection'];

            $profiles =
                isset(
                    $collections[$collectionKey]
                )
                && is_array(
                    $collections[$collectionKey]
                )
                ? array_values(
                    $collections[$collectionKey]
                )
                : [];

            /*
         * Convert the existing matchmaking card contract into the Search card
         * contract without changing the underlying matchmaking logic.
         */
            $profiles =
                $this->quickProfiles(
                    $profiles
                );

            return view(
                'Pages/Search/Results',
                [
                    'pageTitle' =>
                    (string)
                    $configuration['title'],

                    'resultTitle' =>
                    (string)
                    $configuration['title'],

                    'resultHelp' =>
                    (string)
                    $configuration['help'],

                    'profiles' =>
                    $profiles,

                    'total' =>
                    count(
                        $profiles
                    ),

                    'backToSearchUrl' =>
                    route_to(
                        'web.search'
                    ),

                    /*
                 * Reuse Profile View Interest loader/functionality.
                 */
                    'pageScripts' => [
                        'assets/js/pages/member-profile-actions.js',
                    ],
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
                    'member_search_quick_results',

                    'controller' =>
                    self::class,

                    'method' =>
                    __FUNCTION__,

                    'member_id' =>
                    $userId,

                    'quick_type' =>
                    $type,
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
                        'Quick Search unavailable',

                        'message' =>
                        'The requested profiles could not be loaded. '
                            . 'Please try again.',
                    ]
                );
        }
    }

    /**
     * Normalize existing Matchmaking profile cards for the Search result-card
     * partial.
     *
     * The underlying candidates remain those produced by
     * MemberMatchmakingService.
     *
     * @param list<array<string, mixed>> $profiles
     *
     * @return list<array<string, mixed>>
     */
    private function quickProfiles(
        array $profiles
    ): array {
        $result = [];

        foreach (
            $profiles
            as $profile
        ) {
            if (!is_array($profile)) {
                continue;
            }

            /*
         * MemberMatchmakingService already builds member-facing presentation
         * rows. Resolve only the field-name differences required by Search.
         */
            $reference =
                trim(
                    (string) (
                        $profile['referenceId']
                        ?? $profile['profile_ref_number']
                        ?? ''
                    )
                );

            if ($reference === '') {
                continue;
            }

            $result[] =
                array_merge(
                    $profile,
                    [
                        'referenceId' =>
                        $reference,

                        'profileUrl' =>
                        route_to(
                            'web.members.view',
                            $reference
                        ),

                        'interestUrl' =>
                        route_to(
                            'web.members.interest',
                            $reference
                        ),
                    ]
                );
        }

        return $result;
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
