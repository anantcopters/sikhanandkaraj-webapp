<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AdminInvitationModel;
use App\Models\AdminUserModel;
use App\Services\Email\EmailQueueService;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

final class AdminInvitationService
{
    private const INVITATION_LIFETIME_HOURS = 24;

    public function __construct(
        private readonly AdminUserModel $adminUserModel,
        private readonly AdminInvitationModel $invitationModel,
        private readonly EmailQueueService $emailQueueService,
        private readonly BaseConnection $database
    ) {}

    /**
     * @return array{admin_id:int}
     */
    public function createAdmin(
        string $fullName,
        string $mobileNumber,
        string $emailAddress,
        int $createdBy
    ): array {
        $fullName = trim($fullName);

        $mobileNumber = $this->normalizeMobile(
            $mobileNumber
        );

        $emailAddress = mb_strtolower(
            trim($emailAddress)
        );

        $this->assertUniqueContacts(
            $mobileNumber,
            $emailAddress
        );

        $this->database->transBegin();

        try {
            $adminId = $this->adminUserModel->insert([
                'full_name' => $fullName,
                'mobile_number' => $mobileNumber,
                'email_address' => $emailAddress,
                'password_hash' => null,
                'role' => AdminUserModel::ROLE_ADMIN,
                'account_status' =>
                AdminUserModel::STATUS_PENDING,
                'is_mobile_verified' => true,
                'mobile_verified_at' =>
                date('Y-m-d H:i:s'),
                'is_email_verified' => false,
                'created_by' => $createdBy,
            ], true);

            if (!is_numeric($adminId)) {
                throw new RuntimeException(
                    'Administrator could not be created.'
                );
            }

            $this->queueInvitation(
                adminUserId: (int) $adminId,
                createdBy: $createdBy
            );

            if (!$this->database->transStatus()) {
                throw new RuntimeException(
                    'Administrator invitation could not be saved.'
                );
            }

            $this->database->transCommit();

            return [
                'admin_id' => (int) $adminId,
            ];
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw new RuntimeException(
                'Administrator could not be invited.',
                0,
                $exception
            );
        }
    }

    public function resendInvitation(
        int $adminUserId,
        int $createdBy
    ): void {
        $admin = $this->adminUserModel
            ->find($adminUserId);

        if (!is_array($admin)) {
            throw new RuntimeException(
                'Administrator could not be found.'
            );
        }

        if (
            ($admin['role'] ?? null)
            !== AdminUserModel::ROLE_ADMIN
        ) {
            throw new RuntimeException(
                'Only administrator invitations may be resent.'
            );
        }

        if (
            ($admin['account_status'] ?? null)
            !== AdminUserModel::STATUS_PENDING
        ) {
            throw new RuntimeException(
                'Only unverified administrators can receive another invitation.'
            );
        }

        $this->database->transBegin();

        try {
            $this->invitationModel
                ->revokeForAdmin($adminUserId);

            $this->queueInvitation(
                adminUserId: $adminUserId,
                createdBy: $createdBy
            );

            if (!$this->database->transStatus()) {
                throw new RuntimeException(
                    'Invitation could not be renewed.'
                );
            }

            $this->database->transCommit();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Inspect an invitation without consuming it.
     *
     * @return array{
     *     invitation:array<string,mixed>,
     *     admin:array<string,mixed>
     * }
     */
    public function inspectToken(
        string $rawToken
    ): array {
        if (
            preg_match(
                '/^[a-f0-9]{64}$/',
                $rawToken
            ) !== 1
        ) {
            throw new RuntimeException(
                'This invitation is invalid or has expired.'
            );
        }

        $invitation = $this->invitationModel
            ->findUsableInvitation(
                hash('sha256', $rawToken)
            );

        if (!is_array($invitation)) {
            throw new RuntimeException(
                'This invitation is invalid or has expired.'
            );
        }

        $admin = $this->adminUserModel->find(
            (int) $invitation['admin_user_id']
        );

        if (
            !is_array($admin)
            || ($admin['account_status'] ?? null)
            !== AdminUserModel::STATUS_PENDING
        ) {
            throw new RuntimeException(
                'This invitation is invalid or has expired.'
            );
        }

        return [
            'invitation' => $invitation,
            'admin' => $admin,
        ];
    }

    public function acceptInvitation(
        string $rawToken,
        string $password
    ): void {
        $resolved = $this->inspectToken(
            $rawToken
        );

        $invitation = $resolved['invitation'];
        $admin = $resolved['admin'];

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        if (!is_string($passwordHash)) {
            throw new RuntimeException(
                'Password could not be secured.'
            );
        }

        $now = date('Y-m-d H:i:s');

        $this->database->transBegin();

        try {
            $updated = $this->adminUserModel->update(
                (int) $admin['id'],
                [
                    'password_hash' => $passwordHash,
                    'account_status' =>
                    AdminUserModel::STATUS_VERIFIED,
                    'is_email_verified' => true,
                    'email_verified_at' => $now,
                    'password_set_at' => $now,
                ]
            );

            if ($updated === false) {
                throw new RuntimeException(
                    'Administrator account could not be activated.'
                );
            }

            $this->invitationModel->update(
                (int) $invitation['id'],
                [
                    'used_at' => $now,
                ]
            );

            /*
             * Revoke any other outstanding links.
             */
            $this->invitationModel->revokeForAdmin(
                (int) $admin['id']
            );

            if (!$this->database->transStatus()) {
                throw new RuntimeException(
                    'Administrator account could not be activated.'
                );
            }

            $this->database->transCommit();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    private function queueInvitation(
        int $adminUserId,
        int $createdBy
    ): void {
        $admin = $this->adminUserModel
            ->find($adminUserId);

        if (!is_array($admin)) {
            throw new RuntimeException(
                'Administrator could not be found.'
            );
        }

        $rawToken = bin2hex(random_bytes(32));

        $expiresAt = date(
            'Y-m-d H:i:s',
            strtotime(
                '+'
                    . self::INVITATION_LIFETIME_HOURS
                    . ' hours'
            )
        );

        $invitationId = $this->invitationModel
            ->insert([
                'admin_user_id' => $adminUserId,
                'token_hash' =>
                hash('sha256', $rawToken),
                'expires_at' => $expiresAt,
                'created_by' => $createdBy,
            ], true);

        if (!is_numeric($invitationId)) {
            throw new RuntimeException(
                'Administrator invitation could not be created.'
            );
        }

        $invitationUrl = url_to(
            'admin.invitation.show',
            $rawToken
        );

        $this->emailQueueService->enqueue(
            recipientEmail: (string) $admin['email_address'],
            recipientName: (string) $admin['full_name'],
            subject: 'Complete your Sikh Anand Karaj administrator account',
            viewName: 'Emails/Admin/AdminInvitation',
            viewData: [
                'adminName' =>
                (string) $admin['full_name'],
                'invitationUrl' => $invitationUrl,
                'expiresInHours' =>
                self::INVITATION_LIFETIME_HOURS,
            ],
            priority: 5,
            maxAttempts: 3,
            referenceType: 'ADMIN_INVITATION',
            referenceId: (int) $invitationId
        );
    }

    private function assertUniqueContacts(
        string $mobileNumber,
        string $emailAddress
    ): void {
        if (
            $this->adminUserModel
            ->where(
                'mobile_number',
                $mobileNumber
            )
            ->countAllResults() > 0
        ) {
            throw new RuntimeException(
                'An administrator already uses this mobile number.'
            );
        }

        if (
            $this->adminUserModel
            ->where(
                'email_address',
                $emailAddress
            )
            ->countAllResults() > 0
        ) {
            throw new RuntimeException(
                'An administrator already uses this email address.'
            );
        }
    }

    private function normalizeMobile(
        string $mobileNumber
    ): string {
        $digits = preg_replace(
            '/\D+/',
            '',
            $mobileNumber
        ) ?? '';

        if (
            preg_match(
                '/^[6-9][0-9]{9}$/',
                $digits
            ) !== 1
        ) {
            throw new RuntimeException(
                'Enter a valid Indian mobile number.'
            );
        }

        return '+91' . $digits;
    }
}
