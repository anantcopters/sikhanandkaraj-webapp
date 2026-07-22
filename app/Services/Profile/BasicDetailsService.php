<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\MemberBasicDetailModel;
use App\Models\UserModel;
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
        private readonly UserModel $userModel = new UserModel(),
        private readonly MemberBasicDetailModel $basicDetailModel =
        new MemberBasicDetailModel()
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
    public function getForUser(int $userId): array
    {
        $user = $this->userModel->find($userId);

        if (!is_array($user)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $basicDetails = $this
            ->basicDetailModel
            ->findForUser($userId);

        return [
            'user' => $user,
            'basicDetails' => $basicDetails,
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
        $this->assertAdult(
            (string) $data['date_of_birth']
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

            $profileData = [
                'user_id' => $userId,

                'date_of_birth' =>
                $data['date_of_birth'],

                'marital_status' =>
                $data['marital_status'],

                'height_cm' =>
                (int) $data['height_cm'],

                'mother_tongue' =>
                $data['mother_tongue'],

                'current_city' =>
                $data['current_city'],

                'current_state' =>
                $data['current_state'],

                'country_code' =>
                $data['country_code'],
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
     * Ensure date of birth represents an adult member.
     */
    private function assertAdult(
        string $dateOfBirth
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

        $minimumAdultDate = new DateTimeImmutable(
            'today -18 years'
        );

        if ($birthDate > $minimumAdultDate) {
            throw new DomainException(
                'The member must be at least 18 years old.'
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
            $details['marital_status'] ?? null,
            $details['height_cm'] ?? null,
            $details['mother_tongue'] ?? null,
            $details['current_city'] ?? null,
            $details['current_state'] ?? null,
            $details['country_code'] ?? null,
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
