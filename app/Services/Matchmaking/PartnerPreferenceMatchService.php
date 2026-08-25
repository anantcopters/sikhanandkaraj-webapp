<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Models\MemberPartnerBasicPreferenceModel;
use App\Models\MemberPartnerLocationPreferenceModel;
use App\Models\MemberPartnerPreferenceDrinkingHabitModel;
use App\Models\MemberPartnerPreferenceEatingHabitModel;
use App\Models\MemberPartnerPreferenceMotherTongueModel;
use App\Models\MemberPartnerProfessionalPreferenceModel;
use App\Models\MemberPartnerReligiousPreferenceModel;
use App\Support\PartnerPreference\BasicPreferenceItem;
use App\Support\PartnerPreference\AdditionalPreferenceItem;
use App\Models\PartnerPreferenceSelectionModel;
use App\Models\MasterLifestyleCategoryModel;
use App\Models\MemberLifestyleOptionModel;
use App\Models\MemberPartnerLifestylePreferenceModel;
use App\Models\MemberPartnerLifestylePreferenceOptionModel;
use App\Support\Development\PerformanceTimeline;
use App\Support\BooleanValue;
use DateTimeImmutable;

/**
 * SikhAnandKaraj preference matching algorithm.
 *
 * Keep scoring decisions encapsulated in this service.
 */
final class PartnerPreferenceMatchService
{
    public function __construct(
        private readonly MemberPartnerBasicPreferenceModel
        $basicPreferenceModel,

        private readonly MemberPartnerPreferenceMotherTongueModel
        $motherTongueModel,

        private readonly MemberPartnerPreferenceEatingHabitModel
        $eatingHabitModel,

        private readonly MemberPartnerPreferenceDrinkingHabitModel
        $drinkingHabitModel,

        private readonly MemberPartnerReligiousPreferenceModel
        $religiousPreferenceModel,

        private readonly MemberPartnerProfessionalPreferenceModel
        $professionalPreferenceModel,

        private readonly MemberPartnerLocationPreferenceModel
        $locationPreferenceModel,

        private readonly PartnerPreferenceSelectionModel
        $communitySelectionModel,

        private readonly PartnerPreferenceSelectionModel
        $educationSelectionModel,

        private readonly PartnerPreferenceSelectionModel
        $employmentSelectionModel,

        private readonly PartnerPreferenceSelectionModel
        $occupationSelectionModel,

        private readonly PartnerPreferenceSelectionModel
        $annualIncomeSelectionModel,

        private readonly PartnerPreferenceSelectionModel
        $countrySelectionModel,

        private readonly PartnerPreferenceSelectionModel
        $stateSelectionModel,

        private readonly PartnerPreferenceSelectionModel
        $citySelectionModel,

        private readonly MasterLifestyleCategoryModel
        $lifestyleCategoryModel,

        private readonly MemberLifestyleOptionModel
        $memberLifestyleOptionModel,

        private readonly MemberPartnerLifestylePreferenceModel
        $lifestylePreferenceModel,

        private readonly MemberPartnerLifestylePreferenceOptionModel
        $lifestylePreferenceOptionModel,
    ) {}

    /**
     * Score all candidate rows against one member's Partner Preference
     * configuration.
     *
     * Candidates that fail a compulsory preference are excluded from the returned
     * collection.
     *
     * Membership-27 diagnostic pass:
     *
     * The optional timeline measures the existing authoritative preference
     * pipeline without introducing another matching implementation.
     *
     * Normal Dashboard/Search callers do not need to supply the timeline.
     *
     * @param list<array<string, mixed>> $candidates
     *
     * @return list<array<string, mixed>>
     */
    public function scoreCandidates(
        int $userId,
        array $candidates,
        ?PerformanceTimeline $performanceTimeline = null
    ): array {
        if (
            $userId <= 0
            || $candidates === []
        ) {
            return [];
        }

        /*
     * Load the member's complete Partner Preference snapshot once.
     *
     * This snapshot includes the existing Basic, Education/Profession,
     * Lifestyle and Location preference authorities.
     */
        $snapshot =
            $this->snapshotForUser(
                $userId
            );

        $performanceTimeline?->checkpoint(
            'Preference: Viewer snapshot'
        );

        /*
     * Candidate IDs are prepared in memory before the single batch Lifestyle
     * lookup.
     */
        $candidateUserIds =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn(
                                array $candidate
                            ): int =>
                            (int) (
                                $candidate['id']
                                ?? 0
                            ),
                            $candidates
                        ),
                        static fn(
                            int $candidateId
                        ): bool =>
                        $candidateId > 0
                    )
                )
            );

        /*
     * Existing Membership optimization:
     *
     * Candidate Lifestyle selections are loaded in one batch. Never replace
     * this with selectedIdsForUser() inside the candidate loop.
     */
        $candidateLifestyleMap =
            $this
            ->memberLifestyleOptionModel
            ->selectedIdsForUsers(
                $candidateUserIds
            );

        $performanceTimeline?->checkpoint(
            'Preference: Candidate lifestyle batch'
        );

        $scored = [];

        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $candidateId =
                (int) (
                    $candidate['id']
                    ?? 0
                );

            $candidate['lifestyle_option_ids'] =
                $candidateLifestyleMap[$candidateId]
                ?? [];

            /*
         * Existing Partner Preference authority.
         *
         * Do not duplicate individual preference rules in Search.
         */
            $score =
                $this->scoreCandidate(
                    $snapshot,
                    $candidate
                );

            /*
         * Dashboard/Search matching must continue to honour compulsory Partner
         * Preferences.
         *
         * scoreProfile() intentionally behaves differently because Full Profile
         * still needs to display the comparison.
         */
            if (
                $score['passesCompulsory']
                !== true
            ) {
                continue;
            }

            $candidate['match_percentage'] =
                $score['percentage'];

            $candidate['matched_preferences'] =
                $score['matched'];

            $candidate['total_preferences'] =
                $score['total'];

            $scored[] =
                $candidate;
        }

        $performanceTimeline?->checkpoint(
            'Preference: Candidate scoring'
        );

        /*
     * Highest Partner Preference match first.
     *
     * Preserve created_at ordering when two candidates have the same
     * percentage.
     *
     * MemberMatchScoreService subsequently performs the final commercial/trust
     * ranking required by Membership rules.
     */
        usort(
            $scored,
            static function (
                array $first,
                array $second
            ): int {
                $percentageComparison =
                    (int) (
                        $second['match_percentage']
                        ?? 0
                    )
                    <=>
                    (int) (
                        $first['match_percentage']
                        ?? 0
                    );

                if ($percentageComparison !== 0) {
                    return $percentageComparison;
                }

                return strcmp(
                    (string) (
                        $second['created_at']
                        ?? ''
                    ),
                    (string) (
                        $first['created_at']
                        ?? ''
                    )
                );
            }
        );

        $performanceTimeline?->checkpoint(
            'Preference: Initial sorting'
        );

        return $scored;
    }
    
    /**
     * @param array<string, mixed> $snapshot
     * @param array<string, mixed> $candidate
     *
     * @return array{
     *     percentage:int,
     *     matched:int,
     *     total:int,
     *     configured:int,
     *     available:int,
     *     passesCompulsory:bool,
     *     criteria:list<array{
     *         key:string,
     *         matched:bool,
     *         compulsory:bool
     *     }>
     * }
     */
    private function scoreCandidate(
        array $snapshot,
        array $candidate
    ): array {
        $basic = $snapshot['basic'];

        $criteria = [];

        /*
        * 1. AGE
        */
        $candidateAge = $this->age(
            $candidate['date_of_birth']
                ?? null
        );

        $ageFrom = (int) (
            $basic['age_from']
            ?? 0
        );

        $ageTo = (int) (
            $basic['age_to']
            ?? 0
        );

        $this->criterion(
            $criteria,
            key: BasicPreferenceItem::AGE,
            configured: $ageFrom > 0
                && $ageTo >= $ageFrom,
            matched: $candidateAge !== null
                && $candidateAge >= $ageFrom
                && $candidateAge <= $ageTo,
            compulsory: $this->boolean(
                $basic['age_match_mode']
                    ?? false
            )
        );

        /*
        * 2. HEIGHT
        *
        * Existing Partner Preference validation treats the two height
        * master IDs as an ordered range.
        */
        $heightFrom = (int) (
            $basic['height_from_id']
            ?? 0
        );

        $heightTo = (int) (
            $basic['height_to_id']
            ?? 0
        );

        $candidateHeight = (int) (
            $candidate['height_id']
            ?? 0
        );

        $this->criterion(
            $criteria,
            key: BasicPreferenceItem::HEIGHT,
            configured: $heightFrom > 0
                && $heightTo >= $heightFrom,
            matched: $candidateHeight >= $heightFrom
                && $candidateHeight <= $heightTo,
            compulsory: $this->boolean(
                $basic['height_match_mode']
                    ?? false
            )
        );

        /*
        * 3. MARITAL STATUS
        */
        $maritalStatusId = (int) (
            $basic['marital_status_id']
            ?? 0
        );

        $this->criterion(
            $criteria,
            key: BasicPreferenceItem::MARITAL_STATUS,
            configured: $maritalStatusId > 0,
            matched: $maritalStatusId
                === (int) (
                    $candidate['marital_status_id']
                    ?? 0
                ),
            compulsory: $this->boolean(
                $basic['marital_status_match_mode']
                    ?? false
            )
        );

        /*
        * 4. HAVE CHILDREN
        */
        $hasChildrenPreference =
            array_key_exists(
                'have_children',
                $basic
            )
            && $basic['have_children']
            !== null;

        $candidateHasChildren =
            (int) (
                $candidate['number_of_children']
                ?? 0
            ) > 0;

        $this->criterion(
            $criteria,
            key: BasicPreferenceItem::HAVE_CHILDREN,
            configured: $hasChildrenPreference,
            matched: $hasChildrenPreference
                && $candidateHasChildren
                === $this->boolean(
                    $basic['have_children']
                ),
            compulsory: $this->boolean(
                $basic['have_children_match_mode']
                    ?? false
            )
        );

        /*
        * 5. AMRITDHARI
        */
        $hasAmritdhariPreference =
            array_key_exists(
                'amritdhari',
                $basic
            )
            && $basic['amritdhari'] !== null;

        $candidateIsAmritdhari =
            $this->boolean(
                $candidate['is_amritdhari']
                    ?? false
            );

        $this->criterion(
            $criteria,

            key: BasicPreferenceItem::AMRITDHARI,

            configured: $hasAmritdhariPreference,

            matched: $hasAmritdhariPreference
                && $candidateIsAmritdhari
                === $this->boolean(
                    $basic['amritdhari']
                ),

            compulsory: $this->boolean(
                $basic['amritdhari_match_mode']
                    ?? false
            )
        );

        /*
        * 6. MOTHER TONGUE
        */
        $this->multiSelectCriterion(
            $criteria,
            key: BasicPreferenceItem::MOTHER_TONGUE,
            selectedValues: $snapshot['motherTongues'],
            candidateValue: (int) (
                $candidate['mother_tongue_id']
                ?? 0
            ),
            compulsory: $basic['mother_tongue_match_mode']
                ?? false
        );

        /*
        * 7. PHYSICAL STATUS
        */
        $physicalStatusId = (int) (
            $basic['physical_status_id']
            ?? 0
        );

        $this->criterion(
            $criteria,
            key: BasicPreferenceItem::PHYSICAL_STATUS,
            configured: $physicalStatusId > 0,
            matched: $physicalStatusId
                === (int) (
                    $candidate['physical_status_id']
                    ?? 0
                ),
            compulsory: $this->boolean(
                $basic['physical_status_match_mode']
                    ?? false
            )
        );

        /*
        * 8. EATING HABIT
        */
        $this->multiSelectCriterion(
            $criteria,
            key: BasicPreferenceItem::EATING_HABITS,
            selectedValues: $snapshot['eatingHabits'],
            candidateValue: (int) (
                $candidate['eating_habit_id']
                ?? 0
            ),
            compulsory: $basic['eating_habit_match_mode']
                ?? false
        );

        /*
        * 9. DRINKING HABIT
        */
        $this->multiSelectCriterion(
            $criteria,
            key: BasicPreferenceItem::DRINKING_HABITS,
            selectedValues: $snapshot['drinkingHabits'],
            candidateValue: (int) (
                $candidate['drinking_habit_id']
                ?? 0
            ),
            compulsory: $basic['drinking_habit_match_mode']
                ?? false
        );

        /*
        * 10. COMMUNITY
        */
        $this->multiSelectCriterion(
            $criteria,
            key: AdditionalPreferenceItem::COMMUNITY,
            selectedValues: $snapshot['communities'],
            candidateValue: (int) (
                $candidate['community_id']
                ?? 0
            ),
            compulsory: $snapshot['communityMatchMode']
        );

        /*
        * 11. EDUCATION
        */
        $this->multiSelectCriterion(
            $criteria,
            key: AdditionalPreferenceItem::EDUCATION,
            selectedValues: $snapshot['educations'],
            candidateValue: (int) (
                $candidate['highest_education_id']
                ?? 0
            ),
            compulsory: $snapshot['educationMatchMode']
        );

        /*
        * 12. EMPLOYED IN
        */
        $employmentTypes =
            $snapshot['employmentTypes'];

        $candidateEmployment =
            strtoupper(
                trim(
                    (string) (
                        $candidate['employed_in']
                        ?? ''
                    )
                )
            );

        $this->criterion(
            $criteria,
            key: AdditionalPreferenceItem::EMPLOYED_IN,
            configured: $employmentTypes !== [],
            matched: $candidateEmployment !== ''
                && in_array(
                    $candidateEmployment,
                    $employmentTypes,
                    true
                ),
            compulsory: $snapshot['employmentMatchMode']
        );

        /*
        * 13. OCCUPATION
        */
        $this->multiSelectCriterion(
            $criteria,
            key: AdditionalPreferenceItem::OCCUPATION,
            selectedValues: $snapshot['occupations'],
            candidateValue: (int) (
                $candidate['occupation_id']
                ?? 0
            ),
            compulsory: $snapshot['occupationMatchMode']
        );

        /*
        * 14. ANNUAL INCOME
        */
        $this->multiSelectCriterion(
            $criteria,
            key: AdditionalPreferenceItem::ANNUAL_INCOME,
            selectedValues: $snapshot['annualIncomes'],
            candidateValue: (int) (
                $candidate['annual_income_id']
                ?? 0
            ),
            compulsory: $snapshot['annualIncomeMatchMode']
        );

        /*
        * 15. LOCATION
        *
        * City is the more precise criterion when cities are configured.
        * If no city is configured, state is used.
        */
        $countryIds = $snapshot['countries'];
        $cityIds = $snapshot['cities'];
        $stateIds = $snapshot['states'];

        $locationConfigured =
            $cityIds !== []
            || $stateIds !== []
            || $countryIds !== [];

        $locationMatched = false;

        if ($cityIds !== []) {
            $locationMatched = in_array(
                (int) (
                    $candidate['city_id']
                    ?? 0
                ),
                $cityIds,
                true
            );
        } elseif ($stateIds !== []) {
            $locationMatched = in_array(
                (int) (
                    $candidate['state_id']
                    ?? 0
                ),
                $stateIds,
                true
            );
        } elseif ($countryIds !== []) {
            $locationMatched = in_array(
                (int) (
                    $candidate['country_id']
                    ?? 0
                ),
                $countryIds,
                true
            );
        }

        $this->criterion(
            $criteria,
            key: AdditionalPreferenceItem::LOCATION,
            configured: $locationConfigured,
            matched: $locationMatched,
            compulsory: $snapshot['locationMatchMode']
        );

        /*
 * LIFESTYLE
 *
 * Every active Lifestyle category is an independent
 * Partner Preference criterion.
 *
 * A configured category matches when the candidate
 * has at least one option in common with the member's
 * selected options for that category.
 */
        $candidateLifestyleIds =
            is_array(
                $candidate['lifestyle_option_ids']
                    ?? null
            )
            ? array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'intval',
                            $candidate['lifestyle_option_ids']
                        ),
                        static fn(int $optionId): bool =>
                        $optionId > 0
                    )
                )
            )
            : [];

        $activeLifestyleCategories =
            $this
            ->lifestyleCategoryModel
            ->activeOrdered();

        foreach (
            $activeLifestyleCategories
            as $category
        ) {
            $categoryId = (int) (
                $category['id']
                ?? 0
            );

            if ($categoryId <= 0) {
                continue;
            }

            $preference =
                $snapshot['lifestyle'][$categoryId]
                ?? null;

            $preferredOptionIds =
                is_array($preference)
                && is_array(
                    $preference['optionIds']
                        ?? null
                )
                ? array_values(
                    array_unique(
                        array_filter(
                            array_map(
                                'intval',
                                $preference['optionIds']
                            ),
                            static fn(int $optionId): bool =>
                            $optionId > 0
                        )
                    )
                )
                : [];

            $matched =
                $preferredOptionIds !== []
                && array_intersect(
                    $preferredOptionIds,
                    $candidateLifestyleIds
                ) !== [];

            $this->criterion(
                $criteria,

                key: 'lifestyle-'
                    . $categoryId,

                configured: $preferredOptionIds !== [],

                matched: $matched,

                compulsory: is_array($preference)
                    && $this->boolean(
                        $preference['isCompulsory']
                            ?? false
                    )
            );
        }

        /*
        * Every criterion supported by this service is retained in $criteria.
        *
        * This gives us a dynamic available count. A preference is included in
        * the matchmaking denominator only when configured by the member.
        */
        $available = count(
            $criteria
        );

        $configuredCriteria = array_values(
            array_filter(
                $criteria,
                static fn(array $criterion): bool =>
                $criterion['configured']
            )
        );

        $configured = count(
            $configuredCriteria
        );

        /*
     * Keep "total" as the configured count for backward compatibility.
     *
     * Existing matchmaking consumers already understand total_preferences
     * as the number of preferences against which the candidate was scored.
     */
        $total = $configured;

        $matched = count(
            array_filter(
                $configuredCriteria,
                static fn(array $criterion): bool =>
                $criterion['matched']
            )
        );

        $passesCompulsory =
            !array_filter(
                $configuredCriteria,
                static fn(array $criterion): bool =>
                $criterion['compulsory']
                    && !$criterion['matched']
            );

        $percentage =
            $total > 0
            ? (int) round(
                ($matched / $total) * 100
            )
            : 0;

        return [
            'percentage' =>
            $percentage,

            'matched' =>
            $matched,

            'total' =>
            $total,

            'configured' =>
            $configured,

            'available' =>
            $available,

            'passesCompulsory' =>
            $passesCompulsory,

            /*
         * Individual matching states are returned for the
         * member-profile detail modal.
         *
         * Display labels deliberately do not belong here.
         */
            'criteria' => array_map(
                static fn(array $criterion): array => [
                    'key' => (string) (
                        $criterion['key']
                        ?? ''
                    ),

                    'matched' => (
                        $criterion['matched']
                        ?? false
                    ) === true,

                    'compulsory' => (
                        $criterion['compulsory']
                        ?? false
                    ) === true,
                ],
                $configuredCriteria
            ),
        ];
    }


    /**
     * Score one member profile against another member's
     * Partner Preference configuration.
     *
     * Unlike scoreCandidates(), this method does not remove
     * a profile when a compulsory preference fails.
     *
     * @param array<string, mixed> $candidate
     *
     * @return array{
     *     percentage:int,
     *     matched:int,
     *     total:int,
     *     configured:int,
     *     available:int,
     *     passesCompulsory:bool,
     *     criteria:list<array{
     *         key:string,
     *         matched:bool,
     *         compulsory:bool
     *     }>
     * }
     */
    public function scoreProfile(
        int $preferenceOwnerUserId,
        array $candidate
    ): array {
        if (
            $preferenceOwnerUserId <= 0
            || $candidate === []
        ) {
            return [
                'percentage' => 0,
                'matched' => 0,
                'total' => 0,
                'configured' => 0,
                'available' => 0,
                'passesCompulsory' => true,
                'criteria' => [],
            ];
        }

        $snapshot = $this->snapshotForUser(
            $preferenceOwnerUserId
        );

        $candidateUserId = (int) (
            $candidate['id']
            ?? $candidate['user_id']
            ?? 0
        );

        $candidate['lifestyle_option_ids'] =
            $candidateUserId > 0
            ? $this
            ->memberLifestyleOptionModel
            ->selectedIdsForUser(
                $candidateUserId
            )
            : [];

        return $this->scoreCandidate(
            $snapshot,
            $candidate
        );
    }

    /**
     * Return Partner Preference setup progress for a member.
     *
     * Available and configured counts come directly from the same
     * criterion definitions used by matchmaking.
     *
     * Therefore, when a new matching criterion is added to
     * scoreCandidate(), the Dashboard total automatically increases.
     *
     * @return array{
     *     configured:int,
     *     available:int,
     *     percentage:int,
     *     isComplete:bool
     * }
     */
    public function preferenceSetupSummary(
        int $userId
    ): array {
        if ($userId <= 0) {
            return [
                'configured' => 0,
                'available' => 0,
                'percentage' => 0,
                'isComplete' => false,
            ];
        }

        $snapshot = $this->snapshotForUser(
            $userId
        );

        /*
     * Candidate values are irrelevant for setup progress.
     *
     * scoreCandidate() still builds every supported criterion,
     * allowing us to dynamically know both:
     *
     * - how many criteria currently exist;
     * - how many the member has configured.
     */
        $score = $this->scoreCandidate(
            $snapshot,
            []
        );

        $configured = max(
            0,
            (int) (
                $score['configured']
                ?? 0
            )
        );

        $available = max(
            0,
            (int) (
                $score['available']
                ?? 0
            )
        );

        $percentage =
            $available > 0
            ? (int) round(
                (
                    $configured
                    / $available
                ) * 100
            )
            : 0;

        return [
            'configured' =>
            $configured,

            'available' =>
            $available,

            'percentage' =>
            max(
                0,
                min(
                    100,
                    $percentage
                )
            ),

            'isComplete' =>
            $available > 0
                && $configured >= $available,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotForUser(
        int $userId
    ): array {
        $basic = $this
            ->basicPreferenceModel
            ->findForUser($userId)
            ?? [];

        $basicPreferenceId = (int) (
            $basic['id']
            ?? 0
        );

        $religious = $this
            ->religiousPreferenceModel
            ->findForUser($userId)
            ?? [];

        $professional = $this
            ->professionalPreferenceModel
            ->findForUser($userId)
            ?? [];

        $location = $this
            ->locationPreferenceModel
            ->findForUser($userId)
            ?? [];

        $religiousId = (int) (
            $religious['id']
            ?? 0
        );

        $professionalId = (int) (
            $professional['id']
            ?? 0
        );

        $locationId = (int) (
            $location['id']
            ?? 0
        );

        $lifestylePreferences =
            $this
            ->lifestylePreferenceModel
            ->findForUser(
                $userId
            );

        $lifestyle = [];

        foreach (
            $lifestylePreferences
            as $preference
        ) {
            $preferenceId = (int) (
                $preference['id']
                ?? 0
            );

            $categoryId = (int) (
                $preference['lifestyle_category_id']
                ?? 0
            );

            if (
                $preferenceId <= 0
                || $categoryId <= 0
            ) {
                continue;
            }

            $selectedIds =
                $this
                ->lifestylePreferenceOptionModel
                ->idsForPreference(
                    $preferenceId
                );

            if ($selectedIds === []) {
                continue;
            }

            $lifestyle[$categoryId] = [
                'optionIds' =>
                $selectedIds,

                'isCompulsory' =>
                $this->boolean(
                    $preference['is_compulsory']
                        ?? false
                ),
            ];
        }

        return [
            'basic' =>
            $basic,

            'motherTongues' =>
            $basicPreferenceId > 0
                ? $this
                ->motherTongueModel
                ->idsForPreference(
                    $basicPreferenceId
                )
                : [],

            'eatingHabits' =>
            $basicPreferenceId > 0
                ? $this
                ->eatingHabitModel
                ->idsForPreference(
                    $basicPreferenceId
                )
                : [],

            'drinkingHabits' =>
            $basicPreferenceId > 0
                ? $this
                ->drinkingHabitModel
                ->idsForPreference(
                    $basicPreferenceId
                )
                : [],

            'communities' =>
            $religiousId > 0
                ? array_map(
                    'intval',
                    $this
                        ->communitySelectionModel
                        ->selectedValues(
                            $religiousId
                        )
                )
                : [],

            'communityMatchMode' =>
            $this->boolean(
                $religious['community_match_mode']
                    ?? false
            ),

            'educations' =>
            $professionalId > 0
                ? array_map(
                    'intval',
                    $this
                        ->educationSelectionModel
                        ->selectedValues(
                            $professionalId
                        )
                )
                : [],

            'educationMatchMode' =>
            $this->boolean(
                $professional['education_match_mode']
                    ?? false
            ),

            'employmentTypes' =>
            $professionalId > 0
                ? array_values(
                    array_map(
                        static fn(
                            int|string $value
                        ): string =>
                        strtoupper(
                            trim(
                                (string) $value
                            )
                        ),
                        $this
                            ->employmentSelectionModel
                            ->selectedValues(
                                $professionalId
                            )
                    )
                )
                : [],

            'employmentMatchMode' =>
            $this->boolean(
                $professional['employed_in_match_mode']
                    ?? false
            ),

            'occupations' =>
            $professionalId > 0
                ? array_map(
                    'intval',
                    $this
                        ->occupationSelectionModel
                        ->selectedValues(
                            $professionalId
                        )
                )
                : [],

            'occupationMatchMode' =>
            $this->boolean(
                $professional['occupation_match_mode']
                    ?? false
            ),

            'annualIncomes' =>
            $professionalId > 0
                ? array_map(
                    'intval',
                    $this
                        ->annualIncomeSelectionModel
                        ->selectedValues(
                            $professionalId
                        )
                )
                : [],

            'annualIncomeMatchMode' =>
            $this->boolean(
                $professional['annual_income_match_mode']
                    ?? false
            ),

            'states' =>
            $locationId > 0
                ? array_map(
                    'intval',
                    $this
                        ->stateSelectionModel
                        ->selectedValues(
                            $locationId
                        )
                )
                : [],

            'countries' =>
            $locationId > 0
                ? array_map(
                    'intval',
                    $this
                        ->countrySelectionModel
                        ->selectedValues(
                            $locationId
                        )
                )
                : [],

            'cities' =>
            $locationId > 0
                ? array_map(
                    'intval',
                    $this
                        ->citySelectionModel
                        ->selectedValues(
                            $locationId
                        )
                )
                : [],

            'locationMatchMode' =>
            $this->boolean(
                $location['location_match_mode']
                    ?? false
            ),

            'lifestyle' =>
            $lifestyle,
        ];
    }

    /**
     * @param list<array<string, mixed>> $criteria
     */
    private function criterion(
        array &$criteria,
        string $key,
        bool $configured,
        bool $matched,
        bool $compulsory
    ): void {
        $criteria[] = [
            'key' => $key,

            'configured' =>
            $configured,

            'matched' =>
            $configured
                && $matched,

            'compulsory' =>
            $configured
                && $compulsory,
        ];
    }

    /**
     * @param list<array<string, mixed>> $criteria
     * @param list<int|string>          $selectedValues
     */
    private function multiSelectCriterion(
        array &$criteria,
        string $key,
        array $selectedValues,
        int $candidateValue,
        bool|int|string|null $compulsory
    ): void {
        $normalizedValues = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $selectedValues
                    ),
                    static fn(int $value): bool =>
                    $value > 0
                )
            )
        );

        $this->criterion(
            $criteria,
            key: $key,
            configured: $normalizedValues !== [],
            matched: $candidateValue > 0
                && in_array(
                    $candidateValue,
                    $normalizedValues,
                    true
                ),
            compulsory: $this->boolean(
                $compulsory
            )
        );
    }

    private function boolean(
        mixed $value
    ): bool {
        return BooleanValue::fromDatabase(
            $value
        );
    }

    private function age(
        mixed $dateOfBirth
    ): ?int {
        $value = trim(
            (string) $dateOfBirth
        );

        if ($value === '') {
            return null;
        }

        try {
            $birthDate =
                new DateTimeImmutable(
                    mb_substr(
                        $value,
                        0,
                        10
                    )
                );

            $today =
                new DateTimeImmutable(
                    'today'
                );

            if ($birthDate > $today) {
                return null;
            }

            return $birthDate
                ->diff($today)
                ->y;
        } catch (\Throwable) {
            return null;
        }
    }
}
