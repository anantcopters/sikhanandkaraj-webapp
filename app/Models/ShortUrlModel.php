<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class ShortUrlModel extends Model
{
    protected $table = 'short_urls';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'short_code',
        'destination_url',
        'destination_hash',
        'created_by_admin_id',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = false;

    protected $skipValidation = true;

    /**
     * @return array<string, mixed>|null
     */
    public function findByDestination(
        string $destinationUrl
    ): ?array {
        $row = $this
            ->where(
                'destination_hash',
                hash(
                    'sha256',
                    $destinationUrl
                )
            )
            ->first();

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByCode(
        string $shortCode
    ): ?array {
        $row = $this
            ->where(
                'short_code',
                $shortCode
            )
            ->first();

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recent(
        int $limit = 25
    ): array {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this
            ->orderBy(
                'id',
                'DESC'
            )
            ->findAll(
                max(
                    1,
                    min(
                        100,
                        $limit
                    )
                )
            );

        return $rows;
    }
}
