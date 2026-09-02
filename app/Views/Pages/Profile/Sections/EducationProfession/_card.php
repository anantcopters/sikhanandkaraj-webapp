<?php

declare(strict_types=1);

/**
 * Education & Profession profile-section summary.
 *
 * @var array<string, mixed>|null $educationProfession
 * @var array<string, int>        $educationProfessionCompletion
 */

$details = is_array(
    $educationProfession ?? null
)
    ? $educationProfession
    : [];

$completion = is_array(
    $educationProfessionCompletion ?? null
)
    ? $educationProfessionCompletion
    : [];

$percentage = max(
    0,
    min(
        100,
        (int) ($completion['percentage'] ?? 0)
    )
);

$completedFields = max(
    0,
    (int) ($completion['completed'] ?? 0)
);

$totalFields = max(
    0,
    (int) ($completion['total'] ?? 0)
);

$remainingFields = max(
    0,
    $totalFields - $completedFields
);

/**
 * Convert stored enum values to readable labels.
 */
$formatEnum = static function (
    mixed $value
): string {
    $normalized = strtolower(
        str_replace(
            '_',
            ' ',
            trim((string) $value)
        )
    );

    return $normalized !== ''
        ? ucwords($normalized)
        : 'Not added';
};

/**
 * Return a safe display value.
 */
$displayValue = static function (
    mixed $value
): string {
    $normalized = trim((string) $value);

    return $normalized !== ''
        ? $normalized
        : 'Not added';
};
?>

<div
    class="card border border-danger border-opacity-25 shadow-none mb-3
        <?= $percentage === 100
            ? 'ribbon-box right'
            : '' ?>"
    id="education-profession">

    <div
        class="card-body p-3 p-md-4
            <?= $percentage === 100
                ? 'pt-5'
                : '' ?>">

        <?php if ($percentage === 100): ?>
            <div
                class="ribbon-two ribbon-two-success"
                aria-label="Education and Profession completed">
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

                        <i
                            class="ri-graduation-cap-line">
                        </i>
                    </span>
                </div>

                <div>
                    <div
                        class="d-flex flex-wrap
                            align-items-center gap-2 mb-1">

                        <h3 class="fs-16 fw-semibold mb-0">
                            Education &amp; Profession
                        </h3>

                        <?php if ($percentage < 100): ?>
                            <span class="badge bg-primary p-2 text-white">
                                <?= esc(
                                    (string) $remainingFields
                                ) ?>

                                <?= $remainingFields === 1
                                    ? 'field'
                                    : 'fields' ?>
                                remaining
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="text-muted fs-13 mb-0">
                        Education, occupation, employment
                        and income information.
                    </p>
                </div>
            </div>

            <a
                href="<?= url_to(
                            'web.profile.education-profession'
                        ) ?>"
                class="btn btn-outline-primary
                    d-inline-flex align-items-center
                    justify-content-center gap-1">

                <i
                    class="ri-edit-line"
                    aria-hidden="true"></i>

                <?= $completedFields > 0
                    ? 'Edit details'
                    : 'Add details' ?>
            </a>
        </div>

        <?php if ($percentage < 100): ?>
            <div class="profile-progress mt-3">
                <div
                    class="d-flex align-items-center
                        justify-content-between
                        gap-3 mb-1">

                    <span class="text-muted fs-12">
                        Required fields completed
                    </span>

                    <span class="fw-semibold fs-12">
                        <?= esc(
                            (string) $completedFields
                        ) ?>
                        of
                        <?= esc(
                            (string) $totalFields
                        ) ?>
                    </span>
                </div>

                <div
                    class="progress"
                    role="progressbar"
                    aria-label="Education and profession completion"
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

                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="d-flex align-items-start gap-2">
                        <i
                            class="ri-graduation-cap-line
                                text-primary fs-18"
                            aria-hidden="true"></i>

                        <div class="min-w-0">
                            <span
                                class="text-muted fs-12
                                    d-block mb-1">
                                Highest education
                            </span>

                            <strong
                                class="fw-medium text-break">
                                <?= esc($displayValue(
                                    $details['highest_education_name'] ?? null
                                )) ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="d-flex align-items-start gap-2">
                        <i
                            class="ri-building-line
                                text-primary fs-18"
                            aria-hidden="true"></i>

                        <div class="min-w-0">
                            <span
                                class="text-muted fs-12
                                    d-block mb-1">
                                College / Institution
                            </span>

                            <strong
                                class="fw-medium text-break">
                                <?= esc($displayValue(
                                    $details['college_institution'] ?? null
                                )) ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="d-flex align-items-start gap-2">
                        <i
                            class="ri-briefcase-line
                                text-primary fs-18"
                            aria-hidden="true"></i>

                        <div>
                            <span
                                class="text-muted fs-12
                                    d-block mb-1">
                                Employed in
                            </span>

                            <strong class="fw-medium">
                                <?= esc($formatEnum(
                                    $details['employed_in'] ?? null
                                )) ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="d-flex align-items-start gap-2">
                        <i
                            class="ri-user-star-line
                                text-primary fs-18"
                            aria-hidden="true"></i>

                        <div class="min-w-0">
                            <span
                                class="text-muted fs-12
                                    d-block mb-1">
                                Occupation
                            </span>

                            <strong
                                class="fw-medium text-break">
                                <?= esc($displayValue(
                                    $details['occupation_name'] ?? null
                                )) ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="d-flex align-items-start gap-2">
                        <i
                            class="ri-community-line
                                text-primary fs-18"
                            aria-hidden="true"></i>

                        <div class="min-w-0">
                            <span
                                class="text-muted fs-12
                                    d-block mb-1">
                                Organization
                            </span>

                            <strong
                                class="fw-medium text-break">
                                <?= esc($displayValue(
                                    $details['organization'] ?? null
                                )) ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="d-flex align-items-start gap-2">
                        <i
                            class="ri-money-rupee-circle-line
                                text-primary fs-18"
                            aria-hidden="true"></i>

                        <div class="min-w-0">
                            <span
                                class="text-muted fs-12
                                    d-block mb-1">
                                Annual income
                            </span>

                            <strong
                                class="fw-medium text-break">
                                <?= esc($displayValue(
                                    $details['annual_income_display_name'] ?? null
                                )) ?>
                            </strong>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>