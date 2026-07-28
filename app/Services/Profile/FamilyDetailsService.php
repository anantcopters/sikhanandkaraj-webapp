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
    public function __construct(
        private readonly UserModel $userModel,
        private readonly MemberFamilyDetailModel $detailModel,
        private readonly ProfileMasterDataService $masterDataService,
        private readonly BaseConnection $database
    ) {}

    /**
     * Return all data needed by the Family Details page and profile card.
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

        $selectedStateId = $this->existingInteger(
            $details['state_id'] ?? null
        );

        $selectedCommunityId = $this->existingInteger(
            $details['community_id'] ?? null
        );

        return [
            'user' => $user,

            'familyDetails' => $details,

            'masterData' =>
            $this->masterDataService
                ->familyDetailsOptions(
                    $selectedStateId,
                    $selectedCommunityId
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

        $familyValueId = $this->requiredInteger(
            $data['family_value_id'] ?? null,
            'Please select a valid family value.'
        );

        $familyTypeId = $this->requiredInteger(
            $data['family_type_id'] ?? null,
            'Please select a valid family type.'
        );

        $familyStatusId = $this->requiredInteger(
            $data['family_status_id'] ?? null,
            'Please select a valid family status.'
        );

        $communityId = $this->requiredInteger(
            $data['community_id'] ?? null,
            'Please select a valid community.'
        );

        $subcommunityId = $this->requiredInteger(
            $data['subcommunity_id'] ?? null,
            'Please select a valid sub-community.'
        );

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
                'Married brothers cannot exceed '
                    . 'the total number of brothers.'
            );
        }

        if ($marriedSistersCount > $sistersCount) {
            throw new DomainException(
                'Married sisters cannot exceed '
                    . 'the total number of sisters.'
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
                $familyValueId,
                $familyTypeId,
                $familyStatusId,
                $communityId,
                $subcommunityId,
                $fatherOccupationId,
                $motherOccupationId,
                $countryId,
                $stateId,
                $cityId
            );

        $profileData = [
            'user_id' => $userId,
            'family_value_id' => $familyValueId,
            'family_type_id' => $familyTypeId,
            'family_status_id' => $familyStatusId,
            'community_id' => $communityId,
            'subcommunity_id' => $subcommunityId,
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

            $saved = is_array($existing)
                ? $this->detailModel->update(
                    (int) $existing['id'],
                    $profileData
                )
                : $this->detailModel->insert(
                    $profileData,
                    false
                );

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

    private function existingInteger(mixed $value): ?int
    {
        $normalized = trim((string) $value);

        if (
            $normalized === ''
            || !ctype_digit($normalized)
            || (int) $normalized <= 0
        ) {
            return null;
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
     * Calculate completion using mandatory Family Details.
     *
     * @param array<string, mixed>|null $details
     *
     * @return array<string, int>
     */
    private function calculateCompletion(
        ?array $details
    ): array {
        $requiredValues = [
            $details['family_value_id'] ?? null,
            $details['family_type_id'] ?? null,
            $details['family_status_id'] ?? null,
            $details['community_id'] ?? null,
            $details['subcommunity_id'] ?? null,
            $details['country_id'] ?? null,
            $details['state_id'] ?? null,
            $details['city_id'] ?? null,
        ];

        $completed = count(
            array_filter(
                $requiredValues,
                static fn(mixed $value): bool =>
                is_numeric($value)
                    && (int) $value > 0
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
