<?php

declare(strict_types=1);

/**
 * Compact Sikhanandkaraj membership plan cards.
 *
 * Used by:
 * - Public home page
 * - Member Account Settings
 *
 * @var string|null $context
 */

$context = isset($context)
    && in_array(
        $context,
        [
            'public',
            'member',
        ],
        true
    )
    ? $context
    : 'public';

$isMemberContext =
    $context === 'member';

$plans = [
    [
        'name' => 'Sikhanandkaraj Go',
        'positioning' => 'Start Connecting',
        'price' => '1,499',
        'duration' => '3 months',
        'monthly' => '₹500/month',
        'profiles' => '50',
        'dailyProfiles' => '5',
        'introductions' => '10',
        'manager' => false,
        'popular' => false,
        'icon' => 'ri-heart-3-line',
    ],
    [
        'name' => 'Sikhanandkaraj Plus',
        'positioning' => 'Best Value',
        'price' => '2,499',
        'duration' => '6 months',
        'monthly' => 'Just ₹417/month',
        'profiles' => '100',
        'dailyProfiles' => '10',
        'introductions' => '30',
        'manager' => false,
        'popular' => true,
        'icon' => 'ri-vip-crown-line',
    ],
    [
        'name' => 'Sikhanandkaraj Pro',
        'positioning' => 'Personalised Assistance',
        'price' => '9,999',
        'duration' => '12 months',
        'monthly' => null,
        'profiles' => '300',
        'dailyProfiles' => '20',
        'introductions' => '80',
        'manager' => true,
        'popular' => false,
        'icon' => 'ri-customer-service-2-line',
    ],
];
?>

<div class="row gy-4 align-items-stretch">

    <?php foreach ($plans as $plan): ?>

        <div class="col-12 col-lg-4">

            <article
                class="
                    card
                    border
                    border-danger
                    <?= $plan['popular']
                        ? ''
                        : 'border-opacity-25' ?>
                    <?= $plan['popular']
                        ? 'shadow'
                        : 'shadow-sm' ?>
                    h-100
                    mb-0
                    <?= $plan['popular']
                        ? 'ribbon-box left'
                        : '' ?>
                ">

                <div
                    class="
                        card-body
                        p-4
                        d-flex
                        flex-column
                    ">

                    <?php if ($plan['popular']): ?>

                        <div
                            class="
                                ribbon-two
                                ribbon-two-danger
                            ">

                            <span>
                                Popular
                            </span>
                        </div>

                    <?php endif; ?>

                    <div
                        class="
                            d-flex
                            align-items-start
                            gap-3
                            mb-3
                        ">

                        <div class="flex-grow-1">

                            <p
                                class="
                                    fs-12
                                    fw-semibold
                                    text-danger
                                    text-uppercase
                                    mb-1
                                ">

                                <?= esc(
                                    $plan['positioning']
                                ) ?>
                            </p>

                            <h3
                                class="
                                    fs-20
                                    fw-semibold
                                    mb-0
                                ">

                                <?= esc(
                                    $plan['name']
                                ) ?>
                            </h3>

                        </div>

                        <div
                            class="
                                avatar-sm
                                flex-shrink-0
                            ">

                            <div
                                class="
                                    avatar-title
                                    bg-light
                                    rounded-circle
                                    text-danger
                                ">

                                <i
                                    class="
                                        <?= esc(
                                            $plan['icon'],
                                            'attr'
                                        ) ?>
                                        fs-20
                                    "
                                    aria-hidden="true">
                                </i>

                            </div>
                        </div>

                    </div>

                    <div class="text-center py-3">

                        <div class="mb-1">

                            <span
                                class="
                                    fs-18
                                    fw-semibold
                                    align-top
                                ">
                                ₹
                            </span>

                            <span
                                class="
                                    fs-36
                                    fw-bold
                                    ff-secondary
                                ">

                                <?= esc(
                                    $plan['price']
                                ) ?>
                            </span>

                        </div>

                        <p
                            class="
                                text-muted
                                mb-1
                            ">

                            for
                            <?= esc(
                                $plan['duration']
                            ) ?>
                        </p>

                        <?php if (
                            $plan['monthly'] !== null
                        ): ?>

                            <p
                                class="
                                    fs-13
                                    <?= $plan['popular']
                                        ? 'text-danger fw-semibold'
                                        : 'text-muted' ?>
                                    mb-0
                                ">

                                <?= esc(
                                    $plan['monthly']
                                ) ?>
                            </p>

                        <?php else: ?>

                            <p
                                class="
                                    fs-13
                                    fw-semibold
                                    text-danger
                                    mb-0
                                ">
                                Personalised assistance
                            </p>

                        <?php endif; ?>

                    </div>

                    <div
                        class="
                            border-top
                            pt-3
                            mt-1
                        ">

                        <ul
                            class="
                                list-unstyled
                                vstack
                                gap-3
                                mb-0
                            ">

                            <li>
                                <div
                                    class="
                                        d-flex
                                        align-items-start
                                        gap-2
                                    ">

                                    <i
                                        class="
                                            ri-shield-check-line
                                            text-success
                                            fs-18
                                            flex-shrink-0
                                        "
                                        aria-hidden="true">
                                    </i>

                                    <div>
                                        <strong>
                                            <?= esc(
                                                $plan['profiles']
                                            ) ?>
                                            Verified Profiles
                                        </strong>

                                        <div
                                            class="
                                                fs-12
                                                text-muted
                                                mt-1
                                            ">
                                            Up to
                                            <?= esc(
                                                $plan['dailyProfiles']
                                            ) ?>
                                            per day
                                        </div>
                                    </div>

                                </div>
                            </li>

                            <li>
                                <div
                                    class="
                                        d-flex
                                        align-items-start
                                        gap-2
                                    ">

                                    <i
                                        class="
                                            ri-video-line
                                            text-success
                                            fs-18
                                            flex-shrink-0
                                        "
                                        aria-hidden="true">
                                    </i>

                                    <div>
                                        <strong>
                                            <?= esc(
                                                $plan['introductions']
                                            ) ?>
                                            Live Introductions
                                        </strong>

                                        <div
                                            class="
                                                fs-12
                                                text-muted
                                                mt-1
                                            ">
                                            Watch up to
                                            <?= esc(
                                                $plan['introductions']
                                            ) ?>
                                        </div>
                                    </div>

                                </div>
                            </li>

                            <?php if (
                                $plan['manager']
                            ): ?>

                                <li>
                                    <div
                                        class="
                                            d-flex
                                            align-items-start
                                            gap-2
                                        ">

                                        <i
                                            class="
                                                ri-customer-service-2-line
                                                text-success
                                                fs-18
                                                flex-shrink-0
                                            "
                                            aria-hidden="true">
                                        </i>

                                        <div>
                                            <strong>
                                                Dedicated Match Manager
                                            </strong>

                                            <div
                                                class="
                                                    fs-12
                                                    text-muted
                                                    mt-1
                                                ">
                                                Personalised assistance
                                                throughout your membership
                                            </div>
                                        </div>

                                    </div>
                                </li>

                            <?php else: ?>

                                <li>
                                    <div
                                        class="
                                            d-flex
                                            align-items-start
                                            gap-2
                                        ">

                                        <i
                                            class="
                                                ri-checkbox-circle-line
                                                text-success
                                                fs-18
                                                flex-shrink-0
                                            "
                                            aria-hidden="true">
                                        </i>

                                        <strong>
                                            All essential features
                                        </strong>

                                    </div>
                                </li>

                            <?php endif; ?>

                        </ul>

                    </div>

                    <div class="mt-auto pt-4">

                        <?php if (
                            $isMemberContext
                        ): ?>

                            <button
                                type="button"
                                class="
                                    btn
                                    <?= $plan['popular']
                                        ? 'btn-danger'
                                        : 'btn-outline-danger' ?>
                                    w-100
                                "
                                disabled>

                                Select
                                <?= esc(
                                    str_replace(
                                        'Sikhanandkaraj ',
                                        '',
                                        $plan['name']
                                    )
                                ) ?>

                            </button>

                        <?php else: ?>

                            <a
                                href="<?= site_url('/') ?>"
                                class="
                                    btn
                                    <?= $plan['popular']
                                        ? 'btn-danger'
                                        : 'btn-outline-danger' ?>
                                    w-100
                                ">

                                Choose
                                <?= esc(
                                    str_replace(
                                        'Sikhanandkaraj ',
                                        '',
                                        $plan['name']
                                    )
                                ) ?>

                            </a>

                        <?php endif; ?>

                    </div>

                </div>
            </article>

        </div>

    <?php endforeach; ?>

</div>

<div
    class="
        text-center
        border-top
        mt-4
        pt-4
    ">

    <p
        class="
            fs-13
            text-muted
            lh-lg
            mb-2
        ">

        <strong class="text-body">
            All plans include:
        </strong>

        Browse Profiles · Send Interests ·
        Shortlist · Advanced Search ·
        Preference Match Count · Mobile,
        Email &amp; Aadhaar Verification ·
        Live Introduction
    </p>

    <p
        class="
            fs-12
            text-muted
            lh-lg
            mb-2
        ">

        <strong class="text-body">
            Verified Profile:
        </strong>

        A profile with at least one verified
        credential — Mobile, Email, Aadhaar
        or Live Introduction.
    </p>

    <p
        class="
            fs-13
            fw-semibold
            mb-3
        ">
        All prices are inclusive of GST.
        No hidden charges.
    </p>

    <a
        href="<?= route_to(
                    'web.information.membership-plans'
                ) ?>"
        class="
            text-danger
            fw-semibold
            text-decoration-none
        ">

        View full plan details

        <i
            class="ri-arrow-right-line ms-1"
            aria-hidden="true">
        </i>

    </a>

</div>