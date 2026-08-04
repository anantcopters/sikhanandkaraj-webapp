<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $sections
 * @var array<string, int>         $completion
 * @var array<string, string>|null $formAlert
 */

$this->extend('Layouts/Main');
$this->section('content');

$resolvedSections = is_array($sections ?? null)
    ? $sections
    : [];

$resolvedCompletion = is_array($completion ?? null)
    ? $completion
    : [
        'completed' => 0,
        'total' => 8,
        'percentage' => 0,
    ];
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

                <div class="d-flex align-items-start gap-3 mb-3">
                    <div>
                        <a
                            href="<?= url_to(
                                        'web.dashboard'
                                    ) ?>"
                            class="d-inline-flex align-items-center
                                gap-1 text-primary fw-medium mb-2">
                            <i
                                class="ri-arrow-left-line"
                                aria-hidden="true"></i>
                            Back to Dashboard
                        </a>

                        <div class="d-flex align-items-center gap-2 mt-2">
                            <div
                                class="avatar-sm flex-shrink-0"
                                aria-hidden="true">
                                <span
                                    class="avatar-title rounded-circle
                                        bg-primary-subtle text-primary">
                                    <i
                                        class="ri-user-heart-line
                                            fs-20"></i>
                                </span>
                            </div>

                            <div>
                                <h2 class="fs-16 fw-semibold mb-1">
                                    Partner Preference
                                </h2>

                                <p class="text-muted fs-13 mb-0">
                                    Define the criteria you prefer
                                    in your partner.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="alert alert-light border mb-3"
                    role="status">
                    <div
                        class="d-flex align-items-center
                            justify-content-between gap-3">
                        <div>
                            <div class="fw-semibold">
                                Basic preferences
                            </div>

                            <div class="text-muted fs-13">
                                <?= esc(
                                    (string)
                                    $resolvedCompletion['completed']
                                ) ?>
                                of
                                <?= esc(
                                    (string)
                                    $resolvedCompletion['total']
                                ) ?>
                                completed
                            </div>
                        </div>

                        <span class="badge bg-primary-subtle text-primary">
                            <?= esc(
                                (string)
                                $resolvedCompletion['percentage']
                            ) ?>%
                        </span>
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

                    $sectionKey = (string) (
                        $section['key'] ?? ''
                    );
                    ?>

                    <div
                        class="card border shadow-none mb-3"
                        id="<?= esc(
                                $sectionKey,
                                'attr'
                            ) ?>">
                        <div class="card-body p-3 p-md-4">
                            <div
                                class="d-flex align-items-center
                                    justify-content-between gap-3">
                                <div
                                    class="d-flex align-items-center
                                        gap-2">
                                    <span
                                        class="avatar-sm
                                            flex-shrink-0">
                                        <span
                                            class="avatar-title
                                                rounded-circle
                                                bg-light
                                                text-primary">
                                            <i
                                                class="<?= esc(
                                                            (string) (
                                                                $section['icon']
                                                                ?? 'ri-list-check'
                                                            ),
                                                            'attr'
                                                        ) ?>"></i>
                                        </span>
                                    </span>

                                    <div>
                                        <h3
                                            class="fs-15
                                                fw-semibold mb-0">
                                            <?= esc(
                                                (string) (
                                                    $section['title'] ?? ''
                                                )
                                            ) ?>
                                        </h3>
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
                                                fs-20"
                                            aria-hidden="true"></i>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (
                                $sectionKey !== 'basic'
                            ): ?>
                                <div
                                    class="alert alert-light
                                        border mt-3 mb-0">
                                    This section will be added
                                    in the next preference phase.
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush mt-3">
                                    <?php foreach (
                                        $sectionItems as $item
                                    ): ?>
                                        <?php
                                        $itemComplete =
                                            (
                                                $item['isCompleted'] ?? false
                                            ) === true;
                                        ?>

                                        <a
                                            href="<?= url_to(
                                                        'web.partner-preference'
                                                            . '.basic.edit',
                                                        (string)
                                                        $item['key']
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
                                                            gap-2 mb-1">
                                                        <?php if (
                                                            $itemComplete
                                                        ): ?>
                                                            <i
                                                                class="ri-checkbox-circle-fill
                                                                    text-success"
                                                                aria-hidden="true"></i>
                                                        <?php else: ?>
                                                            <i
                                                                class="ri-checkbox-blank-circle-line
                                                                    text-muted"
                                                                aria-hidden="true"></i>
                                                        <?php endif; ?>

                                                        <span
                                                            class="fw-medium">
                                                            <?= esc(
                                                                (string)
                                                                $item['title']
                                                            ) ?>
                                                        </span>

                                                        <?php if (
                                                            (
                                                                $item['isCompulsory'] ?? false
                                                            ) === true
                                                        ): ?>
                                                            <span
                                                                class="badge
                                                                    bg-danger-subtle
                                                                    text-danger">
                                                                Compulsory
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div
                                                        class="text-muted
                                                            fs-13">
                                                        <?= esc(
                                                            (string)
                                                            $item['value']
                                                        ) ?>
                                                    </div>
                                                </div>

                                                <i
                                                    class="<?= $itemComplete
                                                                ? 'ri-edit-line'
                                                                : 'ri-add-circle-line' ?>
                                                        text-primary fs-18"
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