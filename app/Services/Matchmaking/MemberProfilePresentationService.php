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
     *     accountCode:string,
     *     isPaidAccount:bool,
     *     commercialPriority:int,
     *     professionalSummary:string,
     *     verification:array{
     *         mobile:bool,
     *         email:bool,
     *         aadhaar:bool,
     *         videoIntroduction:bool,
     *         nearestGurudwara:bool
     *     },
     *     image:string,
     *     profileUrl:string
     * }|null
     */
    public function summary(
        int $viewerUserId,
        array $member,
        bool $hasInterestRelationship,
        ?string $resolvedImage = null
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
         * Collection consumers may preload primary-photo state in one database
         * query and supply the already-authorized signed URL here.
         *
         * null means:
         *     no batch resolution was supplied -> use existing single-profile flow.
         *
         * empty string means:
         *     batch resolution was supplied but the actual photo is unavailable
         *     or not visible -> use the normal gender placeholder.
         *
         * This distinction lets existing callers remain completely unchanged.
         */
        $image =
            $resolvedImage;

        if ($image === null) {
            $image =
                $this->photoUrlService
                ->getApprovedPrimaryUrlForViewer(
                    memberId: $memberId,
                    viewerUserId: $viewerUserId,
                    hasInterestRelationship: $hasInterestRelationship,
                    variant: 'thumbnail'
                );
        }

        /*
         * Do not reveal why an actual photograph cannot be shown.
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

        /*
        * Candidate membership was resolved by MemberMatchCandidateModel as part of
        * the candidate query.
        *
        * Do NOT call MembershipService here.
        *
        * summary() is executed once per card, so resolving membership from this
        * service would recreate the N+1 query problem which the candidate
        * membership projection is designed to prevent.
        */
        $membershipPlanCode =
            mb_strtoupper(
                trim(
                    (string) (
                        $member['membership_plan_code']
                        ?? ''
                    )
                )
            );

        /*
        * Only known paid plan codes are allowed to produce a paid account label.
        *
        * The candidate projection may contain NULL for Free members. An unexpected
        * or corrupt snapshot also fails closed to Free Account rather than granting
        * paid-looking presentation state.
        */
        $isPaidAccount =
            in_array(
                $membershipPlanCode,
                [
                    'GO',
                    'PLUS',
                    'PRO',
                ],
                true
            );

        $accountType =
            $isPaidAccount
            ? trim(
                (string) (
                    $member['membership_plan_name']
                    ?? ''
                )
            )
            : '';

        /*
        * Historical plan-name snapshots should normally be populated.
        *
        * Falling back to the known plan code keeps presentation usable if an older
        * membership row has an empty name snapshot, without querying the plan master.
        */
        if (
            $isPaidAccount
            && $accountType === ''
        ) {
            $accountType =
                match ($membershipPlanCode) {
                    'GO' =>
                    'Go Account',

                    'PLUS' =>
                    'Plus Account',

                    'PRO' =>
                    'Pro Account',

                    default =>
                    'Free Account',
                };
        }

        if (!$isPaidAccount) {
            $accountType =
                'Free Account';
        }

        /*
        * Commercial priority is projected now even though card presentation does
        * not currently display it.
        *
        * The upcoming ranking phase can therefore consume the value without adding
        * another membership query or changing the candidate contract again.
        */
        $commercialPriority =
            $isPaidAccount
            ? max(
                0,
                (int) (
                    $member['membership_commercial_priority']
                    ?? 0
                )
            )
            : 0;

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
            * Account type comes from the membership snapshot projected by the candidate
            * query.
            *
            * Free remains a derived state: absence of a valid active paid membership
            * means Free Account.
            */
            'accountType' =>
            $accountType,

            /*
            * Machine-readable account state.
            *
            * Keep this separate from accountType so future card/ranking logic never
            * needs to reverse-engineer business state from display text.
            */
            'accountCode' =>
            $isPaidAccount
                ? $membershipPlanCode
                : 'FREE',

            'isPaidAccount' =>
            $isPaidAccount,

            /*
            * Used by the upcoming ranking algorithm.
            *
            * This is the commercial priority snapshot purchased with the membership,
            * not a live lookup against the current plan master.
            */
            'commercialPriority' =>
            $commercialPriority,

            /*
            * Verification/trust values are normalized in the backend so views never
            * need to interpret database values or decide whether a supplied profile
            * detail is meaningful.
            *
            * Nearest Gurudwara is a member-provided trust detail rather than an
            * administrator verification. It is shown only when Family Details
            * contains a non-blank value.
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

                'videoIntroduction' =>
                BooleanValue::fromDatabase(
                    $member['has_video_introduction']
                        ?? false
                ),

                'nearestGurudwara' =>
                trim(
                    (string) (
                        $member['nearest_gurudwara']
                        ?? ''
                    )
                ) !== '',
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
