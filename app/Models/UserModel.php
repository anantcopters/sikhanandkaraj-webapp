<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Handles user account persistence.
 */
final class UserModel extends Model
{
    /**
     * Account has completed registration but is awaiting approval.
     */
    public const STATUS_PENDING = 'PENDING';

    /**
     * Account has been reviewed and approved.
     */
    public const STATUS_APPROVED = 'APPROVED';
    
    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'profile_ref_number',
        'profile_created_for',
        'gender',
        'full_name',
        'password_hash',
        'account_status',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $deletedField = 'deleted_at';

    protected $skipValidation = true;

    /**
     * Check whether a profile reference already exists.
     */
    public function profileReferenceExists(string $reference): bool
    {
        return $this
            ->where('profile_ref_number', $reference)
            ->countAllResults() > 0;
    }
}
