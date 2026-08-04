<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use InvalidArgumentException;

/**
 * Handles partner-preference junction tables.
 */
final class PartnerPreferenceSelectionModel extends Model
{
    /**
     * @var array<string, array{
     *     table: string,
     *     parent: string,
     *     value: string
     * }>
     */
    private const DEFINITIONS = [
        'community' => [
            'table' =>
            'member_partner_preference_communities',

            'parent' =>
            'partner_religious_preference_id',

            'value' =>
            'community_id',
        ],

        'education' => [
            'table' =>
            'member_partner_preference_educations',

            'parent' =>
            'partner_professional_preference_id',

            'value' =>
            'education_id',
        ],

        'employed_in' => [
            'table' =>
            'member_partner_preference_employment_types',

            'parent' =>
            'partner_professional_preference_id',

            'value' =>
            'employed_in',
        ],

        'occupation' => [
            'table' =>
            'member_partner_preference_occupations',

            'parent' =>
            'partner_professional_preference_id',

            'value' =>
            'occupation_id',
        ],

        'annual_income' => [
            'table' =>
            'member_partner_preference_annual_incomes',

            'parent' =>
            'partner_professional_preference_id',

            'value' =>
            'annual_income_id',
        ],

        'state' => [
            'table' =>
            'member_partner_preference_states',

            'parent' =>
            'partner_location_preference_id',

            'value' =>
            'state_id',
        ],

        'city' => [
            'table' =>
            'member_partner_preference_cities',

            'parent' =>
            'partner_location_preference_id',

            'value' =>
            'city_id',
        ],
    ];

    private string $parentField;

    private string $valueField;

    public function __construct(
        string $type,
        ?ConnectionInterface $database = null
    ) {
        if (!isset(self::DEFINITIONS[$type])) {
            throw new InvalidArgumentException(
                'Unsupported partner preference selection model.'
            );
        }

        $definition = self::DEFINITIONS[$type];

        $this->table = $definition['table'];

        $this->parentField = $definition['parent'];

        $this->valueField = $definition['value'];

        $this->primaryKey = 'id';

        $this->returnType = 'array';

        $this->useAutoIncrement = true;

        $this->protectFields = true;

        $this->allowedFields = [
            $this->parentField,
            $this->valueField,
        ];

        $this->useTimestamps = false;

        $this->skipValidation = true;

        parent::__construct($database);
    }

    /**
     * Return all selected values for a preference parent.
     *
     * @return list<int|string>
     */
    public function selectedValues(int $parentId): array
    {
        if ($parentId <= 0) {
            return [];
        }

        $rows = $this
            ->select($this->valueField)
            ->where(
                $this->parentField,
                $parentId
            )
            ->findAll();

        return array_values(
            array_map(
                function (array $row): int|string {
                    $value = $row[$this->valueField];

                    return is_numeric($value)
                        ? (int) $value
                        : (string) $value;
                },
                $rows
            )
        );
    }

    /**
     * Replace all selections for one parent.
     *
     * Must be called inside the service transaction.
     *
     * @param list<int|string> $values
     */
    public function replaceSelections(
        int $parentId,
        array $values
    ): bool {
        if ($parentId <= 0) {
            return false;
        }

        $deleted = $this
            ->where(
                $this->parentField,
                $parentId
            )
            ->delete();

        if ($deleted === false) {
            return false;
        }

        $normalizedValues = array_values(
            array_unique($values)
        );

        if ($normalizedValues === []) {
            return true;
        }

        $rows = array_map(
            fn(int|string $value): array => [
                $this->parentField =>
                $parentId,

                $this->valueField =>
                $value,
            ],
            $normalizedValues
        );

        return $this->insertBatch($rows) !== false;
    }
}
