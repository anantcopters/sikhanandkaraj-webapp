<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Services\Profile\MemberPhotoUrlService;
use App\Support\MemberNameVisibility;
use App\Support\BooleanValue;
use DateTimeImmutable;
use Throwable;

/**
 * Builds the common member-summary presentation contract used by
 * member-facing multi-profile screens.
 *
 * Common presentation values are centralized here while Search,
 * Dashboard and Interest-specific state remains with the owning service.
 */
final class MemberProfilePresentationService
{
    /**
     * Temporary account label until member subscription plans are connected
     * to the common profile-presentation contract.
     */
    private const DEFAULT_ACCOUNT_TYPE =
    'Free Account';

    public function __construct(
        private readonly MemberPhotoUrlService
        $photoUrlService
    ) {}

    /**
     * Build the common member-summary contract for one visible member.
     *
     * The calling service must already have applied the normal
     * member visibility/eligibility rules.
     *
     * @param array<string, mixed> $member
     *
     * @return array{
     *     referenceId:string,
     *     name:string,
     *     age:int|null,
     *     height:string,
     *     country:string,
     *     city:string,
     *     state:string,
     *     location:string,
     *     maritalStatus:string,
     *     accountType:string,
     *     professionalSummary:string,
     *     verification:array{
     *         mobile:bool,
     *         email:bool,
     *         aadhaar:bool,
     *         selfie:bool
     *     },
     *     image:string,
     *     profileUrl:string
     * }|null
     */
    public function summary(
        int $viewerUserId,
        array $member,
        bool $hasInterestRelationship
    ): ?array {
        $memberId = max(
            0,
            (int) (
                $member['id']
                ?? 0
            )
        );

        $profileReference = trim(
            (string) (
                $member['profile_ref_number']
                ?? ''
            )
        );

        /*
         * A valid member and public profile reference are mandatory
         * for any member-facing presentation.
         */
        if (
            $viewerUserId <= 0
            || $memberId <= 0
            || $profileReference === ''
        ) {
            return null;
        }

        /*
         * MemberPhotoUrlService remains the single authority for:
         *
         * - approval;
         * - photo visibility;
         * - Interest-based photo visibility;
         * - signed media URLs.
         */
        $image =
            $this->photoUrlService
            ->getApprovedPrimaryUrlForViewer(
                memberId: $memberId,

                viewerUserId: $viewerUserId,

                hasInterestRelationship: $hasInterestRelationship,

                variant: 'thumbnail'
            );

        /*
         * Do not reveal why an actual photograph cannot be shown.
         *
         * The same gender-based placeholder is used whether the member
         * has no photo, an unapproved photo or a restricted photo.
         */
        if ($image === '') {
            helper(
                'member_profile'
            );

            $image =
                member_profile_placeholder(
                    $member['gender']
                        ?? null
                );
        }

        /*
         * Ordinary members may not see another female member's
         * complete first name.
         *
         * The equality check preserves the full name if this shared
         * presentation service is ever used for the member's own card.
         *
         * A future paid entitlement can be added here by resolving
         * $canViewFullName before calling MemberNameVisibility.
         */
        $canViewFullName =
            $viewerUserId === $memberId;

        $name =
            MemberNameVisibility::forDisplay(
                fullName: $member['full_name']
                    ?? '',

                gender: $member['gender']
                    ?? '',

                canViewFullName: $canViewFullName
            );

        $country =
            trim(
                (string) (
                    $member['country_name']
                    ?? ''
                )
            );

        $city =
            trim(
                (string) (
                    $member['city_name']
                    ?? ''
                )
            );

        $locationParts =
            array_values(
                array_filter(
                    [
                        $country,
                        $city,
                    ],
                    static fn(
                        string $part
                    ): bool =>
                    $part !== ''
                )
            );

        $education = trim(
            (string) (
                $member['education_name']
                ?? ''
            )
        );

        $occupation = trim(
            (string) (
                $member['occupation_name']
                ?? ''
            )
        );

        $employedIn = $this->employmentLabel(
            $member['employed_in']
                ?? null
        );

        $professionalSummary =
            $this->professionalSummary(
                education: $education,

                occupation: $occupation,

                employedIn: $employedIn
            );

        return [
            'referenceId' =>
            $profileReference,

            'name' =>
            $name,

            'age' =>
            $this->age(
                $member['date_of_birth']
                    ?? null
            ),

            'height' =>
            trim(
                (string) (
                    $member['height_name']
                    ?? ''
                )
            ),


            'country' =>
            $country,

            'city' =>
            $city,

            'state' =>
            trim(
                (string) (
                    $member['state_name']
                    ?? ''
                )
            ),

            /*
            * Shared presentation value used by Thumbnail, Search Card
            * and Interest Card.
            *
            * Examples:
            * India, Kota
            * Canada, Toronto
            *
            * If either value is unavailable, the available value is shown
            * without leaving an extra comma.
            */
            'location' =>
            implode(
                ', ',
                $locationParts
            ),

            'maritalStatus' =>
            trim(
                (string) (
                    $member['marital_status_name']
                    ?? ''
                )
            ),

            /*
            * Temporary backend-supplied account type.
            *
            * Replace this value with the member's resolved subscription entitlement
            * when the subscription module becomes authoritative.
            */
            'accountType' =>
            self::DEFAULT_ACCOUNT_TYPE,

            /*
            * Verification values are normalized in the backend so views never need
            * to interpret PostgreSQL boolean representations.
            */
            'verification' => [
                'mobile' =>
                BooleanValue::fromDatabase(
                    $member['is_mobile_verified']
                        ?? false
                ),

                'email' =>
                BooleanValue::fromDatabase(
                    $member['is_email_verified']
                        ?? false
                ),

                'aadhaar' =>
                BooleanValue::fromDatabase(
                    $member['is_aadhaar_verified']
                        ?? false
                ),

                'selfie' =>
                BooleanValue::fromDatabase(
                    $member['is_selfie_verified']
                        ?? false
                ),
            ],

            'image' =>
            $image,

            /*
             * Numeric member IDs are deliberately not exposed in the URL.
             */
            'profileUrl' =>
            route_to(
                'web.members.view',
                $profileReference
            ),

            /*
            * Education, occupation and employment are formatted once so ProfileCard
            * and ProfileInterestCard always follow the same display rules.
            */
            'professionalSummary' =>
            $professionalSummary,
        ];
    }

    /**
     * Convert the stored employment code into its existing member-facing label.
     *
     * Unknown values fail closed instead of exposing an internal code.
     */
    private function employmentLabel(
        mixed $employedIn
    ): string {
        $value = mb_strtoupper(
            trim(
                (string) $employedIn
            )
        );

        return match ($value) {
            'GOVERNMENT_PSU' =>
            'Government / PSU',

            'PRIVATE' =>
            'Private Sector',

            'BUSINESS' =>
            'Business',

            'DEFENSE' =>
            'Defense',

            'SELF_EMPLOYED' =>
            'Self Employed',

            'NOT_WORKING' =>
            'Not Working',

            default =>
            '',
        };
    }

    /**
     * Build a compact professional summary from all available values.
     *
     * Empty values are removed before joining, preventing leading, trailing
     * or repeated separators.
     */
    private function professionalSummary(
        string $education,
        string $occupation,
        string $employedIn
    ): string {
        $parts = array_values(
            array_filter(
                [
                    trim(
                        $education
                    ),

                    trim(
                        $occupation
                    ),

                    trim(
                        $employedIn
                    ),
                ],
                static fn(
                    string $part
                ): bool =>
                $part !== ''
            )
        );

        return implode(
            ' • ',
            $parts
        );
    }

    /**
     * Resolve current age from the stored date of birth.
     *
     * Invalid and future dates fail closed to null.
     */
    private function age(
        mixed $dateOfBirth
    ): ?int {
        $value =
            trim(
                (string) $dateOfBirth
            );

        if ($value === '') {
            return null;
        }

        try {
            $birthDate =
                DateTimeImmutable
                ::createFromFormat(
                    '!Y-m-d',
                    mb_substr(
                        $value,
                        0,
                        10
                    )
                );

            if (
                !$birthDate
                    instanceof DateTimeImmutable
            ) {
                return null;
            }

            $today =
                new DateTimeImmutable(
                    'today'
                );

            if ($birthDate > $today) {
                return null;
            }

            return $birthDate
                ->diff(
                    $today
                )
                ->y;
        } catch (Throwable) {
            return null;
        }
    }
}
