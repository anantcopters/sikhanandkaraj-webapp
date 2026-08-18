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
        ): bool => (
            $verification[$item['key']]
            ?? false
        ) === true
    )
);
?>

<?php if ($verifiedItems !== []): ?>

    <div
        class="border-top
            px-3 px-md-4 py-3 bg-dark-subtle align-text-center">

        <div
            class="d-flex flex-nowrap
                align-items-center gap-2
                overflow-auto"
            aria-label="Verified profile details">

            <?php foreach (
                $verifiedItems
                as $item
            ): ?>

                <span
                    class="badge rounded
                        bg-success text-success
                        border border-success
                        border-opacity-25
                        d-inline-flex
                        align-items-center
                        gap-1 flex-shrink-0
                        px-2 py-2">

                    <span class="avatar-title bg-success rounded-circle shadow">
                        <i class="ri-shield-check-line
                        fs-16 text-white"></i>
                    </span>

                    <span class="fs-13 fw-medium text-white">
                        <?= esc(
                            $item['label']
                        ) ?>
                    </span>

                </span>

            <?php endforeach; ?>

        </div>

    </div>

<?php endif; ?>