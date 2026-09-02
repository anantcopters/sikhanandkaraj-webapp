<?php

declare(strict_types=1);

/**
 * @var array<string, mixed>|null $sikhReligiousDetails
 * @var array<string, int>        $sikhReligiousDetailsCompletion
 */

$details = is_array($sikhReligiousDetails ?? null)
    ? $sikhReligiousDetails
    : [];

$completion = is_array(
    $sikhReligiousDetailsCompletion ?? null
)
    ? $sikhReligiousDetailsCompletion
    : [];

$percentage = (int) (
    $completion['percentage'] ?? 0
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

$display = static function (mixed $value): string {
    $value = trim((string) $value);

    return $value !== ''
        ? $value
        : 'Not added';
};

$birthTime = 'Not added';

if (
    isset(
        $details['birth_hour'],
        $details['birth_minute'],
        $details['birth_meridiem']
    )
) {
    $birthTime = sprintf(
        '%02d:%02d %s',
        (int) $details['birth_hour'],
        (int) $details['birth_minute'],
        (string) $details['birth_meridiem']
    );
}

$doshLabels = [
    'NO' => 'No',
    'YES' => 'Yes',
    'DONT_KNOW' => "Don't know",
    'NOT_APPLICABLE' => 'Not applicable',
];

$dosh = $doshLabels[(string) ($details['has_dosh'] ?? '')] ?? 'Not added';
?>

<div
    class="card border border-danger border-opacity-25 shadow-none mb-3
        <?= $percentage === 100
            ? 'ribbon-box right'
            : '' ?>"
    id="sikh-religious-details">

    <div
        class="card-body p-3 p-md-4
            <?= $percentage === 100
                ? 'pt-5'
                : '' ?>">

        <?php if ($percentage === 100): ?>
            <div class="ribbon-two ribbon-two-success">
                <span>Completed</span>
            </div>
        <?php endif; ?>

        <div
            class="d-flex flex-column flex-md-row
        align-items-md-start
        justify-content-between gap-3">

            <div class="d-flex align-items-start gap-3">
                <div class="avatar-sm flex-shrink-0">
                    <span
                        class="avatar-title rounded-circle
                            bg-primary-subtle
                            text-primary fs-20">

                        <i class="ri-service-line"></i>
                    </span>
                </div>

                <div>
                    <div
                        class="d-flex flex-wrap
            align-items-center gap-2 mb-1">

                        <h3 class="fs-16 fw-semibold mb-0">
                            Sikh &amp; Religious Details
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
                        Community, birthplace and optional
                        astrological information.
                    </p>
                </div>
            </div>
            <a
                href="<?= url_to(
                            'web.profile.sikh-religious-details'
                        ) ?>"
                class="btn btn-outline-primary
                    d-inline-flex align-items-center
                    justify-content-center gap-1">

                <i class="ri-edit-line"></i>

                <?= $completedFields > 0
                    ? 'Edit details'
                    : 'Add details' ?>
            </a>
        </div>

        <div class="row g-4 pt-4">
            <?php
            $items = [
                [
                    'label' => 'Community',
                    'value' => $display(
                        $details['community_name'] ?? null
                    ),
                    'icon'  => 'ri-group-line',
                ],
                [
                    'label' => 'Sub-community',
                    'value' => $display(
                        $details['subcommunity_name'] ?? null
                    ),
                    'icon'  => 'ri-team-line',
                ],
                [
                    'label' => 'Birth time',
                    'value' => $birthTime,
                    'icon'  => 'ri-time-line',
                ],
                [
                    'label' => 'Place of birth',
                    'value' => implode(
                        ', ',
                        array_filter([
                            $details['birth_city_name'] ?? '',
                            $details['birth_state_name'] ?? '',
                            $details['birth_country_name'] ?? '',
                        ])
                    ) ?: 'Not added',
                    'icon'  => 'ri-map-pin-line',
                ],
                [
                    'label' => 'Father Gotra',
                    'value' => $display(
                        $details['gotra'] ?? null
                    ),
                    'icon'  => 'ri-book-3-line',
                ],
                [
                    'label' => 'Raashi / Moon sign',
                    'value' => $display(
                        $details['moon_sign_name'] ?? null
                    ),
                    'icon'  => 'ri-moon-line',
                ],
                [
                    'label' => 'Birth star',
                    'value' => $display(
                        $details['birth_star_name'] ?? null
                    ),
                    'icon'  => 'ri-star-line',
                ],
                [
                    'label' => 'Dosh',
                    'value' => $dosh,
                    'icon'  => 'ri-scales-3-line',
                ],
            ];
            ?>

            <?php foreach ($items as $item): ?>
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