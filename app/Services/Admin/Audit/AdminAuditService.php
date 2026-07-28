<?php

declare(strict_types=1);

namespace App\Services\Admin\Audit;

use App\Models\AdminAuditLogModel;
use CodeIgniter\HTTP\IncomingRequest;
use JsonException;
use Throwable;

final class AdminAuditService
{
    public function __construct(
        private readonly AdminAuditLogModel $model
    ) {}

    /**
     * Persist an administrator business/security audit event.
     *
     * Audit failures are written to the normal CI4 log but should not cause
     * the original business action to fail after it has already committed.
     */
    public function record(
        AdminAuditEvent $event
    ): void {
        try {
            $request = service('request');

            $incomingRequest =
                $request instanceof IncomingRequest
                ? $request
                : null;

            $router = service('router');

            $routeOptions = is_object($router)
                && method_exists(
                    $router,
                    'getMatchedRouteOptions'
                )
                ? $router->getMatchedRouteOptions()
                : [];

            $routeName = is_array($routeOptions)
                ? ($routeOptions['as'] ?? null)
                : null;

            $actorAdminId =
                $event->actorAdminId
                ?? $this->sessionAdminId();

            $actorName =
                $event->actorName
                ?? $this->sessionString(
                    'admin_user_name',
                    150
                );

            $actorRole =
                $event->actorRole
                ?? $this->sessionString(
                    'admin_role',
                    30
                );

            $this->model->insert([
                'occurred_at' =>
                gmdate('Y-m-d H:i:s') . '+00:00',

                'actor_admin_id' =>
                $actorAdminId,

                'actor_name' =>
                $actorName,

                'actor_role' =>
                $actorRole,

                'action' => mb_substr(
                    strtoupper(
                        trim($event->action)
                    ),
                    0,
                    100
                ),

                'target_type' =>
                $this->truncateNullable(
                    $event->targetType,
                    100
                ),

                'target_id' =>
                $event->targetId,

                'target_label' =>
                $this->truncateNullable(
                    $event->targetLabel,
                    254
                ),

                'outcome' => $this->normalizeOutcome(
                    $event->outcome
                ),

                'description' =>
                $this->truncateNullable(
                    $event->description,
                    5000
                ),

                'before_data' =>
                $this->encodeJson(
                    $this->sanitizeData(
                        $event->beforeData
                    )
                ),

                'after_data' =>
                $this->encodeJson(
                    $this->sanitizeData(
                        $event->afterData
                    )
                ),

                'metadata' =>
                $this->encodeJson(
                    $this->sanitizeData(
                        $event->metadata
                    )
                ),

                'request_id' =>
                $this->requestId(
                    $incomingRequest
                ),

                'route_name' =>
                is_string($routeName)
                    ? mb_substr(
                        $routeName,
                        0,
                        150
                    )
                    : null,

                'ip_address' =>
                $incomingRequest?->getIPAddress(),

                'user_agent' =>
                $incomingRequest !== null
                    ? mb_substr(
                        $incomingRequest
                            ->getUserAgent()
                            ->getAgentString(),
                        0,
                        1000
                    )
                    : null,
            ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Unable to persist admin audit event '
                    . '{action}: {message}',
                [
                    'action' => $event->action,
                    'message' =>
                    $exception->getMessage(),
                ]
            );
        }
    }

    private function sessionAdminId(): ?int
    {
        try {
            $value = session('admin_user_id');

            return is_numeric($value)
                ? (int) $value
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function sessionString(
        string $key,
        int $maximumLength
    ): ?string {
        try {
            $value = session($key);

            if (!is_string($value)) {
                return null;
            }

            $value = trim($value);

            return $value !== ''
                ? mb_substr(
                    $value,
                    0,
                    $maximumLength
                )
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function requestId(
        ?IncomingRequest $request
    ): ?string {
        if ($request === null) {
            return null;
        }

        $requestId = trim(
            $request->getHeaderLine(
                'X-Request-ID'
            )
        );

        return $requestId !== ''
            ? mb_substr(
                $requestId,
                0,
                100
            )
            : null;
    }

    private function normalizeOutcome(
        string $outcome
    ): string {
        $outcome = strtoupper(
            trim($outcome)
        );

        return in_array(
            $outcome,
            [
                'SUCCESS',
                'FAILURE',
                'DENIED',
            ],
            true
        )
            ? $outcome
            : 'FAILURE';
    }

    /**
     * Remove sensitive fields before storing selected snapshots.
     *
     * @param array<string, mixed>|null $data
     *
     * @return array<string, mixed>|null
     */
    private function sanitizeData(
        ?array $data
    ): ?array {
        if ($data === null) {
            return null;
        }

        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'password_hash',
            'token',
            'token_hash',
            'raw_token',
            'invitation_url',
            'csrf_token',
            'smtp_pass',
            'upi_id',
        ];

        $sanitized = [];

        foreach ($data as $key => $value) {
            $normalizedKey = mb_strtolower(
                (string) $key
            );

            if (
                in_array(
                    $normalizedKey,
                    $sensitiveKeys,
                    true
                )
            ) {
                $sanitized[$key] =
                    '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] =
                    $this->sanitizeData($value);

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    private function encodeJson(
        ?array $data
    ): ?string {
        if ($data === null || $data === []) {
            return null;
        }

        try {
            return json_encode(
                $data,
                JSON_THROW_ON_ERROR
                    | JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
            );
        } catch (JsonException) {
            return json_encode([
                '_error' =>
                'Unable to encode audit data.',
            ]);
        }
    }

    private function truncateNullable(
        ?string $value,
        int $maximumLength
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? mb_substr(
                $value,
                0,
                $maximumLength
            )
            : null;
    }
}
