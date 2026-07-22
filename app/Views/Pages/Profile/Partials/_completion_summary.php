<?php

declare(strict_types=1);

/**
 * Overall member profile completion summary.
 *
 * @var array<string, mixed> $overallProfileSummary
 */

$summary = is_array($overallProfileSummary ?? null)
    ? $overallProfileSummary
    : [];

$percentage = max(
    0,
    min(
        100,
        (int) ($summary['percentage'] ?? 0)
    )
);

$completedSections = max(
    0,
    (int) ($summary['completedSteps'] ?? 0)
);

$totalSections = max(
    0,
    (int) ($summary['totalSteps'] ?? 0)
);

$pendingSections = max(
    0,
    (int) ($summary['pendingSections'] ?? 0)
);

$hasProfilePhoto = (bool) (
    $summary['hasProfilePhoto'] ?? false
);

$visibilityLabel = trim(
    (string) ($summary['visibilityLabel'] ?? 'Low')
);

$allowedVisibilityClasses = [
    'success',
    'warning',
    'danger',
];

$visibilityClass = trim(
    (string) ($summary['visibilityClass'] ?? 'danger')
);

if (!in_array(
    $visibilityClass,
    $allowedVisibilityClasses,
    true
)) {
    $visibilityClass = 'danger';
}
?>

<section
    class="card border-0 shadow-sm mb-4"
    aria-labelledby="profileCompletionTitle">
    <div class="card-body p-3 p-md-4">
        <div
            class="d-flex flex-column flex-lg-row
                        align-items-lg-center
                        justify-content-between gap-4 mb-4">
            <div class="d-flex align-items-center gap-3">
                <div
                    class="profile-completion-circle"
                    style="--profile-progress:
                                <?= esc(
                                    (string) $percentage,
                                    'attr'
                                ) ?>;"
                    aria-label="<?= esc(
                                    $percentage
                                        . '% profile completed',
                                    'attr'
                                ) ?>">
                    <div
                        class="profile-completion-circle__value">
                        <strong>
                            <?= esc(
                                (string) $percentage
                            ) ?>%
                        </strong>

                        <span>
                            Complete
                        </span>
                    </div>
                </div>

                <div>
                    <h2
                        class="fs-18 fw-semibold mb-1"
                        id="profileCompletionTitle">
                        Your Profile Journey
                    </h2>

                    <p class="text-muted mb-0">
                        Complete the remaining sections to
                        increase profile visibility.
                    </p>
                </div>
            </div>

            <?php if ($percentage < 100): ?>
                <button
                    type="button"
                    class="btn btn-outline-primary
                                d-inline-flex align-items-center
                                justify-content-center gap-1"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#basicDetailsOffcanvas"
                    aria-controls="basicDetailsOffcanvas">
                    <i
                        class="ri-arrow-right-line"
                        aria-hidden="true"></i>

                    Continue Profile
                </button>
            <?php else: ?>
                <span
                    class="badge bg-success-subtle
                                text-success fs-13 px-3 py-2">
                    <i
                        class="ri-checkbox-circle-line me-1"
                        aria-hidden="true"></i>

                    Profile completed
                </span>
            <?php endif; ?>
        </div>

        <div class="profile-progress mb-4">
            <div
                class="d-flex justify-content-between
                            align-items-center gap-3 mb-2">
                <span class="text-muted fs-12">
                    Overall completion
                </span>

                <span class="fw-semibold fs-12">
                    <?= esc(
                        (string) $completedSections
                    ) ?>
                    of
                    <?= esc(
                        (string) $totalSections
                    ) ?>
                    sections
                </span>
            </div>

            <div
                class="progress"
                role="progressbar"
                aria-label="Overall profile completion"
                aria-valuenow="<?= esc(
                                    (string) $percentage,
                                    'attr'
                                ) ?>"
                aria-valuemin="0"
                aria-valuemax="100"
                style="height: 9px;">
                <div
                    class="progress-bar"
                    style="width: <?= esc(
                                        (string) $percentage,
                                        'attr'
                                    ) ?>%;"></div>
            </div>
        </div>

        <div class="row g-3">

            <div class="col-6 col-lg-3">
                <div
                    class="border rounded p-3 py-1 h-100
                                bg-light-subtle">
                    <div
                        class="d-flex align-items-center
                                    justify-content-between gap-2 mb-2">
                        <span class="text-muted fs-13">
                            Profile completed
                        </span>

                        <i
                            class="ri-pie-chart-line
                                        text-primary fs-20"
                            aria-hidden="true"></i>
                    </div>

                    <strong class="fs-20">
                        <?= esc(
                            (string) $percentage
                        ) ?>%
                    </strong>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div
                    class="border rounded p-3 py-1 h-100
                                bg-light-subtle">
                    <div
                        class="d-flex align-items-center
                                    justify-content-between gap-2 mb-2">
                        <span class="text-muted fs-13">
                            Completed sections
                        </span>

                        <i
                            class="ri-checkbox-circle-line
                                        text-success fs-20"
                            aria-hidden="true"></i>
                    </div>

                    <strong class="fs-20">
                        <?= esc(
                            (string) $completedSections
                        ) ?>
                        /
                        <?= esc(
                            (string) $totalSections
                        ) ?>
                    </strong>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div
                    class="border rounded p-3 py-1 h-100
                                bg-light-subtle">
                    <div
                        class="d-flex align-items-center
                                    justify-content-between gap-2 mb-2">
                        <span class="text-muted fs-13">
                            Pending sections
                        </span>

                        <i
                            class="ri-time-line
                                        text-warning fs-20"
                            aria-hidden="true"></i>
                    </div>

                    <strong class="fs-20">
                        <?= esc(
                            (string) $pendingSections
                        ) ?>
                    </strong>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div
                    class="border rounded p-3 py-1 h-100
                                bg-light-subtle">
                    <div
                        class="d-flex align-items-center
                                    justify-content-between gap-2 mb-2">
                        <span class="text-muted fs-13">
                            Profile visibility
                        </span>

                        <i
                            class="ri-eye-line
                                        text-<?= esc(
                                                    $visibilityClass,
                                                    'attr'
                                                ) ?> fs-20"
                            aria-hidden="true"></i>
                    </div>

                    <strong
                        class="fs-20 text-<?= esc(
                                                $visibilityClass,
                                                'attr'
                                            ) ?>">
                        <?= esc($visibilityLabel) ?>
                    </strong>
                </div>
            </div>

        </div>

        <?php if (!$hasProfilePhoto): ?>
            <div
                class="alert alert-warning
                            d-flex align-items-start gap-2 mt-3 mb-0"
                role="alert">
                <i
                    class="ri-image-add-line fs-20"
                    aria-hidden="true"></i>

                <div>
                    <strong class="d-block mb-1">
                        Add a profile photo
                    </strong>

                    <span class="fs-13">
                        Profiles with a clear photo usually
                        receive more attention from members.
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>