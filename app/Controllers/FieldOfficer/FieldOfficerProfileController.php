<?php

declare(strict_types=1);

namespace App\Controllers\FieldOfficer;

use App\Controllers\BaseController;
use App\Services\FieldOfficer\FieldOfficerProfileService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;

final class FieldOfficerProfileController
extends BaseController
{
    private const PER_PAGE = 10;

    /**
     * Display profiles connected with the currently authenticated
     * SAK Volunteer.
     */
    public function index(): string
    {
        $status =
            strtoupper(
                trim(
                    (string) $this->request
                        ->getGet(
                            'status'
                        )
                )
            );

        $search =
            trim(
                (string) $this->request
                    ->getGet(
                        'search'
                    )
            );

        /** @var FieldOfficerProfileService $service */
        $service =
            service(
                'fieldOfficerProfileService'
            );

        $fieldOfficerId =
            $this->fieldOfficerId();

        $result =
            $service
            ->paginatedProfiles(
                $fieldOfficerId,
                $status,
                $search,
                self::PER_PAGE
            );

        return view(
            'FieldOfficer/Profiles/Index',
            [
                'pageTitle' =>
                'Profiles Submitted',

                'profiles' =>
                $result['profiles'],

                'pager' =>
                $result['pager'],

                'selectedStatus' =>
                $result['status'],

                'searchTerm' =>
                $result['search'],

                'fieldOfficerName' =>
                trim(
                    (string) session(
                        'fo_field_officer_name'
                    )
                ),

                /*
             * Use the existing service instead of calculating the number
             * from the current page of results.
             */
                'totalProfiles' =>
                $service
                    ->totalProfiles(
                        $fieldOfficerId
                    ),

                'formAlert' =>
                $this->readFormAlert(),

                /*
             * Explicit context keeps the shared view readable.
             */
                'pageLayout' =>
                'FieldOfficer/Layouts/Main',

                'profilesUrl' =>
                route_to(
                    'field-officer.profiles.index'
                ),

                'backUrl' =>
                '',

                'isAdminView' =>
                false,
            ]
        );
    }

    public function prelaunch(
        int $profileId
    ): string {
        /** @var FieldOfficerProfileService $service */
        $service = service(
            'fieldOfficerProfileService'
        );

        $fieldOfficerId =
            $this->fieldOfficerId();

        $memberId =
            $service->migratedMemberId(
                $fieldOfficerId,
                $profileId
            );

        /*
         * Once migrated, show the normal member profile.
         */
        if ($memberId !== null) {
            return view(
                'Pages/Profile/View',
                array_merge(
                    [
                        'pageTitle' =>
                        'Member Profile',
                    ],
                    $service
                        ->memberPreview(
                            $fieldOfficerId,
                            $memberId
                        )
                )
            );
        }

        return view(
            'FieldOfficer/Profiles/PrelaunchView',
            [
                'pageTitle' =>
                'Prelaunch Profile',

                'profile' =>
                $service
                    ->prelaunchProfile(
                        $fieldOfficerId,
                        $profileId
                    ),

                'photos' =>
                $service
                    ->prelaunchPhotos(
                        $fieldOfficerId,
                        $profileId
                    ),
            ]
        );
    }

    public function member(
        int $memberId
    ): string {
        /** @var FieldOfficerProfileService $service */
        $service = service(
            'fieldOfficerProfileService'
        );

        return view(
            'Pages/Profile/View',
            array_merge(
                [
                    'pageTitle' =>
                    'Member Profile',
                ],
                $service
                    ->memberPreview(
                        $this->fieldOfficerId(),
                        $memberId
                    )
            )
        );
    }

    public function memberPhoto(
        int $memberId,
        int $photoId
    ): ResponseInterface {
        try {
            /** @var FieldOfficerProfileService $service */
            $service = service(
                'fieldOfficerProfileService'
            );

            $url =
                $service
                ->memberMediumPhotoUrl(
                    $this->fieldOfficerId(),
                    $memberId,
                    $photoId
                );

            return $this->response
                ->setHeader(
                    'Cache-Control',
                    'private, no-store, no-cache, '
                        . 'must-revalidate, max-age=0'
                )
                ->setJSON([
                    'successful' =>
                    true,

                    'mediumUrl' =>
                    $url,
                ]);
        } catch (
            PageNotFoundException
            | DomainException) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'successful' =>
                    false,

                    'message' =>
                    'The photograph is unavailable.',
                ]);
        }
    }

    public function prelaunchPhoto(
        int $profileId,
        int $photoId
    ): ResponseInterface {
        /** @var FieldOfficerProfileService $service */
        $service = service(
            'fieldOfficerProfileService'
        );

        $photo =
            $service->prelaunchPhotoPath(
                $this->fieldOfficerId(),
                $profileId,
                $photoId
            );

        return $this->response
            ->setHeader(
                'Content-Type',
                $photo['mimeType']
            )
            ->setHeader(
                'Cache-Control',
                'private, no-store, max-age=0'
            )
            ->setBody(
                file_get_contents(
                    $photo['path']
                ) ?: ''
            );
    }

    private function fieldOfficerId(): int
    {
        $id = session(
            'fo_field_officer_id'
        );

        if (!is_numeric($id)) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        return (int) $id;
    }
}
