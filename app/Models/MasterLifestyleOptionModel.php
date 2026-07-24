<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MasterLifestyleOptionModel extends Model
{
    protected $table = 'master_lifestyle_options';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'lifestyle_category_id',
        'code',
        'name',
        'display_order',
        'is_active',
    ];

    protected $useTimestamps = true;

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    /**
     * @return list<array<string, mixed>>
     */
    public function activeOrdered(): array
    {
        return $this
            ->select(
                'master_lifestyle_options.*, '
                    . 'master_lifestyle_categories.code AS category_code, '
                    . 'master_lifestyle_categories.name AS category_name'
            )
            ->join(
                'master_lifestyle_categories',
                'master_lifestyle_categories.id = '
                    . 'master_lifestyle_options.lifestyle_category_id'
            )
            ->where('master_lifestyle_options.is_active', true)
            ->where('master_lifestyle_categories.is_active', true)
            ->orderBy(
                'master_lifestyle_categories.display_order',
                'ASC'
            )
            ->orderBy(
                'master_lifestyle_options.display_order',
                'ASC'
            )
            ->orderBy('master_lifestyle_options.id', 'ASC')
            ->findAll();
    }

    /**
     * @param list<int> $optionIds
     *
     * @return list<array<string, mixed>>
     */
    public function activeByIds(array $optionIds): array
    {
        if ($optionIds === []) {
            return [];
        }

        return $this
            ->whereIn('id', $optionIds)
            ->where('is_active', true)
            ->findAll();
    }
}
