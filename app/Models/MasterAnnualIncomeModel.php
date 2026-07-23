<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Provides annual-income ranges.
 */
final class MasterAnnualIncomeModel extends Model
{
    protected $table = 'master_annual_incomes';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'code',
        'display_name',
        'min_amount',
        'max_amount',
        'display_order',
        'is_active',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * Return active annual-income ranges.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeOptions(): array
    {
        return $this
            ->select([
                'id',
                'code',
                'display_name',
                'min_amount',
                'max_amount',
            ])
            ->where('is_active', true)
            ->orderBy('display_order', 'ASC')
            ->findAll();
    }
}
