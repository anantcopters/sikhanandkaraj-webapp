<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\MemberFamilyDetailModel;
use App\Models\UserModel;
use App\Support\IndianMobileNormalizer;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Reads and updates the Family Details profile section.
 */
final class FamilyDetailsService
{
    private const PARENT_NAME_MAX_LENGTH = 150;

    private const GOTRA_MAX_LENGTH = 100;

    private const GURUDWARA_MAX_LENGTH = 300;

    private const REFERENCE_PERSON_MAX_LENGTH = 200;

    public function __construct(
        private readonly UserModel $userModel,
        private readonly MemberFamilyDetailModel $detailModel,
        private readonly ProfileMasterDataService $masterDataService,
        private readonly BaseConnection $database
    ) {}

    /**
     * Return all data required by the Family Details page and profile card.
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

        $details = $this->detailModel->findForUser(
            $userId
        );

        $selectedStateId = $this->existingInteger(
            $details['state_id'] ?? null
        );

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

        /*
        * These fields are optional, but when selected they must contain valid
        * positive master-data identifiers.
        */
        $familyValueId = $this->nullableInteger(
            $data['family_value_id'] ?? null,
            'Please select a valid family value.'
        );

        $familyTypeId = $this->nullableInteger(
            $data['family_type_id'] ?? null,
            'Please select a valid family type.'
        );

        $familyStatusId = $this->nullableInteger(
            $data['family_status_id'] ?? null,
            'Please select a valid family status.'
        );

        $communityId = $this->requiredInteger(
            $data['community_id'] ?? null,
            'Please select a valid community.'
        );

        $gotra = $this->requiredGotra(
            $data['gotra'] ?? null
        );

        /*
        * Service-level checks protect the domain if this service is later
        * called through an API, command or another controller.
        */
        $fatherName = $this->requiredParentName(
            $data['father_name'] ?? null,
            "Please enter your father's name."
        );

        $motherName = $this->requiredParentName(
            $data['mother_name'] ?? null,
            "Please enter your mother's name."
        );

        $parentContactNumber =
            $this->requiredParentContactNumber(
                $data['parent_contact_number']
                    ?? null
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

        $sistersCount = $this->siblingCount(
            $data['sisters_count'] ?? null,
            'Please select the number of sisters.'
        );

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

        $nearestGurudwara = $this->requiredText(
            $data['nearest_gurudwara'] ?? null,
            self::GURUDWARA_MAX_LENGTH,
            'Please enter the nearest Gurudwara name or location.',
            'Nearest Gurudwara'
        );

        $referencePerson1 = $this->optionalText(
            $data['reference_person_1'] ?? null,
            self::REFERENCE_PERSON_MAX_LENGTH,
            'First reference person'
        );

        $referencePerson2 = $this->optionalText(
            $data['reference_person_2'] ?? null,
            self::REFERENCE_PERSON_MAX_LENGTH,
            'Second reference person'
        );

        $this->masterDataService
            ->assertValidFamilySelection(
                $familyValueId,
                $familyTypeId,
                $familyStatusId,
                $communityId,
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
            'gotra' => $gotra,
            'father_name' => $fatherName,
            'mother_name' => $motherName,
            'parent_contact_number' =>
            $parentContactNumber,
            'father_occupation_id' => $fatherOccupationId,
            'mother_occupation_id' => $motherOccupationId,
            'brothers_count' => $brothersCount,
            'sisters_count' => $sistersCount,
            'country_id' => $countryId,
            'state_id' => $stateId,
            'city_id' => $cityId,
            'nearest_gurudwara' => $nearestGurudwara,
            'reference_person_1' => $referencePerson1,
            'reference_person_2' => $referencePerson2,
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

    /**
     * Normalize and require an Indian parent contact number.
     */
    private function requiredParentContactNumber(
        mixed $value
    ): string {
        $submittedValue = preg_replace(
            '/\D+/',
            '',
            (string) $value
        ) ?? '';

        if ($submittedValue === '') {
            throw new DomainException(
                'Please enter a contact number for either parent/guardian.'
            );
        }

        $normalized =
            IndianMobileNormalizer::normalize(
                '+91' . $submittedValue
            );

        if ($normalized === null) {
            throw new DomainException(
                'Please enter a valid 10-digit Indian '
                    . 'parent/guardian contact number.'
            );
        }

        return $normalized;
    }

    /**
     * Normalize and require a parent's name.
     */
    private function requiredParentName(
        mixed $value,
        string $requiredMessage
    ): string {
        $normalized = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $value)
        ) ?? '';

        if ($normalized === '') {
            throw new DomainException($requiredMessage);
        }

        if (
            mb_strlen(
                $normalized,
                'UTF-8'
            ) > self::PARENT_NAME_MAX_LENGTH
        ) {
            throw new DomainException(
                'Parent name cannot exceed '
                    . self::PARENT_NAME_MAX_LENGTH
                    . ' characters.'
            );
        }

        return $normalized;
    }

    /**
     * Normalize and require a profile text value.
     */
    private function requiredText(
        mixed $value,
        int $maximumLength,
        string $requiredMessage,
        string $label
    ): string {
        $normalized = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $value)
        ) ?? '';

        if ($normalized === '') {
            throw new DomainException(
                $requiredMessage
            );
        }

        if (
            mb_strlen(
                $normalized,
                'UTF-8'
            ) > $maximumLength
        ) {
            throw new DomainException(
                $label . ' cannot exceed '
                    . $maximumLength
                    . ' characters.'
            );
        }

        return $normalized;
    }

    /**
     * Normalize and require the member's Gotra.
     */
    private function requiredGotra(mixed $value): string
    {
        $normalized = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $value)
        ) ?? '';

        if ($normalized === '') {
            throw new DomainException(
                'Please enter your Gotra.'
            );
        }

        if (
            mb_strlen(
                $normalized,
                'UTF-8'
            ) > self::GOTRA_MAX_LENGTH
        ) {
            throw new DomainException(
                'Gotra cannot exceed '
                    . self::GOTRA_MAX_LENGTH
                    . ' characters.'
            );
        }

        return $normalized;
    }

    /**
     * Normalize optional profile text.
     *
     * Empty values are converted to NULL so PostgreSQL stores the absence of
     * a value consistently instead of storing empty strings.
     */
    private function optionalText(
        mixed $value,
        int $maximumLength,
        string $label
    ): ?string {
        $normalized = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $value)
        ) ?? '';

        if ($normalized === '') {
            return null;
        }

        if (
            mb_strlen(
                $normalized,
                'UTF-8'
            ) > $maximumLength
        ) {
            throw new DomainException(
                $label . ' cannot exceed '
                    . $maximumLength
                    . ' characters.'
            );
        }

        return $normalized;
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
     * Calculate completion from compulsory Family Details.
     *
     * Family value, family type and family status are optional and therefore
     * deliberately excluded from completion.
     *
     * A zero sibling count is a valid completed value.
     *
     * @param array<string, mixed>|null $details
     *
     * @return array<string, int>
     */
    private function calculateCompletion(
        ?array $details
    ): array {
        $details = is_array($details)
            ? $details
            : [];

        $requiredChecks = [
            $this->hasPositiveInteger(
                $details['community_id'] ?? null
            ),

            $this->hasRequiredText(
                $details['gotra'] ?? null
            ),

            $this->hasRequiredText(
                $details['father_name'] ?? null
            ),

            $this->hasRequiredText(
                $details['mother_name'] ?? null
            ),

            $this->hasRequiredText(
                $details['parent_contact_number']
                    ?? null
            ),

            $this->hasValidSiblingCount(
                $details,
                'brothers_count'
            ),

            $this->hasValidSiblingCount(
                $details,
                'sisters_count'
            ),

            $this->hasPositiveInteger(
                $details['country_id'] ?? null
            ),

            $this->hasPositiveInteger(
                $details['state_id'] ?? null
            ),

            $this->hasPositiveInteger(
                $details['city_id'] ?? null
            ),

            $this->hasRequiredText(
                $details['nearest_gurudwara']
                    ?? null
            ),
        ];

        $completed = count(
            array_filter(
                $requiredChecks,
                static fn(bool $completed): bool => $completed
            )
        );

        $total = count($requiredChecks);

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

    private function hasPositiveInteger(mixed $value): bool
    {
        return is_numeric($value)
            && (int) $value > 0;
    }

    private function hasRequiredText(mixed $value): bool
    {
        return trim((string) $value) !== '';
    }

    /**
     * Determine whether a required sibling count was actually stored.
     *
     * array_key_exists is required because zero is a valid value.
     *
     * @param array<string, mixed> $details
     */
    private function hasValidSiblingCount(
        array $details,
        string $field
    ): bool {
        if (!array_key_exists($field, $details)) {
            return false;
        }

        $value = $details[$field];

        return is_numeric($value)
            && (int) $value >= 0
            && (int) $value <= 10;
    }
}
