<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\EmailVerification\EmailVerificationService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;
use RuntimeException;

final class EmailVerificationController extends BaseController
{
    public function send(): ResponseInterface
    {
        $userId = session('auth_user_id');

        if (!is_numeric($userId)) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'type' => 'error',
                    'title' => 'Session expired',
                    'message' =>
                    'Your session has expired. Please log in again.',
                    'redirectUrl' => route_to('web.login'),
                ]);
        }

        try {
            $result = (
                new EmailVerificationService()
            )->sendForUser(
                (int) $userId
            );

            $statusCode = $result->success
                ? 200
                : 422;

            return $this->response
                ->setStatusCode($statusCode)
                ->setJSON([
                    'success' => $result->success,
                    'type' => $result->success
                        ? 'success'
                        : 'warning',
                    'title' => $result->success
                        ? 'Verification email sent'
                        : 'Unable to send email',
                    'message' => $result->message,
                    'buttonText' => 'Okay',
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Verification email failure for user {userId}: {message}',
                [
                    'userId' => $userId,
                    'message' => $exception->getMessage(),
                ]
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'type' => 'error',
                    'title' => 'Unable to send email',
                    'message' =>
                    'We could not send the verification email. '
                        . 'Please try again.',
                    'buttonText' => 'Okay',
                ]);
        }
    }

    public function verify(
        string $token
    ): string {
        try {
            $result = (
                new EmailVerificationService()
            )->verifyToken($token);
        } catch (RuntimeException $exception) {
            log_message(
                'error',
                'Email verification failed: {message}',
                [
                    'message' => $exception->getMessage(),
                ]
            );

            $result = new \App\Services\EmailVerification\VerificationResult(
                false,
                'We could not verify your email address. Please try again.'
            );
        }

        return view(
            'Pages/Authentication/EmailVerificationResult',
            [
                'pageTitle' => 'Email Verification',
                'verificationSuccessful' =>
                $result->success,
                'verificationMessage' =>
                $result->message,
            ]
        );
    }
}
