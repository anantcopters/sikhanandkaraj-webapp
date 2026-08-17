<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Handles email and mobile contact records.
 */
final class UserContactModel extends Model
{
    public const TYPE_EMAIL = 'EMAIL';

    public const TYPE_MOBILE = 'MOBILE';

    protected $table = 'user_contacts';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'user_id',
        'contact_type',
        'contact_value',
        'normalized_value',
        'is_primary',
        'is_verified',
        'verified_at',

        /*
         * Pending replacement-email state.
         */
        'replaces_contact_id',
        'change_available_at',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * Find a contact by its type and normalized value.
     *
     * @return array<string, mixed>|null
     */
    public function findByNormalizedValue(
        string $type,
        string $normalizedValue
    ): ?array {
        $record = $this
            ->where(
                'contact_type',
                $type
            )
            ->where(
                'normalized_value',
                $normalizedValue
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }

    /**
     * Find a user's primary contact of a particular type.
     *
     * @return array<string, mixed>|null
     */
    public function findPrimaryForUser(
        int $userId,
        string $type
    ): ?array {
        $record = $this
            ->where(
                'user_id',
                $userId
            )
            ->where(
                'contact_type',
                $type
            )
            ->where(
                'is_primary',
                true
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }

    /**
     * Find a contact belonging to a particular user.
     *
     * @return array<string, mixed>|null
     */
    public function findForUser(
        int $contactId,
        int $userId,
        string $type
    ): ?array {
        $record = $this
            ->where(
                'id',
                $contactId
            )
            ->where(
                'user_id',
                $userId
            )
            ->where(
                'contact_type',
                $type
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }

    /**
     * Return the replacement email currently awaiting verification.
     *
     * @return array<string, mixed>|null
     */
    public function findPendingEmailForUser(
        int $userId
    ): ?array {
        $record = $this
            ->where(
                'user_id',
                $userId
            )
            ->where(
                'contact_type',
                self::TYPE_EMAIL
            )
            ->where(
                'is_primary',
                false
            )
            ->where(
                'is_verified',
                false
            )
            ->where(
                'replaces_contact_id IS NOT NULL',
                null,
                false
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }

    /**
     * Return all email contacts for a member.
     *
     * @return list<array<string, mixed>>
     */
    public function emailContactsForUser(
        int $userId
    ): array {
        $records = $this
            ->where(
                'user_id',
                $userId
            )
            ->where(
                'contact_type',
                self::TYPE_EMAIL
            )
            ->orderBy(
                'is_primary',
                'DESC'
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->findAll();

        return is_array($records)
            ? $records
            : [];
    }
}
