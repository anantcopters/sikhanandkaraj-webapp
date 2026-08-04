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
     * @return list<int|string>
     */
    public function selectedValues(int $parentId): array
    {
        $rows = $this
            ->select($this->valueField)
            ->where(
                $this->parentField,
                $parentId
            )
            ->findAll();

        return array_values(
            array_map(
                fn(array $row): int|string =>
                is_numeric($row[$this->valueField])
                    ? (int) $row[$this->valueField]
                    : (string) $row[$this->valueField],
                $rows
            )
        );
    }

    /**
     * Replace all selections for one parent.
     *
     * @param list<int|string> $values
     */
    public function replaceSelections(
        int $parentId,
        array $values
    ): bool {
        if (
            $this
            ->where(
                $this->parentField,
                $parentId
            )
            ->delete() === false
        ) {
            return false;
        }

        if ($values === []) {
            return true;
        }

        $rows = array_map(
            fn(int|string $value): array => [
                $this->parentField => $parentId,
                $this->valueField => $value,
            ],
            $values
        );

        return $this->insertBatch($rows) !== false;
    }
}
