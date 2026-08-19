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

$verifiedCount =
    count(
        $verifiedItems
    );

$verificationSummary =
    $verifiedCount === 1
    ? '1 profile detail verified'
    : $verifiedCount
    . ' profile details verified';
?>

<?php if ($verifiedItems !== []): ?>

    <div
        class="border-top
            bg-light-subtle
            px-3 px-md-4 py-3">

        <!-- Verification heading -->
        <div
            class="d-flex
                align-items-center
                gap-2 mb-3">

            <span
                class="avatar-xs
                    flex-shrink-0">

                <span
                    class="avatar-title
                        rounded-circle
                        bg-success-subtle
                        text-success">

                    <i
                        class="ri-shield-check-fill
                            fs-18"
                        aria-hidden="true">
                    </i>

                </span>

            </span>

            <div class="min-w-0">

                <p
                    class="fs-13
                        fw-semibold
                        text-body mb-0">

                    Profile Verification

                </p>

                <p
                    class="fs-11
                        text-muted mb-0">

                    <?= esc(
                        $verificationSummary
                    ) ?>

                </p>

            </div>

        </div>

        <!-- Successfully verified profile attributes -->
        <div
            class="d-flex
                flex-wrap
                align-items-center
                gap-2"
            role="list"
            aria-label="<?= esc(
                            $verificationSummary,
                            'attr'
                        ) ?>">

            <?php foreach (
                $verifiedItems
                as $item
            ): ?>

                <span
                    class="badge rounded
                        bg-success-subtle
                        text-success
                        border border-success
                        border-opacity-25
                        d-inline-flex
                        align-items-center
                        gap-2
                        px-2 py-2"
                    role="listitem"
                    aria-label="<?= esc(
                                    $item['label'],
                                    'attr'
                                ) ?>">

                    <i
                        class="<?= esc(
                                    $item['icon'],
                                    'attr'
                                ) ?>
                            fs-15 text-black fw-medium"
                        aria-hidden="true">
                    </i>

                    <span
                        class="fs-13
                            fw-normal text-black">

                        <?= esc(
                            $item['label']
                        ) ?>

                    </span>

                    <i
                        class="ri-checkbox-circle-fill
                            fs-15"
                        aria-hidden="true">
                    </i>

                </span>

            <?php endforeach; ?>

        </div>

    </div>

<?php endif; ?>