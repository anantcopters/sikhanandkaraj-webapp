<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MasterLifestyleCategoryModel extends Model
{
    protected $table = 'master_lifestyle_categories';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'code',
        'name',
        'icon_class',
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
            ->where('is_active', true)
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
