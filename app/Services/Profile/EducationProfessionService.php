<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\MemberEducationProfessionDetailModel;
use App\Models\UserModel;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Reads and updates the Education & Profession profile section.
 */
final class EducationProfessionService
{
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
        private readonly ProfileMasterDataService $masterDataService
    ) {}

    /**
     * Return data required to display the section.
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

            'masterData' =>
            $this->masterDataService
                ->educationProfessionOptions(),

            'completion' =>
            $this->calculateCompletion($details),
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
        $employedIn = strtoupper(
            trim((string) $data['employed_in'])
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

        $annualIncomeId = $this->nullableInteger(
            $data['annual_income_id'] ?? null
        );

        $this->masterDataService
            ->assertValidEducationProfessionSelection(
                (int) $data['highest_education_id'],
                (int) $data['occupation_id'],
                $annualIncomeId
            );

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

            $profileData = [
                'user_id' => $userId,

                'highest_education_id' =>
                (int) $data['highest_education_id'],

                'education_detail' =>
                $this->nullableText(
                    $data['education_detail'] ?? null
                ),

                'college_institution' =>
                $this->nullableText(
                    $data['college_institution'] ?? null
                ),

                'employed_in' => $employedIn,

                'occupation_id' =>
                (int) $data['occupation_id'],

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

            $database->transComplete();
        } catch (Throwable $exception) {
            $database->transRollback();

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
     * Return a positive integer or NULL.
     */
    private function nullableInteger(mixed $value): ?int
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

    /**
     * Calculate completion for this section.
     *
     * Optional fields contribute to profile richness and therefore
     * are included in section completion.
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
        $values = [
            $details['highest_education_id'] ?? null,
            $details['education_detail'] ?? null,
            $details['college_institution'] ?? null,
            $details['employed_in'] ?? null,
            $details['occupation_id'] ?? null,
            $details['occupation_detail'] ?? null,
            $details['organization'] ?? null,
            $details['annual_income_id'] ?? null,
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
            'percentage' => $total > 0
                ? (int) round(
                    ($completed / $total) * 100
                )
                : 0,
        ];
    }
}
