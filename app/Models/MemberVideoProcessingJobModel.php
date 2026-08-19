<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MemberVideoProcessingJobModel extends Model
{
    protected $table = 'member_video_processing_jobs';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $useTimestamps = true;

    protected $allowedFields = [
        'video_introduction_id',
        'status',
        'attempt_count',
        'available_at',
        'locked_at',
        'locked_by',
        'last_error',
        'completed_at',
    ];
}
