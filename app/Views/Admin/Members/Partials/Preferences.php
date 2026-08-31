<?php

declare(strict_types=1);

/**
 * Read-only administrator view of every partner-preference section.
 *
 * @var list<array<string, mixed>> $partnerPreferenceSections
 */

$resolvedSections = isset(
    $partnerPreferenceSections
)
    && is_array(
        $partnerPreferenceSections
    )
    ? $partnerPreferenceSections
    : [];
?>

<div
    class="card border border-danger
        border-opacity-25 mb-4">

    <div class="card-header">

        <h2 class="card-title fs-16 mb-0">

            <i
                class="ri-user-heart-line me-1"
                aria-hidden="true">
            </i>

            Partner Preferences
        </h2>
    </div>

    <div class="card-body">

        <?php if (
            $resolvedSections === []
        ): ?>

            <p class="text-muted mb-0">
                Partner preferences have not been added.
            </p>

        <?php else: ?>

            <div class="row g-3">

                <?php foreach (
                    $resolvedSections as $section
                ): ?>

                    <?php
                    if (!is_array($section)) {
                        continue;
                    }

                    $title = trim(
                        (string) (
                            $section['title']
                            ?? 'Preferences'
                        )
                    );

                    $icon = trim(
                        (string) (
                            $section['icon']
                            ?? 'ri-list-check-line'
                        )
                    );

                    $items = is_array(
                        $section['items'] ?? null
                    )
                        ? $section['items']
                        : [];
                    ?>

                    <div class="col-12 col-xl-6">

                        <section
                            class="border rounded
                                h-100 p-3">

                            <h3
                                class="fs-15
                                    fw-semibold mb-3">

                                <i
                                    class="<?= esc(
                                                $icon,
                                                'attr'
                                            ) ?> me-1"
                                    aria-hidden="true">
                                </i>

                                <?= esc($title) ?>
                            </h3>

                            <?php if (
                                $items === []
                            ): ?>

                                <p class="text-muted mb-0">
                                    Not added
                                </p>

                            <?php else: ?>

                                <div class="row g-3">

                                    <?php foreach (
                                        $items as $item
                                    ): ?>

                                        <?php
                                        if (!is_array($item)) {
                                            continue;
                                        }

                                        $itemTitle = trim(
                                            (string) (
                                                $item['title']
                                                ?? 'Preference'
                                            )
                                        );

                                        $itemValue = trim(
                                            (string) (
                                                $item['value']
                                                ?? ''
                                            )
                                        );

                                        if ($itemValue === '') {
                                            $itemValue = 'Not added';
                                        }

                                        $isCompulsory =
                                            (
                                                $item['isCompulsory']
                                                ?? false
                                            ) === true;
                                        ?>

                                        <div
                                            class="col-12
                                                col-sm-6">

                                            <div
                                                class="border-bottom
                                                    pb-2 h-100">

                                                <div
                                                    class="d-flex
                                                        align-items-center
                                                        flex-wrap gap-2
                                                        mb-1">

                                                    <span
                                                        class="text-muted
                                                            fs-12">

                                                        <?= esc(
                                                            $itemTitle
                                                        ) ?>
                                                    </span>

                                                    <?php if (
                                                        $isCompulsory
                                                    ): ?>

                                                        <span
                                                            class="badge
                                                                bg-danger-subtle
                                                                text-body p-2">

                                                            Strict
                                                        </span>

                                                    <?php endif; ?>

                                                </div>

                                                <div
                                                    class="fw-medium
                                                        fs-14
                                                        text-break">

                                                    <?= esc(
                                                        $itemValue
                                                    ) ?>
                                                </div>
                                            </div>
                                        </div>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>

                        </section>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>
</div>