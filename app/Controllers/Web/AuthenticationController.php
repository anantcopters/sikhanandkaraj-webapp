<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Handles authenticated web session actions.
 */
final class AuthenticationController extends BaseController
{
    public function logout(): RedirectResponse
    {
        session()->destroy();

        return redirect()
            ->to(route_to('web.home'))
            ->with('formAlert', [
                'type' => 'success',
                'title' => 'Logged out',
                'message' =>
                    'You have been logged out successfully.',
            ]);
    }
}

