<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\EmailVerification\EmailVerificationService;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;

final class EmailVerificationController extends BaseController
{
    public function send(): RedirectResponse
    {
        $userId = session('auth_user_id');

        if (!is_numeric($userId)) {
            return redirect()->to(
                route_to('web.login')
            );
        }

        try {
            $result = (
                new EmailVerificationService()
            )->sendForUser(
                (int) $userId
            );

            return redirect()
                ->to(route_to('web.dashboard'))
                ->with('formAlert', [
                    'type' => $result->success
                        ? 'success'
                        : 'danger',
                    'message' => $result->message,
                ]);
        } catch (RuntimeException $exception) {
            log_message(
                'error',
                'Verification email failure for user {userId}: {message}',
                [
                    'userId' => $userId,
                    'message' => $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(route_to('web.dashboard'))
                ->with('formAlert', [
                    'type' => 'danger',
                    'message' =>
                    'We could not send the verification email. Please try again.',
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
