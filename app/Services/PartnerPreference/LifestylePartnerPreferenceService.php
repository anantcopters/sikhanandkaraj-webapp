<?php

declare(strict_types=1);

namespace App\Services\PartnerPreference;

use App\Models\MasterLifestyleCategoryModel;
use App\Models\MasterLifestyleOptionModel;
use App\Models\MemberPartnerLifestylePreferenceModel;
use App\Models\MemberPartnerLifestylePreferenceOptionModel;
use App\Models\UserModel;
use App\Support\BooleanValue;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

final class LifestylePartnerPreferenceService
{
    public function __construct(
        private readonly UserModel $userModel,
        private readonly MasterLifestyleCategoryModel
        $categoryModel,
        private readonly MasterLifestyleOptionModel
        $optionModel,
        private readonly MemberPartnerLifestylePreferenceModel
        $preferenceModel,
        private readonly MemberPartnerLifestylePreferenceOptionModel
        $selectionModel,
        private readonly BaseConnection $database
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function getSummaryForUser(
        int $userId
    ): array {
        $this->assertUserExists(
            $userId
        );

        $categories = $this
            ->categoryModel
            ->activeOrdered();

        $preferences = $this
            ->preferenceModel
            ->findForUser(
                $userId
            );

        $preferencesByCategory = [];

        foreach ($preferences as $preference) {
            $categoryId = (int) (
                $preference['lifestyle_category_id']
                ?? 0
            );

            if ($categoryId > 0) {
                $preferencesByCategory[$categoryId] =
                    $preference;
            }
        }

        $allOptions = $this
            ->optionModel
            ->activeOrdered();

        $optionsById = [];

        foreach ($allOptions as $option) {
            $optionId = (int) (
                $option['id']
                ?? 0
            );

            if ($optionId > 0) {
                $optionsById[$optionId] =
                    $option;
            }
        }

        $items = [];

        foreach ($categories as $category) {
            $categoryId = (int) (
                $category['id']
                ?? 0
            );

            if ($categoryId <= 0) {
                continue;
            }

            $preference =
                $preferencesByCategory[$categoryId]
                ?? null;

            $preferenceId = is_array(
                $preference
            )
                ? (int) (
                    $preference['id']
                    ?? 0
                )
                : 0;

            $selectedIds =
                $preferenceId > 0
                ? $this
                ->selectionModel
                ->idsForPreference(
                    $preferenceId
                )
                : [];

            $labels = [];

            foreach ($selectedIds as $selectedId) {
                $label = trim(
                    (string) (
                        $optionsById[$selectedId]['name']
                        ?? ''
                    )
                );

                if ($label !== '') {
                    $labels[] = $label;
                }
            }

            $items[] = [
                'key' =>
                (string) $categoryId,

                'title' =>
                trim(
                    (string) (
                        $category['name']
                        ?? ''
                    )
                ),

                'value' =>
                $labels !== []
                    ? implode(
                        ', ',
                        $labels
                    )
                    : 'Not added',

                'isCompleted' =>
                $selectedIds !== [],

                'isCompulsory' =>
                is_array($preference)
                    && BooleanValue::fromDatabase(
                        $preference['is_compulsory']
                            ?? false
                    ),
            ];
        }

        $completed = count(
            array_filter(
                $items,
                static fn(array $item): bool => ($item['isCompleted'] ?? false)
                    === true
            )
        );

        $total = count($items);

        return [
            'section' => [
                'key' =>
                'lifestyle',

                'title' =>
                'Lifestyle',

                'description' =>
                'Hobbies, music, reading, entertainment, sports and other lifestyle preferences.',

                'icon' =>
                'ri-palette-line text-primary fs-20',

                'isCompleted' =>
                $total > 0
                    && $completed === $total,

                'items' =>
                $items,
            ],

            'completion' => [
                'completed' =>
                $completed,

                'total' =>
                $total,

                'percentage' =>
                $total > 0
                    ? (int) round(
                        (
                            $completed
                            / $total
                        ) * 100
                    )
                    : 0,
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function getCategoryForUser(
        int $userId,
        int $categoryId
    ): array {
        $this->assertUserExists(
            $userId
        );

        $category = $this
            ->activeCategory(
                $categoryId
            );

        $preference = $this
            ->preferenceModel
            ->findForUserAndCategory(
                $userId,
                $categoryId
            );

        $preferenceId = is_array(
            $preference
        )
            ? (int) (
                $preference['id']
                ?? 0
            )
            : 0;

        return [
            'category' =>
            $category,

            'options' =>
            $this->optionsForCategory(
                $categoryId
            ),

            'selectedOptionIds' =>
            $preferenceId > 0
                ? $this
                ->selectionModel
                ->idsForPreference(
                    $preferenceId
                )
                : [],

            'isCompulsory' =>
            is_array($preference)
                && BooleanValue::fromDatabase(
                    $preference['is_compulsory']
                        ?? false
                ),
        ];
    }

    /**
     * @param list<int> $optionIds
     */
    public function saveCategory(
        int $userId,
        int $categoryId,
        array $optionIds,
        bool $isCompulsory
    ): void {
        $this->assertUserExists(
            $userId
        );

        $this->activeCategory(
            $categoryId
        );

        $optionIds = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $optionIds
                    ),
                    static fn(int $optionId): bool =>
                    $optionId > 0
                )
            )
        );

        if ($optionIds === []) {
            throw new DomainException(
                'Please select at least one lifestyle preference.'
            );
        }

        $validOptions = $this
            ->optionsForCategory(
                $categoryId
            );

        $validIds = array_map(
            static fn(array $option): int =>
            (int) $option['id'],
            $validOptions
        );

        if (
            array_diff(
                $optionIds,
                $validIds
            ) !== []
        ) {
            throw new DomainException(
                'One or more selected lifestyle preferences are invalid.'
            );
        }

        $this->database
            ->transException(true);

        $this->database
            ->transStart();

        try {
            $preference = $this
                ->preferenceModel
                ->findForUserAndCategory(
                    $userId,
                    $categoryId
                );

            if (is_array($preference)) {
                $preferenceId = (int) (
                    $preference['id']
                    ?? 0
                );

                $updated = $this
                    ->preferenceModel
                    ->update(
                        $preferenceId,
                        [
                            'is_compulsory' =>
                            $isCompulsory,
                        ]
                    );

                if ($updated === false) {
                    throw new RuntimeException(
                        'The lifestyle preference could not be saved.'
                    );
                }
            } else {
                $insertId = $this
                    ->preferenceModel
                    ->insert(
                        [
                            'user_id' =>
                            $userId,

                            'lifestyle_category_id' =>
                            $categoryId,

                            'is_compulsory' =>
                            $isCompulsory,
                        ],
                        true
                    );

                if (!is_numeric($insertId)) {
                    throw new RuntimeException(
                        'The lifestyle preference could not be created.'
                    );
                }

                $preferenceId =
                    (int) $insertId;
            }

            if (
                !$this
                    ->selectionModel
                    ->replaceSelections(
                        $preferenceId,
                        $optionIds
                    )
            ) {
                throw new RuntimeException(
                    'The lifestyle preference selections could not be saved.'
                );
            }

            $this->database
                ->transComplete();
        } catch (Throwable $exception) {
            $this->database
                ->transRollback();

            throw $exception;
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function activeCategory(
        int $categoryId
    ): array {
        if ($categoryId <= 0) {
            throw new DomainException(
                'Please select a valid lifestyle category.'
            );
        }

        $category = $this
            ->categoryModel
            ->where(
                'id',
                $categoryId
            )
            ->where(
                'is_active',
                true
            )
            ->first();

        if (!is_array($category)) {
            throw new DomainException(
                'Please select a valid lifestyle category.'
            );
        }

        return $category;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function optionsForCategory(
        int $categoryId
    ): array {
        return array_values(
            $this
                ->optionModel
                ->where(
                    'lifestyle_category_id',
                    $categoryId
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'display_order',
                    'ASC'
                )
                ->orderBy(
                    'id',
                    'ASC'
                )
                ->findAll()
        );
    }

    private function assertUserExists(
        int $userId
    ): void {
        if (
            $userId <= 0
            || !is_array(
                $this->userModel
                    ->find($userId)
            )
        ) {
            throw new DomainException(
                'Member account could not be found.'
            );
        }
    }
}
