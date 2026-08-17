<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MasterCountryModel extends Model
{
    protected $table = 'master_countries';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'iso_code',
        'name',
        'phone_code',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $skipValidation = true;

    /**
     * Return active countries with India first and the remainder alphabetical.
     *
     * @return list<array<string, mixed>>
     */
    public function activeOptions(): array
    {
        return $this
            ->select('id, iso_code, name, phone_code')
            ->where('is_active', true)
            ->orderBy(
                "CASE WHEN iso_code = 'IN' THEN 0 ELSE 1 END",
                'ASC',
                false
            )
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    /**
     * Return one active country.
     *
     * @return array<string, mixed>|null
     */
    public function findActive(int $countryId): ?array
    {
        if ($countryId <= 0) {
            return null;
        }

        $record = $this
            ->where('id', $countryId)
            ->where('is_active', true)
            ->first();

        return is_array($record) ? $record : null;
    }

    /**
     * Return active India master record.
     *
     * @return array<string, mixed>|null
     */
    public function findIndia(): ?array
    {
        $record = $this
            ->where('iso_code', 'IN')
            ->where('is_active', true)
            ->first();

        return is_array($record) ? $record : null;
    }
}
