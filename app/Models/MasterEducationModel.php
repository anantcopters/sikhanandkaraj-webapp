<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Provides active education master values.
 */
final class MasterEducationModel extends Model
{
    protected $table = 'master_educations';

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
     * Return active education options as a flat collection.
     *
     * Keep this method for existing consumers such as partner
     * preferences and development-data generation.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeOptions(): array
    {
        return $this
            ->select([
                'master_educations.id',
                'master_educations.category_id',
                'master_educations.code',
                'master_educations.name',
            ])
            ->join(
                'master_education_categories',
                'master_education_categories.id = '
                    . 'master_educations.category_id',
                'inner'
            )
            ->where(
                'master_educations.is_active',
                true
            )
            ->where(
                'master_education_categories.is_active',
                true
            )
            ->orderBy(
                'master_education_categories.display_order',
                'ASC'
            )
            ->orderBy(
                'master_educations.display_order',
                'ASC'
            )
            ->orderBy(
                'master_educations.name',
                'ASC'
            )
            ->findAll();
    }

    /**
     * Return active educations grouped by active category.
     *
     * @return array<int, array{
     *     id:int,
     *     code:string,
     *     name:string,
     *     educations:array<int, array{
     *         id:int,
     *         code:string,
     *         name:string
     *     }>
     * }>
     */
    public function activeGroupedOptions(): array
    {
        $rows = $this
            ->select([
                'master_educations.id',
                'master_educations.code',
                'master_educations.name',

                'master_education_categories.id '
                    . 'AS category_id',

                'master_education_categories.code '
                    . 'AS category_code',

                'master_education_categories.name '
                    . 'AS category_name',
            ])
            ->join(
                'master_education_categories',
                'master_education_categories.id = '
                    . 'master_educations.category_id',
                'inner'
            )
            ->where(
                'master_educations.is_active',
                true
            )
            ->where(
                'master_education_categories.is_active',
                true
            )
            ->orderBy(
                'master_education_categories.display_order',
                'ASC'
            )
            ->orderBy(
                'master_education_categories.name',
                'ASC'
            )
            ->orderBy(
                'master_educations.display_order',
                'ASC'
            )
            ->orderBy(
                'master_educations.name',
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

                    'educations' => [],
                ];
            }

            $educationId = (int) (
                $row['id'] ?? 0
            );

            $educationName = trim(
                (string) (
                    $row['name'] ?? ''
                )
            );

            if (
                $educationId <= 0
                || $educationName === ''
            ) {
                continue;
            }

            $grouped[$categoryId]['educations'][] = [
                'id' => $educationId,

                'code' => (string) (
                    $row['code'] ?? ''
                ),

                'name' => $educationName,
            ];
        }

        return array_values($grouped);
    }
}
