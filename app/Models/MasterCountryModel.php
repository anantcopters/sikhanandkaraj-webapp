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
