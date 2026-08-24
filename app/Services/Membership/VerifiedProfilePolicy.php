<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Services\Profile\MemberTrustVerificationService;

/**
 * Defines the commercial/product meaning of a Verified Profile.
 *
 * A profile is Verified when at least one supported credential is currently
 * verified:
 *
 * - Mobile;
 * - Email;
 * - Aadhaar;
 * - approved Live Introduction.
 *
 * Account type is deliberately irrelevant. A Free member with verified
 * Mobile is a Verified Profile.
 *
 * This policy must remain the single product-level authority for deciding
 * whether a candidate qualifies as a Verified Profile.
 */
final class VerifiedProfilePolicy
{
    public function __construct(
        private readonly MemberTrustVerificationService
        $trustVerificationService
    ) {}

    /**
     * Return whether the member currently qualifies as a Verified Profile.
     */
    public function isVerified(
        int $userId
    ): bool {
        if ($userId <= 0) {
            return false;
        }

        $verification = $this
            ->trustVerificationService
            ->getForUser(
                $userId
            );

        return (
            (
                $verification['mobile']['isVerified']
                ?? false
            ) === true
            || (
                $verification['email']['isVerified']
                ?? false
            ) === true
            || (
                $verification['aadhaar']['isVerified']
                ?? false
            ) === true
            || (
                $verification['videoIntroduction']['isApproved']
                ?? false
            ) === true
        );
    }

    /**
     * Return the credential state together with the aggregate result.
     *
     * This is useful for future access-policy, administration and Match Score
     * consumers that need to know why a profile is verified without
     * re-querying each verification subsystem independently.
     *
     * @return array{
     *     isVerifiedProfile:bool,
     *     mobile:bool,
     *     email:bool,
     *     aadhaar:bool,
     *     liveIntroduction:bool,
     *     verifiedCredentialCount:int
     * }
     */
    public function stateForUser(
        int $userId
    ): array {
        if ($userId <= 0) {
            return $this->emptyState();
        }

        $verification = $this
            ->trustVerificationService
            ->getForUser(
                $userId
            );

        $mobile = (
            $verification['mobile']['isVerified']
            ?? false
        ) === true;

        $email = (
            $verification['email']['isVerified']
            ?? false
        ) === true;

        $aadhaar = (
            $verification['aadhaar']['isVerified']
            ?? false
        ) === true;

        $liveIntroduction = (
            $verification['videoIntroduction']['isApproved']
            ?? false
        ) === true;

        $verifiedCredentialCount =
            (int) $mobile
            + (int) $email
            + (int) $aadhaar
            + (int) $liveIntroduction;

        return [
            'isVerifiedProfile' =>
            $verifiedCredentialCount > 0,

            'mobile' =>
            $mobile,

            'email' =>
            $email,

            'aadhaar' =>
            $aadhaar,

            'liveIntroduction' =>
            $liveIntroduction,

            'verifiedCredentialCount' =>
            $verifiedCredentialCount,
        ];
    }

    /**
     * Return the safe empty verification state.
     *
     * @return array{
     *     isVerifiedProfile:false,
     *     mobile:false,
     *     email:false,
     *     aadhaar:false,
     *     liveIntroduction:false,
     *     verifiedCredentialCount:0
     * }
     */
    private function emptyState(): array
    {
        return [
            'isVerifiedProfile' =>
            false,

            'mobile' =>
            false,

            'email' =>
            false,

            'aadhaar' =>
            false,

            'liveIntroduction' =>
            false,

            'verifiedCredentialCount' =>
            0,
        ];
    }
}
