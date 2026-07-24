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
    class="card border shadow-none mb-3
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
                    <h3 class="fs-16 fw-semibold mb-1">
                        Sikh &amp; Religious Details
                    </h3>

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

                <?= $percentage > 0
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
                ],
                [
                    'label' => 'Sub-community',
                    'value' => $display(
                        $details['subcommunity_name'] ?? null
                    ),
                ],
                [
                    'label' => 'Birth time',
                    'value' => $birthTime,
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
                ],
                [
                    'label' => 'Gotra',
                    'value' => $display(
                        $details['gotra'] ?? null
                    ),
                ],
                [
                    'label' => 'Raashi / Moon sign',
                    'value' => $display(
                        $details['moon_sign_name'] ?? null
                    ),
                ],
                [
                    'label' => 'Birth star',
                    'value' => $display(
                        $details['birth_star_name'] ?? null
                    ),
                ],
                [
                    'label' => 'Dosh',
                    'value' => $dosh,
                ],
            ];
            ?>

            <?php foreach ($items as $item): ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <span
                        class="text-muted fs-12
                            d-block mb-1">
                        <?= esc($item['label']) ?>
                    </span>

                    <strong class="fw-medium">
                        <?= esc($item['value']) ?>
                    </strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>