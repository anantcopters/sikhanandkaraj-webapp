<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class EmailQueueAttemptModel extends Model
{
    protected $table = 'email_queue_attempts';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'email_queue_id',
        'attempt_number',
        'status',
        'started_at',
        'completed_at',
        'duration_ms',
        'error_message',
        'smtp_debug',
        'worker_name',
    ];

    protected $useTimestamps = false;

    protected $skipValidation = true;
}
