<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;

final class CouponAuditLogModel extends Model
{
    protected $table =
    'coupon_audit_logs';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useTimestamps =
    false;

    protected $allowedFields = [
        'coupon_id',
        'admin_user_id',
        'action',
        'previous_values',
        'new_values',
        'created_at',
    ];

    public function __construct(
        ?ConnectionInterface $db = null
    ) {
        parent::__construct(
            $db
        );
    }
}
