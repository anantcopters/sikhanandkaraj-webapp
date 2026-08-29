<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\MemberBasicDetailModel;
use App\Models\UserModel;
use App\Services\Profile\ProfileMasterDataService;
use App\Support\Domain\MemberAgePolicy;
use App\Support\BooleanValue;
use DateTimeImmutable;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Reads and updates the Basic Details profile section.
 *
 * All multi-table updates are performed within one transaction.
 */
final class BasicDetailsService
{
    public function __construct(
        private readonly UserModel $userModel,
        private readonly MemberBasicDetailModel $basicDetailModel,
        private readonly ProfileMasterDataService $masterDataService
    ) {}

    /**
     * Return the data required to display the Basic Details section.
     *
     * @return array{
     *     user: array<string, mixed>,
     *     basicDetails: array<string, mixed>|null,
     *     completion: array{
     *         completed: int,
     *         total: int,
     *         percentage: int
     *     }
     * }
     */
    public function getForUser(
        int $userId,
        ?int $requestedCountryId = null,
        ?int $requestedStateId = null
    ): array {
        $user = $this->userModel->find($userId);

        if (!is_array($user)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $basicDetails = $this
            ->basicDetailModel
            ->findForUser($userId);

        $selectedStateId = is_array($basicDetails)
            && is_numeric($basicDetails['state_id'] ?? null)
            ? (int) $basicDetails['state_id']
            : null;

        $selectedCountryId = is_array($basicDetails)
            && is_numeric($basicDetails['country_id'] ?? null)
            ? (int) $basicDetails['country_id']
            : null;

        if ($requestedCountryId !== null && $requestedCountryId > 0) {
            $selectedCountryId = $requestedCountryId;
        }

        if ($requestedStateId !== null && $requestedStateId > 0) {
            $selectedStateId = $requestedStateId;
        }

        return [
            'user' => $user,
            'basicDetails' => $basicDetails,

            'masterData' =>
            $this->masterDataService->basicDetailsOptions(
                $selectedStateId,
                $selectedCountryId
            ),

            'completion' => $this->calculateCompletion(
                $user,
                $basicDetails
            ),
        ];
    }

    /**
     * Create or update the member's Basic Details section.
     *
     * @param array<string, mixed> $data
     */
    public function save(
        int $userId,
        array $data
    ): void {
        $user = $this->userModel->find(
            $userId
        );

        if (!is_array($user)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $this->assertMinimumAge(
            (string) $data['date_of_birth'],
            (string) (
                $user['gender']
                ?? ''
            )
        );

        $this->masterDataService->assertValidSelection(
            (int) $data['marital_status_id'],
            (int) $data['height_id'],
            (int) $data['mother_tongue_id'],
            (int) $data['country_id'],
            (int) $data['state_id'],
            (int) $data['city_id']
        );

        /*
        * Optional numeric fields arrive as empty strings when no value is
        * selected. Convert only non-empty validated values to integers.
        *
        * This preserves the intended distinction:
        *
        * - empty optional value => NULL
        * - selected master value => positive integer
        */
        $drinkingHabitValue = trim(
            (string) (
                $data['drinking_habit_id']
                ?? ''
            )
        );

        $drinkingHabitId =
            $drinkingHabitValue !== ''
            ? (int) $drinkingHabitValue
            : null;

        $eatingHabitValue = trim(
            (string) (
                $data['eating_habit_id']
                ?? ''
            )
        );

        $eatingHabitId =
            $eatingHabitValue !== ''
            ? (int) $eatingHabitValue
            : null;

        $physicalStatusValue = trim(
            (string) (
                $data['physical_status_id']
                ?? ''
            )
        );

        $physicalStatusId =
            $physicalStatusValue !== ''
            ? (int) $physicalStatusValue
            : null;

        $numberOfChildrenValue = trim(
            (string) (
                $data['number_of_children']
                ?? ''
            )
        );

        $numberOfChildren =
            $numberOfChildrenValue !== ''
            ? (int) $numberOfChildrenValue
            : null;

        /*
        * Preserve the difference between:
        *
        * - no selection: NULL
        * - selected No: FALSE
        * - selected Yes: TRUE
        *
        * BooleanValue::fromDatabase() is reused because it already understands
        * PostgreSQL and HTML-form boolean representations.
        */
        $childrenLivingTogetherValue =
            $data['children_living_together']
            ?? null;

        $childrenLivingTogether =
            $childrenLivingTogetherValue === null
            || trim(
                (string) $childrenLivingTogetherValue
            ) === ''
            ? null
            : BooleanValue::fromDatabase(
                $childrenLivingTogetherValue
            );

        $this->masterDataService
            ->assertValidOptionalBasicSelections(
                $drinkingHabitId,
                $eatingHabitId,
                $physicalStatusId
            );

        $maritalStatusId =
            (int) $data['marital_status_id'];

        $isNeverMarried = $this
            ->masterDataService
            ->isNeverMarried(
                $maritalStatusId
            );

        if ($isNeverMarried) {
            /*
            * Never Married profiles must not retain stale child details if the
            * marital status is changed from a previous saved value.
            */
            $numberOfChildren = null;
            $childrenLivingTogether = null;
        } elseif (
            $childrenLivingTogether !== null
            && $numberOfChildren === null
        ) {
            throw new DomainException(
                'Please enter the number of children before selecting '
                    . 'whether they live together.'
            );
        }

        $database = db_connect();

        $database->transException(true);
        $database->transStart();

        try {
            $user = $this->userModel->find($userId);

            if (!is_array($user)) {
                throw new DomainException(
                    'The member account could not be found.'
                );
            }

            $updated = $this->userModel->update(
                $userId,
                [
                    'full_name' => $data['full_name'],
                ]
            );

            if ($updated === false) {
                throw new RuntimeException(
                    'The member name could not be updated.'
                );
            }

            $isAmritdhari = BooleanValue::fromDatabase(
                $data['is_amritdhari']
            );

            $profileData = [
                'user_id' => $userId,

                'date_of_birth' =>
                $data['date_of_birth'],

                'marital_status_id' =>
                (int) $data['marital_status_id'],

                'height_id' =>
                (int) $data['height_id'],

                'mother_tongue_id' =>
                (int) $data['mother_tongue_id'],

                'country_id' =>
                (int) $data['country_id'],

                'state_id' =>
                (int) $data['state_id'],

                'city_id' =>
                (int) $data['city_id'],

                'drinking_habit_id' =>
                $drinkingHabitId,

                'eating_habit_id' =>
                $eatingHabitId,

                'physical_status_id' =>
                $physicalStatusId,

                'number_of_children' =>
                $numberOfChildren,

                'children_living_together' =>
                $childrenLivingTogether,

                'is_amritdhari' =>
                $isAmritdhari,
            ];

            $existing = $this
                ->basicDetailModel
                ->findForUser($userId);

            if (is_array($existing)) {
                $saved = $this->basicDetailModel->update(
                    (int) $existing['id'],
                    $profileData
                );
            } else {
                $saved = $this->basicDetailModel->insert(
                    $profileData,
                    false
                );
            }

            if ($saved === false) {
                throw new RuntimeException(
                    'The basic details could not be saved.'
                );
            }

            $database->transComplete();
        } catch (Throwable $exception) {
            $database->transRollback();

            throw $exception;
        }
    }

    /**
     * Enforce the gender-specific minimum member age.
     *
     * Male members must be at least 21 years old.
     * Female members must be at least 18 years old.
     */
    private function assertMinimumAge(
        string $dateOfBirth,
        string $gender
    ): void {
        $birthDate = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $dateOfBirth
        );

        if (
            !$birthDate instanceof DateTimeImmutable
            || $birthDate->format('Y-m-d') !== $dateOfBirth
        ) {
            throw new DomainException(
                'Please enter a valid date of birth.'
            );
        }

        $normalizedGender = mb_strtoupper(
            trim($gender)
        );

        $minimumAge =
            MemberAgePolicy::minimumAgeForGender(
                $normalizedGender
            );

        $latestEligibleBirthDate =
            new DateTimeImmutable(
                'today -'
                    . $minimumAge
                    . ' years'
            );

        if (
            $birthDate
            > $latestEligibleBirthDate
        ) {
            throw new DomainException(
                'The member must be at least '
                    . $minimumAge
                    . ' years old.'
            );
        }
    }

    /**
     * Calculate completion for only this profile section.
     *
     * Gender and profile-created-for are not counted because registration
     * already requires them.
     *
     * @param array<string, mixed>      $user
     * @param array<string, mixed>|null $details
     *
     * @return array{
     *     completed: int,
     *     total: int,
     *     percentage: int
     * }
     */
    private function calculateCompletion(
        array $user,
        ?array $details
    ): array {
        $values = [
            $user['full_name'] ?? null,
            $details['date_of_birth'] ?? null,
            $details['marital_status_id'] ?? null,
            $details['height_id'] ?? null,
            $details['mother_tongue_id'] ?? null,

            /*
            * Both Yes and No are completed values.
            *
            * Do not put the raw boolean FALSE into $values because
            * FALSE casts to an empty string and would incorrectly
            * be treated as incomplete.
            */
            is_array($details)
                && array_key_exists(
                    'is_amritdhari',
                    $details
                )
                && $details['is_amritdhari'] !== null
                ? 'completed'
                : null,

            $details['country_id'] ?? null,
            $details['state_id'] ?? null,
            $details['city_id'] ?? null,
        ];

        $completed = count(array_filter(
            $values,
            static fn(mixed $value): bool =>
            $value !== null
                && trim((string) $value) !== ''
        ));

        $total = count($values);

        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => (int) round(
                ($completed / $total) * 100
            ),
        ];
    }
}
