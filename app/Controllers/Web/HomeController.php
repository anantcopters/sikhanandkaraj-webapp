<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

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
        ]);
    }
}