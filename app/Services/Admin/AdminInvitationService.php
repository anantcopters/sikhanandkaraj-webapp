<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AdminInvitationModel;
use App\Models\AdminUserModel;
use App\Services\Email\AdminEmailService;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

final class AdminInvitationService
{
    private const INVITATION_LIFETIME_HOURS = 24;

    public function __construct(
        private readonly AdminUserModel $adminUserModel,
        private readonly AdminInvitationModel $invitationModel,
        private readonly AdminEmailService $adminEmailService,
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

            /** @var \App\Services\Admin\Audit\AdminAuditService $audit */
            $audit = service('adminAuditService');

            $audit->record(
                new \App\Services\Admin\Audit\AdminAuditEvent(
                    action: \App\Services\Admin\Audit\AdminAuditAction::ADMIN_CREATED,

                    targetType: 'ADMIN_USER',

                    targetId: (int) $adminId,

                    targetLabel: $emailAddress,

                    description: 'Administrator account was created and '
                        . 'an invitation was queued.',

                    afterData: [
                        'full_name' => $fullName,
                        'mobile_number' => $mobileNumber,
                        'email_address' => $emailAddress,
                        'role' =>
                        AdminUserModel::ROLE_ADMIN,
                        'account_status' =>
                        AdminUserModel::STATUS_PENDING,
                        'is_mobile_verified' => true,
                        'is_email_verified' => false,
                    ]
                )
            );

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

            /** @var \App\Services\Admin\Audit\AdminAuditService $audit */
            $audit = service('adminAuditService');

            $audit->record(
                new \App\Services\Admin\Audit\AdminAuditEvent(
                    action: \App\Services\Admin\Audit\AdminAuditAction::INVITATION_RESENT,

                    targetType: 'ADMIN_USER',

                    targetId: $adminUserId,

                    targetLabel: (string) $admin['email_address'],

                    description: 'A replacement administrator invitation was queued.'
                )
            );
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

    /**
     * Accept a one-time administrator invitation.
     *
     * The invitation row is locked inside the transaction so only one request
     * can consume the token. A concurrent second request waits for the first
     * transaction and then finds used_at populated.
     */
    public function acceptInvitation(
        string $rawToken,
        string $password
    ): void {
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

        $tokenHash = hash(
            'sha256',
            $rawToken
        );

        /*
     * Hashing may happen before the transaction because it does not modify
     * state and avoids doing unnecessary work while holding a row lock.
     */
        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        if (!is_string($passwordHash)) {
            throw new RuntimeException(
                'Password could not be secured.'
            );
        }

        $adminUserId = 0;
        $admin = [];

        $this->database->transBegin();

        try {
            /*
         * Lock the invitation row until this transaction commits or rolls
         * back. PostgreSQL blocks any concurrent acceptance of this token.
         */
            $query = $this->database->query(
                <<<'SQL'
                SELECT
                    id,
                    admin_user_id,
                    token_hash,
                    expires_at,
                    used_at,
                    revoked_at,
                    created_by,
                    created_at,
                    updated_at
                FROM admin_invitations
                WHERE token_hash = ?
                FOR UPDATE
            SQL,
                [
                    $tokenHash,
                ]
            );

            $invitation = $query->getRowArray();

            if (!is_array($invitation)) {
                throw new RuntimeException(
                    'This invitation is invalid or has expired.'
                );
            }

            /*
         * These checks must occur after the row lock is acquired.
         */
            if (
                $invitation['used_at'] !== null
                || $invitation['revoked_at'] !== null
            ) {
                throw new RuntimeException(
                    'This invitation is invalid or has expired.'
                );
            }

            $expiresAt = strtotime(
                (string) $invitation['expires_at']
            );

            if (
                $expiresAt === false
                || $expiresAt < time()
            ) {
                throw new RuntimeException(
                    'This invitation is invalid or has expired.'
                );
            }

            $adminUserId = (int) (
                $invitation['admin_user_id']
                ?? 0
            );

            /*
         * Lock the administrator as well so another process cannot suspend,
         * delete or activate the account while this request is completing.
         */
            $adminQuery = $this->database->query(
                <<<'SQL'
                SELECT
                    id,
                    full_name,
                    mobile_number,
                    email_address,
                    password_hash,
                    role,
                    account_status,
                    is_mobile_verified,
                    mobile_verified_at,
                    is_email_verified,
                    email_verified_at,
                    password_set_at,
                    last_login_at,
                    created_by,
                    created_at,
                    updated_at,
                    deleted_at
                FROM admin_users
                WHERE id = ?
                  AND deleted_at IS NULL
                FOR UPDATE
            SQL,
                [
                    $adminUserId,
                ]
            );

            $admin = $adminQuery->getRowArray();

            if (!is_array($admin)) {
                throw new RuntimeException(
                    'This invitation is invalid or has expired.'
                );
            }

            if (
                ($admin['role'] ?? null)
                !== AdminUserModel::ROLE_ADMIN
            ) {
                throw new RuntimeException(
                    'This invitation is invalid or has expired.'
                );
            }

            if (
                ($admin['account_status'] ?? null)
                !== AdminUserModel::STATUS_PENDING
            ) {
                throw new RuntimeException(
                    'This invitation is invalid or has expired.'
                );
            }

            $now = date('Y-m-d H:i:s');

            $updatedAdmin = $this->adminUserModel->update(
                $adminUserId,
                [
                    'password_hash' => $passwordHash,
                    'account_status' =>
                    AdminUserModel::STATUS_VERIFIED,
                    'is_email_verified' => true,
                    'email_verified_at' => $now,
                    'password_set_at' => $now,
                ]
            );

            if ($updatedAdmin === false) {
                throw new RuntimeException(
                    'Administrator account could not be activated.'
                );
            }

            $updatedInvitation =
                $this->invitationModel->update(
                    (int) $invitation['id'],
                    [
                        'used_at' => $now,
                    ]
                );

            if ($updatedInvitation === false) {
                throw new RuntimeException(
                    'Administrator invitation could not be completed.'
                );
            }

            /*
         * Revoke any additional outstanding invitations for this admin.
         * The consumed invitation has used_at set, so revokeForAdmin() will
         * affect only other unused links.
         */
            $this->invitationModel->revokeForAdmin(
                $adminUserId
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

        /*
     * Audit failure must not make a successfully committed activation
     * appear unsuccessful.
     */
        try {
            /** @var \App\Services\Admin\Audit\AdminAuditService $audit */
            $audit = service('adminAuditService');

            $audit->record(
                new \App\Services\Admin\Audit\AdminAuditEvent(
                    action: \App\Services\Admin\Audit\AdminAuditAction::INVITATION_ACCEPTED,

                    actorAdminId: $adminUserId,

                    actorName: (string) (
                        $admin['full_name']
                        ?? ''
                    ),

                    actorRole: (string) (
                        $admin['role']
                        ?? ''
                    ),

                    targetType: 'ADMIN_USER',

                    targetId: $adminUserId,

                    targetLabel: (string) (
                        $admin['email_address']
                        ?? ''
                    ),

                    description: 'Administrator invitation was accepted and the account was activated.'
                )
            );
        } catch (Throwable $auditException) {
            log_message(
                'error',
                'Unable to record invitation acceptance audit: {message}',
                [
                    'message' =>
                    $auditException->getMessage(),
                ]
            );
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

        $this->adminEmailService
            ->queueInvitation(
                recipientEmail: (string) $admin['email_address'],

                adminName: (string) $admin['full_name'],

                invitationUrl: $invitationUrl,

                expiresInHours: self::INVITATION_LIFETIME_HOURS,

                invitationId: (int) $invitationId
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
