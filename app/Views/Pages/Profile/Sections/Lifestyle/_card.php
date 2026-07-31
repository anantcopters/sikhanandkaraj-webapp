<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $lifestyleDetails
 * @var array<string, int> $lifestyleCompletion
 */

$lifestyleDetails = isset($lifestyleDetails)
    && is_array($lifestyleDetails)
    ? $lifestyleDetails
    : [];

$lifestyleCompletion = isset($lifestyleCompletion)
    && is_array($lifestyleCompletion)
    ? $lifestyleCompletion
    : [];

$percentage = max(
    0,
    min(
        100,
        (int) ($lifestyleCompletion['percentage'] ?? 0)
    )
);

$completedFields = (int) (
    $lifestyleCompletion['completed'] ?? 0
);

$totalFields = (int) (
    $lifestyleCompletion['total'] ?? 0
);

$remainingFields = max(
    0,
    $totalFields - $completedFields
);

$groupedSelections = [];

foreach ($lifestyleDetails as $detail) {
    $categoryCode = (string) (
        $detail['category_code'] ?? ''
    );

    if ($categoryCode === '') {
        continue;
    }

    $groupedSelections[$categoryCode]['name'] =
        (string) ($detail['category_name'] ?? '');

    $groupedSelections[$categoryCode]['icon'] =
        (string) (
            $detail['icon_class']
            ?? 'ri-checkbox-circle-line'
        );

    $groupedSelections[$categoryCode]['values'][] =
        (string) ($detail['name'] ?? '');
}
?>

<div
    class="card border border-danger border-opacity-25
        shadow-none mb-3
        <?= $percentage === 100
            ? 'ribbon-box right'
            : '' ?>"
    id="lifestyle">

    <div
        class="card-body p-3 p-md-4
            <?= $percentage === 100
                ? 'pt-5'
                : '' ?>">

        <?php if ($percentage === 100): ?>
            <div
                class="ribbon-two ribbon-two-success"
                aria-label="Lifestyle completed">

                <span>Completed</span>
            </div>
        <?php endif; ?>

        <div
            class="d-flex flex-column flex-md-row
                align-items-md-start
                justify-content-between gap-3">

            <div class="d-flex align-items-start gap-3">
                <div
                    class="avatar-sm flex-shrink-0"
                    aria-hidden="true">

                    <span
                        class="avatar-title rounded-circle
                            bg-primary-subtle
                            text-primary fs-20">

                        <i class="ri-heart-pulse-line"></i>
                    </span>
                </div>

                <div>
                    <div
                        class="d-flex flex-wrap
                            align-items-center gap-2 mb-1">

                        <h3 class="fs-16 fw-semibold mb-0">
                            Lifestyle
                        </h3>

                        <?php if ($percentage < 100): ?>
                            <span class="badge bg-primary p-2">
                                <?= esc(
                                    (string) $remainingFields
                                ) ?>

                                <?= $remainingFields === 1
                                    ? 'category'
                                    : 'categories' ?>
                                remaining
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="text-muted fs-13 mb-0">
                        Interests, music, reading, entertainment,
                        fitness and food.
                    </p>
                </div>
            </div>

            <a
                href="<?= url_to('web.profile.lifestyle') ?>"
                class="btn btn-outline-primary
                    d-inline-flex align-items-center
                    justify-content-center gap-1">

                <i
                    class="ri-edit-line"
                    aria-hidden="true">
                </i>

                <?= $completedFields > 0
                    ? 'Edit details'
                    : 'Add details' ?>
            </a>
        </div>

        <?php if ($percentage < 100): ?>
            <div class="profile-progress mt-3">
                <div
                    class="d-flex align-items-center
                        justify-content-between gap-3 mb-1">

                    <span class="text-muted fs-12">
                        Categories selected
                    </span>

                    <span class="fw-semibold fs-12">
                        <?= esc((string) $completedFields) ?>
                        of
                        <?= esc((string) $totalFields) ?>
                    </span>
                </div>

                <div
                    class="progress"
                    role="progressbar"
                    aria-valuenow="<?= esc(
                                        (string) $percentage,
                                        'attr'
                                    ) ?>"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    style="height: 8px;">

                    <div
                        class="progress-bar"
                        style="width: <?= esc(
                                            (string) $percentage,
                                            'attr'
                                        ) ?>%;">
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="pt-4">
            <div class="row g-4">

                <?php foreach (
                    $groupedSelections as $group
                ): ?>
                    <?php
                    $values = isset($group['values'])
                        && is_array($group['values'])
                        ? $group['values']
                        : [];
                    ?>

                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="d-flex align-items-start gap-2">

                            <i
                                class="<?= esc(
                                            (string) (
                                                $group['icon']
                                                ?? 'ri-check-line'
                                            ),
                                            'attr'
                                        ) ?> text-primary fs-18"
                                aria-hidden="true">
                            </i>

                            <div class="min-w-0">
                                <span
                                    class="text-muted fs-12
                                        d-block mb-1">

                                    <?= esc(
                                        (string) (
                                            $group['name']
                                            ?? ''
                                        )
                                    ) ?>
                                </span>

                                <strong
                                    class="fw-medium text-break">

                                    <?= $values !== []
                                        ? esc(
                                            implode(', ', $values)
                                        )
                                        : 'Not added' ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>
</div>