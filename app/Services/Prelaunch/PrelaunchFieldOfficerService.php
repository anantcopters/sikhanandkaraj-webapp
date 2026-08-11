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
     * Resolve the configured Field Officer.
     *
     * Used by QA/development where prelaunch entry continues
     * assigning the configured Field Officer automatically.
     *
     * @return array{
     *     id:int,
     *     officer_code:string,
     *     full_name:string,
     *     account_status:string
     * }
     */
    public function resolveConfiguredOfficer(
        int $fieldOfficerId
    ): array {
        if ($fieldOfficerId <= 0) {
            throw new RuntimeException(
                'The prelaunch Field Officer is not configured.'
            );
        }

        $fieldOfficer =
            $this->fieldOfficerModel
            ->findActiveById(
                $fieldOfficerId
            );

        if ($fieldOfficer === null) {
            throw new RuntimeException(
                'The configured prelaunch Field Officer '
                    . 'is invalid or inactive.'
            );
        }

        return [
            'id' =>
            (int) $fieldOfficer['id'],

            'officer_code' =>
            (string) (
                $fieldOfficer['officer_code']
                ?? ''
            ),

            'full_name' =>
            (string) (
                $fieldOfficer['full_name']
                ?? ''
            ),

            'account_status' =>
            (string) (
                $fieldOfficer['account_status']
                ?? ''
            ),
        ];
    }

    /**
     * Verify an ACTIVE and non-deleted Field Officer by code.
     *
     * Used by the production prelaunch flow.
     *
     * @return array{
     *     id:int,
     *     officer_code:string,
     *     full_name:string,
     *     state_name:string,
     *     city_name:string,
     *     location:string
     * }
     */
    public function verifyCode(
        string $officerCode
    ): array {
        $normalizedCode =
            $this->normalizeCode(
                $officerCode
            );

        if ($normalizedCode === '') {
            throw new RuntimeException(
                'Please enter a Field Officer code.'
            );
        }

        /*
         * Current generated Field Officer format:
         *
         * FOSAK + six digits
         *
         * Keep this aligned with FieldOfficerService.
         */
        if (
            preg_match(
                '/^FOSAK[0-9]{6}$/',
                $normalizedCode
            ) !== 1
        ) {
            throw new RuntimeException(
                'Please enter a valid Field Officer code.'
            );
        }

        $fieldOfficer =
            $this->fieldOfficerModel
            ->findActiveByCode(
                $normalizedCode
            );

        if ($fieldOfficer === null) {
            throw new RuntimeException(
                'The Field Officer code is invalid or inactive.'
            );
        }

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

            'state_name' =>
            $stateName,

            'city_name' =>
            $cityName,

            'location' =>
            $location,
        ];
    }

    /**
     * Revalidate the submitted verification before save.
     *
     * The hidden Field Officer ID is browser-controlled and
     * therefore never trusted directly.
     *
     * The officer:
     *
     * - must still exist;
     * - must still be ACTIVE;
     * - must still match the entered code;
     * - must match the ID returned during verification.
     *
     * @return array{
     *     id:int,
     *     officer_code:string,
     *     full_name:string,
     *     state_name:string,
     *     city_name:string,
     *     location:string
     * }
     */
    public function assertVerifiedOfficer(
        int $fieldOfficerId,
        string $officerCode
    ): array {
        if ($fieldOfficerId <= 0) {
            throw new RuntimeException(
                'Please verify the Field Officer '
                    . 'before saving the profile.'
            );
        }

        $fieldOfficer =
            $this->verifyCode(
                $officerCode
            );

        if (
            (int) $fieldOfficer['id']
            !== $fieldOfficerId
        ) {
            throw new RuntimeException(
                'The verified Field Officer no longer '
                    . 'matches the entered code. '
                    . 'Please verify it again.'
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
