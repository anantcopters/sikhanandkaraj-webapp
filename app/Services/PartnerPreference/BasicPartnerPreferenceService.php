<?php

declare(strict_types=1);

namespace App\Services\PartnerPreference;

use App\Models\MemberPartnerBasicPreferenceModel;
use App\Models\MemberPartnerPreferenceDrinkingHabitModel;
use App\Models\MemberPartnerPreferenceEatingHabitModel;
use App\Models\MemberPartnerPreferenceMotherTongueModel;
use App\Models\UserModel;
use App\Services\Profile\ProfileMasterDataService;
use App\Support\BooleanValue;
use App\Support\PartnerPreference\BasicPreferenceItem;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Reads and saves the member's Basic Partner Preference aggregate.
 */
final class BasicPartnerPreferenceService
{
    public function __construct(
        private readonly UserModel $userModel,
        private readonly MemberPartnerBasicPreferenceModel
        $preferenceModel,
        private readonly MemberPartnerPreferenceMotherTongueModel
        $motherTongueModel,
        private readonly MemberPartnerPreferenceEatingHabitModel
        $eatingHabitModel,
        private readonly MemberPartnerPreferenceDrinkingHabitModel
        $drinkingHabitModel,
        private readonly ProfileMasterDataService $masterDataService,
        private readonly BaseConnection $database
    ) {}

    /**
     * Return data used by the main Partner Preference page.
     *
     * @return array<string, mixed>
     */
    public function getSummaryForUser(int $userId): array
    {
        $this->assertUserExists($userId);

        $preference = $this
            ->preferenceModel
            ->findForUser($userId);

        $selected = $this->selectedIds($preference);

        $masterData = $this
            ->masterDataService
            ->partnerBasicPreferenceOptions();

        $items = $this->buildSummaryItems(
            $preference,
            $selected,
            $masterData
        );

        $completedItems = count(
            array_filter(
                $items,
                static fn(array $item): bool =>
                $item['isCompleted'] === true
            )
        );

        return [
            'preference' => $preference,
            'items' => $items,
            'sections' => [
                [
                    'key' => 'basic',
                    'title' => 'Basic',
                    'description' =>
                    'Age, height, marital status and lifestyle preferences.',
                    'icon' => 'ri-user-heart-line text-primary',
                    'isCompleted' =>
                    $completedItems === count($items),
                    'items' => $items,
                ]
            ],
            'completion' => [
                'completed' => $completedItems,
                'total' => count($items),
                'percentage' => (int) round(
                    ($completedItems / count($items)) * 100
                ),
            ],
        ];
    }

    /**
     * Return data used by one item edit page.
     *
     * @return array<string, mixed>
     */
    public function getItemForUser(
        int $userId,
        string $item
    ): array {
        $this->assertSupportedItem($item);
        $this->assertUserExists($userId);

        $preference = $this
            ->preferenceModel
            ->findForUser($userId);

        return [
            'item' => $item,
            'itemTitle' =>
            BasicPreferenceItem::title($item),
            'compulsoryText' =>
            BasicPreferenceItem::compulsoryText($item),
            'preference' => $preference,
            'selectedIds' =>
            $this->selectedIds($preference),
            'masterData' =>
            $this->masterDataService
                ->partnerBasicPreferenceOptions(),
        ];
    }

    /**
     * Save one Basic Partner Preference item.
     *
     * @param array<string, mixed> $data
     */
    public function saveItem(
        int $userId,
        string $item,
        array $data
    ): void {
        $this->assertSupportedItem($item);
        $this->assertUserExists($userId);

        $normalizedData = $this->normalizeItemData(
            $item,
            $data
        );

        $this->assertValidItemData(
            $item,
            $normalizedData
        );

        $this->database->transException(true);
        $this->database->transStart();

        try {
            $preferenceId = $this->ensurePreferenceRow(
                $userId
            );

            $this->saveNormalizedItem(
                $preferenceId,
                $item,
                $normalizedData
            );

            $this->database->transComplete();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Ensure the member has one parent preference row.
     */
    private function ensurePreferenceRow(int $userId): int
    {
        $existing = $this
            ->preferenceModel
            ->findForUser($userId);

        if (is_array($existing)) {
            return (int) $existing['id'];
        }

        $insertId = $this->preferenceModel->insert(
            [
                'user_id' => $userId,
            ],
            true
        );

        if (!is_numeric($insertId)) {
            throw new RuntimeException(
                'The partner preference could not be created.'
            );
        }

        return (int) $insertId;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveNormalizedItem(
        int $preferenceId,
        string $item,
        array $data
    ): void {
        $updated = match ($item) {
            BasicPreferenceItem::AGE =>
            $this->preferenceModel->update(
                $preferenceId,
                [
                    'age_from' => $data['age_from'],
                    'age_to' => $data['age_to'],
                    'age_match_mode' =>
                    $data['is_compulsory'],
                ]
            ),

            BasicPreferenceItem::HEIGHT =>
            $this->preferenceModel->update(
                $preferenceId,
                [
                    'height_from_id' =>
                    $data['height_from_id'],
                    'height_to_id' =>
                    $data['height_to_id'],
                    'height_match_mode' =>
                    $data['is_compulsory'],
                ]
            ),

            BasicPreferenceItem::MARITAL_STATUS =>
            $this->preferenceModel->update(
                $preferenceId,
                [
                    'marital_status_id' =>
                    $data['marital_status_id'],
                    'marital_status_match_mode' =>
                    $data['is_compulsory'],
                ]
            ),

            BasicPreferenceItem::HAVE_CHILDREN =>
            $this->preferenceModel->update(
                $preferenceId,
                [
                    'have_children' =>
                    $data['have_children'],
                    'have_children_match_mode' =>
                    $data['is_compulsory'],
                ]
            ),

            BasicPreferenceItem::AMRITDHARI =>
            $this->preferenceModel->update(
                $preferenceId,
                [
                    'amritdhari' =>
                    $data['amritdhari'],

                    'amritdhari_match_mode' =>
                    $data['is_compulsory'],
                ]
            ),

            BasicPreferenceItem::PHYSICAL_STATUS =>
            $this->preferenceModel->update(
                $preferenceId,
                [
                    'physical_status_id' =>
                    $data['physical_status_id'],
                    'physical_status_match_mode' =>
                    $data['is_compulsory'],
                ]
            ),

            BasicPreferenceItem::MOTHER_TONGUE =>
            $this->saveMultiSelect(
                $preferenceId,
                $data['mother_tongue_ids'],
                $this->motherTongueModel,
                'mother_tongue_id',
                'mother_tongue_match_mode',
                $data['is_compulsory']
            ),

            BasicPreferenceItem::EATING_HABITS =>
            $this->saveMultiSelect(
                $preferenceId,
                $data['eating_habit_ids'],
                $this->eatingHabitModel,
                'eating_habit_id',
                'eating_habit_match_mode',
                $data['is_compulsory']
            ),

            BasicPreferenceItem::DRINKING_HABITS =>
            $this->saveMultiSelect(
                $preferenceId,
                $data['drinking_habit_ids'],
                $this->drinkingHabitModel,
                'drinking_habit_id',
                'drinking_habit_match_mode',
                $data['is_compulsory']
            ),

            default => false,
        };

        if ($updated === false) {
            throw new RuntimeException(
                'The partner preference could not be saved.'
            );
        }
    }

    /**
     * Replace one multi-select collection inside the active transaction.
     *
     * @param list<int> $selectedIds
     */
    private function saveMultiSelect(
        int $preferenceId,
        array $selectedIds,
        \CodeIgniter\Model $model,
        string $masterIdField,
        string $compulsoryField,
        bool $isCompulsory
    ): bool {
        $deleted = $model
            ->where(
                'partner_basic_preference_id',
                $preferenceId
            )
            ->delete();

        if ($deleted === false) {
            return false;
        }

        if ($selectedIds !== []) {
            $rows = array_map(
                static fn(int $selectedId): array => [
                    'partner_basic_preference_id' =>
                    $preferenceId,
                    $masterIdField => $selectedId,
                ],
                $selectedIds
            );

            if ($model->insertBatch($rows) === false) {
                return false;
            }
        }

        return $this->preferenceModel->update(
            $preferenceId,
            [
                $compulsoryField => $isCompulsory,
            ]
        );
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function normalizeItemData(
        string $item,
        array $data
    ): array {
        $isCompulsory = BooleanValue::fromDatabase(
            $data['is_compulsory'] ?? false
        );

        return match ($item) {
            BasicPreferenceItem::AGE => [
                'age_from' =>
                (int) $data['age_from'],
                'age_to' =>
                (int) $data['age_to'],
                'is_compulsory' => $isCompulsory,
            ],

            BasicPreferenceItem::HEIGHT => [
                'height_from_id' =>
                (int) $data['height_from_id'],
                'height_to_id' =>
                (int) $data['height_to_id'],
                'is_compulsory' => $isCompulsory,
            ],

            BasicPreferenceItem::MARITAL_STATUS => [
                'marital_status_id' =>
                (int) $data['marital_status_id'],
                'is_compulsory' => $isCompulsory,
            ],

            BasicPreferenceItem::HAVE_CHILDREN => [
                'have_children' =>
                BooleanValue::fromDatabase(
                    $data['have_children']
                ),
                'is_compulsory' => $isCompulsory,
            ],

            BasicPreferenceItem::AMRITDHARI => [
                'amritdhari' =>
                BooleanValue::fromDatabase(
                    $data['amritdhari']
                ),

                'is_compulsory' =>
                $isCompulsory,
            ],

            BasicPreferenceItem::MOTHER_TONGUE => [
                'mother_tongue_ids' =>
                $this->normalizeIds(
                    $data['mother_tongue_ids'] ?? []
                ),
                'is_compulsory' => $isCompulsory,
            ],

            BasicPreferenceItem::PHYSICAL_STATUS => [
                'physical_status_id' =>
                (int) $data['physical_status_id'],
                'is_compulsory' => $isCompulsory,
            ],

            BasicPreferenceItem::EATING_HABITS => [
                'eating_habit_ids' =>
                $this->normalizeIds(
                    $data['eating_habit_ids'] ?? []
                ),
                'is_compulsory' => $isCompulsory,
            ],

            BasicPreferenceItem::DRINKING_HABITS => [
                'drinking_habit_ids' =>
                $this->normalizeIds(
                    $data['drinking_habit_ids'] ?? []
                ),
                'is_compulsory' => $isCompulsory,
            ],

            default => [],
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertValidItemData(
        string $item,
        array $data
    ): void {
        if (
            $item === BasicPreferenceItem::AGE
            && $data['age_from'] > $data['age_to']
        ) {
            throw new DomainException(
                'Minimum age cannot be greater than maximum age.'
            );
        }

        if (
            $item === BasicPreferenceItem::HEIGHT
            && $data['height_from_id']
            > $data['height_to_id']
        ) {
            throw new DomainException(
                'Minimum height cannot be greater than maximum height.'
            );
        }

        $masterData = $this
            ->masterDataService
            ->partnerBasicPreferenceOptions();

        match ($item) {
            BasicPreferenceItem::HEIGHT =>
            $this->assertIdsExist(
                [
                    $data['height_from_id'],
                    $data['height_to_id'],
                ],
                $masterData['heights']
            ),

            BasicPreferenceItem::MARITAL_STATUS =>
            $this->assertIdsExist(
                [$data['marital_status_id']],
                $masterData['maritalStatuses']
            ),

            BasicPreferenceItem::MOTHER_TONGUE =>
            $this->assertIdsExist(
                $data['mother_tongue_ids'],
                $masterData['motherTongues']
            ),

            BasicPreferenceItem::PHYSICAL_STATUS =>
            $this->assertIdsExist(
                [$data['physical_status_id']],
                $masterData['physicalStatuses']
            ),

            BasicPreferenceItem::EATING_HABITS =>
            $this->assertIdsExist(
                $data['eating_habit_ids'],
                $masterData['eatingHabits']
            ),

            BasicPreferenceItem::DRINKING_HABITS =>
            $this->assertIdsExist(
                $data['drinking_habit_ids'],
                $masterData['drinkingHabits']
            ),

            default => null,
        };
    }

    /**
     * @param list<int>                 $submittedIds
     * @param list<array<string, mixed>> $masterRows
     */
    private function assertIdsExist(
        array $submittedIds,
        array $masterRows
    ): void {
        $validIds = array_map(
            static fn(array $row): int =>
            (int) $row['id'],
            $masterRows
        );

        if (
            array_diff(
                $submittedIds,
                $validIds
            ) !== []
        ) {
            throw new DomainException(
                'One or more selected preference values are invalid.'
            );
        }
    }

    /**
     * @param mixed $values
     *
     * @return list<int>
     */
    private function normalizeIds(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $ids = [];

        foreach ($values as $value) {
            $stringValue = trim((string) $value);

            if (
                $stringValue === ''
                || !ctype_digit($stringValue)
                || (int) $stringValue <= 0
            ) {
                continue;
            }

            $ids[] = (int) $stringValue;
        }

        return array_values(
            array_unique($ids)
        );
    }

    /**
     * @param array<string, mixed>|null $preference
     *
     * @return array<string, list<int>>
     */
    private function selectedIds(?array $preference): array
    {
        if (!is_array($preference)) {
            return [
                'motherTongues' => [],
                'eatingHabits' => [],
                'drinkingHabits' => [],
            ];
        }

        $preferenceId = (int) $preference['id'];

        return [
            'motherTongues' =>
            $this->motherTongueModel
                ->idsForPreference($preferenceId),

            'eatingHabits' =>
            $this->eatingHabitModel
                ->idsForPreference($preferenceId),

            'drinkingHabits' =>
            $this->drinkingHabitModel
                ->idsForPreference($preferenceId),
        ];
    }

    /**
     * @param array<string, mixed>|null $preference
     * @param array<string, list<int>>  $selected
     * @param array<string, mixed>      $masterData
     *
     * @return list<array<string, mixed>>
     */
    private function buildSummaryItems(
        ?array $preference,
        array $selected,
        array $masterData
    ): array {
        $preference ??= [];

        return [
            $this->summaryItem(
                BasicPreferenceItem::AGE,
                isset(
                    $preference['age_from'],
                    $preference['age_to']
                ),
                isset(
                    $preference['age_from'],
                    $preference['age_to']
                )
                    ? sprintf(
                        '%d to %d years',
                        (int) $preference['age_from'],
                        (int) $preference['age_to']
                    )
                    : null,
                $preference['age_match_mode']
                    ?? false
            ),

            $this->summaryItem(
                BasicPreferenceItem::HEIGHT,
                isset(
                    $preference['height_from_id'],
                    $preference['height_to_id']
                ),
                $this->rangeLabel(
                    $preference['height_from_id'] ?? null,
                    $preference['height_to_id'] ?? null,
                    $masterData['heights']
                ),
                $preference['height_match_mode']
                    ?? false
            ),

            $this->summaryItem(
                BasicPreferenceItem::MARITAL_STATUS,
                isset($preference['marital_status_id']),
                $this->singleLabel(
                    $preference['marital_status_id'] ?? null,
                    $masterData['maritalStatuses']
                ),
                $preference['marital_status_match_mode'] ?? false
            ),

            $this->summaryItem(
                BasicPreferenceItem::HAVE_CHILDREN,
                array_key_exists(
                    'have_children',
                    $preference
                )
                    && $preference['have_children'] !== null,
                $this->booleanLabel(
                    $preference['have_children'] ?? null
                ),
                $preference['have_children_match_mode'] ?? false
            ),

            $this->summaryItem(
                BasicPreferenceItem::AMRITDHARI,

                array_key_exists(
                    'amritdhari',
                    $preference
                )
                    && $preference['amritdhari'] !== null,

                array_key_exists(
                    'amritdhari',
                    $preference
                )
                    && $preference['amritdhari'] !== null
                    ? (
                        BooleanValue::fromDatabase(
                            $preference['amritdhari']
                        )
                        ? 'Yes'
                        : 'No'
                    )
                    : null,

                $preference['amritdhari_match_mode']
                    ?? false
            ),

            $this->summaryItem(
                BasicPreferenceItem::MOTHER_TONGUE,
                $selected['motherTongues'] !== [],
                $this->multipleLabels(
                    $selected['motherTongues'],
                    $masterData['motherTongues']
                ),
                $preference['mother_tongue_match_mode'] ?? false
            ),

            $this->summaryItem(
                BasicPreferenceItem::PHYSICAL_STATUS,
                isset($preference['physical_status_id']),
                $this->singleLabel(
                    $preference['physical_status_id'] ?? null,
                    $masterData['physicalStatuses']
                ),
                $preference['physical_status_match_mode'] ?? false
            ),

            $this->summaryItem(
                BasicPreferenceItem::EATING_HABITS,
                $selected['eatingHabits'] !== [],
                $this->multipleLabels(
                    $selected['eatingHabits'],
                    $masterData['eatingHabits']
                ),
                $preference['eating_habit_match_mode'] ?? false
            ),

            $this->summaryItem(
                BasicPreferenceItem::DRINKING_HABITS,
                $selected['drinkingHabits'] !== [],
                $this->multipleLabels(
                    $selected['drinkingHabits'],
                    $masterData['drinkingHabits']
                ),
                $preference['drinking_habit_match_mode'] ?? false
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summaryItem(
        string $key,
        bool $isCompleted,
        ?string $value,
        mixed $isCompulsory
    ): array {
        return [
            'key' => $key,
            'title' =>
            BasicPreferenceItem::title($key),
            'value' => $value ?? 'Not added',
            'isCompleted' => $isCompleted,
            'isCompulsory' =>
            BooleanValue::fromDatabase($isCompulsory),
        ];
    }

    /**
     * Resolve the readable label of one selected master record.
     *
     * Most master tables use "name". Height master data uses
     * "display_name", so both supported keys are handled here.
     *
     * @param list<array<string, mixed>> $rows
     */
    private function singleLabel(
        mixed $selectedId,
        array $rows
    ): ?string {
        if (!is_numeric($selectedId)) {
            return null;
        }

        $resolvedSelectedId = (int) $selectedId;

        foreach ($rows as $row) {
            if (
                !isset($row['id'])
                || (int) $row['id']
                !== $resolvedSelectedId
            ) {
                continue;
            }

            $label = trim(
                (string) (
                    $row['name']
                    ?? $row['display_name']
                    ?? ''
                )
            );

            return $label !== ''
                ? $label
                : null;
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function rangeLabel(
        mixed $fromId,
        mixed $toId,
        array $rows
    ): ?string {
        $from = $this->singleLabel($fromId, $rows);
        $to = $this->singleLabel($toId, $rows);

        if ($from === null || $to === null) {
            return null;
        }

        return $from . ' to ' . $to;
    }

    /**
     * Resolve readable labels for multiple selected master records.
     *
     * @param list<int>                  $selectedIds
     * @param list<array<string, mixed>> $rows
     */
    private function multipleLabels(
        array $selectedIds,
        array $rows
    ): ?string {
        if ($selectedIds === []) {
            return null;
        }

        $labels = [];

        foreach ($rows as $row) {
            if (
                !isset($row['id'])
                || !in_array(
                    (int) $row['id'],
                    $selectedIds,
                    true
                )
            ) {
                continue;
            }

            $label = trim(
                (string) (
                    $row['name']
                    ?? $row['display_name']
                    ?? ''
                )
            );

            if ($label !== '') {
                $labels[] = $label;
            }
        }

        return $labels !== []
            ? implode(', ', $labels)
            : null;
    }

    private function booleanLabel(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return BooleanValue::fromDatabase($value)
            ? 'Yes'
            : 'No';
    }

    private function assertSupportedItem(string $item): void
    {
        if (!BasicPreferenceItem::isValid($item)) {
            throw new DomainException(
                'The requested partner preference is invalid.'
            );
        }
    }

    private function assertUserExists(int $userId): void
    {
        if (!is_array($this->userModel->find($userId))) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }
    }
}
