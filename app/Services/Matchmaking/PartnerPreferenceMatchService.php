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
use App\Support\BooleanValue;
use DateTimeImmutable;

/**
 * SikhanandKaraj preference matching algorithm.
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
     * Score candidates for matrimonial Match eligibility.
     *
     * This is the authoritative Partner Preference collection used by:
     *
     * - Dashboard All Matches;
     * - Dashboard New Matches;
     * - default Matches.
     *
     * Candidates failing a compulsory Partner Preference are excluded here.
     *
     * @param list<array<string, mixed>> $candidates
     *
     * @return list<array<string, mixed>>
     */
    public function scoreCandidates(
        int $userId,
        array $candidates
    ): array {
        return $this->scoreCandidateCollection(
            $userId,
            $candidates,
            true
        );
    }

    /**
     * Calculate Partner Preference percentages for Match Score ranking without
     * using Partner Preference as candidate eligibility.
     *
     * Search filters are the eligibility authority for filtered Basic Search,
     * Advanced Search and the independent Matches filters.
     *
     * Partner Preference is still calculated because it is one weighted
     * component of MemberMatchScoreService.
     *
     * A candidate failing a compulsory Partner Preference therefore remains in
     * the Search collection; the preference result only affects Match Score.
     *
     * @param list<array<string, mixed>> $candidates
     *
     * @return list<array<string, mixed>>
     */
    public function scoreCandidatesForRanking(
        int $userId,
        array $candidates
    ): array {
        return $this->scoreCandidateCollection(
            $userId,
            $candidates,
            false
        );
    }

    /**
     * Apply the existing Partner Preference algorithm to a candidate collection.
     *
     * The scoring implementation is shared deliberately so Dashboard/Matches and
     * Search can never develop separate Partner Preference calculations.
     *
     * $enforceCompulsory:
     *
     * true
     *     Partner Preference determines matrimonial Match eligibility.
     *
     * false
     *     Partner Preference is scoring context only. Search filters determine
     *     candidate eligibility.
     *
     * @param list<array<string, mixed>> $candidates
     *
     * @return list<array<string, mixed>>
     */
    private function scoreCandidateCollection(
        int $userId,
        array $candidates,
        bool $enforceCompulsory
    ): array {
        if (
            $userId <= 0
            || $candidates === []
        ) {
            return [];
        }

        /*
        * Resolve the viewer's Partner Preference configuration once for the
        * complete candidate collection.
        */
        $snapshot =
            $this->snapshotForUser(
                $userId
            );

        /*
        * Candidate Lifestyle selections are batch-loaded.
        *
        * Do not introduce per-candidate selectedIdsForUser() calls here.
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

        $candidateLifestyleMap =
            $this
            ->memberLifestyleOptionModel
            ->selectedIdsForUsers(
                $candidateUserIds
            );

        /*
        * Active Lifestyle categories are common master data for the complete
        * collection and are therefore resolved once.
        */
        $activeLifestyleCategories =
            $this
            ->lifestyleCategoryModel
            ->activeOrdered();

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
            * Reuse the single existing Partner Preference algorithm.
            */
            $score =
                $this->scoreCandidate(
                    $snapshot,
                    $candidate,
                    $activeLifestyleCategories
                );

            /*
            * Dashboard/default Matches enforce compulsory preferences.
            *
            * Filtered Search deliberately does not.
            */
            if (
                $enforceCompulsory
                && $score['passesCompulsory'] !== true
            ) {
                continue;
            }

            $candidate['match_percentage'] =
                $score['percentage'];

            $candidate['matched_preferences'] =
                $score['matched'];

            $candidate['total_preferences'] =
                $score['total'];

            /*
            * Keep individual configured criterion results available to internal
            * presentation consumers.
            *
            * Display labels deliberately remain outside the matching engine.
            */
            $candidate['match_criteria'] =
                $score['criteria'];

            /*
            * Keep the result available to internal consumers without turning it
            * into Search eligibility.
            */
            $candidate['passes_compulsory_preferences'] =
                $score['passesCompulsory'] === true;

            $scored[] =
                $candidate;
        }

        return array_values(
            $scored
        );
    }

    /**
     * Score one candidate using already-resolved preference and master state.
     *
     * This method intentionally performs no master-data lookup while iterating the
     * candidate collection.
     *
     * @param array<string, mixed> $snapshot
     * @param array<string, mixed> $candidate
     * @param list<array<string, mixed>> $activeLifestyleCategories
     */
    private function scoreCandidate(
        array $snapshot,
        array $candidate,
        array $activeLifestyleCategories
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

        /*
        * Full Profile scores one candidate only, so one master read is appropriate.
        *
        * The collection optimization belongs to scoreCandidates(); Full Profile does
        * not need a separate batching abstraction.
        */
        $activeLifestyleCategories =
            $this
            ->lifestyleCategoryModel
            ->activeOrdered();

        return $this->scoreCandidate(
            $snapshot,
            $candidate,
            $activeLifestyleCategories
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
        *
        
        *
        * Lifestyle categories are resolved outside scoreCandidate() so that
        * scoreCandidate() itself remains database-free.
        */
        $activeLifestyleCategories =
            $this
            ->lifestyleCategoryModel
            ->activeOrdered();

        $score = $this->scoreCandidate(
            $snapshot,
            [],
            $activeLifestyleCategories
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



        /*
 
 *
 * Resolve the existing selection-model reads before constructing the snapshot
 * so their logical groups can be measured.
 *
 * Business behaviour is unchanged.
 */

        /*
 * Basic multi-select preferences.
 */
        $motherTongues =
            $basicPreferenceId > 0
            ? $this
            ->motherTongueModel
            ->idsForPreference(
                $basicPreferenceId
            )
            : [];

        $eatingHabits =
            $basicPreferenceId > 0
            ? $this
            ->eatingHabitModel
            ->idsForPreference(
                $basicPreferenceId
            )
            : [];

        $drinkingHabits =
            $basicPreferenceId > 0
            ? $this
            ->drinkingHabitModel
            ->idsForPreference(
                $basicPreferenceId
            )
            : [];



        /*
 * Religious selections.
 */
        $communities =
            $religiousId > 0
            ? array_map(
                'intval',
                $this
                    ->communitySelectionModel
                    ->selectedValues(
                        $religiousId
                    )
            )
            : [];



        /*
 * Education/Profession selections.
 */
        $educations =
            $professionalId > 0
            ? array_map(
                'intval',
                $this
                    ->educationSelectionModel
                    ->selectedValues(
                        $professionalId
                    )
            )
            : [];

        $employmentTypes =
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
            : [];

        $occupations =
            $professionalId > 0
            ? array_map(
                'intval',
                $this
                    ->occupationSelectionModel
                    ->selectedValues(
                        $professionalId
                    )
            )
            : [];

        $annualIncomes =
            $professionalId > 0
            ? array_map(
                'intval',
                $this
                    ->annualIncomeSelectionModel
                    ->selectedValues(
                        $professionalId
                    )
            )
            : [];



        /*
 * Location selections.
 */
        $states =
            $locationId > 0
            ? array_map(
                'intval',
                $this
                    ->stateSelectionModel
                    ->selectedValues(
                        $locationId
                    )
            )
            : [];

        $countries =
            $locationId > 0
            ? array_map(
                'intval',
                $this
                    ->countrySelectionModel
                    ->selectedValues(
                        $locationId
                    )
            )
            : [];

        $cities =
            $locationId > 0
            ? array_map(
                'intval',
                $this
                    ->citySelectionModel
                    ->selectedValues(
                        $locationId
                    )
            )
            : [];



        return [
            'basic' =>
            $basic,

            'motherTongues' =>
            $motherTongues,

            'eatingHabits' =>
            $eatingHabits,

            'drinkingHabits' =>
            $drinkingHabits,

            'communities' =>
            $communities,

            'communityMatchMode' =>
            $this->boolean(
                $religious['community_match_mode']
                    ?? false
            ),

            'educations' =>
            $educations,

            'educationMatchMode' =>
            $this->boolean(
                $professional['education_match_mode']
                    ?? false
            ),

            'employmentTypes' =>
            $employmentTypes,

            'employmentMatchMode' =>
            $this->boolean(
                $professional['employed_in_match_mode']
                    ?? false
            ),

            'occupations' =>
            $occupations,

            'occupationMatchMode' =>
            $this->boolean(
                $professional['occupation_match_mode']
                    ?? false
            ),

            'annualIncomes' =>
            $annualIncomes,

            'annualIncomeMatchMode' =>
            $this->boolean(
                $professional['annual_income_match_mode']
                    ?? false
            ),
            'states' =>
            $states,

            'countries' =>
            $countries,

            'cities' =>
            $cities,
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
