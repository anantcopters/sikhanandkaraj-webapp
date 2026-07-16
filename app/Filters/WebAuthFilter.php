<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Protect authenticated web pages.
 */
final class WebAuthFilter implements FilterInterface
{
    public function before(
        RequestInterface $request,
        $arguments = null
    ) {
        if (
            session('is_authenticated') !== true
            || !is_numeric(session('auth_user_id'))
        ) {
            return redirect()
                ->to(route_to('web.login'))
                ->with('formAlert', [
                    'type' => 'warning',
                    'title' => 'Login required',
                    'message' =>
                        'Please log in to continue.',
                ]);
        }

        /**
         * Prevent protected pages from being served from browser cache.
         */
        service('response')
            ->setHeader(
                'Cache-Control',
                'no-store, no-cache, must-revalidate, max-age=0'
            )
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0');

        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ): void {}
}

