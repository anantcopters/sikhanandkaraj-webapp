<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $categories
 * @var array<int, list<array<string, mixed>>> $optionsByCategory
 * @var list<int> $selectedOptionIds
 * @var array<string, string> $validationErrors
 */

$categories = isset($categories) && is_array($categories)
    ? $categories
    : [];

$optionsByCategory = isset($optionsByCategory)
    && is_array($optionsByCategory)
    ? $optionsByCategory
    : [];

$selectedOptionIds = isset($selectedOptionIds)
    && is_array($selectedOptionIds)
    ? array_map('intval', $selectedOptionIds)
    : [];

$validationErrors = isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$submittedIds = old('lifestyle_option_ids');

if (is_array($submittedIds)) {
    $selectedOptionIds = array_map(
        'intval',
        $submittedIds
    );
}
?>

<form
    action="<?= url_to('web.profile.lifestyle.update') ?>"
    method="post"
    id="lifestyle-form"
    novalidate>

    <?= csrf_field() ?>

    <div class="profile-section-intro mb-4">
        <h3 class="fs-16 fw-semibold mb-1">
            Choose what describes you
        </h3>

        <p class="text-muted fs-13 mb-0">
            You may select multiple options under every category.
            Click a selected tag again to remove it.
        </p>
    </div>

    <?php if ($categories === []): ?>
        <div class="alert alert-warning mb-0" role="alert">
            Lifestyle choices are currently unavailable.
        </div>
    <?php else: ?>

        <ul
            class="nav nav-pills flex-nowrap
        overflow-auto gap-2 mb-4"
            id="lifestyle-tabs"
            role="tablist">

            <?php foreach ($categories as $index => $category): ?>
                <?php
                $categoryId = (int) ($category['id'] ?? 0);
                $categoryName = (string) (
                    $category['name'] ?? ''
                );
                $iconClass = (string) (
                    $category['icon_class']
                    ?? 'ri-checkbox-circle-line'
                );
                $isActive = $index === 0;
                ?>

                <li
                    class="nav-item flex-shrink-0"
                    role="presentation">

                    <button
                        class="nav-link d-inline-flex
        align-items-center gap-2
        <?= $isActive
                    ? 'active'
                    : 'bg-info-subtle text-info' ?>"
                        id="lifestyle-tab-<?= esc(
                                                (string) $categoryId,
                                                'attr'
                                            ) ?>"
                        data-bs-toggle="pill"
                        data-bs-target="#lifestyle-panel-<?= esc(
                                                                (string) $categoryId,
                                                                'attr'
                                                            ) ?>"
                        type="button"
                        role="tab"
                        aria-controls="lifestyle-panel-<?= esc(
                                                            (string) $categoryId,
                                                            'attr'
                                                        ) ?>"
                        aria-selected="<?= $isActive
                                            ? 'true'
                                            : 'false' ?>">

                        <i
                            class="<?= esc(
                                        $iconClass,
                                        'attr'
                                    ) ?>"
                            aria-hidden="true">
                        </i>

                        <?= esc($categoryName) ?>

                        <span
                            class="avatar-xs flex-shrink-0"
                            aria-hidden="true">

                            <span
                                class="avatar-title rounded-circle
            bg-info-subtle text-info">

                                <i class="<?= esc($iconClass, 'attr') ?>"></i>
                            </span>
                        </span>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="tab-content">
            <?php foreach ($categories as $index => $category): ?>
                <?php
                $categoryId = (int) ($category['id'] ?? 0);
                $categoryName = (string) (
                    $category['name'] ?? ''
                );
                $categoryOptions =
                    $optionsByCategory[$categoryId] ?? [];
                $isActive = $index === 0;
                ?>

                <div
                    class="tab-pane fade
                        <?= $isActive ? 'show active' : '' ?>"
                    id="lifestyle-panel-<?= esc(
                                            (string) $categoryId,
                                            'attr'
                                        ) ?>"
                    role="tabpanel"
                    aria-labelledby="lifestyle-tab-<?= esc(
                                                        (string) $categoryId,
                                                        'attr'
                                                    ) ?>"
                    tabindex="0">

                    <div
                        class="d-flex align-items-center
                            justify-content-between gap-3 mb-3">

                        <h4 class="fs-15 fw-semibold mb-0">
                            <?= esc($categoryName) ?>
                        </h4>

                        <span
                            class="badge rounded-pill bg-info text-white"
                            data-tab-selected-count="<?= esc(
                                                            (string) $categoryId,
                                                            'attr'
                                                        ) ?>">

                            0
                        </span>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($categoryOptions as $option): ?>
                            <?php
                            $optionId = (int) ($option['id'] ?? 0);
                            $optionName = (string) ($option['name'] ?? '');

                            $isChecked = in_array(
                                $optionId,
                                $selectedOptionIds,
                                true
                            );
                            ?>

                            <input
                                type="checkbox"
                                class="btn-check"
                                name="lifestyle_option_ids[]"
                                value="<?= esc(
                                            (string) $optionId,
                                            'attr'
                                        ) ?>"
                                id="lifestyle-option-<?= esc(
                                                            (string) $optionId,
                                                            'attr'
                                                        ) ?>"
                                data-category-id="<?= esc(
                                                        (string) $categoryId,
                                                        'attr'
                                                    ) ?>"
                                autocomplete="off"
                                <?= $isChecked ? 'checked' : '' ?>>

                            <label
                                class="btn btn-outline-primary
                d-inline-flex align-items-center gap-1"
                                for="lifestyle-option-<?= esc(
                                                            (string) $optionId,
                                                            'attr'
                                                        ) ?>">

                                <i
                                    class="<?= $isChecked
                                                ? 'ri-check-line'
                                                : 'ri-add-line' ?>"
                                    aria-hidden="true">
                                </i>

                                <?= esc($optionName) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (
            isset($validationErrors['lifestyle_option_ids'])
        ): ?>
            <div class="invalid-feedback d-block mt-3">
                <?= esc(
                    $validationErrors['lifestyle_option_ids']
                ) ?>
            </div>
        <?php endif; ?>

        <div class="row g-2 mt-4">
            <div
                class="col-12 col-sm-6 col-md-3
            ms-md-auto order-2 order-sm-1">

                <a
                    href="<?= url_to('web.profile.edit') ?>"
                    class="btn btn-outline-danger
                fs-14 fw-medium w-100">

                    Cancel
                </a>
            </div>

            <div
                class="col-12 col-sm-6 col-md-3
            order-1 order-sm-2">

                <button
                    type="submit"
                    id="saveLifestyleButton"
                    class="btn registration-form__submit
                fs-14 fw-semibold text-uppercase">

                    <span class="registration-submit__label">
                        Save Details
                    </span>

                    <span
                        class="registration-submit__loading d-none"
                        aria-hidden="true">

                        <span
                            class="spinner-border spinner-border-sm"
                            role="status"
                            aria-hidden="true">
                        </span>

                        <span>Saving...</span>
                    </span>
                </button>
            </div>
        </div>

    <?php endif; ?>
</form>