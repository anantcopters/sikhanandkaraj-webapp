<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\Api\BaseApiController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Provides a lightweight application-health endpoint.
 */
final class HealthController extends BaseApiController
{
    /**
     * Confirms that the CodeIgniter application is responding.
     */
    public function index(): ResponseInterface
    {
        return $this->respondSuccess(
            [
                'application' => 'Sikhanandkaraj',
                'timestamp'   => gmdate('c'),
            ],
            'Application is healthy.'
        );
    }
}