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
use App\Models\PartnerPreferenceSelectionModel;
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
        $stateSelectionModel,

        private readonly PartnerPreferenceSelectionModel
        $citySelectionModel
    ) {}

    /**
     * Score all candidate rows against one member's preference snapshot.
     *
     * @param list<array<string, mixed>> $candidates
     *
     * @return list<array<string, mixed>>
     */
    public function scoreCandidates(
        int $userId,
        array $candidates
    ): array {
        $snapshot = $this->snapshotForUser(
            $userId
        );

        $scored = [];

        foreach ($candidates as $candidate) {
            $score = $this->scoreCandidate(
                $snapshot,
                $candidate
            );

            /*
             * A compulsory mismatch is a hard exclusion.
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

            $scored[] = $candidate;
        }

        usort(
            $scored,
            static function (
                array $first,
                array $second
            ): int {
                $percentageComparison =
                    (int) $second['match_percentage']
                    <=>
                    (int) $first['match_percentage'];

                if ($percentageComparison !== 0) {
                    return $percentageComparison;
                }

                return strcmp(
                    (string) $second['created_at'],
                    (string) $first['created_at']
                );
            }
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
     *     passesCompulsory:bool
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
         * 5. MOTHER TONGUE
         */
        $this->multiSelectCriterion(
            $criteria,
            $snapshot['motherTongues'],
            (int) (
                $candidate['mother_tongue_id']
                ?? 0
            ),
            $basic['mother_tongue_match_mode'] ?? false
        );

        /*
         * 6. PHYSICAL STATUS
         */
        $physicalStatusId = (int) (
            $basic['physical_status_id']
            ?? 0
        );

        $this->criterion(
            $criteria,
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
         * 7. EATING HABIT
         */
        $this->multiSelectCriterion(
            $criteria,
            $snapshot['eatingHabits'],
            (int) (
                $candidate['eating_habit_id']
                ?? 0
            ),
            $basic['eating_habit_match_mode'] ?? false
        );

        /*
         * 8. DRINKING HABIT
         */
        $this->multiSelectCriterion(
            $criteria,
            $snapshot['drinkingHabits'],
            (int) (
                $candidate['drinking_habit_id']
                ?? 0
            ),
            $basic['drinking_habit_match_mode'] ?? false
        );

        /*
         * 9. COMMUNITY
         */
        $this->multiSelectCriterion(
            $criteria,
            $snapshot['communities'],
            (int) (
                $candidate['community_id']
                ?? 0
            ),
            $snapshot['communityMatchMode']
        );

        /*
         * 10. EDUCATION
         */
        $this->multiSelectCriterion(
            $criteria,
            $snapshot['educations'],
            (int) (
                $candidate['highest_education_id']
                ?? 0
            ),
            $snapshot['educationMatchMode']
        );

        /*
         * 11. EMPLOYED IN
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
         * 12. OCCUPATION
         */
        $this->multiSelectCriterion(
            $criteria,
            $snapshot['occupations'],
            (int) (
                $candidate['occupation_id']
                ?? 0
            ),
            $snapshot['occupationMatchMode']
        );

        /*
         * 13. ANNUAL INCOME
         */
        $this->multiSelectCriterion(
            $criteria,
            $snapshot['annualIncomes'],
            (int) (
                $candidate['annual_income_id']
                ?? 0
            ),
            $snapshot['annualIncomeMatchMode']
        );

        /*
         * 14. LOCATION
         *
         * City is the more precise criterion when cities are configured.
         * If no city is configured, state is used.
         */
        $cityIds = $snapshot['cities'];
        $stateIds = $snapshot['states'];

        $locationConfigured =
            $cityIds !== []
            || $stateIds !== [];

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
        }

        $this->criterion(
            $criteria,
            configured: $locationConfigured,
            matched: $locationMatched,
            compulsory: $snapshot['locationMatchMode']
        );

        /*
         * SPECIAL REQUEST IS INTENTIONALLY EXCLUDED.
         *
         * It is free text and therefore cannot form a deterministic
         * master-data-backed matching criterion.
         */

        $total = count($criteria);

        $matched = count(
            array_filter(
                $criteria,
                static fn(array $criterion): bool =>
                $criterion['matched']
            )
        );

        $passesCompulsory =
            !array_filter(
                $criteria,
                static fn(array $criterion): bool =>
                $criterion['compulsory']
                    && !$criterion['matched']
            );

        $percentage = $total > 0
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

            'passesCompulsory' =>
            $passesCompulsory,
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
        ];
    }

    /**
     * @param list<array{
     *     matched:bool,
     *     compulsory:bool
     * }> $criteria
     */
    private function criterion(
        array &$criteria,
        bool $configured,
        bool $matched,
        bool $compulsory
    ): void {
        if (!$configured) {
            return;
        }

        $criteria[] = [
            'matched' =>
            $matched,

            'compulsory' =>
            $compulsory,
        ];
    }

    /**
     * @param list<array{
     *     matched:bool,
     *     compulsory:bool
     * }> $criteria
     *
     * @param list<int> $acceptedIds
     */
    private function multiSelectCriterion(
        array &$criteria,
        array $acceptedIds,
        int $candidateId,
        mixed $matchMode
    ): void {
        $this->criterion(
            $criteria,
            configured: $acceptedIds !== [],
            matched: $candidateId > 0
                && in_array(
                    $candidateId,
                    $acceptedIds,
                    true
                ),
            compulsory: $this->boolean(
                $matchMode
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
