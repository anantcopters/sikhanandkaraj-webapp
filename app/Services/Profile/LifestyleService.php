<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\MasterLifestyleCategoryModel;
use App\Models\MasterLifestyleOptionModel;
use App\Models\MemberLifestyleOptionModel;
use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

final class LifestyleService
{
    public function __construct(
        private readonly UserModel $userModel,
        private readonly MasterLifestyleCategoryModel $categoryModel,
        private readonly MasterLifestyleOptionModel $optionModel,
        private readonly MemberLifestyleOptionModel $memberOptionModel,
        private readonly BaseConnection $database
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getForUser(int $userId): array
    {
        $user = $this->userModel->find($userId);

        if (!is_array($user)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $categories = $this->categoryModel->activeOrdered();
        $options = $this->optionModel->activeOrdered();
        $selectedIds = $this
            ->memberOptionModel
            ->selectedIdsForUser($userId);

        $optionsByCategory = [];

        foreach ($options as $option) {
            $categoryId = (int) (
                $option['lifestyle_category_id'] ?? 0
            );

            if ($categoryId <= 0) {
                continue;
            }

            $optionsByCategory[$categoryId][] = $option;
        }

        $selectedDetails = $this
            ->memberOptionModel
            ->selectedDetailsForUser($userId);

        return [
            'user' => $user,
            'categories' => $categories,
            'optionsByCategory' => $optionsByCategory,
            'selectedOptionIds' => $selectedIds,
            'selectedDetails' => $selectedDetails,
            'completion' => $this->calculateCompletion(
                $categories,
                $selectedDetails
            ),
        ];
    }

    /**
     * Replace the member's Lifestyle selections atomically.
     *
     * @param list<int|string> $submittedOptionIds
     */
    public function save(
        int $userId,
        array $submittedOptionIds
    ): void {
        $user = $this->userModel->find($userId);

        if (!is_array($user)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $optionIds = $this->normalizeOptionIds(
            $submittedOptionIds
        );

        $this->assertValidOptions($optionIds);

        $this->database->transException(true);
        $this->database->transStart();

        try {
            $deleted = $this
                ->memberOptionModel
                ->where('user_id', $userId)
                ->delete();

            if ($deleted === false) {
                throw new RuntimeException(
                    'Existing lifestyle selections could not be cleared.'
                );
            }

            if ($optionIds !== []) {
                $rows = array_map(
                    static fn(int $optionId): array => [
                        'user_id' => $userId,
                        'lifestyle_option_id' => $optionId,
                        'created_at' => date('Y-m-d H:i:s'),
                    ],
                    $optionIds
                );

                $inserted = $this
                    ->memberOptionModel
                    ->insertBatch($rows);

                if ($inserted === false) {
                    throw new RuntimeException(
                        'Lifestyle selections could not be saved.'
                    );
                }
            }

            $this->database->transComplete();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * @param list<int|string> $submittedOptionIds
     *
     * @return list<int>
     */
    private function normalizeOptionIds(
        array $submittedOptionIds
    ): array {
        $optionIds = [];

        foreach ($submittedOptionIds as $submittedOptionId) {
            $value = trim((string) $submittedOptionId);

            if ($value === '') {
                continue;
            }

            if (!ctype_digit($value) || (int) $value <= 0) {
                throw new DomainException(
                    'One or more lifestyle selections are invalid.'
                );
            }

            $optionIds[] = (int) $value;
        }

        $optionIds = array_values(array_unique($optionIds));
        sort($optionIds);

        return $optionIds;
    }

    /**
     * @param list<int> $optionIds
     */
    private function assertValidOptions(array $optionIds): void
    {
        if ($optionIds === []) {
            return;
        }

        $activeOptions = $this->optionModel->activeByIds(
            $optionIds
        );

        if (count($activeOptions) !== count($optionIds)) {
            throw new DomainException(
                'One or more selected lifestyle options are unavailable.'
            );
        }
    }

    /**
     * Completion means at least one selection in each active category.
     *
     * @param list<array<string, mixed>> $categories
     * @param list<array<string, mixed>> $selectedDetails
     *
     * @return array{
     *     completed: int,
     *     total: int,
     *     percentage: int
     * }
     */
    private function calculateCompletion(
        array $categories,
        array $selectedDetails
    ): array {
        $selectedCategoryIds = [];

        foreach ($selectedDetails as $selectedDetail) {
            $categoryId = (int) (
                $selectedDetail['category_id'] ?? 0
            );

            if ($categoryId > 0) {
                $selectedCategoryIds[$categoryId] = true;
            }
        }

        $completed = count($selectedCategoryIds);
        $total = count($categories);

        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total > 0
                ? (int) round(($completed / $total) * 100)
                : 0,
        ];
    }
}
