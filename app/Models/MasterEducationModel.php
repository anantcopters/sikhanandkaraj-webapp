<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Provides active highest-education master values.
 */
final class MasterEducationModel extends Model
{
    protected $table = 'master_educations';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'code',
        'name',
        'display_order',
        'is_active',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * Return active options in configured display order.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeOptions(): array
    {
        return $this
            ->select([
                'id',
                'code',
                'name',
            ])
            ->where('is_active', true)
            ->orderBy('display_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }
}
