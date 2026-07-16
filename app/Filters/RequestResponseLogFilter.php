<?php

declare(strict_types=1);

namespace App\Filters;

use App\Services\Logging\HttpRequestLogService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Captures request timing before the controller and writes one completed
 * technical log after the response.
 */
final class RequestResponseLogFilter implements FilterInterface
{
    /**
     * PHP-FPM may reuse the class across requests, so before() always
     * overwrites these values.
     */
    private static string $requestId = '';

    private static int $startedAtNanoseconds = 0;

    public function before(
        RequestInterface $request,
        $arguments = null
    ) {
        self::$requestId =
            $this->generateUuidV4();

        self::$startedAtNanoseconds =
            hrtime(true);

        /**
         * Return the identifier to the browser and make support/debugging
         * easier without exposing internal data.
         */
        service('response')->setHeader(
            'X-Request-ID',
            self::$requestId
        );

        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ): void {
        if (
            self::$requestId === ''
            || self::$startedAtNanoseconds <= 0
        ) {
            return;
        }

        $response->setHeader(
            'X-Request-ID',
            self::$requestId
        );

        /** @var HttpRequestLogService $service */
        $service = service(
            'httpRequestLogService'
        );

        $service->write(
            self::$requestId,
            self::$startedAtNanoseconds,
            $request,
            $response
        );
    }

    private function generateUuidV4(): string
    {
        $bytes = random_bytes(16);

        /**
         * Set UUID version 4 and RFC 4122 variant bits.
         */
        $bytes[6] = chr(
            (ord($bytes[6]) & 0x0f) | 0x40
        );

        $bytes[8] = chr(
            (ord($bytes[8]) & 0x3f) | 0x80
        );

        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}

