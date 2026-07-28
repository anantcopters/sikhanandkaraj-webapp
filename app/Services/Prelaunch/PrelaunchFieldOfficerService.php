<?php

declare(strict_types=1);

namespace App\Services\Prelaunch;

use App\Models\FieldOfficerModel;
use RuntimeException;

/**
 * Resolves and validates Field Officers for pre-launch data entry.
 */
final class PrelaunchFieldOfficerService
{
    public function __construct(
        private readonly FieldOfficerModel $fieldOfficerModel
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function verifyCode(
        string $officerCode
    ): array {
        $normalizedCode = mb_strtoupper(
            trim($officerCode)
        );

        if ($normalizedCode === '') {
            throw new RuntimeException(
                'Please enter a Field Officer code.'
            );
        }

        $fieldOfficer = $this->fieldOfficerModel
            ->findActiveByCode($normalizedCode);

        if ($fieldOfficer === null) {
            throw new RuntimeException(
                'The Field Officer code is invalid or inactive.'
            );
        }

        return [
            'id' => (int) $fieldOfficer['id'],
            'officer_code' =>
            (string) $fieldOfficer['officer_code'],
            'full_name' =>
            (string) $fieldOfficer['full_name'],
            'state_name' =>
            (string) $fieldOfficer['state_name'],
            'city_name' =>
            (string) $fieldOfficer['city_name'],
        ];
    }

    public function assertVerifiedOfficer(
        int $fieldOfficerId,
        string $officerCode
    ): void {
        $fieldOfficer = $this->verifyCode(
            $officerCode
        );

        if (
            (int) $fieldOfficer['id']
            !== $fieldOfficerId
        ) {
            throw new RuntimeException(
                'The verified Field Officer no longer matches the selected code.'
            );
        }
    }
}
