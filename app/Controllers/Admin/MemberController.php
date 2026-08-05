<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MemberAccountStatusHistoryModel;
use App\Services\Admin\MemberManagementService;
use App\Services\Profile\MemberPhotoUrlService;
use App\Validation\Admin\MemberAccountStatusValidation;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/**
 * Administrator member listing, profile review and account management.
 */
final class MemberController extends BaseController
{
    /**
     * Number of members displayed on each list page.
     */
    private const MEMBERS_PER_PAGE = 10;

    /**
     * Display the searchable, filterable and paginated member listing.
     */
    public function index(): string
    {
        $status = mb_strtoupper(
            trim(
                (string) $this->request
                    ->getGet('status')
            )
        );

        $allowedStatuses = [
            'ALL',
            'PENDING',
            'ACTIVE',
            'SUSPENDED',
            'DELETED',
        ];

        if (
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {
            $status = 'ALL';
        }

        $search = preg_replace(
            '/\s+/u',
            ' ',
            trim(
                (string) $this->request
                    ->getGet('search')
            )
        ) ?? '';

        $search = mb_substr(
            $search,
            0,
            100
        );

        /** @var MemberManagementService $service */
        $service = service(
            'memberManagementService'
        );

        $result = $service
            ->paginatedMembers(
                $status,
                $search,
                self::MEMBERS_PER_PAGE
            );

        return view(
            'Admin/Members/Index',
            [
                'pageTitle' =>
                'Members',

                'members' =>
                $result['members'],

                'pager' =>
                $result['pager'],

                'selectedStatus' =>
                $result['status'],

                'searchTerm' =>
                $result['search'],

                'perPage' =>
                $result['perPage'],

                'validationErrors' =>
                session(
                    'validationErrors'
                ) ?? [],

                'statusModal' =>
                session('statusModal'),

                'formAlert' =>
                session('formAlert'),

                'pageScripts' => [
                    'assets/js/pages/admin-members.js',
                ],
            ]
        );
    }

    /**
     * Display one member profile for administrator review.
     */
    public function view(
        int $userId
    ): string {
        try {
            /** @var MemberManagementService $service */
            $service = service(
                'memberManagementService'
            );

            $profile = $service
                ->profilePreview(
                    $userId
                );

            return view(
                'Admin/Members/View',
                array_merge(
                    [
                        'pageTitle' =>
                        'Member Profile',

                        'memberId' =>
                        $userId,

                        'validationErrors' =>
                        session(
                            'validationErrors'
                        ) ?? [],

                        'statusModal' =>
                        session('statusModal'),

                        'formAlert' =>
                        session('formAlert'),

                        /*
                         * This script attaches History, Block/Unblock and
                         * photo-preview modal handlers on the profile page.
                         */
                        'pageScripts' => [
                            'assets/js/pages/admin-member-view.js',
                        ],
                    ],
                    $profile
                )
            );
        } catch (
            PageNotFoundException $exception
        ) {
            throw $exception;
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Unable to display member {userId} '
                    . 'for administrator: {message}',
                [
                    'userId' =>
                    $userId,

                    'message' =>
                    $exception->getMessage(),
                ]
            );

            throw PageNotFoundException
                ::forPageNotFound();
        }
    }

    /**
     * Block an active member.
     */
    public function block(
        int $userId
    ): RedirectResponse {
        return $this->changeStatus(
            $userId,
            MemberAccountStatusHistoryModel
            ::ACTION_BLOCK
        );
    }

    /**
     * Unblock a suspended member.
     */
    public function unblock(
        int $userId
    ): RedirectResponse {
        return $this->changeStatus(
            $userId,
            MemberAccountStatusHistoryModel
            ::ACTION_UNBLOCK
        );
    }

    /**
     * Return member block/unblock history as JSON.
     */
    public function history(
        int $userId
    ): ResponseInterface {
        try {
            /** @var MemberManagementService $service */
            $service = service(
                'memberManagementService'
            );

            $result = $service->history(
                $userId
            );

            $history = array_map(
                static function (
                    array $row
                ): array {
                    return [
                        'action' =>
                        (string) (
                            $row['action']
                            ?? ''
                        ),

                        'previousStatus' =>
                        (string) (
                            $row['previous_status']
                            ?? ''
                        ),

                        'newStatus' =>
                        (string) (
                            $row['new_status']
                            ?? ''
                        ),

                        'reason' =>
                        (string) (
                            $row['reason']
                            ?? ''
                        ),

                        'adminName' =>
                        (string) (
                            $row['admin_name']
                            ?? 'Administrator'
                        ),

                        'adminRole' =>
                        (string) (
                            $row['admin_role']
                            ?? ''
                        ),

                        'changedAt' =>
                        (string) (
                            $row['changed_at']
                            ?? ''
                        ),
                    ];
                },
                $result['history']
            );

            return $this->response
                ->setHeader(
                    'Cache-Control',
                    'private, no-store, '
                        . 'no-cache, must-revalidate, '
                        . 'max-age=0'
                )
                ->setJSON([
                    'successful' =>
                    true,

                    'member' => [
                        'name' =>
                        (string) (
                            $result['member']['full_name']
                            ?? ''
                        ),

                        'reference' =>
                        (string) (
                            $result['member']['profile_ref_number']
                            ?? ''
                        ),
                    ],

                    'history' =>
                    $history,
                ]);
        } catch (
            PageNotFoundException) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'successful' =>
                    false,

                    'message' =>
                    'The member was not found.',
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Unable to load member status history. '
                    . 'Member: {userId}; '
                    . 'message: {message}.',
                [
                    'userId' =>
                    $userId,

                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'successful' =>
                    false,

                    'message' =>
                    'The member history could not be loaded.',
                ]);
        }
    }

    /**
     * Return original and medium signed URLs for one retained member photo.
     *
     * Unlike the member-facing endpoint, the administrator endpoint may read
     * an approved, pending or rejected photo. Deleted photos remain excluded.
     */
    public function photoModalUrls(
        int $userId,
        int $photoId
    ): ResponseInterface {
        if (
            $userId <= 0
            || $photoId <= 0
        ) {
            return $this->photoNotFoundResponse();
        }

        try {
            /** @var MemberManagementService $service */
            $service = service(
                'memberManagementService'
            );

            $urls = $service
                ->adminPhotoModalUrls(
                    $userId,
                    $photoId
                );

            return $this->response
                ->setHeader(
                    'Cache-Control',
                    'private, no-store, '
                        . 'no-cache, must-revalidate, '
                        . 'max-age=0'
                )
                ->setHeader(
                    'Pragma',
                    'no-cache'
                )
                ->setJSON([
                    'successful' =>
                    true,

                    'photoId' =>
                    $urls['photoId'],

                    'originalUrl' =>
                    $urls['originalUrl'],

                    'mediumUrl' =>
                    $urls['mediumUrl'],
                ]);
        } catch (
            PageNotFoundException
            | DomainException) {
            return $this->photoNotFoundResponse();
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Administrator photo URL generation failed. '
                    . 'Member: {userId}; '
                    . 'photo: {photoId}; '
                    . 'message: {message}.',
                [
                    'userId' =>
                    $userId,

                    'photoId' =>
                    $photoId,

                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'successful' =>
                    false,

                    'message' =>
                    'The photograph could not be loaded.',
                ]);
        }
    }

    /**
     * Validate and perform a block or unblock action.
     */
    private function changeStatus(
        int $userId,
        string $action
    ): RedirectResponse {
        $input = [
            'reason' =>
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) $this->request
                        ->getPost('reason')
                )
            ) ?? '',
        ];

        $validation = service(
            'validation'
        );

        $validation->setRules(
            MemberAccountStatusValidation
                ::reasonRules()
        );

        $returnUrl = trim(
            (string) $this->request
                ->getPost('return_url')
        );

        if (
            $returnUrl === ''
            || !str_starts_with(
                $returnUrl,
                site_url('admin/members')
            )
        ) {
            $returnUrl = route_to(
                'admin.members.index'
            );
        }

        if (!$validation->run($input)) {
            return redirect()
                ->to($returnUrl)
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                )
                ->with(
                    'statusModal',
                    [
                        'userId' =>
                        $userId,

                        'action' =>
                        $action,

                        'reason' =>
                        $input['reason'],
                    ]
                );
        }

        try {
            /** @var MemberManagementService $service */
            $service = service(
                'memberManagementService'
            );

            if (
                $action
                === MemberAccountStatusHistoryModel
                ::ACTION_BLOCK
            ) {
                $service->block(
                    $userId,
                    $validation
                        ->getValidated()['reason'],
                    $this->adminUserId()
                );

                $title = 'Member blocked';
                $message =
                    'The member account has been blocked.';
            } else {
                $service->unblock(
                    $userId,
                    $validation
                        ->getValidated()['reason'],
                    $this->adminUserId()
                );

                $title = 'Member unblocked';
                $message =
                    'The member account is active again.';
            }

            return redirect()
                ->to($returnUrl)
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        $title,

                        'message' =>
                        $message,
                    ]
                );
        } catch (
            DomainException $exception
        ) {
            return redirect()
                ->to($returnUrl)
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Status not changed',

                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Member status update failed. '
                    . 'Member: {userId}; '
                    . 'action: {action}; '
                    . 'message: {message}.',
                [
                    'userId' =>
                    $userId,

                    'action' =>
                    $action,

                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->to($returnUrl)
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Status not changed',

                        'message' =>
                        'The member status could not be changed.',
                    ]
                );
        }
    }

    /**
     * Resolve the authenticated administrator identifier.
     */
    private function adminUserId(): int
    {
        $adminUserId = session(
            'admin_user_id'
        );

        if (!is_numeric($adminUserId)) {
            session()->destroy();

            throw PageNotFoundException
                ::forPageNotFound();
        }

        return (int) $adminUserId;
    }

    /**
     * Return one generic unavailable-photo response.
     */
    private function photoNotFoundResponse(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(404)
            ->setJSON([
                'successful' =>
                false,

                'message' =>
                'The requested photograph is unavailable.',
            ]);
    }
}
