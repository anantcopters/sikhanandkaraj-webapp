<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Displays the registration OTP verification page.
 */
final class RegistrationVerificationController extends BaseController
{
    /**
     * Display the OTP verification screen.
     *
     * @return string|RedirectResponse
     */
    public function index(): string|RedirectResponse
    {
        $userId = session(
            'pending_registration_user_id'
        );

        $mobileContactId = session(
            'pending_mobile_contact_id'
        );

        /**
         * Prevent direct access without a pending registration.
         */
        if (
            !is_numeric($userId)
            || !is_numeric($mobileContactId)
        ) {
            return redirect()
                ->to(route_to('web.home'))
                ->with('formAlert', [
                    'type' => 'warning',
                    'title' => 'Registration required',
                    'message' =>
                        'Please complete the registration form first.',
                ]);
        }

        return view(
            'Pages/Registration/VerifyOtp',
            [
                'pageTitle' => 'Verify OTP',
                'profileReference' => session(
                    'pending_profile_reference'
                ),
            ]
        );
    }
}