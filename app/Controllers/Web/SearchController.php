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
                            'assets/js/components/submit-loader.js',
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

                    /*
                    * Fail closed if Search form state could not be resolved.
                    *
                    * Never accidentally expose a membership-controlled feature because its
                    * entitlement lookup failed.
                    */
                    'canUseAdvancedSearch' =>
                    false,

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
                        'assets/js/components/submit-loader.js',
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
 * --------------------------------------------------------------------------
 * Resolve result-page context
 * --------------------------------------------------------------------------
 *
 * Search and Matches reuse Results.php, but Matches has a different heading
 * and deliberately does not display Search-specific navigation.
 */

            $resultActivity =
                trim(
                    (string) (
                        $pageData['activity']
                        ?? ''
                    )
                );

            $isAllMatches =
                $resultActivity
                === 'all-matches';

            $resultTitle =
                $isAllMatches
                ? 'All Matches'
                : 'Search Results';

            $showBackToSearch =
                !$isAllMatches;

            $showSearchCriteria =
                !$isAllMatches;

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
                        $resultTitle,

                        'resultTitle' =>
                        $resultTitle,

                        /*
 * Matches originates from the primary member menu and therefore does not
 * display Search-specific Back/Modify controls.
 */
                        'showBackToSearch' =>
                        $showBackToSearch,

                        'showSearchCriteria' =>
                        $showSearchCriteria,

                        'backToSearchUrl' =>
                        $backToSearchUrl,

                        'formAlert' =>
                        $this->readFormAlert(),

                        'pager' =>
                        $pager,

                        'pagerGroup' =>
                        'default',

                        /*
                        * One Report CAPTCHA is generated for the complete result page.
                        *
                        * Do not generate one challenge per card because the CAPTCHA service owns
                        * session challenge state.
                        */
                        'reportCaptcha' =>
                        service(
                            'memberProfileReportCaptchaService'
                        )->generate(),

                        /*
                     * Existing Profile View action JS handles the Interest
                     * loader on Search result cards as well.
                     */
                        'pageScripts' => [
                            'assets/js/pages/member-profile-actions.js',
                            'assets/js/pages/search-results.js',
                            'assets/js/components/form-validator.js',
                            'assets/js/components/submit-loader.js',
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
     * IMPORTANT:
     *
     * Profile-ID Search returns ProfileCard for both Free and Paid members.
     *
     * It must never bypass the Full Profile membership policy by redirecting
     * directly to MemberProfileController.
     */
    public function profile(): string|RedirectResponse
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
                ->profileCardByReference(
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

            return view(
                'Pages/Search/ProfileResult',
                [
                    'pageTitle' =>
                    'Profile Search',

                    'profile' =>
                    $profile,

                    /*
                 * Report is available to Free and Paid members.
                 */
                    'reportCaptcha' =>
                    service(
                        'memberProfileReportCaptchaService'
                    )->generate(),

                    'formAlert' =>
                    $this->readFormAlert(),

                    'pageScripts' => [
                        'assets/js/components/form-validator.js',
                        'assets/js/components/submit-loader.js',
                        'assets/js/pages/member-profile-actions.js',
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
                        'The profile could not be loaded. '
                            . 'Please try again.',
                    ]
                );
        }
    }

    /**
     * Return active states for zero, one or multiple selected countries.
     */
    public function states(): ResponseInterface
    {
        $countryIds =
            $this->request
            ->getGet(
                'country_ids'
            );

        $countryIds =
            is_array($countryIds)
            ? $countryIds
            : [];

        $countryIds =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'intval',
                            $countryIds
                        ),
                        static fn(
                            int $countryId
                        ): bool =>
                        $countryId > 0
                    )
                )
            );

        $states =
            service(
                'profileMasterDataService'
            )->statesForCountries(
                $countryIds
            );

        return $this->response
            ->setJSON(
                [
                    'states' =>
                    $states,
                ]
            );
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
     * The "activity" value is an internal Quick Link Search preset. It still
     * executes through the normal Search results pipeline.
     *
     * @return array<string, mixed>
     */
    private function searchInput(): array
    {
        /*
     * ----------------------------------------------------------------------
     * Local Search input declarations
     * ----------------------------------------------------------------------
     */

        /*
     * Multi-value filters.
     *
     * Annual Income follows the same multi-bracket approach already used by
     * Partner Preference.
     */
        $arrayFields = [
            'marital_status_ids',
            'country_ids',
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
     * Scalar Search filters.
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

            /*
         * Activity is an allow-listed Search preset.
         *
         * Examples:
         *
         * shortlisted-by-you
         * shortlisted-you
         * viewed-you
         * viewed-by-you
         */
            'activity' =>
            trim(
                (string)
                $this->request
                    ->getGet(
                        'activity'
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
                is_array(
                    $submitted
                )
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
        /*
        * Result-only and Quick-Link-only state must never become editable Search
        * form criteria.
        */
        unset(
            $input['page'],
            $input['sort'],
            $input['activity']
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
