<?php

declare(strict_types=1);

/**
 * Family details profile-section summary.
 *
 * @var array<string, mixed>|null $familyDetails
 * @var array<string, int>        $familyDetailsCompletion
 */

$details = is_array($familyDetails ?? null)
    ? $familyDetails
    : [];

$completion = is_array(
    $familyDetailsCompletion ?? null
)
    ? $familyDetailsCompletion
    : [];

$percentage = max(
    0,
    min(
        100,
        (int) ($completion['percentage'] ?? 0)
    )
);

$completedFields = (int) (
    $completion['completed'] ?? 0
);

$totalFields = (int) (
    $completion['total'] ?? 0
);

$remainingFields = max(
    0,
    $totalFields - $completedFields
);

$displayValue = static function (
    mixed $value
): string {
    $normalized = trim((string) $value);

    return $normalized !== ''
        ? $normalized
        : 'Not added';
};

$formatEnum = static function (
    mixed $value
): string {
    $normalized = trim((string) $value);

    return $normalized !== ''
        ? ucwords(
            strtolower(
                str_replace('_', ' ', $normalized)
            )
        )
        : 'Not added';
};
?>

<div
    class="card border border-danger border-opacity-25 shadow-none mb-3
        <?= $percentage === 100
            ? 'ribbon-box right'
            : '' ?>"
    id="family-details">

    <div
        class="card-body p-3 p-md-4
            <?= $percentage === 100
                ? 'pt-5'
                : '' ?>">

        <?php if ($percentage === 100): ?>
            <div
                class="ribbon-two ribbon-two-success"
                aria-label="Family Details completed">
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

                        <i class="ri-group-line"></i>
                    </span>
                </div>

                <div>
                    <div
                        class="d-flex flex-wrap
                            align-items-center gap-2 mb-1">

                        <h3 class="fs-16 fw-semibold mb-0">
                            Family Details
                        </h3>

                        <?php if ($percentage < 100): ?>
                            <span class="badge bg-primary p-2">
                                <?= esc(
                                    (string) $remainingFields
                                ) ?>
                                fields remaining
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="text-muted fs-13 mb-0">
                        Family background, siblings and location.
                    </p>
                </div>
            </div>

            <a
                href="<?= url_to(
                            'web.profile.family-details'
                        ) ?>"
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
                        Required fields completed
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

                <?php
                $summaryItems = [
                    [
                        'label' => 'Family value',
                        'value' => $displayValue(
                            $details['family_value_name'] ?? null
                        ),
                        'icon' => 'ri-heart-3-line',
                    ],
                    [
                        'label' => 'Family type',
                        'value' => $displayValue(
                            $details['family_type_name'] ?? null
                        ),
                        'icon' => 'ri-home-heart-line',
                    ],
                    [
                        'label' => 'Family status',
                        'value' => $displayValue(
                            $details['family_status_name'] ?? null
                        ),
                        'icon' => 'ri-vip-crown-line',
                    ],
                    [
                        'label' => "Father's name",
                        'value' => $displayValue(
                            $details['father_name'] ?? null
                        ),
                        'icon' => 'ri-user-line',
                    ],
                    [
                        'label' => "Father's occupation",
                        'value' => $displayValue(
                            $details['father_occupation_name'] ?? null
                        ),
                        'icon' => 'ri-user-star-line',
                    ],
                    [
                        'label' => "Mother's name",
                        'value' => $displayValue(
                            $details['mother_name'] ?? null
                        ),
                        'icon' => 'ri-user-line',
                    ],
                    [
                        'label' => "Mother's occupation",
                        'value' => $displayValue(
                            $details['mother_occupation_name'] ?? null
                        ),
                        'icon' => 'ri-user-heart-line',
                    ],
                    [
                        'label' => 'Brothers',
                        'value' => (string) (
                            (int) ($details['brothers_count'] ?? 0)
                        ),
                        'icon' => 'ri-men-line',
                    ],
                    [
                        'label' => 'Sisters',
                        'value' => (string) (
                            (int) ($details['sisters_count'] ?? 0)
                        ),
                        'icon' => 'ri-women-line',
                    ],
                    [
                        'label' => 'Community',
                        'value' => implode(
                            ' - ',
                            array_filter([
                                trim(
                                    (string) (
                                        $details['community_name'] ?? ''
                                    )
                                )
                            ])
                        ) ?: 'Not added',
                        'icon' => 'ri-group-2-line',
                    ],
                    [
                        'label' => 'Gotra',
                        'value' => $displayValue(
                            $details['gotra'] ?? null
                        ),
                        'icon' => 'ri-organization-chart',
                    ],
                    [
                        'label' => 'Family location',
                        'value' => implode(
                            ', ',
                            array_filter([
                                $details['city_name'] ?? '',
                                $details['state_name'] ?? '',
                                $details['country_name'] ?? '',
                            ])
                        ) ?: 'Not added',
                        'icon' => 'ri-map-pin-line',
                    ],

                    [
                        'label' => 'Field Officer',
                        'value' => (
                            !empty($details['field_officer_id']
                                ?? null)
                        )
                            ? implode(
                                ' - ',
                                array_filter([
                                    trim(
                                        (string) (
                                            $details['field_officer_code'] ?? ''
                                        )
                                    ),

                                    trim(
                                        (string) (
                                            $details['field_officer_name'] ?? ''
                                        )
                                    ),
                                ])
                            )
                            : 'Not added',
                        'icon' => 'ri-user-star-line',
                    ],
                ];
                ?>

                <?php foreach ($summaryItems as $item): ?>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="d-flex align-items-start gap-2">
                            <i
                                class="<?= esc(
                                            $item['icon'],
                                            'attr'
                                        ) ?> text-primary fs-18"
                                aria-hidden="true">
                            </i>

                            <div class="min-w-0">
                                <span
                                    class="text-muted fs-12
                                        d-block mb-1">
                                    <?= esc($item['label']) ?>
                                </span>

                                <strong
                                    class="fw-medium text-break">
                                    <?= esc($item['value']) ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>
</div>