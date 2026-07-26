<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminMemberPhotoApprovalModel;
use App\Services\Admin\MemberPhotoApprovalService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
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

        /** @var MemberPhotoApprovalService $service */
        $service = service(
            'memberPhotoApprovalService'
        );

        foreach ($members as &$member) {
            $memberId = (int) (
                $member['member_id'] ?? 0
            );

            $member['photos'] =
                $service->getMemberPhotos(
                    $memberId
                );
        }

        unset($member);

        return view(
            'Admin/Members/PendingPhotoApproval',
            [
                'pageTitle' =>
                'Pending Member Photo Approval',

                'members' => $members,

                'pager' => $model->pager,

                'search' => $search,

                'formAlert' =>
                session('formAlert'),

                'pageScripts' => [
                    'assets/js/pages/'
                        . 'admin-member-photo-approval.js',
                ],
            ]
        );
    }

    /**
     * Approve one pending photo.
     */
    public function approvePhoto(
        int $photoId
    ): RedirectResponse {
        $adminId = $this->authenticatedAdminId();

        try {
            /** @var MemberPhotoApprovalService $service */
            $service = service(
                'memberPhotoApprovalService'
            );

            $service->approvePhoto(
                $photoId,
                $adminId
            );

            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Photo approved',
                    'message' =>
                    'The member photo has been approved.',
                ]);
        } catch (DomainException $exception) {
            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' => 'warning',
                    'title' =>
                    'Photo not approved',
                    'message' =>
                    $exception->getMessage(),
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Admin photo approval failed for '
                    . 'photo {photoId}: {message}',
                [
                    'photoId' => $photoId,
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' =>
                    'Photo not approved',
                    'message' =>
                    'The photo could not be approved. '
                        . 'Please try again.',
                ]);
        }
    }

    /**
     * Reject one pending photo.
     */
    public function rejectPhoto(
        int $photoId
    ): RedirectResponse {
        $adminId = $this->authenticatedAdminId();

        $reason = trim(
            (string) $this->request
                ->getPost('rejection_reason')
        );

        try {
            /** @var MemberPhotoApprovalService $service */
            $service = service(
                'memberPhotoApprovalService'
            );

            $service->rejectPhoto(
                $photoId,
                $adminId,
                $reason
            );

            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Photo rejected',
                    'message' =>
                    'The member photo has been rejected.',
                ]);
        } catch (DomainException $exception) {
            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' => 'warning',
                    'title' =>
                    'Photo not rejected',
                    'message' =>
                    $exception->getMessage(),
                ]);
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

            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' =>
                    'Photo not rejected',
                    'message' =>
                    'The photo could not be rejected. '
                        . 'Please try again.',
                ]);
        }
    }

    /**
     * Quickly approve all pending photos for one member.
     */
    public function approveMemberPhotos(
        int $memberId
    ): RedirectResponse {
        $adminId = $this->authenticatedAdminId();

        try {
            /** @var MemberPhotoApprovalService $service */
            $service = service(
                'memberPhotoApprovalService'
            );

            $approvedCount =
                $service->approvePendingForMember(
                    $memberId,
                    $adminId
                );

            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' => 'success',
                    'title' =>
                    'Member photos approved',
                    'message' =>
                    $approvedCount
                        . ' pending photo'
                        . ($approvedCount === 1
                            ? ''
                            : 's')
                        . ' approved.',
                ]);
        } catch (DomainException $exception) {
            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' => 'warning',
                    'title' =>
                    'Photos not approved',
                    'message' =>
                    $exception->getMessage(),
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Quick member photo approval failed for '
                    . 'member {memberId}: {message}',
                [
                    'memberId' => $memberId,
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' =>
                    'Photos not approved',
                    'message' =>
                    'The member photos could not be '
                        . 'approved. Please try again.',
                ]);
        }
    }

    /**
     * Return the authenticated administrator ID.
     */
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
