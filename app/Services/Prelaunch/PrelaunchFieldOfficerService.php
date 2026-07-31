<?php

declare(strict_types=1);

namespace App\Services\Prelaunch;

use App\Models\FieldOfficerModel;
use RuntimeException;

/**
 * Resolves and validates Field Officers used during pre-launch entry.
 */
final class PrelaunchFieldOfficerService
{
    public function __construct(
        private readonly FieldOfficerModel $fieldOfficerModel
    ) {}

    /**
     * Verify an active Field Officer by code.
     *
     * @return array{
     *     id: int,
     *     officer_code: string,
     *     full_name: string,
     *     country_name: string,
     *     state_name: string,
     *     city_name: string,
     *     location: string
     * }
     */
    public function verifyCode(
        string $officerCode
    ): array {
        $normalizedCode = $this->normalizeCode(
            $officerCode
        );

        if ($normalizedCode === '') {
            throw new RuntimeException(
                'Please enter a Field Officer code.'
            );
        }

        if (
            preg_match(
                '/^[A-Z0-9-]{4,20}$/',
                $normalizedCode
            ) !== 1
        ) {
            throw new RuntimeException(
                'Please enter a valid Field Officer code.'
            );
        }

        $fieldOfficer = $this->fieldOfficerModel
            ->findActiveByCode(
                $normalizedCode
            );

        if ($fieldOfficer === null) {
            throw new RuntimeException(
                'The Field Officer code is invalid or inactive.'
            );
        }

        $countryName = trim(
            (string) (
                $fieldOfficer['country_name']
                ?? ''
            )
        );

        $stateName = trim(
            (string) (
                $fieldOfficer['state_name']
                ?? ''
            )
        );

        $cityName = trim(
            (string) (
                $fieldOfficer['city_name']
                ?? ''
            )
        );

        $location = implode(
            ', ',
            array_filter(
                [
                    $cityName,
                    $stateName,
                    $countryName,
                ],
                static fn(
                    string $value
                ): bool => $value !== ''
            )
        );

        return [
            'id' =>
            (int) $fieldOfficer['id'],

            'officer_code' =>
            (string) $fieldOfficer['officer_code'],

            'full_name' =>
            (string) $fieldOfficer['full_name'],

            'country_name' =>
            $countryName,

            'state_name' =>
            $stateName,

            'city_name' =>
            $cityName,

            'location' =>
            $location,
        ];
    }

    /**
     * Revalidate the submitted hidden ID against the submitted code.
     *
     * Hidden form fields are user-controlled and must never be trusted
     * without this server-side verification.
     *
     * @return array{
     *     id: int,
     *     officer_code: string,
     *     full_name: string,
     *     country_name: string,
     *     state_name: string,
     *     city_name: string,
     *     location: string
     * }
     */
    public function assertVerifiedOfficer(
        int $fieldOfficerId,
        string $officerCode
    ): array {
        if ($fieldOfficerId <= 0) {
            throw new RuntimeException(
                'Please verify the Field Officer before saving the profile.'
            );
        }

        $fieldOfficer = $this->verifyCode(
            $officerCode
        );

        if (
            $fieldOfficer['id']
            !== $fieldOfficerId
        ) {
            throw new RuntimeException(
                'The verified Field Officer no longer matches the entered code. Please verify it again.'
            );
        }

        return $fieldOfficer;
    }

    private function normalizeCode(
        string $officerCode
    ): string {
        return mb_strtoupper(
            trim($officerCode)
        );
    }
}
