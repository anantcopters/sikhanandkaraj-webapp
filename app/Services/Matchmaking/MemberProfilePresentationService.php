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

        $name =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) (
                        $member['full_name']
                        ?? ''
                    )
                )
            )
            ?? '';

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
             * Numeric member IDs are deliberately not exposed in the URL.
             */
            'profileUrl' =>
            route_to(
                'web.members.view',
                $profileReference
            ),
        ];
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
