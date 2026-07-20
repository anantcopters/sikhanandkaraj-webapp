<?php

declare(strict_types=1);

namespace App\Filters;

use App\Models\AdminUserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

final class SuperAdminFilter implements FilterInterface
{
    public function before(
        RequestInterface $request,
        $arguments = null
    ) {
        if (
            session('admin_role')
            !== AdminUserModel::ROLE_SUPER_ADMIN
        ) {
            return service('response')
                ->setStatusCode(403)
                ->setBody(
                    view(
                        'errors/html/error_403'
                    )
                );
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
