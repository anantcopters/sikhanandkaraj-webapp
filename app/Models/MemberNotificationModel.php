<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Model;

/**
 * Handles persistence and querying of member notifications.
 *
 * Controllers must not directly build notification queries. All reusable
 * notification operations are exposed here and orchestrated by the service.
 */
final class MemberNotificationModel extends Model
{
    public const TYPE_MESSAGE = 'MESSAGE';

    public const TYPE_INTEREST_RECEIVED =
    'INTEREST_RECEIVED';

    public const TYPE_INTEREST_ACCEPTED =
    'INTEREST_ACCEPTED';

    public const TYPE_INTEREST_REJECTED =
    'INTEREST_REJECTED';

    public const TYPE_PROFILE_VIEW =
    'PROFILE_VIEW';

    public const TYPE_SHORTLISTED =
    'SHORTLISTED';

    public const TYPE_PHOTO_REJECTED =
    'PHOTO_REJECTED';

    public const TYPE_SYSTEM =
    'SYSTEM';

    protected $table = 'member_notifications';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $useTimestamps = true;

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'recipient_user_id',
        'actor_user_id',
        'notification_type',
        'title',
        'message',
        'entity_type',
        'entity_id',
        'target_url',
        'read_at',
    ];

    /**
     * @param BaseConnection|null $database
     */
    public function __construct(
        ?BaseConnection $database = null
    ) {
        parent::__construct($database);
    }

    /**
     * Return the total number of unread notifications for a member.
     */
    public function countUnreadForMember(
        int $memberUserId
    ): int {
        return $this
            ->where(
                'recipient_user_id',
                $memberUserId
            )
            ->where('read_at', null)
            ->countAllResults();
    }

    /**
     * Return the number of unread message notifications.
     */
    public function countUnreadMessagesForMember(
        int $memberUserId
    ): int {
        return $this
            ->where(
                'recipient_user_id',
                $memberUserId
            )
            ->where(
                'notification_type',
                self::TYPE_MESSAGE
            )
            ->where('read_at', null)
            ->countAllResults();
    }

    /**
     * Return recent notifications for the header dropdown.
     *
     * @return list<array<string, mixed>>
     */
    public function findRecentForMember(
        int $memberUserId,
        int $limit = 5
    ): array {
        $resolvedLimit = max(
            1,
            min($limit, 20)
        );

        return $this
            ->where(
                'recipient_user_id',
                $memberUserId
            )
            ->orderBy('created_at', 'DESC')
            ->findAll($resolvedLimit);
    }

    /**
     * Return all notifications for the notification page.
     *
     * @return list<array<string, mixed>>
     */
    public function findAllForMember(
        int $memberUserId,
        int $limit = 50
    ): array {
        $resolvedLimit = max(
            1,
            min($limit, 100)
        );

        return $this
            ->where(
                'recipient_user_id',
                $memberUserId
            )
            ->orderBy('created_at', 'DESC')
            ->findAll($resolvedLimit);
    }

    /**
     * Find a notification while ensuring it belongs to the member.
     *
     * @return array<string, mixed>|null
     */
    public function findForMember(
        int $notificationId,
        int $memberUserId
    ): ?array {
        $notification = $this
            ->where('id', $notificationId)
            ->where(
                'recipient_user_id',
                $memberUserId
            )
            ->first();

        return is_array($notification)
            ? $notification
            : null;
    }

    /**
     * Mark one member-owned notification as read.
     */
    public function markAsReadForMember(
        int $notificationId,
        int $memberUserId
    ): bool {
        return $this
            ->where('id', $notificationId)
            ->where(
                'recipient_user_id',
                $memberUserId
            )
            ->where('read_at', null)
            ->set([
                'read_at' => date('Y-m-d H:i:s'),
            ])
            ->update();
    }

    /**
     * Mark all unread notifications as read for one member.
     */
    public function markAllAsReadForMember(
        int $memberUserId
    ): bool {
        return $this
            ->where(
                'recipient_user_id',
                $memberUserId
            )
            ->where('read_at', null)
            ->set([
                'read_at' => date('Y-m-d H:i:s'),
            ])
            ->update();
    }
}
