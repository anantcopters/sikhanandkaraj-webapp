<?php

declare(strict_types=1);

namespace App\Services\Prelaunch;

use App\Models\Prelaunch\PrelaunchProfileModel;
use App\Models\UserContactModel;
use RuntimeException;

/**
 * Validates prelaunch contacts against both staged and live members.
 */
final class PrelaunchContactAvailabilityService
{
    public function __construct(
        private readonly PrelaunchProfileModel $prelaunchProfileModel,
        private readonly UserContactModel $userContactModel
    ) {}

    public function assertAvailable(
        int $profileId,
        string $countryCode,
        string $mobileNumber,
        ?string $email
    ): void {
        $countryCode = trim($countryCode);

        $mobileNumber = preg_replace(
            '/\D+/',
            '',
            $mobileNumber
        ) ?? '';

        $email = mb_strtolower(
            trim((string) $email)
        );

        if (
            $countryCode === ''
            || $mobileNumber === ''
        ) {
            throw new RuntimeException(
                'A valid mobile number is required.'
            );
        }

        if (
            $this->prelaunchProfileModel
            ->mobileExists(
                $countryCode,
                $mobileNumber,
                $profileId
            )
        ) {
            throw new RuntimeException(
                'Another prelaunch profile already uses '
                    . 'this mobile number.'
            );
        }

        if (
            $this->userContactModel
            ->findByNormalizedValue(
                UserContactModel::TYPE_MOBILE,
                $countryCode . $mobileNumber
            ) !== null
        ) {
            throw new RuntimeException(
                'This mobile number is already registered '
                    . 'to an existing member.'
            );
        }

        if ($email === '') {
            return;
        }

        if (
            $this->prelaunchProfileModel
            ->emailExists(
                $email,
                $profileId
            )
        ) {
            throw new RuntimeException(
                'Another prelaunch profile already uses '
                    . 'this email address.'
            );
        }

        if (
            $this->userContactModel
            ->findByNormalizedValue(
                UserContactModel::TYPE_EMAIL,
                $email
            ) !== null
        ) {
            throw new RuntimeException(
                'This email address is already registered '
                    . 'to an existing member.'
            );
        }
    }
}
