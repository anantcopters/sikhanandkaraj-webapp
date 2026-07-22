<?php

declare(strict_types=1);

/**
 * Basic Details profile section summary.
 *
 * @var array<string, mixed>      $user
 * @var array<string, mixed>|null $basicDetails
 * @var array<string, int>        $basicDetailsCompletion
 */

$details = is_array($basicDetails ?? null)
    ? $basicDetails
    : [];

$completion = is_array(
    $basicDetailsCompletion ?? null
)
    ? $basicDetailsCompletion
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
 * Convert stored enum values to readable text.
 */
$formatEnum = static function (
    mixed $value
): string {
    $normalizedValue = strtolower(
        str_replace(
            '_',
            ' ',
            trim((string) $value)
        )
    );

    return $normalizedValue !== ''
        ? ucwords($normalizedValue)
        : 'Not added';
};

/**
 * Display centimetres in feet/inches and centimetres.
 */
$formatHeight = static function (
    mixed $height
): string {
    if (!is_numeric($height)) {
        return 'Not added';
    }

    $heightCm = (int) $height;
    $totalInches = (int) round(
        $heightCm / 2.54
    );

    $feet = intdiv(
        $totalInches,
        12
    );

    $inches = $totalInches % 12;

    return sprintf(
        '%d\' %d" (%d cm)',
        $feet,
        $inches,
        $heightCm
    );
};

$fullName = trim(
    (string) ($user['full_name'] ?? '')
);

$currentCity = trim(
    (string) ($details['current_city'] ?? '')
);

$currentState = trim(
    (string) ($details['current_state'] ?? '')
);

$location = implode(
    ', ',
    array_filter(
        [
            $currentCity,
            $currentState,
        ],
        static fn(string $value): bool =>
        $value !== ''
    )
);
?>

<div
    class="card border shadow-none mb-3"
    id="basic-details">
    <div class="card-body p-3 p-md-4">
        <div
            class="d-flex flex-column flex-md-row
                align-items-md-start
                justify-content-between gap-3">
            <div
                class="d-flex align-items-start gap-3">
                <div
                    class="avatar-sm flex-shrink-0"
                    aria-hidden="true">
                    <span
                        class="avatar-title rounded-circle
                            bg-primary-subtle
                            text-primary fs-20">
                        <i class="ri-user-line"></i>
                    </span>
                </div>

                <div>
                    <div
                        class="d-flex flex-wrap
                            align-items-center gap-2 mb-1">
                        <h3
                            class="fs-16 fw-semibold mb-0">
                            Basic Details
                        </h3>

                        <?php if (
                            $percentage === 100
                        ): ?>
                            <!-- <span
                                class="badge bg-success-subtle
                                            text-success p-2">
                                <i
                                    class="ri-checkbox-circle-line
                                                me-1"
                                    aria-hidden="true"></i>

                                Complete
                            </span> -->
                            <i class="ri-checkbox-circle-line text-success fs-18 float-end align-middle"></i>
                        <?php else: ?>
                            <span
                                class="badge bg-primary p-2">
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
                        Personal, marital and current
                        location information.
                    </p>
                </div>
            </div>

            <button
                type="button"
                class="btn btn-outline-primary
                            d-inline-flex align-items-center
                            justify-content-center gap-1"
                data-bs-toggle="offcanvas"
                data-bs-target="#basicDetailsOffcanvas"
                aria-controls="basicDetailsOffcanvas">
                <i
                    class="ri-edit-line"
                    aria-hidden="true"></i>

                <?= $completedFields > 1
                    ? 'Edit details'
                    : 'Add details' ?>
            </button>
        </div>

        <!-- Completion progress -->
        <?php if ($percentage < 100): ?>
            <!-- Show progress only while this section is incomplete. -->
            <div class="profile-progress mt-3">
                <div
                    class="d-flex align-items-center
                justify-content-between gap-3 mb-1">
                    <span class="text-muted fs-12">
                        Section completion
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
                    aria-label="Basic details completion"
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
                                        ) ?>%;"></div>
                </div>
            </div>
        <?php endif; ?>


        <div class="card-body p-3 p-md-4">
            <div class="row g-4">

                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="d-flex align-items-start gap-2">
                        <i
                            class="ri-user-line text-primary fs-18"
                            aria-hidden="true"></i>

                        <div class="min-w-0">
                            <span class="text-muted fs-12 d-block mb-1">
                                Full name
                            </span>

                            <strong class="fw-medium text-break">
                                <?= esc(
                                    (string) (
                                        $details['marital_status_name']
                                        ?? 'Not added'
                                    )
                                ) ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="d-flex align-items-start gap-2">
                        <i
                            class="ri-calendar-line
                                    text-primary fs-18"
                            aria-hidden="true"></i>

                        <div>
                            <span class="text-muted fs-12 d-block mb-1">
                                Date of birth
                            </span>

                            <strong class="fw-medium">
                                <?php if (
                                    !empty($details['date_of_birth'])
                                ): ?>
                                    <?= esc(date(
                                        'd M Y',
                                        strtotime(
                                            (string) $details['date_of_birth']
                                        )
                                    )) ?>
                                <?php else: ?>
                                    Not added
                                <?php endif; ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="d-flex align-items-start gap-2">
                        <i
                            class="ri-heart-2-line
                                    text-primary fs-18"
                            aria-hidden="true"></i>

                        <div>
                            <span class="text-muted fs-12 d-block mb-1">
                                Marital status
                            </span>

                            <strong class="fw-medium">
                                <?= esc($formatEnum(
                                    $details['marital_status']
                                        ?? ''
                                )) ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="d-flex align-items-start gap-2">
                        <i
                            class="ri-ruler-line text-primary fs-18"
                            aria-hidden="true"></i>

                        <div>
                            <span class="text-muted fs-12 d-block mb-1">
                                Height
                            </span>

                            <strong class="fw-medium">
                                <?= esc(
                                    (string) (
                                        $details['height_display_name']
                                        ?? 'Not added'
                                    )
                                ) ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="d-flex align-items-start gap-2">
                        <i
                            class="ri-translate-2
                                    text-primary fs-18"
                            aria-hidden="true"></i>

                        <div>
                            <span class="text-muted fs-12 d-block mb-1">
                                Mother tongue
                            </span>

                            <strong class="fw-medium">
                                <?= esc(
                                    (string) (
                                        $details['mother_tongue_name']
                                        ?? 'Not added'
                                    )
                                ) ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="d-flex align-items-start gap-2">
                        <i
                            class="ri-map-pin-line
                                    text-primary fs-18"
                            aria-hidden="true"></i>

                        <div class="min-w-0">
                            <span class="text-muted fs-12 d-block mb-1">
                                Current location
                            </span>

                            <strong class="fw-medium text-break">
                                <?php
                                $locationParts = array_filter([
                                    trim(
                                        (string) ($details['city_name'] ?? '')
                                    ),
                                    trim(
                                        (string) ($details['state_name'] ?? '')
                                    ),
                                    trim(
                                        (string) ($details['country_name'] ?? '')
                                    ),
                                ]);

                                $location = $locationParts !== []
                                    ? implode(', ', $locationParts)
                                    : 'Not added';
                                ?>

                                <?= esc($location) ?>
                            </strong>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>