<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Base controller for all REST API endpoints.
 *
 * Enforces a consistent JSON response format for the web application,
 * Postman clients, and future mobile applications.
 */
abstract class BaseApiController extends ResourceController
{
    protected $format = 'json';

    /**
     * Returns a successful structured JSON response.
     *
     * @param mixed               $data       Response payload.
     * @param string              $message    User-readable message.
     * @param int                 $statusCode HTTP status code.
     * @param array<string,mixed> $meta       Optional response metadata.
     */
    protected function respondSuccess(
        mixed $data = null,
        string $message = 'Request completed successfully.',
        int $statusCode = ResponseInterface::HTTP_OK,
        array $meta = []
    ): ResponseInterface {
        return $this->respond([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
            'errors'  => [],
            'meta'    => $meta,
        ], $statusCode);
    }

    /**
     * Returns a structured JSON error response.
     *
     * @param array<string,mixed> $errors     Validation or processing errors.
     * @param string              $message    User-readable error message.
     * @param int                 $statusCode HTTP status code.
     */
    protected function respondError(
        array $errors,
        string $message,
        int $statusCode
    ): ResponseInterface {
        return $this->respond([
            'status'  => 'error',
            'message' => $message,
            'data'    => null,
            'errors'  => $errors,
            'meta'    => [],
        ], $statusCode);
    }
}