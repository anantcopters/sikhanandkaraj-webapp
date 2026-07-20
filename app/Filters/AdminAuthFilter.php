<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

final class AdminAuthFilter implements FilterInterface
{
    public function before(
        RequestInterface $request,
        $arguments = null
    ) {
        if (
            session('admin_is_authenticated') !== true
            || !is_numeric(
                session('admin_user_id')
            )
        ) {
            return redirect()
                ->to(route_to('admin.login'))
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Login required',
                    'message' =>
                    'Please log in to access administration.',
                ]);
        }

        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        return null;
    }
}
