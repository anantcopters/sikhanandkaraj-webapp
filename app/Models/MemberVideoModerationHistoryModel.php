<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MemberVideoModerationHistoryModel extends Model
{
    protected $table = 'member_video_moderation_history';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $useTimestamps = false;

    protected $allowedFields = [
        'video_introduction_id',
        'admin_user_id',
        'from_status',
        'to_status',
        'reason',
        'created_at',
    ];
}
