<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Provides active occupation master values.
 */
final class MasterOccupationModel extends Model
{
    protected $table = 'master_occupations';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'category_id',
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
     * Return a flat list of active occupations.
     *
     * Keep this method for consumers such as partner preferences that
     * currently expect a simple array of occupation records.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeOptions(): array
    {
        return $this
            ->select([
                'master_occupations.id',
                'master_occupations.category_id',
                'master_occupations.code',
                'master_occupations.name',
            ])
            ->join(
                'master_occupation_categories',
                'master_occupation_categories.id = '
                    . 'master_occupations.category_id',
                'inner'
            )
            ->where(
                'master_occupations.is_active',
                true
            )
            ->where(
                'master_occupation_categories.is_active',
                true
            )
            ->orderBy(
                'master_occupation_categories.display_order',
                'ASC'
            )
            ->orderBy(
                'master_occupations.display_order',
                'ASC'
            )
            ->orderBy(
                'master_occupations.name',
                'ASC'
            )
            ->findAll();
    }

    /**
     * Return active occupations grouped by active category.
     *
     * @return array<int, array{
     *     id: int,
     *     code: string,
     *     name: string,
     *     occupations: array<int, array{
     *         id: int,
     *         code: string,
     *         name: string
     *     }>
     * }>
     */
    public function activeGroupedOptions(): array
    {
        $rows = $this
            ->select([
                'master_occupations.id',
                'master_occupations.code',
                'master_occupations.name',
                'master_occupation_categories.id '
                    . 'AS category_id',
                'master_occupation_categories.code '
                    . 'AS category_code',
                'master_occupation_categories.name '
                    . 'AS category_name',
            ])
            ->join(
                'master_occupation_categories',
                'master_occupation_categories.id = '
                    . 'master_occupations.category_id',
                'inner'
            )
            ->where(
                'master_occupations.is_active',
                true
            )
            ->where(
                'master_occupation_categories.is_active',
                true
            )
            ->orderBy(
                'master_occupation_categories.display_order',
                'ASC'
            )
            ->orderBy(
                'master_occupation_categories.name',
                'ASC'
            )
            ->orderBy(
                'master_occupations.display_order',
                'ASC'
            )
            ->orderBy(
                'master_occupations.name',
                'ASC'
            )
            ->findAll();

        $grouped = [];

        foreach ($rows as $row) {
            $categoryId = (int) (
                $row['category_id'] ?? 0
            );

            if ($categoryId <= 0) {
                continue;
            }

            if (!isset($grouped[$categoryId])) {
                $grouped[$categoryId] = [
                    'id' => $categoryId,

                    'code' => (string) (
                        $row['category_code'] ?? ''
                    ),

                    'name' => (string) (
                        $row['category_name'] ?? ''
                    ),

                    'occupations' => [],
                ];
            }

            $grouped[$categoryId]['occupations'][] = [
                'id' => (int) (
                    $row['id'] ?? 0
                ),

                'code' => (string) (
                    $row['code'] ?? ''
                ),

                'name' => (string) (
                    $row['name'] ?? ''
                ),
            ];
        }

        return array_values($grouped);
    }
}
