<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;

/**
 * Displays publicly accessible website pages.
 */
final class HomeController extends BaseController
{
    /**
     * Displays the public homepage.
     */
    public function index(): string
    {
        return view('Pages/Home/Index', [
            'pageTitle' => 'Sikh Anand Karaj',

            /**
             * JavaScript files required only by the homepage.
             *
             * Paths are relative to the public directory.
             */
            'pageScripts' => [
                'assets/js/pages/home.js',
                'assets/js/components/password-toggle.js'
            ],
        ]);
    }
}
