<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminMemberPhotoApprovalModel;
use App\Services\Admin\MemberPhotoApprovalService;
use App\Support\AdminErrorContext;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/**
 * Displays and processes pending member photo approvals.
 */
final class MemberPhotoApprovalController extends BaseController
{
    private const PER_PAGE = 20;

    /**
     * Show members having at least one pending photo.
     */
    public function index(): string
    {
        $search = trim(
            (string) $this->request
                ->getGet('search')
        );

        if (mb_strlen($search) > 100) {
            $search = mb_substr(
                $search,
                0,
                100
            );
        }

        $database = db_connect();

        $model = new AdminMemberPhotoApprovalModel(
            $database
        );

        $members = $model
            ->pendingMembers($search)
            ->paginate(
                self::PER_PAGE,
                'pendingPhotoMembers'
            );

        return view(
            'Admin/Members/PendingPhotoApproval',
            [
                'pageTitle' =>
                'Pending Member Photo Approval',

                'members' => $members,

                'pager' => $model->pager,

                'search' => $search,

                'formAlert' =>
                $this->readFormAlert(),

                'pageScripts' => [
                    'assets/js/pages/'
                        . 'admin-member-photo-approval.js',
                ],
            ]
        );
    }

    /**
     * Return member photos for the Bootstrap modal through AJAX.
     */
    public function memberPhotos(
        int $memberId
    ): ResponseInterface {
        try {
            /** @var MemberPhotoApprovalService $service */
            $service = service(
                'memberPhotoApprovalService'
            );

            $review = $service
                ->getMemberPhotoReview($memberId);

            return $this->response
                ->setJSON([
                    'status' => 'success',
                    'data' => $review,
                    'csrf' => [
                        'name' => csrf_token(),
                        'hash' => csrf_hash(),
                    ],
                ]);
        } catch (DomainException $exception) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status' => 'error',
                    'message' =>
                    $exception->getMessage(),
                    'csrf' => [
                        'name' => csrf_token(),
                        'hash' => csrf_hash(),
                    ],
                ]);
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'admin_member_photo_list',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'target_member_user_id' =>
                        $memberId,
                    ]
                )
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'successful' =>
                    false,

                    'message' =>
                    'Member photographs could not be loaded.',
                ]);
        }
    }

    /**
     * Approve one pending photo.
     */
    public function approvePhoto(
        int $photoId
    ): RedirectResponse|ResponseInterface {
        $adminId = $this->authenticatedAdminId();

        try {
            /** @var MemberPhotoApprovalService $service */
            $service = service(
                'memberPhotoApprovalService'
            );

            $result = $service->approvePhoto(
                $photoId,
                $adminId
            );

            return $this->actionResponse(
                true,
                'Photo approved',
                'The member photo has been approved.',
                $result
            );
        } catch (DomainException $exception) {
            return $this->actionResponse(
                false,
                'Photo not approved',
                $exception->getMessage(),
                [],
                409
            );
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'admin_member_photo_moderation',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'photo_id' =>
                        $photoId,

                        'moderation_action' =>
                        'Approved',
                    ]
                )
            );

            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Photo not updated',

                        'message' =>
                        'The photograph could not be moderated.',
                    ]
                );
        }
    }

    /**
     * Reject one pending photo.
     */
    public function rejectPhoto(
        int $photoId
    ): RedirectResponse|ResponseInterface {
        $adminId = $this->authenticatedAdminId();

        /*
         * The reason field remains supported in the service and DB,
         * although the current interface no longer asks for it.
         */
        $reason = trim(
            (string) $this->request
                ->getPost('rejection_reason')
        );

        try {
            /** @var MemberPhotoApprovalService $service */
            $service = service(
                'memberPhotoApprovalService'
            );

            $result = $service->rejectPhoto(
                $photoId,
                $adminId,
                $reason
            );

            return $this->actionResponse(
                true,
                'Photo rejected',
                'The member photo has been rejected.',
                $result
            );
        } catch (DomainException $exception) {
            return $this->actionResponse(
                false,
                'Photo not rejected',
                $exception->getMessage(),
                [],
                409
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Admin photo rejection failed for '
                    . 'photo {photoId}: {message}',
                [
                    'photoId' => $photoId,
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return $this->actionResponse(
                false,
                'Photo not rejected',
                'The photo could not be rejected. '
                    . 'Please try again.',
                [],
                500
            );
        }
    }

    /**
     * Approve all pending photos for one member.
     */
    public function approveMemberPhotos(
        int $memberId
    ): RedirectResponse|ResponseInterface {
        $adminId = $this->authenticatedAdminId();

        try {
            /** @var MemberPhotoApprovalService $service */
            $service = service(
                'memberPhotoApprovalService'
            );

            $result = $service
                ->approvePendingForMember(
                    $memberId,
                    $adminId
                );

            $approvedCount = (int) (
                $result['approvedCount'] ?? 0
            );

            return $this->actionResponse(
                true,
                'Member photos approved',
                $approvedCount
                    . ' pending photo'
                    . ($approvedCount === 1 ? '' : 's')
                    . ' approved.',
                $result
            );
        } catch (DomainException $exception) {
            return $this->actionResponse(
                false,
                'Photos not approved',
                $exception->getMessage(),
                [],
                409
            );
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'admin_member_photos_approve_all',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'target_member_user_id' =>
                        $memberId,
                    ]
                )
            );

            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Photos not approved',

                        'message' =>
                        'The member photographs could not be approved.',
                    ]
                );
        }
    }

    /**
     * Return JSON for AJAX requests or preserve normal redirect flow.
     *
     * @param array<string, mixed> $data
     */
    private function actionResponse(
        bool $successful,
        string $title,
        string $message,
        array $data = [],
        int $statusCode = 200
    ): RedirectResponse|ResponseInterface {
        if ($this->request->isAJAX()) {
            return $this->response
                ->setStatusCode($statusCode)
                ->setJSON([
                    'status' =>
                    $successful
                        ? 'success'
                        : 'error',
                    'title' => $title,
                    'message' => $message,
                    'data' => $data,
                    'csrf' => [
                        'name' => csrf_token(),
                        'hash' => csrf_hash(),
                    ],
                ]);
        }

        return redirect()
            ->back()
            ->with('formAlert', [
                'type' =>
                $successful
                    ? 'success'
                    : 'danger',
                'title' => $title,
                'message' => $message,
            ]);
    }

    private function authenticatedAdminId(): int
    {
        $adminId = session('admin_user_id');

        if (!is_numeric($adminId)) {
            session()->destroy();

            throw PageNotFoundException::forPageNotFound();
        }

        return (int) $adminId;
    }
}
