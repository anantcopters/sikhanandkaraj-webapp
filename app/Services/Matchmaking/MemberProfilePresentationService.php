<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Services\Profile\MemberPhotoUrlService;
use DateTimeImmutable;
use Throwable;

/**
 * Builds the common member-summary presentation contract used by
 * member-facing multi-profile screens.
 *
 * The service deliberately owns presentation-data preparation rather than
 * HTML rendering.
 *
 * It centralizes:
 *
 * - public profile reference;
 * - member name;
 * - age;
 * - height;
 * - city/state;
 * - marital status;
 * - viewer-authorized thumbnail;
 * - standard gender placeholder;
 * - public Profile View URL.
 *
 * Context-specific data such as:
 *
 * - Interest status;
 * - match percentage;
 * - Search activity;
 * - action URLs;
 *
 * remains owned by the relevant domain service.
 *
 * Numeric member database IDs are never returned to the browser contract.
 */
final class MemberProfilePresentationService
{
    public function __construct(
        private readonly MemberPhotoUrlService
        $photoUrlService
    ) {}

    /**
     * Build the common profile-summary contract for one visible member.
     *
     * The supplied row must already have passed the caller's normal
     * member-visibility/eligibility authorization.
     *
     * @param array<string, mixed> $member
     *
     * @return array{
     *     referenceId:string,
     *     name:string,
     *     age:int|null,
     *     height:string,
     *     city:string,
     *     state:string,
     *     maritalStatus:string,
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
         * The component contract cannot exist without a real target member
         * and public reference.
         */
        if (
            $viewerUserId <= 0
            || $memberId <= 0
            || $profileReference === ''
        ) {
            return null;
        }

        /*
         * All multi-profile presentations request only the thumbnail variant.
         *
         * MemberPhotoUrlService remains the authority for:
         *
         * - approval;
         * - photo visibility;
         * - interest-based visibility;
         * - CloudFront signing.
         */
        $image = $this
            ->photoUrlService
            ->getApprovedPrimaryUrlForViewer(
                memberId: $memberId,
                viewerUserId: $viewerUserId,
                hasInterestRelationship: $hasInterestRelationship,
                variant: 'thumbnail'
            );

        /*
         * Do not reveal why a real image is unavailable.
         *
         * Whether the member:
         *
         * - has no photo;
         * - has an unapproved photo;
         * - has a private photo;
         *
         * remains hidden from the viewer.
         */
        if ($image === '') {
            helper(
                'member_profile'
            );

            $image = member_profile_placeholder(
                $member['gender']
                    ?? null
            );
        }

        $name = preg_replace(
            '/\s+/u',
            ' ',
            trim(
                (string) (
                    $member['full_name']
                    ?? ''
                )
            )
        ) ?? '';

        if ($name === '') {
            $name = 'Member';
        }

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

            'city' =>
            trim(
                (string) (
                    $member['city_name']
                    ?? ''
                )
            ),

            'state' =>
            trim(
                (string) (
                    $member['state_name']
                    ?? ''
                )
            ),

            'maritalStatus' =>
            trim(
                (string) (
                    $member['marital_status_name']
                    ?? ''
                )
            ),

            'image' =>
            $image,

            /*
             * Never expose the numeric User ID in member-facing URLs.
             */
            'profileUrl' =>
            route_to(
                'web.members.view',
                $profileReference
            ),
        ];
    }

    /**
     * Resolve current age from the persisted date of birth.
     *
     * Invalid/future dates fail closed to null so presentation code never
     * invents an age.
     */
    private function age(
        mixed $dateOfBirth
    ): ?int {
        $value = trim(
            (string) $dateOfBirth
        );

        if ($value === '') {
            return null;
        }

        try {
            $birthDate =
                DateTimeImmutable::createFromFormat(
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
