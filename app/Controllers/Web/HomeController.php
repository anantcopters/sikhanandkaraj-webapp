<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Displays publicly accessible website pages.
 */
final class HomeController extends BaseController
{
    /**
     * Display the public registration homepage.
     *
     * Authenticated users are redirected to their dashboard because the
     * registration form is intended only for visitors.
     */
    public function index(): string|RedirectResponse
    {
        if ($this->isAuthenticated()) {
            return redirect()->to(
                route_to('web.dashboard')
            );
        }

        /**
         * Prevent the registration page from being restored with stale
         * session-dependent content after login or logout.
         */
        $this->response
            ->setHeader(
                'Cache-Control',
                'no-store, no-cache, must-revalidate, max-age=0'
            )
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0');

        return view(
            'Pages/Home/Index',
            [
                'pageTitle' => 'SikhAnandKaraj',

                'validationErrors' =>
                $this->readValidationErrors(),

                'formAlert' =>
                $this->readFormAlert(),

                'pageScripts' => [
                    'assets/js/pages/home.js',
                    'assets/js/components/password-toggle.js',
                    'assets/js/components/submit-loader.js',
                ],
            ]
        );
    }
}
