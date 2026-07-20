<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class AdminUserModel extends Model
{
    public const ROLE_SUPER_ADMIN = 'SUPER_ADMIN';
    public const ROLE_ADMIN = 'ADMIN';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_VERIFIED = 'VERIFIED';
    public const STATUS_SUSPENDED = 'SUSPENDED';

    protected $table = 'admin_users';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'full_name',
        'mobile_number',
        'email_address',
        'password_hash',
        'role',
        'account_status',
        'is_mobile_verified',
        'mobile_verified_at',
        'is_email_verified',
        'email_verified_at',
        'password_set_at',
        'last_login_at',
        'created_by',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $deletedField = 'deleted_at';

    protected $skipValidation = true;

    /**
     * Find an administrator by normalized email or mobile.
     *
     * @return array<string, mixed>|null
     */
    public function findByIdentifier(
        string $identifier
    ): ?array {
        $record = $this
            ->groupStart()
            ->where('email_address', $identifier)
            ->orWhere('mobile_number', $identifier)
            ->groupEnd()
            ->first();

        return is_array($record)
            ? $record
            : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAdministrators(): array
    {
        return $this
            ->select([
                'id',
                'full_name',
                'mobile_number',
                'email_address',
                'role',
                'account_status',
                'is_mobile_verified',
                'is_email_verified',
                'created_at',
                'last_login_at',
            ])
            ->where('role', self::ROLE_ADMIN)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
