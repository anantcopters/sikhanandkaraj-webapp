<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MemberMessageReportModel extends Model
{
    protected $table =
    'member_message_reports';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useAutoIncrement =
    true;

    protected $protectFields =
    true;

    protected $allowedFields = [
        'message_id',
        'conversation_id',
        'reporter_user_id',
        'reported_user_id',
        'reason',
        'comment',
        'created_at',
    ];

    protected $useTimestamps =
    false;

    protected $skipValidation =
    true;
}
