<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Persists sanitized technical request logs.
 */
final class HttpRequestLogModel extends Model
{
    protected $table = 'http_request_logs';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'request_id',
        'occurred_at',
        'environment',
        'request_method',
        'request_uri',
        'route_name',
        'controller_action',
        'response_status',
        'duration_ms',
        'ip_address',
        'user_id',
        'profile_reference',
        'is_authenticated',
        'user_agent',
        'referer',
        'request_headers',
        'request_payload',
        'response_payload',
        'request_size_bytes',
        'response_size_bytes',
        'severity',
        'is_successful',
    ];

    protected $useTimestamps = false;

    protected $skipValidation = true;
}

