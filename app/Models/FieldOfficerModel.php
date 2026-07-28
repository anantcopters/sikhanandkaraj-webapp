<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Model;

/**
 * Persistence model for Field Officer records.
 */
final class FieldOfficerModel extends Model
{
    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    protected $table = 'field_officers';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'officer_code',
        'full_name',
        'mobile_number',
        'country_id',
        'state_id',
        'city_id',
        'address',
        'upi_id',
        'account_status',
        'activated_at',
        'deactivated_at',
        'created_by',
        'updated_by',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $deletedField = 'deleted_at';

    protected $skipValidation = true;

    public function __construct(
        ?BaseConnection $database = null
    ) {
        parent::__construct($database);
    }

    /**
     * Return Field Officers with resolved location names.
     *
     * @return list<array<string, mixed>>
     */
    public function listWithLocation(): array
    {
        return $this
            ->select([
                'field_officers.id',
                'field_officers.officer_code',
                'field_officers.full_name',
                'field_officers.mobile_number',
                'field_officers.country_id',
                'field_officers.state_id',
                'field_officers.city_id',
                'field_officers.address',
                'field_officers.upi_id',
                'field_officers.account_status',
                'field_officers.activated_at',
                'field_officers.deactivated_at',
                'field_officers.created_at',
                'master_countries.name AS country_name',
                'master_states.name AS state_name',
                'master_cities.name AS city_name',
            ])
            ->join(
                'master_countries',
                'master_countries.id = '
                    . 'field_officers.country_id',
                'inner'
            )
            ->join(
                'master_states',
                'master_states.id = '
                    . 'field_officers.state_id',
                'inner'
            )
            ->join(
                'master_cities',
                'master_cities.id = '
                    . 'field_officers.city_id',
                'inner'
            )
            ->orderBy(
                'field_officers.created_at',
                'DESC'
            )
            ->findAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findActiveRecord(
        int $fieldOfficerId
    ): ?array {
        $record = $this->find(
            $fieldOfficerId
        );

        return is_array($record)
            ? $record
            : null;
    }

    public function mobileExists(
        string $mobileNumber,
        ?int $exceptId = null
    ): bool {
        $builder = $this
            ->where(
                'mobile_number',
                $mobileNumber
            );

        if ($exceptId !== null) {
            $builder->where(
                'id !=',
                $exceptId
            );
        }

        return $builder->first() !== null;
    }
}
