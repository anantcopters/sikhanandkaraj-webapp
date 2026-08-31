<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Read/write persistence for SMS delivery attempts.
 *
 * Business services must not write here directly. Delivery logging is owned
 * centrally by LoggingSmsProvider so every SmsProviderInterface consumer is
 * covered automatically.
 */
final class SmsDeliveryLogModel extends Model
{
    public const STATUS_SENT =
    'SENT';

    public const STATUS_FAILED =
    'FAILED';

    protected $table =
    'sms_delivery_logs';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useAutoIncrement =
    true;

    protected $allowedFields = [
        'message_type',
        'recipient_mobile',
        'provider',
        'provider_message_id',
        'status',
        'error_message',
        'sent_at',
        'failed_at',
    ];

    protected $useTimestamps =
    false;

    protected $skipValidation =
    true;
}
