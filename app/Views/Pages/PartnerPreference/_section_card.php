<?php

declare(strict_types=1);

/**
 * Partner Preference section card.
 *
 * @var array<string, mixed> $section
 */

$resolvedSection = is_array($section ?? null)
    ? $section
    : [];

$sectionItems = is_array(
    $resolvedSection['items'] ?? null
)
    ? $resolvedSection['items']
    : [];

$sectionComplete =
    ($resolvedSection['isCompleted'] ?? false)
    === true;

$sectionKey = trim(
    (string) (
        $resolvedSection['key'] ?? ''
    )
);

$sectionTitle = trim(
    (string) (
        $resolvedSection['title'] ?? ''
    )
);

$sectionDescription = trim(
    (string) (
        $resolvedSection['description'] ?? ''
    )
);

$sectionIcon = trim(
    (string) (
        $resolvedSection['icon']
        ?? 'ri-list-check-line'
    )
);
?>

<div
    class="card border border-danger
        border-opacity-25 shadow-none mb-2"
    id="<?= esc(
            $sectionKey,
            'attr'
        ) ?>">

    <div class="card-body p-3 p-md-4">

        <div
            class="d-flex
                align-items-start
                justify-content-between
                gap-3">

            <div
                class="d-flex
                    align-items-start
                    gap-2">

                <span
                    class="avatar-sm
                        flex-shrink-0">

                    <span
                        class="avatar-title
                            rounded-circle
                            bg-primary-subtle
                            text-primary">

                        <i
                            class="<?= esc(
                                        $sectionIcon,
                                        'attr'
                                    ) ?> fs-18"
                            aria-hidden="true"></i>
                    </span>
                </span>

                <div>
                    <h2
                        class="fs-16 fw-semibold mb-1">

                        <?= esc(
                            $sectionTitle
                        ) ?>
                    </h2>

                    <?php if (
                        $sectionDescription !== ''
                    ): ?>
                        <p
                            class="text-muted
                                fs-13 mb-0">

                            <?= esc(
                                $sectionDescription
                            ) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <span
                class="<?= $sectionComplete
                            ? 'text-success'
                            : 'text-warning' ?>"
                title="<?= $sectionComplete
                            ? 'Completed'
                            : 'Incomplete' ?>"
                aria-label="<?= $sectionComplete
                                ? 'Completed'
                                : 'Incomplete' ?>">

                <i
                    class="<?= $sectionComplete
                                ? 'ri-checkbox-circle-fill'
                                : 'ri-checkbox-blank-circle-line' ?>
                        fs-20"
                    aria-hidden="true"></i>
            </span>
        </div>

        <div
            class="list-group
                list-group-flush mt-3">

            <?php foreach (
                $sectionItems as $item
            ): ?>
                <?php
                if (!is_array($item)) {
                    continue;
                }

                $itemComplete =
                    ($item['isCompleted'] ?? false)
                    === true;

                $itemStrict =
                    ($item['isCompulsory'] ?? false)
                    === true;

                $itemKey = trim(
                    (string) (
                        $item['key'] ?? ''
                    )
                );

                $itemTitle = trim(
                    (string) (
                        $item['title'] ?? ''
                    )
                );

                $itemValue = trim(
                    (string) (
                        $item['value']
                        ?? 'Not added'
                    )
                );

                $editRoute =
                    $sectionKey === 'basic'
                    ? 'web.partner-preference'
                    . '.basic.edit'
                    : 'web.partner-preference'
                    . '.item.edit';
                ?>

                <a
                    href="<?= url_to(
                                $editRoute,
                                $itemKey
                            ) ?>"
                    class="list-group-item
                        list-group-item-action
                        px-0 py-3">

                    <div
                        class="d-flex
                            align-items-center
                            justify-content-between
                            gap-3">

                        <div class="min-w-0">
                            <div
                                class="d-flex
                                    align-items-center
                                    flex-wrap
                                    gap-2 mb-1">

                                <i
                                    class="<?= $itemComplete
                                                ? 'ri-checkbox-circle-fill text-success'
                                                : 'ri-checkbox-blank-circle-line text-warning' ?>
                                        fs-18"
                                    aria-hidden="true"></i>

                                <span
                                    class="fw-medium
                                        text-dark fs-14">

                                    <?= esc(
                                        $itemTitle
                                    ) ?>
                                </span>

                                <?php if (
                                    $itemStrict
                                ): ?>
                                    <span
                                        class="badge
                                            bg-danger-subtle
                                            text-danger text-uppercase fw-medium px-2 py-1">

                                        Strict
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div
                                class="<?= $itemComplete
                                            ? 'text-body'
                                            : 'text-secondary' ?>
                                    fs-14 text-break">

                                <?= esc(
                                    $itemValue
                                ) ?>
                            </div>
                        </div>

                        <i
                            class="<?= $itemComplete
                                        ? 'ri-edit-line'
                                        : 'ri-add-circle-line' ?>
                                text-primary
                                fs-20
                                flex-shrink-0"
                            aria-hidden="true"></i>
                    </div>
                </a>
            <?php endforeach; ?>

        </div>
    </div>
</div>