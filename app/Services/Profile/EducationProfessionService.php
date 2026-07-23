<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\MemberEducationProfessionDetailModel;
use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Reads and updates the Education & Profession profile section.
 */
final class EducationProfessionService
{
    /**
     * Supported employment types.
     *
     * These values must remain synchronized with:
     *
     * 1. EducationProfessionValidation
     * 2. The database CHECK constraint
     * 3. ProfileMasterDataService employment options
     */
    private const EMPLOYMENT_TYPES = [
        'GOVERNMENT_PSU',
        'PRIVATE',
        'BUSINESS',
        'DEFENSE',
        'SELF_EMPLOYED',
        'NOT_WORKING',
    ];

    public function __construct(
        private readonly UserModel $userModel,
        private readonly MemberEducationProfessionDetailModel $detailModel,
        private readonly ProfileMasterDataService $masterDataService,
        private readonly BaseConnection $database
    ) {}

    /**
     * Return all data required to display this profile section.
     *
     * @return array{
     *     user: array<string, mixed>,
     *     educationProfession: array<string, mixed>|null,
     *     masterData: array<string, mixed>,
     *     completion: array{
     *         completed: int,
     *         total: int,
     *         percentage: int
     *     }
     * }
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

        return [
            'user' => $user,

            'educationProfession' => $details,

            'masterData' => $this->masterDataService
                ->educationProfessionOptions(),

            'completion' => $this->calculateCompletion($details),
        ];
    }

    /**
     * Create or update Education & Profession details.
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

        $employedIn = strtoupper(
            trim((string) ($data['employed_in'] ?? ''))
        );

        if (!in_array(
            $employedIn,
            self::EMPLOYMENT_TYPES,
            true
        )) {
            throw new DomainException(
                'Please select a valid employment type.'
            );
        }

        $highestEducationId = $this->requiredInteger(
            $data['highest_education_id'] ?? null,
            'Please select your highest education.'
        );

        $occupationId = $this->requiredInteger(
            $data['occupation_id'] ?? null,
            'Please select your occupation.'
        );

        $annualIncomeId = $this->nullableInteger(
            $data['annual_income_id'] ?? null,
            'Please select a valid annual income.'
        );

        /*
         * Ensure selected master records exist and are active.
         * This prevents inactive or manually submitted IDs
         * from being stored.
         */
        $this->masterDataService
            ->assertValidEducationProfessionSelection(
                $highestEducationId,
                $occupationId,
                $annualIncomeId
            );

        $profileData = [
            'user_id' => $userId,

            'highest_education_id' =>
            $highestEducationId,

            'education_detail' =>
            $this->nullableText(
                $data['education_detail'] ?? null
            ),

            'college_institution' =>
            $this->nullableText(
                $data['college_institution'] ?? null
            ),

            'employed_in' => $employedIn,

            'occupation_id' => $occupationId,

            'occupation_detail' =>
            $this->nullableText(
                $data['occupation_detail'] ?? null
            ),

            'organization' =>
            $this->nullableText(
                $data['organization'] ?? null
            ),

            'annual_income_id' => $annualIncomeId,
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
                    'Education and profession details '
                        . 'could not be saved.'
                );
            }

            $this->database->transComplete();

            if ($this->database->transStatus() === false) {
                throw new RuntimeException(
                    'Education and profession details '
                        . 'could not be saved.'
                );
            }
        } catch (Throwable $exception) {
            /*
             * transRollback() is safe here even when CI4 has already
             * marked the transaction as failed.
             */
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Return trimmed text or NULL for an empty optional value.
     */
    private function nullableText(mixed $value): ?string
    {
        $normalized = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $value)
        ) ?? '';

        return $normalized !== ''
            ? $normalized
            : null;
    }

    /**
     * Convert a required numeric master ID.
     */
    private function requiredInteger(
        mixed $value,
        string $errorMessage
    ): int {
        $normalized = trim((string) $value);

        if (
            $normalized === ''
            || !ctype_digit($normalized)
            || (int) $normalized <= 0
        ) {
            throw new DomainException($errorMessage);
        }

        return (int) $normalized;
    }

    /**
     * Convert an optional numeric master ID.
     *
     * Empty value becomes NULL. Invalid non-empty input throws
     * an exception rather than silently becoming NULL.
     */
    private function nullableInteger(
        mixed $value,
        string $errorMessage
    ): ?int {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        if (
            !ctype_digit($normalized)
            || (int) $normalized <= 0
        ) {
            throw new DomainException($errorMessage);
        }

        return (int) $normalized;
    }

    /**
     * Calculate whether this section is complete.
     *
     * Only mandatory fields determine section completion.
     * Optional fields improve profile richness but must not prevent
     * the member from completing the section.
     *
     * Mandatory fields:
     *
     * 1. Highest education
     * 2. Employed in
     * 3. Occupation
     *
     * @param array<string, mixed>|null $details
     *
     * @return array{
     *     completed: int,
     *     total: int,
     *     percentage: int
     * }
     */
    private function calculateCompletion(
        ?array $details
    ): array {
        $requiredValues = [
            $details['highest_education_id'] ?? null,
            $details['employed_in'] ?? null,
            $details['occupation_id'] ?? null,
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
