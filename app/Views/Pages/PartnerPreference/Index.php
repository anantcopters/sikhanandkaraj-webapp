<?php

declare(strict_types=1);

/**
 * Partner Preference overview.
 *
 * @var list<array<string, mixed>> $sections
 * @var array<string, string>|null $formAlert
 */

$this->extend('Layouts/Main');

$this->section('content');

$resolvedSections = is_array($sections ?? null)
    ? $sections
    : [];
?>

<section class="py-3 py-lg-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">

                <?= view(
                    'Pages/Profile/Partials/_feedback_alert',
                    [
                        'formAlert' =>
                        $formAlert ?? null,
                    ]
                ) ?>

                <div
                    class="d-flex align-items-start
                        gap-3 mb-3">

                    <div>
                        <a
                            href="<?= url_to(
                                        'web.dashboard'
                                    ) ?>"
                            class="d-inline-flex
                                align-items-center
                                gap-1 text-primary
                                fw-medium mb-2">

                            <i
                                class="ri-arrow-left-line"
                                aria-hidden="true"></i>

                            Back to Dashboard
                        </a>

                        <div
                            class="d-flex align-items-center
                                gap-2 mt-2">

                            <div
                                class="avatar-sm flex-shrink-0"
                                aria-hidden="true">

                                <span
                                    class="avatar-title
                                        rounded-circle
                                        bg-primary-subtle
                                        text-primary">

                                    <i
                                        class="ri-user-heart-line
                                            fs-20"></i>
                                </span>
                            </div>

                            <div>
                                <h2
                                    class="fs-16 fw-semibold mb-1">
                                    Partner Preference
                                </h2>

                                <p
                                    class="text-muted fs-13 mb-0">
                                    Define the criteria you prefer
                                    in your partner.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php foreach (
                    $resolvedSections as $section
                ): ?>
                    <?php
                    $sectionItems = is_array(
                        $section['items'] ?? null
                    )
                        ? $section['items']
                        : [];

                    $sectionComplete =
                        ($section['isCompleted'] ?? false)
                        === true;

                    $sectionKey = trim(
                        (string) (
                            $section['key'] ?? ''
                        )
                    );

                    $sectionTitle = trim(
                        (string) (
                            $section['title'] ?? ''
                        )
                    );

                    $sectionDescription = trim(
                        (string) (
                            $section['description'] ?? ''
                        )
                    );

                    $sectionIcon = trim(
                        (string) (
                            $section['icon']
                            ?? 'ri-list-check'
                        )
                    );
                    ?>

                    <div
                        class="card border border-danger
                        border-opacity-25 shadow-none mb-3"
                        id="<?= esc(
                                $sectionKey,
                                'attr'
                            ) ?>">

                        <div class="card-body p-3 p-md-4">

                            <div
                                class="d-flex align-items-center
                                    justify-content-between
                                    gap-3">

                                <div
                                    class="d-flex align-items-center
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
                                            class="fs-16 fw-semibold mb-0 mt-2">

                                            <?= esc($sectionTitle) ?>
                                        </h2>

                                        <?php if (
                                            $sectionDescription !== ''
                                        ): ?>
                                            <p
                                                class="text-muted fs-13 mb-0">

                                                <?= esc(
                                                    $sectionDescription
                                                ) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (
                                    $sectionComplete
                                ): ?>
                                    <span
                                        class="text-success"
                                        title="Completed"
                                        aria-label="Completed">

                                        <i
                                            class="ri-checkbox-circle-fill
                                                fs-20"
                                            aria-hidden="true"></i>
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="text-muted"
                                        title="Incomplete"
                                        aria-label="Incomplete">

                                        <i
                                            class="ri-checkbox-blank-circle-line
                                                fs-20 text-warning"
                                            aria-hidden="true"></i>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (
                                $sectionKey !== 'basic'
                            ): ?>
                                <div
                                    class="alert alert-light
                                        border mt-3 mb-0
                                        text-body fs-14">

                                    This section will be added
                                    in the next preference phase.
                                </div>
                            <?php else: ?>
                                <div
                                    class="list-group
                                        list-group-flush mt-3">

                                    <?php foreach (
                                        $sectionItems as $item
                                    ): ?>
                                        <?php
                                        $itemComplete =
                                            (
                                                $item['isCompleted'] ?? false
                                            ) === true;

                                        $itemCompulsory =
                                            (
                                                $item['isCompulsory'] ?? false
                                            ) === true;

                                        $itemKey = trim(
                                            (string) (
                                                $item['key']
                                                ?? ''
                                            )
                                        );

                                        $itemTitle = trim(
                                            (string) (
                                                $item['title']
                                                ?? ''
                                            )
                                        );

                                        $itemValue = trim(
                                            (string) (
                                                $item['value']
                                                ?? 'Not added'
                                            )
                                        );
                                        ?>

                                        <a
                                            href="<?= url_to(
                                                        'web.partner-preference'
                                                            . '.basic.edit',
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

                                                        <?php if (
                                                            $itemComplete
                                                        ): ?>
                                                            <i
                                                                class="ri-checkbox-circle-fill
                                                                    text-success
                                                                    fs-18"
                                                                aria-hidden="true"></i>
                                                        <?php else: ?>
                                                            <i
                                                                class="ri-checkbox-blank-circle-line
                                                                    text-warning
                                                                    fs-18"
                                                                aria-hidden="true"></i>
                                                        <?php endif; ?>

                                                        <span
                                                            class="fw-medium
                                                                text-dark
                                                                fs-14">

                                                            <?= esc(
                                                                $itemTitle
                                                            ) ?>
                                                        </span>

                                                        <?php if (
                                                            $itemCompulsory
                                                        ): ?>
                                                            <span
                                                                class="badge
                                                                    bg-danger-subtle
                                                                    text-danger p-2">

                                                                Compulsory
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div
                                                        class="<?= $itemComplete
                                                                    ? 'text-body'
                                                                    : 'text-secondary' ?>
                                                            fs-14">

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
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>