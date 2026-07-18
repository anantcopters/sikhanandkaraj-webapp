<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class EmailQueueModel extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_SENT = 'SENT';
    public const STATUS_FAILED = 'FAILED';

    protected $table = 'email_queue';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'queue_name',
        'recipient_email',
        'recipient_name',
        'subject',
        'view_name',
        'view_data',
        'status',
        'priority',
        'attempts',
        'max_attempts',
        'available_at',
        'locked_at',
        'locked_by',
        'sent_at',
        'failed_at',
        'last_error',
        'reference_type',
        'reference_id',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;
}
