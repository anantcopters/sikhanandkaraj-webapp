<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Base model for simple active code/name master tables.
 */
abstract class AbstractActiveMasterModel extends Model
{
    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

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
     * Return active master values in their configured UI order.
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

    /**
     * Return one active record.
     *
     * @return array<string, mixed>|null
     */
    public function findActive(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $record = $this
            ->where('id', $id)
            ->where('is_active', true)
            ->first();

        return is_array($record)
            ? $record
            : null;
    }
}
