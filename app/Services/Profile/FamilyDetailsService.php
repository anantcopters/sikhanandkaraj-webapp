<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\MemberFamilyDetailModel;
use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Reads and updates the Family Details profile section.
 */
final class FamilyDetailsService
{
    private const FAMILY_VALUES = [
        'ORTHODOX',
        'TRADITIONAL',
        'MODERATE',
        'LIBERAL',
    ];

    private const FAMILY_TYPES = [
        'JOINT_FAMILY',
        'NUCLEAR_FAMILY',
        'OTHERS',
    ];

    private const FAMILY_STATUSES = [
        'MIDDLE_CLASS',
        'UPPER_MIDDLE_CLASS',
        'HIGH_CLASS',
        'RICH_AFFLUENT',
    ];

    public function __construct(
        private readonly UserModel $userModel,
        private readonly MemberFamilyDetailModel $detailModel,
        private readonly ProfileMasterDataService $masterDataService,
        private readonly BaseConnection $database
    ) {}

    /**
     * Return all data required for the Family Details page/card.
     *
     * @return array<string, mixed>
     */
    public function getForUser(int $userId): array
    {
        $user = $this->userModel->find($userId);

        if (!is_array($user)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $details = $this->detailModel->findForUser($userId);

        $selectedStateId = is_array($details)
            && is_numeric($details['state_id'] ?? null)
            ? (int) $details['state_id']
            : null;

        return [
            'user' => $user,

            'familyDetails' => $details,

            'masterData' =>
            $this->masterDataService
                ->familyDetailsOptions(
                    $selectedStateId
                ),

            'completion' =>
            $this->calculateCompletion($details),
        ];
    }

    /**
     * Create or update Family Details.
     *
     * @param array<string, mixed> $data
     */
    public function save(
        int $userId,
        array $data
    ): void {
        $user = $this->userModel->find($userId);

        if (!is_array($user)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $familyValue = strtoupper(
            trim((string) ($data['family_value'] ?? ''))
        );

        $familyType = strtoupper(
            trim((string) ($data['family_type'] ?? ''))
        );

        $familyStatus = strtoupper(
            trim((string) ($data['family_status'] ?? ''))
        );

        if (!in_array(
            $familyValue,
            self::FAMILY_VALUES,
            true
        )) {
            throw new DomainException(
                'Please select a valid family value.'
            );
        }

        if (!in_array(
            $familyType,
            self::FAMILY_TYPES,
            true
        )) {
            throw new DomainException(
                'Please select a valid family type.'
            );
        }

        if (!in_array(
            $familyStatus,
            self::FAMILY_STATUSES,
            true
        )) {
            throw new DomainException(
                'Please select a valid family status.'
            );
        }

        $fatherOccupationId = $this->nullableInteger(
            $data['father_occupation_id'] ?? null,
            "Please select a valid father's occupation."
        );

        $motherOccupationId = $this->nullableInteger(
            $data['mother_occupation_id'] ?? null,
            "Please select a valid mother's occupation."
        );

        $brothersCount = $this->siblingCount(
            $data['brothers_count'] ?? null,
            'Please select the number of brothers.'
        );

        $marriedBrothersCount = $this->siblingCount(
            $data['married_brothers_count'] ?? null,
            'Please select the number of married brothers.'
        );

        $sistersCount = $this->siblingCount(
            $data['sisters_count'] ?? null,
            'Please select the number of sisters.'
        );

        $marriedSistersCount = $this->siblingCount(
            $data['married_sisters_count'] ?? null,
            'Please select the number of married sisters.'
        );

        if ($marriedBrothersCount > $brothersCount) {
            throw new DomainException(
                'Married brothers cannot exceed the total number of brothers.'
            );
        }

        if ($marriedSistersCount > $sistersCount) {
            throw new DomainException(
                'Married sisters cannot exceed the total number of sisters.'
            );
        }

        $countryId = $this->requiredInteger(
            $data['country_id'] ?? null,
            'Please select a valid country.'
        );

        $stateId = $this->requiredInteger(
            $data['state_id'] ?? null,
            'Please select a valid family state.'
        );

        $cityId = $this->requiredInteger(
            $data['city_id'] ?? null,
            'Please select a valid family city.'
        );

        $this->masterDataService
            ->assertValidFamilySelection(
                $fatherOccupationId,
                $motherOccupationId,
                $countryId,
                $stateId,
                $cityId
            );

        $profileData = [
            'user_id' => $userId,
            'family_value' => $familyValue,
            'family_type' => $familyType,
            'family_status' => $familyStatus,
            'father_occupation_id' =>
            $fatherOccupationId,
            'mother_occupation_id' =>
            $motherOccupationId,
            'brothers_count' => $brothersCount,
            'married_brothers_count' =>
            $marriedBrothersCount,
            'sisters_count' => $sistersCount,
            'married_sisters_count' =>
            $marriedSistersCount,
            'country_id' => $countryId,
            'state_id' => $stateId,
            'city_id' => $cityId,
        ];

        $this->database->transException(true);
        $this->database->transStart();

        try {
            $existing = $this->detailModel
                ->findForUser($userId);

            if (is_array($existing)) {
                $saved = $this->detailModel->update(
                    (int) $existing['id'],
                    $profileData
                );
            } else {
                $saved = $this->detailModel->insert(
                    $profileData,
                    false
                );
            }

            if ($saved === false) {
                throw new RuntimeException(
                    'Family details could not be saved.'
                );
            }

            $this->database->transComplete();

            if ($this->database->transStatus() === false) {
                throw new RuntimeException(
                    'Family details could not be saved.'
                );
            }
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    private function requiredInteger(
        mixed $value,
        string $message
    ): int {
        $normalized = trim((string) $value);

        if (
            $normalized === ''
            || !ctype_digit($normalized)
            || (int) $normalized <= 0
        ) {
            throw new DomainException($message);
        }

        return (int) $normalized;
    }

    private function nullableInteger(
        mixed $value,
        string $message
    ): ?int {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        if (
            !ctype_digit($normalized)
            || (int) $normalized <= 0
        ) {
            throw new DomainException($message);
        }

        return (int) $normalized;
    }

    private function siblingCount(
        mixed $value,
        string $message
    ): int {
        $normalized = trim((string) $value);

        if (
            $normalized === ''
            || !ctype_digit($normalized)
            || (int) $normalized < 0
            || (int) $normalized > 10
        ) {
            throw new DomainException($message);
        }

        return (int) $normalized;
    }

    /**
     * Calculate completion using mandatory profile information.
     *
     * @param array<string, mixed>|null $details
     *
     * @return array<string, int>
     */
    private function calculateCompletion(
        ?array $details
    ): array {
        $requiredValues = [
            $details['family_value'] ?? null,
            $details['family_type'] ?? null,
            $details['family_status'] ?? null,
            $details['country_id'] ?? null,
            $details['state_id'] ?? null,
            $details['city_id'] ?? null,
        ];

        $completed = count(
            array_filter(
                $requiredValues,
                static fn(mixed $value): bool =>
                $value !== null
                    && trim((string) $value) !== ''
            )
        );

        $total = count($requiredValues);

        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total > 0
                ? (int) round(
                    ($completed / $total) * 100
                )
                : 0,
        ];
    }
}
