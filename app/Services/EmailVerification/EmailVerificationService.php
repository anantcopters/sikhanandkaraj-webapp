<?php

declare(strict_types=1);

namespace App\Services\EmailVerification;

use App\Models\EmailVerificationTokenModel;
use App\Models\UserContactModel;
use App\Models\UserModel;
use App\Services\Email\MailService;
use App\Support\BooleanValue;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

final class EmailVerificationService
{
    private const TOKEN_LIFETIME_HOURS = 24;

    private UserModel $userModel;

    private UserContactModel $contactModel;

    private EmailVerificationTokenModel $tokenModel;

    private MailService $mailService;

    public function __construct(
        ?UserModel $userModel = null,
        ?UserContactModel $contactModel = null,
        ?EmailVerificationTokenModel $tokenModel = null,
        ?MailService $mailService = null
    ) {
        $this->userModel =
            $userModel ?? new UserModel();

        $this->contactModel =
            $contactModel ?? new UserContactModel();

        $this->tokenModel =
            $tokenModel
            ?? new EmailVerificationTokenModel();

        $this->mailService =
            $mailService ?? new MailService();
    }

    /**
     * Send a verification email to the user's primary email.
     */
    public function sendForUser(
        int $userId
    ): VerificationResult {
        $user = $this->userModel->find($userId);

        if (!is_array($user)) {
            return VerificationResult::failure(
                'User account could not be found.'
            );
        }

        $contact = $this->contactModel
            ->findPrimaryForUser(
                $userId,
                UserContactModel::TYPE_EMAIL
            );

        if (!is_array($contact)) {
            return VerificationResult::failure(
                'No email address is associated with your account.'
            );
        }

        if (
            BooleanValue::fromDatabase(
                $contact['is_verified'] ?? false
            )
        ) {
            return VerificationResult::success(
                'Your email address is already verified.'
            );
        }

        $emailAddress = trim(
            (string) ($contact['contact_value'] ?? '')
        );

        if (
            $emailAddress === ''
            || !filter_var(
                $emailAddress,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            return VerificationResult::failure(
                'Your account does not contain a valid email address.'
            );
        }

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        $expiresAt = date(
            'Y-m-d H:i:s',
            strtotime(
                '+' . self::TOKEN_LIFETIME_HOURS . ' hours'
            )
        );

        $contactId = (int) $contact['id'];

        /*
         * Invalidate previous unused links before creating a new one.
         */
        $this->tokenModel->invalidateForContact(
            $contactId
        );

        $tokenId = $this->tokenModel->insert([
            'user_id' => $userId,
            'user_contact_id' => $contactId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ], true);

        if (!is_numeric($tokenId)) {
            throw new RuntimeException(
                'Verification token could not be created.'
            );
        }

        $verificationUrl = url_to(
            'web.email.verify',
            $rawToken
        );

        try {
            $this->mailService->sendTemplate(
                $emailAddress,
                trim(
                    (string) ($user['full_name'] ?? '')
                ),
                'Verify your Sikh Anand Karaj email',
                'Emails/Authentication/VerifyEmail',
                [
                    'userName' =>
                    trim(
                        (string) (
                            $user['full_name']
                            ?? 'Member'
                        )
                    ),

                    'verificationUrl' =>
                    $verificationUrl,

                    'expiresInHours' =>
                    self::TOKEN_LIFETIME_HOURS,
                ]
            );
        } catch (Throwable $exception) {
            /*
             * Mark an unsent token unusable.
             */
            $this->tokenModel->update(
                (int) $tokenId,
                [
                    'used_at' => date(
                        'Y-m-d H:i:s'
                    ),
                ]
            );

            throw $exception;
        }

        return VerificationResult::success(
            'A verification link has been sent to your email address.'
        );
    }

    /**
     * Verify an email using a single-use token.
     */
    public function verifyToken(
        string $rawToken
    ): VerificationResult {
        if (
            !preg_match(
                '/^[a-f0-9]{64}$/',
                $rawToken
            )
        ) {
            return VerificationResult::failure(
                'The verification link is invalid.'
            );
        }

        $tokenHash = hash('sha256', $rawToken);

        $token = $this->tokenModel
            ->findUsableToken($tokenHash);

        if (!is_array($token)) {
            return VerificationResult::failure(
                'This verification link is invalid or has expired.'
            );
        }

        $userId = (int) $token['user_id'];
        $contactId = (int) $token['user_contact_id'];
        $tokenId = (int) $token['id'];

        $contact = $this->contactModel->findForUser(
            $contactId,
            $userId,
            UserContactModel::TYPE_EMAIL
        );

        if (!is_array($contact)) {
            return VerificationResult::failure(
                'The email address associated with this link no longer exists.'
            );
        }

        if (
            BooleanValue::fromDatabase(
                $contact['is_verified'] ?? false
            )
        ) {
            $this->tokenModel->update(
                $tokenId,
                [
                    'used_at' => date(
                        'Y-m-d H:i:s'
                    ),
                ]
            );

            return VerificationResult::success(
                'Your email address is already verified.'
            );
        }

        $database = db_connect();

        $database->transStart();

        $this->contactModel->update(
            $contactId,
            [
                'is_verified' => true,
                'verified_at' => date(
                    'Y-m-d H:i:s'
                ),
            ]
        );

        $this->tokenModel->update(
            $tokenId,
            [
                'used_at' => date(
                    'Y-m-d H:i:s'
                ),
            ]
        );

        /*
         * Invalidate any other outstanding verification links.
         */
        $this->tokenModel->invalidateForContact(
            $contactId
        );

        $database->transComplete();

        if (!$database->transStatus()) {
            throw new RuntimeException(
                'Email verification could not be completed.'
            );
        }

        return VerificationResult::success(
            'Your email address has been verified successfully.'
        );
    }
}
