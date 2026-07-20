<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class AdminAuditLogModel extends Model
{
    protected $table = 'admin_audit_logs';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'occurred_at',
        'actor_admin_id',
        'actor_name',
        'actor_role',
        'action',
        'target_type',
        'target_id',
        'target_label',
        'outcome',
        'description',
        'before_data',
        'after_data',
        'metadata',
        'request_id',
        'route_name',
        'ip_address',
        'user_agent',
    ];

    protected $useTimestamps = false;

    protected $skipValidation = true;
}
