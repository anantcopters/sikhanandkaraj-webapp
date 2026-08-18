<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MasterStateModel extends Model
{
    protected $table = 'master_states';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'country_id',
        'code',
        'name',
        'display_order',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $skipValidation = true;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeForCountry(int $countryId): array
    {
        return $this
            ->select('id, code, name')
            ->where('country_id', $countryId)
            ->where('is_active', true)
            ->orderBy('display_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    /**
     * Return one active state only when its country is also active.
     *
     * @return array<string, mixed>|null
     */
    public function findActiveWithCountry(int $stateId): ?array
    {
        if ($stateId <= 0) {
            return null;
        }

        $record = $this
            ->select('master_states.id, master_states.country_id')
            ->join(
                'master_countries',
                'master_countries.id = master_states.country_id',
                'inner'
            )
            ->where('master_states.id', $stateId)
            ->where('master_states.is_active', true)
            ->where('master_countries.is_active', true)
            ->first();

        return is_array($record) ? $record : null;
    }

    /**
     * Return active states across active countries for Search and Partner
     * Preference, including country metadata for unambiguous labels.
     *
     * @return list<array<string, mixed>>
     */
    public function activeAcrossCountries(): array
    {
        return $this
            ->select(
                'master_states.id, master_states.country_id, '
                    . 'master_states.code, '
                    . 'master_states.name AS state_name, '
                    . "master_states.name || ', ' || master_countries.name AS name, "
                    . 'master_countries.name AS country_name',
                false
            )
            ->join(
                'master_countries',
                'master_countries.id = master_states.country_id',
                'inner'
            )
            ->where('master_states.is_active', true)
            ->where('master_countries.is_active', true)
            ->orderBy(
                "CASE WHEN master_countries.iso_code = 'IN' THEN 0 ELSE 1 END",
                'ASC',
                false
            )
            ->orderBy('master_countries.name', 'ASC')
            ->orderBy('master_states.display_order', 'ASC')
            ->orderBy('master_states.name', 'ASC')
            ->findAll();
    }

    /**
     * Return active states belonging to one or more active countries.
     *
     * An empty country list returns states across every active country.
     *
     * @param list<int> $countryIds
     *
     * @return list<array<string, mixed>>
     */
    public function activeForCountries(
        array $countryIds
    ): array {
        $countryIds =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'intval',
                            $countryIds
                        ),
                        static fn(
                            int $countryId
                        ): bool =>
                        $countryId > 0
                    )
                )
            );

        /*
     * Search treats an empty Country selection as Any Country.
     */
        if ($countryIds === []) {
            return $this
                ->activeAcrossCountries();
        }

        return $this
            ->select(
                'master_states.id, '
                    . 'master_states.country_id, '
                    . 'master_states.code, '
                    . 'master_states.name AS state_name, '
                    . "master_states.name || ', ' "
                    . '|| master_countries.name AS name, '
                    . 'master_countries.name AS country_name',
                false
            )
            ->join(
                'master_countries',
                'master_countries.id '
                    . '= master_states.country_id',
                'inner'
            )
            ->whereIn(
                'master_states.country_id',
                $countryIds
            )
            ->where(
                'master_states.is_active',
                true
            )
            ->where(
                'master_countries.is_active',
                true
            )
            ->orderBy(
                "CASE WHEN master_countries.iso_code = 'IN' "
                    . 'THEN 0 ELSE 1 END',
                'ASC',
                false
            )
            ->orderBy(
                'master_countries.name',
                'ASC'
            )
            ->orderBy(
                'master_states.display_order',
                'ASC'
            )
            ->orderBy(
                'master_states.name',
                'ASC'
            )
            ->findAll();
    }
}
