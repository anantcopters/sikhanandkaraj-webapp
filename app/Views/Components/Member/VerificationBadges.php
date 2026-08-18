<?php

declare(strict_types=1);

/**
 * Member profile verification trust strip.
 *
 * Backend services supply normalized verification booleans. This component
 * owns only the UI labels and icons.
 *
 * Only successfully verified attributes are displayed.
 *
 * @var array<string, bool> $verification
 */

$verification =
    isset($verification)
    && is_array($verification)
    ? $verification
    : [];

$verificationItems = [
    [
        'key' =>
        'mobile',

        'label' =>
        'Mobile',

        'icon' =>
        'ri-smartphone-line',
    ],

    [
        'key' =>
        'email',

        'label' =>
        'Email',

        'icon' =>
        'ri-mail-check-line',
    ],

    [
        'key' =>
        'aadhaar',

        'label' =>
        'Aadhaar',

        'icon' =>
        'ri-fingerprint-line',
    ],

    [
        'key' =>
        'selfie',

        'label' =>
        'Selfie',

        'icon' =>
        'ri-camera-line',
    ],
];

$verifiedItems = array_values(
    array_filter(
        $verificationItems,
        static fn(
            array $item
        ): bool =>
        (
            $verification[
                $item['key']
            ]
            ?? false
        ) === true
    )
);
?>

<?php if ($verifiedItems !== []): ?>

    <div
        class="border-top
            bg-success-subtle
            px-3 px-md-4 py-3">

        <div
            class="d-flex flex-nowrap
                align-items-center gap-2
                overflow-auto pb-1"
            aria-label="Verified profile details">

            <span
                class="d-inline-flex
                    align-items-center gap-1
                    text-success
                    fw-semibold fs-12
                    flex-shrink-0 me-1">

                <i
                    class="ri-shield-check-line
                        fs-16"
                    aria-hidden="true">
                </i>

                Verified profile

            </span>

            <?php foreach (
                $verifiedItems
                as $item
            ): ?>

                <span
                    class="badge rounded-pill
                        bg-body text-success
                        border border-success
                        border-opacity-25
                        d-inline-flex
                        align-items-center
                        gap-1 flex-shrink-0
                        px-2 py-2">

                    <i
                        class="ri-checkbox-circle-fill"
                        aria-hidden="true">
                    </i>

                    <i
                        class="<?= esc(
                                    $item['icon'],
                                    'attr'
                                ) ?>"
                        aria-hidden="true">
                    </i>

                    <span>
                        <?= esc(
                            $item['label']
                        ) ?>
                    </span>

                </span>

            <?php endforeach; ?>

        </div>

    </div>

<?php endif; ?>