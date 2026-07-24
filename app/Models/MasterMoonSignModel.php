<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MasterMoonSignModel extends Model
{
    protected $table = 'master_moon_signs';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'code',
        'name',
        'english_name',
        'display_order',
        'is_active',
    ];

    protected $useTimestamps = true;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeOptions(): array
    {
        return $this
            ->select([
                'id',
                'code',
                'name',
                'english_name',
            ])
            ->where('is_active', true)
            ->orderBy('display_order', 'ASC')
            ->findAll();
    }
}
