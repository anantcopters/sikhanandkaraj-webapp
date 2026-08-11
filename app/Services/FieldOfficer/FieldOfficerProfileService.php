<?php

declare(strict_types=1);

namespace App\Services\FieldOfficer;

use App\Models\FieldOfficerSubmittedProfileModel;
use App\Models\Prelaunch\PrelaunchPhotoModel;
use App\Models\Prelaunch\PrelaunchProfileModel;
use App\Services\Prelaunch\PrelaunchPhotoService;
use App\Services\Profile\MemberPhotoUrlService;
use App\Services\Profile\MemberProfileSummaryService;
use CodeIgniter\Exceptions\PageNotFoundException;
use DomainException;

final class FieldOfficerProfileService
{
    public function __construct(
        private readonly FieldOfficerSubmittedProfileModel
        $submittedProfileModel,

        private readonly PrelaunchProfileModel
        $prelaunchProfileModel,

        private readonly PrelaunchPhotoModel
        $prelaunchPhotoModel,

        private readonly PrelaunchPhotoService
        $prelaunchPhotoService,

        private readonly MemberProfileSummaryService
        $profileSummaryService,

        private readonly MemberPhotoUrlService
        $photoUrlService
    ) {}

    public function paginatedProfiles(
        int $fieldOfficerId,
        string $status,
        string $search,
        int $perPage = 10
    ): array {
        $status = strtoupper(
            trim($status)
        );

        if (
            !in_array(
                $status,
                [
                    'ALL',
                    'DRAFT',
                    'APPROVED',
                ],
                true
            )
        ) {
            $status = 'ALL';
        }

        $search = preg_replace(
            '/\s+/u',
            ' ',
            trim($search)
        ) ?? '';

        $search = mb_substr(
            $search,
            0,
            100
        );

        $this->submittedProfileModel
            ->prepareListing(
                $fieldOfficerId,
                $status,
                $search
            );

        $profiles =
            $this->submittedProfileModel
            ->paginate(
                max(
                    5,
                    min(
                        $perPage,
                        50
                    )
                ),
                'fieldOfficerProfiles'
            );

        return [
            'profiles' =>
            is_array($profiles)
                ? $profiles
                : [],

            'pager' =>
            $this
                ->submittedProfileModel
                ->pager,

            'status' =>
            $status,

            'search' =>
            $search,
        ];
    }

    public function totalProfiles(
        int $fieldOfficerId
    ): int {
        return $this
            ->submittedProfileModel
            ->countForOfficer(
                $fieldOfficerId
            );
    }

    /**
     * Return one prelaunch record owned by this FO.
     */
    public function prelaunchProfile(
        int $fieldOfficerId,
        int $profileId
    ): array {
        $profile =
            $this->prelaunchProfileModel
            ->findForAdmin(
                $profileId
            );

        if (
            !is_array($profile)
            || (int) (
                $profile['field_officer_id'] ?? 0
            ) !== $fieldOfficerId
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        return $profile;
    }

    /**
     * If migrated, return the normal member profile.
     */
    public function migratedMemberId(
        int $fieldOfficerId,
        int $profileId
    ): ?int {
        $profile =
            $this->prelaunchProfile(
                $fieldOfficerId,
                $profileId
            );

        $memberId = (int) (
            $profile['migrated_user_id'] ?? 0
        );

        return $memberId > 0
            ? $memberId
            : null;
    }

    public function memberPreview(
        int $fieldOfficerId,
        int $memberId
    ): array {
        if (
            !$this->submittedProfileModel
                ->memberBelongsToOfficer(
                    $memberId,
                    $fieldOfficerId
                )
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        $summary =
            $this->profileSummaryService
            ->getForUser(
                $memberId
            );

        return array_merge(
            $summary,
            [
                'approvedPhotos' =>
                $this->photoUrlService
                    ->getApprovedThumbnailPhotos(
                        $memberId
                    ),

                'profileViewMode' =>
                'field-officer',

                'fieldOfficerViewedMemberId' =>
                $memberId,

                'profileBackUrl' =>
                route_to(
                    'field-officer.profiles.index'
                ),

                'profileBackLabel' =>
                'Back to Profiles Submitted',

                'profileNoticeTitle' =>
                'Read-only profile',

                'profileNoticeMessage' =>
                'This profile is shown in read-only mode. '
                    . 'No member actions are available.',
            ]
        );
    }

    public function memberMediumPhotoUrl(
        int $fieldOfficerId,
        int $memberId,
        int $photoId
    ): string {
        if (
            !$this->submittedProfileModel
                ->memberBelongsToOfficer(
                    $memberId,
                    $fieldOfficerId
                )
        ) {
            throw new DomainException(
                'The requested photograph is unavailable.'
            );
        }

        return $this->photoUrlService
            ->getOwnedApprovedMediumUrl(
                $memberId,
                $photoId
            );
    }

    public function prelaunchPhotos(
        int $fieldOfficerId,
        int $profileId
    ): array {
        $this->prelaunchProfile(
            $fieldOfficerId,
            $profileId
        );

        return $this->prelaunchPhotoModel
            ->findByProfile(
                $profileId
            );
    }

    public function prelaunchPhotoPath(
        int $fieldOfficerId,
        int $profileId,
        int $photoId
    ): array {
        $this->prelaunchProfile(
            $fieldOfficerId,
            $profileId
        );

        $photo =
            $this->prelaunchPhotoModel
            ->find(
                $photoId
            );

        if (
            !is_array($photo)
            || (int) (
                $photo['prelaunch_profile_id'] ?? 0
            ) !== $profileId
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        $relativePath = trim(
            (string) (
                $photo['medium_path']
                ?? $photo['thumbnail_path']
                ?? ''
            )
        );

        if ($relativePath === '') {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        $absolutePath =
            $this->prelaunchPhotoService
            ->absolutePath(
                $relativePath
            );

        if (
            !is_file($absolutePath)
            || !is_readable(
                $absolutePath
            )
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        return [
            'path' =>
            $absolutePath,

            'mimeType' =>
            (string) (
                $photo['mime_type']
                ?? 'image/webp'
            ),
        ];
    }
}
