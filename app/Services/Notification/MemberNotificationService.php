<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\MemberNotificationModel;
use InvalidArgumentException;
use RuntimeException;

/**
 * Provides the reusable member-notification workflow.
 *
 * Other features such as messaging, interests and profile views should call
 * this service instead of inserting directly into member_notifications.
 */
final class MemberNotificationService
{
    private const HEADER_NOTIFICATION_LIMIT = 5;

    public function __construct(
        private readonly MemberNotificationModel $notificationModel
    ) {}

    /**
     * Return notification information required by the member header.
     *
     * @return array{
     *     unreadNotificationCount:int,
     *     unreadMessageCount:int,
     *     recentNotifications:list<array<string, mixed>>
     * }
     */
    public function getHeaderData(
        int $memberUserId
    ): array {
        $this->assertValidMemberId($memberUserId);

        return [
            'unreadNotificationCount' =>
            $this->notificationModel
                ->countUnreadForMember(
                    $memberUserId
                ),

            'unreadMessageCount' =>
            $this->notificationModel
                ->countUnreadMessagesForMember(
                    $memberUserId
                ),

            'recentNotifications' =>
            $this->notificationModel
                ->findRecentForMember(
                    $memberUserId,
                    self::HEADER_NOTIFICATION_LIMIT
                ),
        ];
    }

    /**
     * Return notifications for the full notification screen.
     *
     * @return list<array<string, mixed>>
     */
    public function getMemberNotifications(
        int $memberUserId
    ): array {
        $this->assertValidMemberId($memberUserId);

        return $this->notificationModel
            ->findAllForMember($memberUserId);
    }

    /**
     * Create a new notification.
     *
     * @param array{
     *     recipientUserId:int,
     *     actorUserId?:int|null,
     *     type:string,
     *     title:string,
     *     message:string,
     *     entityType?:string|null,
     *     entityId?:int|null,
     *     targetUrl?:string|null
     * } $input
     */
    public function create(
        array $input
    ): int {
        $recipientUserId = (int) (
            $input['recipientUserId'] ?? 0
        );

        $this->assertValidMemberId(
            $recipientUserId
        );

        $type = strtoupper(
            trim((string) ($input['type'] ?? ''))
        );

        $allowedTypes = [
            MemberNotificationModel::TYPE_MESSAGE,
            MemberNotificationModel::TYPE_INTEREST_RECEIVED,
            MemberNotificationModel::TYPE_INTEREST_ACCEPTED,
            MemberNotificationModel::TYPE_INTEREST_REJECTED,
            MemberNotificationModel::TYPE_PROFILE_VIEW,
            MemberNotificationModel::TYPE_SHORTLISTED,
            MemberNotificationModel::TYPE_SYSTEM,
        ];

        if (! in_array($type, $allowedTypes, true)) {
            throw new InvalidArgumentException(
                'Unsupported notification type.'
            );
        }

        $title = trim(
            (string) ($input['title'] ?? '')
        );

        $message = trim(
            (string) ($input['message'] ?? '')
        );

        if ($title === '' || mb_strlen($title) > 150) {
            throw new InvalidArgumentException(
                'Notification title is invalid.'
            );
        }

        if (
            $message === ''
            || mb_strlen($message) > 500
        ) {
            throw new InvalidArgumentException(
                'Notification message is invalid.'
            );
        }

        $targetUrl = $this->sanitizeTargetUrl(
            $input['targetUrl'] ?? null
        );

        $notificationId =
            $this->notificationModel->insert(
                [
                    'recipient_user_id' =>
                    $recipientUserId,

                    'actor_user_id' =>
                    $this->nullablePositiveInteger(
                        $input['actorUserId'] ?? null
                    ),

                    'notification_type' => $type,

                    'title' => $title,

                    'message' => $message,

                    'entity_type' =>
                    $this->nullableString(
                        $input['entityType'] ?? null,
                        40
                    ),

                    'entity_id' =>
                    $this->nullablePositiveInteger(
                        $input['entityId'] ?? null
                    ),

                    'target_url' => $targetUrl,
                ],
                true
            );

        if (
            ! is_numeric($notificationId)
            || (int) $notificationId <= 0
        ) {
            throw new RuntimeException(
                'Unable to create member notification.'
            );
        }

        return (int) $notificationId;
    }

    /**
     * Mark one notification as read and return its safe target URL.
     */
    public function read(
        int $notificationId,
        int $memberUserId
    ): ?string {
        $this->assertValidMemberId($memberUserId);

        if ($notificationId <= 0) {
            throw new InvalidArgumentException(
                'Notification identifier is invalid.'
            );
        }

        $notification =
            $this->notificationModel->findForMember(
                $notificationId,
                $memberUserId
            );

        if (! is_array($notification)) {
            return null;
        }

        if (
            empty($notification['read_at'])
            && ! $this->notificationModel
                ->markAsReadForMember(
                    $notificationId,
                    $memberUserId
                )
        ) {
            throw new RuntimeException(
                'Unable to update notification.'
            );
        }

        return $this->sanitizeTargetUrl(
            $notification['target_url'] ?? null
        );
    }

    /**
     * Mark all notifications as read.
     */
    public function readAll(
        int $memberUserId
    ): void {
        $this->assertValidMemberId($memberUserId);

        if (
            ! $this->notificationModel
                ->markAllAsReadForMember(
                    $memberUserId
                )
        ) {
            throw new RuntimeException(
                'Unable to update notifications.'
            );
        }
    }

    private function assertValidMemberId(
        int $memberUserId
    ): void {
        if ($memberUserId <= 0) {
            throw new InvalidArgumentException(
                'Member identifier is invalid.'
            );
        }
    }

    /**
     * Permit only internal application paths.
     */
    private function sanitizeTargetUrl(
        mixed $targetUrl
    ): ?string {
        if (! is_scalar($targetUrl)) {
            return null;
        }

        $resolvedTargetUrl = trim(
            (string) $targetUrl
        );

        if ($resolvedTargetUrl === '') {
            return null;
        }

        /*
         * Block absolute, protocol-relative and JavaScript URLs.
         */
        if (
            str_starts_with(
                $resolvedTargetUrl,
                '//'
            )
            || preg_match(
                '/^[a-z][a-z0-9+.-]*:/i',
                $resolvedTargetUrl
            ) === 1
        ) {
            return null;
        }

        if (! str_starts_with(
            $resolvedTargetUrl,
            '/'
        )) {
            $resolvedTargetUrl =
                '/' . $resolvedTargetUrl;
        }

        return mb_substr(
            $resolvedTargetUrl,
            0,
            255
        );
    }

    private function nullablePositiveInteger(
        mixed $value
    ): ?int {
        if (
            $value === null
            || $value === ''
            || ! is_numeric($value)
        ) {
            return null;
        }

        $resolvedValue = (int) $value;

        return $resolvedValue > 0
            ? $resolvedValue
            : null;
    }

    private function nullableString(
        mixed $value,
        int $maximumLength
    ): ?string {
        if (! is_scalar($value)) {
            return null;
        }

        $resolvedValue = trim(
            (string) $value
        );

        if ($resolvedValue === '') {
            return null;
        }

        return mb_substr(
            $resolvedValue,
            0,
            $maximumLength
        );
    }
}
