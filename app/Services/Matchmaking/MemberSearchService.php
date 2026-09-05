<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Models\MemberMatchCandidateModel;
use App\Models\UserModel;
use App\Services\Profile\LifestyleService;
use App\Services\Profile\ProfileMasterDataService;
use App\Validation\RegisterFreeValidation;
use App\Services\Membership\MembershipEntitlementService;
use App\Services\Profile\MemberPhotoUrlService;
use DomainException;

final class MemberSearchService
{
    public const PER_PAGE = 10;

    /**
     * Create the member Search service.
     *
     * MemberInteractionService remains the single authority for member-to-member
     * Interest state.
     *
     * MemberProfilePresentationService provides the common member-card contract.
     */
    public function __construct(
        private readonly UserModel
        $userModel,

        private readonly MemberMatchCandidateModel
        $candidateModel,

        private readonly MemberInteractionService
        $interactionService,

        private readonly MemberProfilePresentationService
        $profilePresentationService,

        private readonly ProfileMasterDataService
        $masterDataService,

        private readonly LifestyleService
        $lifestyleService,

        /*
        * Partner Preference remains the single authority for calculating the
        * viewer-specific preference percentage.
        *
        * IMPORTANT:
        *
        * Filtered Search does NOT use Partner Preference as candidate eligibility.
        * The calculated percentage is supplied only as one weighted component of
        * MemberMatchScoreService.
        */
        private readonly PartnerPreferenceMatchService
        $partnerPreferenceMatchService,

        /*
        * Final weighted ranking authority shared with Dashboard.
        */
        private readonly MemberMatchScoreService
        $matchScoreService,

        /*
        * Membership-controlled Search capabilities are resolved centrally.
        *
        * MemberSearchService must never inspect plan codes or membership rows
        * directly.
        */
        private readonly MembershipEntitlementService
        $membershipEntitlementService,

        private readonly MemberPhotoUrlService
        $photoUrlService,
    ) {}

    /**
     * Return the Search-form data without executing candidate Search.
     *
     * Existing query-string criteria are normalized so "Back to Search" restores
     * the member's previous selections.
     *
     * @param array<string, mixed> $input
     *
     * @return array{
     *     mode:string,
     *     filters:array<string, mixed>,
     *     masterData:array<string, mixed>
     * }
     */
    public function formData(
        int $viewerUserId,
        array $input = []
    ): array {
        $viewer =
            $this->userModel
            ->find(
                $viewerUserId
            );

        if (!is_array($viewer)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $mode =
            $this->mode(
                $input['mode']
                    ?? null
            );

        /*
        * Advanced Search remains visible as a product capability to Free members,
        * but the form itself will be replaced by an upgrade lock.
        *
        * Returning capability state rather than silently converting the requested
        * mode to Basic lets the UI clearly explain why Advanced Search is
        * unavailable.
        */
        $canUseAdvancedSearch =
            $this->membershipEntitlementService
            ->canUseAdvancedSearch(
                $viewerUserId
            );

        /*
        * State selection must be known before city master data is loaded.
        */
        $stateIds =
            $this->positiveIds(
                $input['state_ids']
                    ?? []
            );

        $masterData =
            $this->searchMasterData(
                $stateIds
            );

        /*
     * Use the same normalization/active-master validation as Search execution.
     */
        $filters =
            $this->normaliseFilters(
                $mode,
                $input,
                $masterData
            );

        return [
            'mode' =>
            $mode,

            /*
            * Presentation-only capability state.
            *
            * search() independently repeats the authorization before executing an
            * Advanced Search.
            */
            'canUseAdvancedSearch' =>
            $canUseAdvancedSearch,

            'filters' =>
            $filters,

            'masterData' =>
            $masterData,
        ];
    }

    /**
     * Execute member Search.
     *
     * Candidate eligibility is resolved by MemberMatchCandidateModel.
     * Partner Preference matching and Match Score ranking are applied before
     * pagination when Match Score ordering is requested.
     *
     * Advanced Search authorization is enforced here rather than relying on
     * presentation-layer controls.
     *
     * @param array<string, mixed> $input
     */
    public function search(
        int $viewerUserId,
        array $input
    ): array {
        $viewer = $this->userModel
            ->find($viewerUserId);

        if (!is_array($viewer)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        /*
        * Presentation state used by member-discovery screens.
        *
        * Male members must be clearly informed that viewing a female member's
        * Full Profile is subject to her accepting their Interest.
        *
        * This is presentation information only. Full Profile authorization
        * continues to be enforced independently by the existing profile-access
        * policy.
        */
        $isMaleViewer =
            mb_strtoupper(
                trim(
                    (string) (
                        $viewer['gender']
                        ?? ''
                    )
                )
            ) === 'M';

        $mode = $this->mode(
            $input['mode'] ?? null
        );

        /*
        * SECURITY BOUNDARY
        * --------------------------------------------------------------------------
        *
        * Never rely on the Advanced Search tab/form being hidden or locked.
        *
        * A Free member can manually construct:
        *
        *     /search/results?mode=advanced&...
        *
        * so execution itself must be membership-authorized before Advanced Search
        * filters reach the candidate query.
        */
        if (
            $mode === 'advanced'
            && !$this->membershipEntitlementService
                ->canUseAdvancedSearch(
                    $viewerUserId
                )
        ) {
            throw new DomainException(
                'Advanced Search is available with a paid membership. '
                    . 'Please upgrade your plan to use Advanced Search.'
            );
        }

        $sort = $this->sort(
            $input['sort'] ?? null
        );

        $page = max(
            1,
            (int) (
                $input['page']
                ?? 1
            )
        );

        /*
        * Resolve selected states first because Advanced Search City master data
        * depends on them.
        */
        $requestedStateIds =
            $this->positiveIds(
                $input['state_ids']
                    ?? []
            );

        /*
        * Resolve Search master data through the existing profile master-data
        * authorities.
        *
        * Selected states are supplied so City options are restricted to the
        * applicable state collection before submitted filters are normalized.
        */
        $masterData =
            $this->searchMasterData(
                $requestedStateIds
            );

        $filters =
            $this->normaliseFilters(
                $mode,
                $input,
                $masterData
            );



        /*
        * --------------------------------------------------------------------------
        * Existing result-collection preset
        * --------------------------------------------------------------------------
        *
        * Activity collections reuse their existing domain authorities rather than
        * rebuilding shortlist/view/match rules inside Search.
        */
        $activity =
            $this->activity(
                $input['activity']
                    ?? null
            );

        if ($activity !== '') {
            $filters['candidate_ids'] =
                $this->activityMemberIds(
                    $viewerUserId,
                    $activity
                );

            $filters['activity'] =
                $activity;
        } elseif (
            !$this->hasCandidateFilters(
                $filters
            )
        ) {
            /*
            * Product rule:
            *
            * Basic/Advanced Search with no candidate criteria is the same collection
            * as All Matches.
            *
            * MemberMatchmakingService remains the sole authority for:
            *
            * - Partner Preference compulsory eligibility;
            * - configured minimum Partner Preference percentage.
            *
            * Search only lists/ranks the resulting candidate IDs.
            */
            $filters['candidate_ids'] =
                $this->allMatchIds(
                    $viewerUserId
                );
        }



        /*
        * Default Search is Match Score ranked.
        *
        * Explicit user-selected chronology/activity sorts retain their existing
        * database sorting semantics.
        */
        $useMatchScoreRanking =
            $sort === 'match';

        /*
        * Match-ranked Search must obtain the complete database-filtered candidate
        * pool BEFORE pagination.
        *
        * Otherwise we would rank only ten rows at a time and a stronger candidate
        * could incorrectly appear on page 2.
        */
        $results =
            $this->candidateModel
            ->searchCandidates(
                viewerUserId: $viewerUserId,

                viewerGender: (string) (
                    $viewer['gender']
                    ?? ''
                ),

                filters: $filters,

                page: $page,

                perPage: self::PER_PAGE,

                sort: $sort,

                paginate: !$useMatchScoreRanking
            );



        $resultRows =
            is_array(
                $results['rows']
                    ?? null
            )
            ? $results['rows']
            : [];

        if ($useMatchScoreRanking) {
            /*
            * Search filters/activity restrictions have already determined candidate
            * eligibility.
            *
            * Partner Preference is calculated exactly once here because Match Score
            * uses match_percentage as one of its weighted components.
            *
            * scoreCandidatesForRanking() deliberately does NOT remove candidates
            * failing compulsory Partner Preferences.
            *
            * All Matches/default Matches have already received their Partner
            * Preference eligibility restriction through allMatchCandidateIds().
            */
            $resultRows =
                $this->partnerPreferenceMatchService
                ->scoreCandidatesForRanking(
                    $viewerUserId,
                    $resultRows
                );

            /*
            * MemberMatchScoreService is the single final ranking authority.
            */
            $resultRows =
                $this->matchScoreService
                ->rankCandidates(
                    $resultRows
                );

            $total =
                count(
                    $resultRows
                );

            $totalPages =
                max(
                    1,
                    (int) ceil(
                        $total
                            / self::PER_PAGE
                    )
                );

            $page =
                min(
                    $page,
                    $totalPages
                );

            $offset =
                ($page - 1)
                * self::PER_PAGE;

            /*
            * Pagination occurs only after the complete deterministic Match Score
            * ranking.
            */
            $resultRows =
                array_slice(
                    $resultRows,
                    $offset,
                    self::PER_PAGE
                );

            $results['page'] =
                $page;
        } else {
            $total =
                max(
                    0,
                    (int) (
                        $results['total']
                        ?? 0
                    )
                );

            $totalPages =
                max(
                    1,
                    (int) ceil(
                        $total
                            / self::PER_PAGE
                    )
                );
        }

        /*
        * Convert the paginated candidate collection through the shared member-card
        * presentation pipeline.
        *
        * Search must use the same presentation contract as other member discovery
        * surfaces rather than reconstructing profile-card state locally.
        */
        $profiles =
            $this->presentationProfiles(
                $viewerUserId,
                $resultRows
            );

        $chips =
            $this->searchChips(
                $filters,
                $masterData
            );



        /*
        * Activity is presented exactly like another active Search criterion.
        */
        $activityChip =
            $this->activityLabelLinks(
                $activity
            );

        if ($activityChip !== '') {
            array_unshift(
                $chips,
                $activityChip
            );
        }

        /*
        * Quick Links reuse existing member activity and Search flows.
        *
        * No new persistence or matchmaking implementation is introduced.
        */
        $quickLinkGroups =
            $this->quickLinkGroups(
                $viewerUserId
            );



        return [
            'mode' =>
            $mode,

            'activity' =>
            $activity,

            'isMaleViewer' =>
            $isMaleViewer,

            'sort' =>
            $sort,

            'page' =>
            max(
                1,
                (int) (
                    $results['page']
                    ?? $page
                )
            ),

            'perPage' =>
            self::PER_PAGE,

            'total' =>
            $total,

            'totalPages' =>
            $totalPages,

            'profiles' =>
            $profiles,

            'filters' =>
            $filters,

            'masterData' =>
            $masterData,

            'searchChips' =>
            $chips,

            'quickLinkGroups' =>
            $quickLinkGroups,
        ];
    }

    /**
     * Resolve a profile-reference search.
     *
     * The result still passes through MemberMatchCandidateModel so inactive,
     * blocked and otherwise ineligible members cannot be opened by typing a
     * reference directly.
     */
    public function profileByReference(
        int $viewerUserId,
        string $profileReference
    ): ?array {
        $profileReference =
            mb_strtoupper(
                trim(
                    $profileReference
                )
            );

        if (
            $profileReference === ''
            || mb_strlen(
                $profileReference
            ) > 50
        ) {
            return null;
        }

        $viewer = $this->userModel
            ->find(
                $viewerUserId
            );

        if (!is_array($viewer)) {
            return null;
        }

        $target = $this->userModel
            ->where(
                'profile_ref_number',
                $profileReference
            )
            ->first();

        if (!is_array($target)) {
            return null;
        }

        $targetId = max(
            0,
            (int) (
                $target['id']
                ?? 0
            )
        );

        if ($targetId <= 0) {
            return null;
        }

        $visible =
            $this->candidateModel
            ->visibleCandidatesByIds(
                $viewerUserId,
                (string) (
                    $viewer['gender']
                    ?? ''
                ),
                [
                    $targetId,
                ]
            );

        if ($visible === []) {
            return null;
        }

        return $visible[0];
    }

    /**
     * Resolve an exact Profile-ID Search into the normal ProfileCard contract.
     *
     * This deliberately does NOT open Full Profile.
     *
     * Product rule:
     *
     * - Free member -> ProfileCard result;
     * - Paid member -> ProfileCard result;
     * - View Profile button then applies membership authorization normally.
     *
     * @return array<string,mixed>|null
     */
    public function profileCardByReference(
        int $viewerUserId,
        string $profileReference
    ): ?array {
        $candidate =
            $this->profileByReference(
                $viewerUserId,
                $profileReference
            );

        if (!is_array($candidate)) {
            return null;
        }

        $profiles =
            $this->presentationProfiles(
                $viewerUserId,
                [
                    $candidate,
                ]
            );

        return isset($profiles[0])
            && is_array($profiles[0])
            ? $profiles[0]
            : null;
    }

    /**
     * Normalize a predefined profile-listing preset.
     *
     * Existing activity Quick Links and the Matches menu deliberately share the
     * normal Search Results pipeline.
     */
    private function activity(
        mixed $value
    ): string {
        /*
     * ----------------------------------------------------------------------
     * Local activity variables
     * ----------------------------------------------------------------------
     */

        $activity =
            mb_strtolower(
                trim(
                    (string) $value
                )
            );

        $allowed = [
            'all-matches',
            'shortlisted-by-you',
            'shortlisted-you',
            'viewed-you',
            'viewed-by-you',
            'new-profiles',
        ];

        return in_array(
            $activity,
            $allowed,
            true
        )
            ? $activity
            : '';
    }

    /**
     * Resolve candidate IDs for an existing listing preset.
     *
     * Search never reads activity/match tables independently. Existing domain
     * services remain authoritative for each collection.
     *
     * @return list<int>
     */
    private function activityMemberIds(
        int $viewerUserId,
        string $activity
    ): array {
        if (
            $viewerUserId <= 0
            || $activity === ''
        ) {
            return [];
        }

        $memberIds =
            match ($activity) {
                /*
             * All Partner Preference Matches.
             */
                'all-matches' =>
                $this->allMatchIds(
                    $viewerUserId
                ),

                /*
             * Profiles shortlisted by the logged-in member.
             */
                'shortlisted-by-you' =>
                $this->interactionService
                    ->shortlistedMemberIds(
                        $viewerUserId
                    ),

                /*
             * Members who shortlisted the logged-in member.
             */
                'shortlisted-you' =>
                $this->interactionService
                    ->shortlistedByMemberIds(
                        $viewerUserId
                    ),

                /*
             * Members who viewed the logged-in member.
             */
                'viewed-you' =>
                $this->interactionService
                    ->profileVisitorIds(
                        $viewerUserId
                    ),

                /*
             * Profiles viewed by the logged-in member.
             */
                'viewed-by-you' =>
                $this->interactionService
                    ->profilesViewedIds(
                        $viewerUserId
                    ),

                /*
             * Existing Partner Preference New Match collection.
             */
                'new-profiles' =>
                $this->newProfileIds(
                    $viewerUserId
                ),

                default =>
                [],
            };

        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        is_array($memberIds)
                            ? $memberIds
                            : []
                    ),
                    static fn(
                        int $memberId
                    ): bool =>
                    $memberId > 0
                )
            )
        );
    }

    /**
     * Return the IDs belonging to the existing All Matches collection.
     *
     * MemberMatchmakingService remains the authority for Partner Preference
     * matching. Search is responsible only for paginated listing/presentation.
     *
     * @return list<int>
     */
    private function allMatchIds(
        int $viewerUserId
    ): array {
        if ($viewerUserId <= 0) {
            return [];
        }

        /** @var MemberMatchmakingService $matchmakingService */
        $matchmakingService =
            service(
                'memberMatchmakingService'
            );

        return $matchmakingService
            ->allMatchCandidateIds(
                $viewerUserId
            );
    }

    /**
     * Return member IDs for recently joined Partner Preference matches.
     *
     
     *
     * Do not build complete Dashboard presentation cards and then resolve each
     * profile reference back to users.id.
     *
     * MemberMatchmakingService remains the matching authority and returns the
     * qualified numeric candidate IDs directly.
     *
     * @return list<int>
     */
    private function newProfileIds(
        int $viewerUserId
    ): array {
        if ($viewerUserId <= 0) {
            return [];
        }

        /** @var MemberMatchmakingService $matchmakingService */
        $matchmakingService =
            service(
                'memberMatchmakingService'
            );

        return $matchmakingService
            ->newMatchCandidateIds(
                $viewerUserId
            );
    }

    /**
     * Return the member-facing label for a predefined result collection.
     */
    private function activityLabelLinks(
        string $activity
    ): string {
        return match ($activity) {
            'all-matches' =>
            'All Matches',

            'shortlisted-by-you' =>
            'Shortlisted by you',

            'shortlisted-you' =>
            'Shortlisted you',

            'viewed-you' =>
            'Viewed you',

            'viewed-by-you' =>
            'Viewed by you',

            'new-profiles' =>
            'New Matches',

            default =>
            '',
        };
    }

    /**
     * Build user-facing Search criteria chips.
     *
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $masterData
     *
     * @return list<string>
     */
    private function searchChips(
        array $filters,
        array $masterData
    ): array {
        $chips = [];

        if (
            ($filters['mode'] ?? 'basic')
            === 'advanced'
            && in_array(
                $filters['amritdhari'] ?? '',
                ['0', '1'],
                true
            )
        ) {
            $chips[] =
                'Amritdhari: '
                . (
                    $filters['amritdhari'] === '1'
                    ? 'Yes'
                    : 'No'
                );
        }

        /*
     * Age.
     */
        $ageMin =
            $filters['age_min']
            ?? null;

        $ageMax =
            $filters['age_max']
            ?? null;

        if (
            $ageMin !== null
            || $ageMax !== null
        ) {
            $chips[] =
                'Age '
                . ($ageMin ?? '18')
                . '–'
                . ($ageMax ?? 'Any');
        }

        /*
     * Height.
     */
        $heightMin =
            $this->masterLabel(
                $filters['height_min_id']
                    ?? null,
                $masterData['heights']
                    ?? [],
                'display_name'
            );

        $heightMax =
            $this->masterLabel(
                $filters['height_max_id']
                    ?? null,
                $masterData['heights']
                    ?? [],
                'display_name'
            );

        if (
            $heightMin !== ''
            || $heightMax !== ''
        ) {
            $chips[] =
                'Height '
                . ($heightMin !== ''
                    ? $heightMin
                    : 'Any')
                . ' – '
                . ($heightMax !== ''
                    ? $heightMax
                    : 'Any');
        }

        $this->appendMultiMasterChips(
            $chips,
            'Marital',
            $filters['marital_status_ids']
                ?? [],
            $masterData['maritalStatuses']
                ?? [],
            'name'
        );

        $this->appendMultiMasterChips(
            $chips,
            'Country',
            $filters['country_ids']
                ?? [],
            $masterData['countries']
                ?? [],
            'name'
        );

        $this->appendMultiMasterChips(
            $chips,
            'State',
            $filters['state_ids']
                ?? [],
            $masterData['states']
                ?? [],
            'name'
        );



        if (
            ($filters['mode'] ?? '')
            === 'advanced'
        ) {
            $this->appendMultiMasterChips(
                $chips,
                'City',
                $filters['city_ids']
                    ?? [],
                $masterData['cities']
                    ?? [],
                'name'
            );

            $this->appendMultiMasterChips(
                $chips,
                'Community',
                $filters['community_ids']
                    ?? [],
                $masterData['communities']
                    ?? [],
                'name'
            );

            $this->appendMultiMasterChips(
                $chips,
                'Education',
                $filters['education_ids']
                    ?? [],
                $masterData['educations']
                    ?? [],
                'name'
            );

            $this->appendMultiMasterChips(
                $chips,
                'Occupation',
                $filters['occupation_ids']
                    ?? [],
                $masterData['occupations']
                    ?? [],
                'name'
            );

            $this->appendMultiMasterChips(
                $chips,
                'Annual Income',
                $filters['annual_income_ids']
                    ?? [],
                $masterData['annualIncomes']
                    ?? [],
                'display_name'
            );
        }

        /*
     * Photo requirements.
     */
        foreach (
            $filters['photo_visibility']
                ?? []
            as $visibility
        ) {
            if ($visibility === 'PUBLIC') {
                $chips[] =
                    'Public Photo';
            }

            if (
                $visibility
                === 'INTERESTED_MEMBERS'
            ) {
                $chips[] =
                    'Interested Members Photo';
            }
        }

        return array_values(
            array_unique(
                $chips
            )
        );
    }

    /**
     * Resolve one active master label.
     *
     * @param list<array<string, mixed>> $rows
     */
    private function masterLabel(
        mixed $id,
        array $rows,
        string $labelColumn = 'name'
    ): string {
        if (
            !is_numeric($id)
            || (int) $id <= 0
        ) {
            return '';
        }

        foreach ($rows as $row) {
            if (
                is_array($row)
                && (int) (
                    $row['id']
                    ?? 0
                ) === (int) $id
            ) {
                return trim(
                    (string) (
                        $row[$labelColumn]
                        ?? ''
                    )
                );
            }
        }

        return '';
    }

    /**
     * Append one chip for each selected active master value.
     *
     * @param list<string> $chips
     * @param list<int> $selectedIds
     * @param list<array<string, mixed>> $rows
     */
    private function appendMultiMasterChips(
        array &$chips,
        string $prefix,
        array $selectedIds,
        array $rows,
        string $labelColumn
    ): void {
        foreach ($selectedIds as $id) {
            $label =
                $this->masterLabel(
                    $id,
                    $rows,
                    $labelColumn
                );

            if ($label !== '') {
                $chips[] =
                    $prefix
                    . ': '
                    . $label;
            }
        }
    }

    /**
     * Return active master data required by member Search.
     *
     
     *
     * The optional timeline allows the CLI profiler to identify which existing
     * master-data authority contributes to Search execution time.
     *
     * Normal HTTP Search may pass null, so no diagnostic work is performed unless
     * profiling is explicitly enabled.
     *
     * @param list<int> $selectedStateIds
     *
     * @return array<string, mixed>
     */
    private function searchMasterData(
        array $selectedStateIds = []
    ): array {
        /*
     * Existing Basic Details masters.
     */
        $basic =
            $this->masterDataService
            ->basicDetailsOptions(
                selectedStateId: null,

                selectedCountryId: null
            );

        /*

 *
 * Basic Details already loaded the authoritative active-country collection.
 * Reuse it for Additional Partner Preference master data instead of issuing an
 * identical second country query during every Search request.
 */
        $additional =
            $this->masterDataService
            ->additionalPartnerPreferenceOptions(
                resolvedCountries: is_array(
                    $basic['countries']
                        ?? null
                )
                    ? $basic['countries']
                    : []
            );

        /*
     * Existing Lifestyle master hierarchy.
     */
        $lifestyle =
            $this->lifestyleService
            ->activeOptions();



        /*
     * Advanced Search may select multiple states, unlike Profile Edit.
     *
     * Load active cities across all selected states so submitted city values
     * remain visible after Search, sorting and pagination.
     *
     * Basic Search normally reaches this checkpoint without a city query.
     */
        $cities =
            $selectedStateIds !== []
            ? $this->masterDataService
            ->citiesForStates(
                $selectedStateIds
            )
            : [];



        return array_merge(
            $basic,
            $additional,
            [
                'cities' =>
                $cities,

                'lifestyleCategories' =>
                $lifestyle['categories']
                    ?? [],

                'lifestyleOptionsByCategory' =>
                $lifestyle['optionsByCategory']
                    ?? [],

                /*
             * Existing registration profile ownership values are reused.
             * No duplicate Search master is introduced.
             */
                'profileManagedBy' => [
                    [
                        'value' =>
                        'self',

                        'label' =>
                        'Self',
                    ],
                    [
                        'value' =>
                        'son',

                        'label' =>
                        'Son',
                    ],
                    [
                        'value' =>
                        'daughter',

                        'label' =>
                        'Daughter',
                    ],
                    [
                        'value' =>
                        'brother',

                        'label' =>
                        'Brother',
                    ],
                    [
                        'value' =>
                        'sister',

                        'label' =>
                        'Sister',
                    ],
                ],
            ]
        );
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $masterData
     *
     * @return array<string, mixed>
     */
    private function normaliseFilters(
        string $mode,
        array $input,
        array $masterData
    ): array {
        $ageMin =
            $this->nullablePositiveInt(
                $input['age_min']
                    ?? null
            );

        $ageMax =
            $this->nullablePositiveInt(
                $input['age_max']
                    ?? null
            );

        if (
            $ageMin !== null
            && $ageMax !== null
            && $ageMin > $ageMax
        ) {
            throw new DomainException(
                'Minimum age cannot be greater than maximum age.'
            );
        }

        $heightMinId =
            $this->nullablePositiveInt(
                $input['height_min_id']
                    ?? null
            );

        $heightMaxId =
            $this->nullablePositiveInt(
                $input['height_max_id']
                    ?? null
            );

        $heightMinCm =
            $this->heightCm(
                $heightMinId,
                $masterData['heights']
                    ?? []
            );

        $heightMaxCm =
            $this->heightCm(
                $heightMaxId,
                $masterData['heights']
                    ?? []
            );

        if (
            $heightMinCm !== null
            && $heightMaxCm !== null
            && $heightMinCm > $heightMaxCm
        ) {
            throw new DomainException(
                'Minimum height cannot be greater than maximum height.'
            );
        }

        $amritdhariValue = trim(
            (string) (
                $input['amritdhari']
                ?? ''
            )
        );

        $amritdhari =
            in_array(
                $amritdhariValue,
                [
                    '0',
                    '1',
                ],
                true
            )
            ? $amritdhariValue
            : '';

        $filters = [
            'mode' =>
            $mode,

            'age_min' =>
            $ageMin,

            'age_max' =>
            $ageMax,

            'height_min_id' =>
            $heightMinId,

            'height_max_id' =>
            $heightMaxId,

            'height_min_cm' =>
            $heightMinCm,

            'height_max_cm' =>
            $heightMaxCm,

            'marital_status_ids' =>
            $this->validatedMasterIds(
                $input['marital_status_ids']
                    ?? [],
                $masterData['maritalStatuses']
                    ?? []
            ),

            'country_ids' =>
            $this->validatedMasterIds(
                $input['country_ids']
                    ?? [],
                $masterData['countries']
                    ?? []
            ),

            'state_ids' =>
            $this->validatedMasterIds(
                $input['state_ids']
                    ?? [],
                $masterData['states']
                    ?? []
            ),
        ];

        /*
         * Country is the parent filter. Ignore browser-supplied states that do
         * not belong to a selected country; an empty country list means Any.
         */
        if ($filters['country_ids'] !== []) {
            $filters['state_ids'] = array_values(
                array_filter(
                    $filters['state_ids'],
                    static function (int $stateId) use ($masterData, $filters): bool {
                        foreach ($masterData['states'] ?? [] as $state) {
                            if (
                                (int) ($state['id'] ?? 0) === $stateId
                                && in_array(
                                    (int) ($state['country_id'] ?? 0),
                                    $filters['country_ids'],
                                    true
                                )
                            ) {
                                return true;
                            }
                        }

                        return false;
                    }
                )
            );
        }

        if ($mode !== 'advanced') {
            return $filters;
        }

        /*
        * Photo Visibility is an Advanced Search criterion.
        *
        * Keeping this normalization after the Advanced Search boundary ensures
        * a Free member cannot manually submit photo_visibility through Basic
        * Search and bypass the membership-controlled Advanced Search flow.
        */
        $filters['photo_visibility'] =
            $this->photoVisibility(
                $input['photo_visibility']
                    ?? []
            );

        /*
        * Amritdhari is an Advanced Search candidate filter.
        *
        * Keep the normalized string value because both "0" and "1" are valid
        * selections and must remain distinguishable from an unselected value.
        */
        $filters['amritdhari'] =
            $amritdhari;

        $filters['community_ids'] =
            $this->validatedMasterIds(
                $input['community_ids']
                    ?? [],
                $masterData['communities']
                    ?? []
            );

        /*
        * Validate cities against active city master data belonging to the selected
        * states. A browser-supplied positive numeric ID is not trusted by itself.
        */
        $selectedStateIds =
            $filters['state_ids'];

        $availableCities =
            $selectedStateIds !== []
            ? $this->masterDataService
            ->citiesForStates(
                $selectedStateIds
            )
            : [];

        $filters['city_ids'] =
            $this->validatedMasterIds(
                $input['city_ids']
                    ?? [],
                $availableCities
            );

        $filters['managed_by'] =
            $this->allowedStrings(
                $input['managed_by']
                    ?? [],
                RegisterFreeValidation
                ::PROFILE_TYPES
            );

        $filters['education_ids'] =
            $this->validatedMasterIds(
                $input['education_ids']
                    ?? [],
                $masterData['educations']
                    ?? []
            );

        $filters['occupation_ids'] =
            $this->validatedMasterIds(
                $input['occupation_ids']
                    ?? [],
                $masterData['occupations']
                    ?? []
            );

        $employmentValues = [];

        foreach (
            $masterData['employmentTypes']
                ?? []
            as $employment
        ) {
            if (!is_array($employment)) {
                continue;
            }

            $value = trim(
                (string) (
                    $employment['value']
                    ?? ''
                )
            );

            if ($value !== '') {
                $employmentValues[] =
                    $value;
            }
        }

        $filters['employed_in'] =
            $this->allowedStrings(
                $input['employed_in']
                    ?? [],
                $employmentValues
            );

        /*
        * Annual Income uses the same selectable master brackets as Partner
        * Preference instead of an artificial From/To range.
        */
        $filters['annual_income_ids'] =
            $this->validatedMasterIds(
                $input['annual_income_ids']
                    ?? [],
                $masterData['annualIncomes']
                    ?? []
            );

        /*
        * Flatten currently active Lifestyle options before validating submitted
        * option IDs.
        */
        $activeLifestyleOptions = [];

        foreach (
            $masterData['lifestyleOptionsByCategory']
                ?? []
            as $categoryOptions
        ) {
            if (!is_array($categoryOptions)) {
                continue;
            }

            foreach (
                $categoryOptions
                as $option
            ) {
                if (is_array($option)) {
                    $activeLifestyleOptions[] =
                        $option;
                }
            }
        }

        $filters['lifestyle_option_ids'] =
            $this->validatedMasterIds(
                $input['lifestyle_option_ids']
                    ?? [],
                $activeLifestyleOptions
            );

        return $filters;
    }

    /**
     * Determine whether the normalized Search request contains at least one
     * candidate-selection criterion.
     *
     * Presentation/query-state values such as mode, sort, page and activity are
     * deliberately not candidate filters.
     *
     * This method operates on normalized filters so invalid/inactive master IDs
     * cannot prevent the no-filter Search fallback.
     *
     * @param array<string, mixed> $filters
     */
    private function hasCandidateFilters(
        array $filters
    ): bool {
        $scalarFilters = [
            'age_min',
            'age_max',
            'height_min_id',
            'height_max_id',
            'height_min_cm',
            'height_max_cm',
        ];

        foreach ($scalarFilters as $key) {
            if (
                array_key_exists(
                    $key,
                    $filters
                )
                && $filters[$key] !== null
            ) {
                return true;
            }
        }

        $arrayFilters = [
            'marital_status_ids',
            'country_ids',
            'state_ids',
            'photo_visibility',
            'city_ids',
            'community_ids',
            'managed_by',
            'education_ids',
            'occupation_ids',
            'employed_in',
            'annual_income_ids',
            'lifestyle_option_ids',
        ];

        foreach ($arrayFilters as $key) {
            if (
                isset($filters[$key])
                && is_array(
                    $filters[$key]
                )
                && $filters[$key] !== []
            ) {
                return true;
            }
        }

        /*
        * "0" is a valid Advanced Search value for Amritdhari = No, therefore
        * truthiness must not be used here.
        */
        return in_array(
            $filters['amritdhari']
                ?? '',
            [
                '0',
                '1',
            ],
            true
        );
    }

    /**
     * Convert eligible Search candidate rows into the common member presentation
     * contract consumed by Search/Match profile cards.
     *
    
     *
     * Viewer capability state, Interest relationships, Shortlist state and
     * approved-primary-photo database state are resolved once per collection.
     *
     * The card loop performs no candidate-specific database reads.
     *
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function presentationProfiles(
        int $viewerUserId,
        array $rows
    ): array {
        $profiles = [];

        /*
         * Resolve viewer-level membership capabilities once.
         */
        $canViewFullProfile =
            $this->membershipEntitlementService
            ->canViewFullProfile(
                $viewerUserId
            );

        $canShortlist =
            $this->membershipEntitlementService
            ->canShortlist(
                $viewerUserId
            );

        $canReport =
            $this->membershipEntitlementService
            ->canReport(
                $viewerUserId
            );

        $canBlock =
            $this->membershipEntitlementService
            ->canBlock(
                $viewerUserId
            );

        $canSendMessage =
            $this->membershipEntitlementService
            ->canSendMessage(
                $viewerUserId
            );

        /*
        * Normalize candidate IDs once.
        */
        $memberIds = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $memberId = max(
                0,
                (int) (
                    $row['id']
                    ?? 0
                )
            );

            if ($memberId > 0) {
                $memberIds[] =
                    $memberId;
            }
        }

        $memberIds =
            array_values(
                array_unique(
                    $memberIds
                )
            );

        /*
         * One Interest query for the complete displayed collection.
         */
        $interestRelationships =
            $this->interactionService
            ->interestRelationshipsFor(
                $viewerUserId,
                $memberIds
            );



        /*
         * One Shortlist query for the complete displayed collection.
         */
        $shortlistStates =
            $this->interactionService
            ->shortlistStatesFor(
                $viewerUserId,
                $memberIds
            );



        /*
         * Convert relationship contracts into the boolean map required by
         * Photo Visibility.
         */
        $photoInterestMap = [];

        foreach (
            $memberIds
            as $memberId
        ) {
            $photoInterestMap[$memberId] =
                (
                    $interestRelationships[$memberId]['hasRelationship']
                    ?? false
                ) === true;
        }

        /*
       
        *
        * MemberPhotoUrlService now records the internal DB, authorization and
        * CloudFront-signing stages itself.
        */
        $thumbnailPresentations =
            $this->photoUrlService
            ->getApprovedPrimaryThumbnailUrlsForViewer(
                memberIds: $memberIds,

                viewerUserId: $viewerUserId,

                interestRelationshipMap: $photoInterestMap
            );

        foreach (
            $rows
            as $row
        ) {
            if (!is_array($row)) {
                continue;
            }

            $memberId = max(
                0,
                (int) (
                    $row['id']
                    ?? 0
                )
            );

            if ($memberId <= 0) {
                continue;
            }

            /*
             * No DB query here.
             */
            $interestRelationship =
                $interestRelationships[$memberId]
                ?? [
                    'state' =>
                    MemberInteractionService
                    ::INTEREST_STATE_NONE,

                    'hasRelationship' =>
                    false,

                    'hasOutgoing' =>
                    false,

                    'hasIncoming' =>
                    false,

                    'canShowInterest' =>
                    true,

                    'canRespond' =>
                    false,

                    'outgoingStatus' =>
                    null,

                    'incomingStatus' =>
                    null,
                ];

            $hasInterestRelationship =
                (
                    $interestRelationship['hasRelationship']
                    ?? false
                ) === true;

            $profile =
                $this->profilePresentationService
                ->summary(
                    viewerUserId: $viewerUserId,

                    member: $row,

                    hasInterestRelationship: $hasInterestRelationship,

                    /*
                    * Supplying a presentation, including an empty URL,
                    * tells summary() that batch photo resolution has already
                    * completed and prevents a per-member fallback query.
                    */
                    resolvedPhoto: $thumbnailPresentations[$memberId]
                        ?? [
                            'url' => '',
                            'focalX' => 50,
                            'focalY' => 20,
                        ]
                );

            if ($profile === null) {
                continue;
            }

            $reference = trim(
                (string) (
                    $profile['referenceId']
                    ?? ''
                )
            );

            if ($reference === '') {
                continue;
            }

            $profile['interestUrl'] =
                route_to(
                    'web.members.interest',
                    $reference
                );

            $profile['interestRelationship'] =
                $interestRelationship;

            /*
             * Presentation capability state only.
             *
             * Domain services continue independently enforcing authorization.
             */
            $profile['canViewFullProfile'] =
                $canViewFullProfile;

            $profile['canShortlist'] =
                $canShortlist;

            $profile['canReport'] =
                $canReport;

            $profile['canBlock'] =
                $canBlock;

            $profile['canSendMessage'] =
                $canSendMessage;

            /*
            * No per-card Shortlist query.
            */
            $profile['isShortlisted'] =
                (
                    $shortlistStates[$memberId]
                    ?? false
                ) === true;

            $profile['shortlistUrl'] =
                route_to(
                    'web.members.shortlist',
                    $reference
                );

            $profile['reportUrl'] =
                route_to(
                    'web.members.report',
                    $reference
                );

            $profile['blockUrl'] =
                route_to(
                    'web.members.block',
                    $reference
                );

            /*
             * Never expose raw last-login timestamp.
             */
            $profile['activity'] =
                $this->activityLabel(
                    $row['last_login_at']
                        ?? null
                );

            $profiles[] =
                $profile;
        }

        /*
        * Everything after photo resolution is in-memory ProfileCard presentation:
        *
        * - MemberProfilePresentationService::summary();
        * - route generation;
        * - capability projection;
        * - activity label generation.
        *
        * The candidate loop must contain no candidate-specific database reads.
        */


        return $profiles;
    }

    /**
     * Build Search-result Quick Links.
     *
     * Existing Dashboard collections are reused where the product already
     * implements the required member activity.
     *
     * Location/public-photo links reuse the existing Search results route.
     *
     * @return list<array{
     *     title:string,
     *     items:list<array{
     *         label:string,
     *         help:string,
     *         icon:string,
     *         url:string,
     *         available:bool
     *     }>
     * }>
     */
    private function quickLinkGroups(
        int $viewerUserId
    ): array {

        /*
        * City, Community and Public Photo Quick Links expose
        * Advanced Search capabilities.
        *
        * Resolve the existing Advanced Search membership entitlement once
        * for the complete Quick Links collection.
        */
        $canUseAdvancedSearch =
            $this->membershipEntitlementService
            ->canUseAdvancedSearch(
                $viewerUserId
            );

        /*
     * Reuse the member's saved Basic Details location.
     */
        $location =
            $this->candidateModel
            ->memberLocationForUser(
                $viewerUserId
            );

        /*
        * Reuse the member's Family Details Community.
        *
        * This is deliberately resolved through MemberMatchCandidateModel rather
        * than reading member_family_details directly inside the service.
        */
        $community =
            $this->candidateModel
            ->memberCommunityForUser(
                $viewerUserId
            );

        $communityId =
            max(
                0,
                (int) (
                    $community['community_id']
                    ?? 0
                )
            );

        $communityName =
            trim(
                (string) (
                    $community['community_name']
                    ?? ''
                )
            );

        $stateId =
            max(
                0,
                (int) (
                    $location['state_id']
                    ?? 0
                )
            );

        $cityId =
            max(
                0,
                (int) (
                    $location['city_id']
                    ?? 0
                )
            );

        $dashboardUrl =
            route_to(
                'web.dashboard'
            );

        $searchResultsUrl =
            route_to(
                'web.search.results'
            );

        /*
     * Same-State preset.
     */
        $sameStateUrl =
            $stateId > 0
            ? $searchResultsUrl
            . '?'
            . http_build_query(
                [
                    'mode' =>
                    'basic',

                    'state_ids' => [
                        $stateId,
                    ],
                ]
            )
            : '';

        /*
     * City validation in Search requires its parent State as well.
     */
        $sameCityUrl =
            $stateId > 0
            && $cityId > 0
            ? $searchResultsUrl
            . '?'
            . http_build_query(
                [
                    'mode' =>
                    'advanced',

                    'state_ids' => [
                        $stateId,
                    ],

                    'city_ids' => [
                        $cityId,
                    ],
                ]
            )
            : '';

        /*
 * Same-Community preset.
 *
 * Community is an Advanced Search criterion, so use mode=advanced.
 * The Search service will still validate this Community ID against the
 * existing active Community master before candidate filtering.
 */
        $sameCommunityUrl =
            $communityId > 0
            ? $searchResultsUrl
            . '?'
            . http_build_query(
                [
                    'mode' =>
                    'advanced',

                    'community_ids' => [
                        $communityId,
                    ],
                ]
            )
            : '';

        /*
        * Existing photo-visibility Search filter.
        */
        $publicPhotoUrl =
            $searchResultsUrl
            . '?'
            . http_build_query(
                [
                    'mode' =>
                    'advanced',

                    'photo_visibility' => [
                        'PUBLIC',
                    ],
                ]
            );

        return [
            [
                'title' =>
                'Based on activity',

                'items' => [
                    [
                        'label' =>
                        'Shortlisted by you',

                        'help' =>
                        'Profiles you have saved to your shortlist.',

                        'icon' =>
                        'ri-bookmark-3-line',

                        /*
             * Same Search results route used by every other Quick Link.
             */
                        'url' =>
                        $searchResultsUrl
                            . '?'
                            . http_build_query(
                                [
                                    'activity' =>
                                    'shortlisted-by-you',
                                ]
                            ),

                        'available' =>
                        true,
                    ],

                    [
                        'label' =>
                        'Shortlisted you',

                        'help' =>
                        'Members who have saved your profile.',

                        'icon' =>
                        'ri-bookmark-fill',

                        'url' =>
                        $searchResultsUrl
                            . '?'
                            . http_build_query(
                                [
                                    'activity' =>
                                    'shortlisted-you',
                                ]
                            ),

                        'available' =>
                        true,
                    ],

                    [
                        'label' =>
                        'Viewed you',

                        'help' =>
                        'Members who have viewed your profile.',

                        'icon' =>
                        'ri-eye-line',

                        'url' =>
                        $searchResultsUrl
                            . '?'
                            . http_build_query(
                                [
                                    'activity' =>
                                    'viewed-you',
                                ]
                            ),

                        'available' =>
                        true,
                    ],

                    [
                        'label' =>
                        'Viewed by you',

                        'help' =>
                        'Profiles you have viewed recently.',

                        'icon' =>
                        'ri-history-line',

                        'url' =>
                        $searchResultsUrl
                            . '?'
                            . http_build_query(
                                [
                                    'activity' =>
                                    'viewed-by-you',
                                ]
                            ),

                        'available' =>
                        true,
                    ],
                ],
            ],

            [
                'title' =>
                'Recently joined & nearby matches',

                'items' => [
                    [
                        'label' =>
                        'New Profile (Last 30 Days)',

                        'help' =>
                        'Recently joined profiles matching your partner preferences.',

                        'icon' =>
                        'ri-user-star-line',

                        /*
     * Use the normal Search results flow.
     *
     * The preset is resolved server-side using the existing Partner
     * Preference matching logic and configured New Match window.
     */
                        'url' =>
                        $searchResultsUrl
                            . '?'
                            . http_build_query(
                                [
                                    'activity' =>
                                    'new-profiles',
                                ]
                            ),

                        'available' =>
                        true,
                    ],

                    [
                        'label' =>
                        'Living in same State',

                        'help' =>
                        $stateId > 0
                            ? 'Find eligible profiles living in your State.'
                            : 'Add your State in Basic Details to use this search.',

                        'icon' =>
                        'ri-map-2-line',

                        'url' =>
                        $sameStateUrl,

                        'available' =>
                        $stateId > 0,
                    ],

                    [
                        'label' =>
                        'Living in same City',

                        'help' =>
                        !$canUseAdvancedSearch
                            ? 'Upgrade your membership to use this search.'
                            : (
                                $cityId > 0
                                ? 'Find eligible profiles living in your City.'
                                : 'Add your City in Basic Details to use this search.'
                            ),

                        'icon' =>
                        'ri-map-pin-line',

                        'url' =>
                        !$canUseAdvancedSearch
                            ? route_to(
                                'web.account.settings.section',
                                'plans'
                            )
                            : $sameCityUrl,

                        'available' =>
                        !$canUseAdvancedSearch
                            || (
                                $stateId > 0
                                && $cityId > 0
                            ),

                        'membershipLocked' =>
                        !$canUseAdvancedSearch,
                    ],
                ],
            ],

            [
                'title' =>
                'Based on profile details',

                'items' => [
                    /*
         * Same Community.
         *
         * Availability depends on the logged-in member having a valid,
         * active Community saved in Family Details.
         */
                    [
                        'label' =>
                        'Same Community',

                        'help' =>
                        !$canUseAdvancedSearch
                            ? 'Upgrade your membership to use this search.'
                            : (
                                $communityId > 0
                                && $communityName !== ''
                                ? 'Find eligible profiles from the '
                                . $communityName
                                . ' community.'
                                : 'Add your Community in Family Details to use this search.'
                            ),

                        'icon' =>
                        'ri-group-line',

                        'url' =>
                        !$canUseAdvancedSearch
                            ? route_to(
                                'web.account.settings.section',
                                'plans'
                            )
                            : $sameCommunityUrl,

                        'available' =>
                        !$canUseAdvancedSearch
                            || (
                                $communityId > 0
                                && $communityName !== ''
                            ),

                        'membershipLocked' =>
                        !$canUseAdvancedSearch,
                    ],

                    [
                        'label' =>
                        'Profiles with Public Photos',

                        'help' =>
                        !$canUseAdvancedSearch
                            ? 'Upgrade your membership to use this search.'
                            : 'Show profiles whose approved primary photo is public.',

                        'icon' =>
                        'ri-image-2-line',

                        'url' =>
                        !$canUseAdvancedSearch
                            ? route_to(
                                'web.account.settings.section',
                                'plans'
                            )
                            : $publicPhotoUrl,

                        'available' =>
                        true,

                        'membershipLocked' =>
                        !$canUseAdvancedSearch,
                    ],
                ],
            ],
        ];
    }

    /**
     * Convert a private login timestamp to a coarse member-facing activity label.
     *
     * Exact login timestamps are deliberately never exposed.
     */
    private function activityLabel(
        mixed $lastLoginAt
    ): string {
        $value =
            trim(
                (string)
                $lastLoginAt
            );

        if ($value === '') {
            return '';
        }

        try {
            $lastLogin =
                new \DateTimeImmutable(
                    $value
                );

            $now =
                new \DateTimeImmutable(
                    'now'
                );

            /*
         * Active today gives useful recency without revealing exact presence.
         */
            if (
                $lastLogin->format(
                    'Y-m-d'
                )
                === $now->format(
                    'Y-m-d'
                )
            ) {
                return 'Active today';
            }

            $days =
                (int)
                $lastLogin
                    ->diff(
                        $now
                    )->format(
                        '%a'
                    );

            if ($days <= 7) {
                return 'Active this week';
            }

            if ($days <= 30) {
                return 'Recently active';
            }

            /*
         * Old activity is intentionally not displayed.
         */
            return '';
        } catch (
            \Throwable) {
            return '';
        }
    }

    private function mode(
        mixed $value
    ): string {
        $value = mb_strtolower(
            trim(
                (string) $value
            )
        );

        return $value === 'advanced'
            ? 'advanced'
            : 'basic';
    }

    private function sort(
        mixed $value
    ): string {
        $value = mb_strtolower(
            trim(
                (string) $value
            )
        );

        return in_array(
            $value,
            [
                'match',
                'latest',
                'oldest',
                'last_login',
            ],
            true
        )
            ? $value
            : 'match';
    }

    private function nullablePositiveInt(
        mixed $value
    ): ?int {
        $value = trim(
            (string) $value
        );

        if (
            $value === ''
            || !ctype_digit($value)
        ) {
            return null;
        }

        $number = (int) $value;

        return $number > 0
            ? $number
            : null;
    }

    /**
     * @param mixed $values
     *
     * @return list<int>
     */
    private function positiveIds(
        mixed $values
    ): array {
        if (!is_array($values)) {
            return [];
        }

        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $values
                    ),
                    static fn(
                        int $value
                    ): bool =>
                    $value > 0
                )
            )
        );
    }

    /**
     * @param list<array<string, mixed>> $masterRows
     *
     * @return list<int>
     */
    private function validatedMasterIds(
        mixed $values,
        array $masterRows
    ): array {
        $ids =
            $this->positiveIds(
                $values
            );

        if ($ids === []) {
            return [];
        }

        $allowed = [];

        foreach ($masterRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = (int) (
                $row['id']
                ?? 0
            );

            if ($id > 0) {
                $allowed[$id] =
                    true;
            }
        }

        foreach ($ids as $id) {
            if (!isset($allowed[$id])) {
                throw new DomainException(
                    'One or more selected search values are invalid.'
                );
            }
        }

        return $ids;
    }

    /**
     * @param list<string> $allowed
     *
     * @return list<string>
     */
    private function allowedStrings(
        mixed $values,
        array $allowed
    ): array {
        if (!is_array($values)) {
            return [];
        }

        $result = [];

        foreach ($values as $value) {
            $value = trim(
                (string) $value
            );

            if (
                $value !== ''
                && in_array(
                    $value,
                    $allowed,
                    true
                )
            ) {
                $result[] =
                    $value;
            }
        }

        return array_values(
            array_unique(
                $result
            )
        );
    }

    /**
     * @return list<string>
     */
    private function photoVisibility(
        mixed $values
    ): array {
        return $this->allowedStrings(
            $values,
            [
                'PUBLIC',
                'INTERESTED_MEMBERS',
            ]
        );
    }

    /**
     * @param list<array<string, mixed>> $heights
     */
    private function heightCm(
        ?int $heightId,
        array $heights
    ): ?int {
        if ($heightId === null) {
            return null;
        }

        foreach ($heights as $height) {
            if (
                is_array($height)
                && (int) (
                    $height['id']
                    ?? 0
                ) === $heightId
            ) {
                return (int) (
                    $height['height_cm']
                    ?? 0
                );
            }
        }

        throw new DomainException(
            'Please select a valid height.'
        );
    }
}
