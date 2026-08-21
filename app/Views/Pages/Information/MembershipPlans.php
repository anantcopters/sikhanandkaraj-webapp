<?php

declare(strict_types=1);

/**
 * @var string|null $pageTitle
 */

$this->setVar(
    'footerView',
    'Components/Home/Footer'
)->extend(
    'Layouts/Main'
);

$this->section('content');

$commonFeatures = [
    [
        'icon' => 'ri-search-eye-line',
        'title' => 'Browse Profiles',
        'description' =>
        'Discover profiles and explore potential matches.',
    ],
    [
        'icon' => 'ri-heart-add-line',
        'title' => 'Send Interests',
        'description' =>
        'Express interest in profiles you would like to connect with.',
    ],
    [
        'icon' => 'ri-bookmark-line',
        'title' => 'Shortlist Profiles',
        'description' =>
        'Save profiles you are interested in and revisit them anytime.',
    ],
    [
        'icon' => 'ri-equalizer-line',
        'title' => 'Advanced Search',
        'description' =>
        'Refine your search using detailed profile preferences.',
    ],
    [
        'icon' => 'ri-list-check-3',
        'title' => 'Preference Match Count',
        'description' =>
        'Quickly see how closely a profile matches your preferences.',
    ],
];

$verificationFeatures = [
    [
        'icon' => 'ri-smartphone-line',
        'title' => 'Mobile Verification',
    ],
    [
        'icon' => 'ri-mail-check-line',
        'title' => 'Email Verification',
    ],
    [
        'icon' => 'ri-fingerprint-line',
        'title' => 'Aadhaar Authentication',
    ],
    [
        'icon' => 'ri-video-line',
        'title' => 'Live Introduction',
    ],
];
?>

<section
    class="section py-5 light-yellowish"
    aria-labelledby="membership-plans-title">

    <div class="container">

        <!-- Page introduction -->
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">

                <header class="text-center mb-4">

                    <p
                        class="
                            fs-13
                            fw-semibold
                            text-danger
                            text-uppercase
                            mb-2
                        ">

                        Simple Membership Plans
                    </p>

                    <h1
                        id="membership-plans-title"
                        class="fs-36 fw-bold mb-3">

                        Choose the Right Plan
                        for Your Search
                    </h1>

                    <p
                        class="
                            fs-16
                            text-secondary
                            lh-lg
                            mx-auto
                            mb-2
                        ">

                        Connect with verified profiles, discover
                        compatible matches and take the next step
                        with confidence.
                    </p>

                    <p class="fw-medium mb-0">
                        Simple plans. Meaningful connections.
                        No hidden charges.
                    </p>

                </header>
            </div>
        </div>

        <!-- Membership plans -->
        <div class="row g-4 align-items-stretch mb-4">

            <!-- Go -->
            <div class="col-12 col-lg-4">
                <article
                    class="
                        card
                        border
                        border-danger
                        border-opacity-25
                        shadow-sm
                        h-100
                    ">

                    <div
                        class="
                            card-body
                            p-4
                            d-flex
                            flex-column
                        ">

                        <div class="text-center mb-4">

                            <p
                                class="
        fs-13
        fw-semibold
        text-danger
        text-uppercase
        mb-3
    ">
                                Start Connecting
                            </p>

                            <img
                                src="<?= base_url(
                                            'assets/images/plan_go_short_removebg.png'
                                        ) ?>"
                                alt="Sikhanandkaraj Go"
                                class="img-fluid mb-3"
                                width="200"
                                height="90"
                                loading="lazy">

                            <div class="mb-1">
                                <span class="fs-36 fw-bold">
                                    ₹1,499
                                </span>
                            </div>

                            <p class="text-secondary mb-1">
                                for 3 months
                            </p>

                            <p class="fs-13 text-secondary mb-0">
                                ₹500/month
                            </p>

                        </div>

                        <p
                            class="
                                text-center
                                text-secondary
                                lh-lg
                                mb-4
                            ">

                            A simple way to begin your search
                            and connect with verified profiles.
                        </p>

                        <div class="border-top pt-4">

                            <div
                                class="
                                    d-flex
                                    align-items-start
                                    gap-3
                                    mb-4
                                ">

                                <i
                                    class="
                                        ri-shield-check-line
                                        text-danger
                                        fs-24
                                        flex-shrink-0
                                    "
                                    aria-hidden="true">
                                </i>

                                <div>
                                    <h3 class="fs-16 fw-semibold mb-1">
                                        Up to 50 Verified Profiles
                                    </h3>

                                    <p
                                        class="
                                            fs-13
                                            text-secondary
                                            mb-0
                                        ">
                                        View up to 5 per day
                                    </p>
                                </div>
                            </div>

                            <div
                                class="
                                    d-flex
                                    align-items-start
                                    gap-3
                                    mb-4
                                ">

                                <i
                                    class="
                                        ri-video-line
                                        text-danger
                                        fs-24
                                        flex-shrink-0
                                    "
                                    aria-hidden="true">
                                </i>

                                <div>
                                    <h3 class="fs-16 fw-semibold mb-1">
                                        10 Live Introductions
                                    </h3>

                                    <p
                                        class="
                                            fs-13
                                            text-secondary
                                            mb-0
                                        ">
                                        Watch up to 10
                                        Live Introductions
                                    </p>
                                </div>
                            </div>

                        </div>

                        <div class="mt-auto">

                            <p
                                class="
                                    fs-13
                                    text-secondary
                                    text-center
                                    mb-3
                                ">
                                All essential matchmaking
                                features included.
                            </p>

                            <a
                                href="<?= site_url('/') ?>"
                                class="
                                    btn
                                    btn-outline-danger
                                    w-100
                                ">
                                Choose Go
                            </a>

                        </div>
                    </div>
                </article>
            </div>

            <!-- Plus -->
            <div class="col-12 col-lg-4">
                <article
                    class="
                        card
                        border
                        border-danger
                        shadow
                        h-100
                    ">

                    <div
                        class="
                            card-header
                            bg-danger
                            text-white
                            text-center
                            fw-semibold
                            text-uppercase
                            fs-13
                        ">
                        Most Popular
                    </div>

                    <div
                        class="
                            card-body
                            p-4
                            d-flex
                            flex-column
                        ">

                        <div class="text-center mb-4">

                            <p
                                class="
        fs-13
        fw-semibold
        text-danger
        text-uppercase
        mb-3
    ">
                                Best Value
                            </p>

                            <img
                                src="<?= base_url(
                                            'assets/images/plan_plus_short_removebg.png'
                                        ) ?>"
                                alt="Sikhanandkaraj Plus"
                                class="img-fluid mb-3"
                                width="200"
                                height="90"
                                loading="lazy">

                            <div class="mb-1">
                                <span class="fs-36 fw-bold">
                                    ₹2,499
                                </span>
                            </div>

                            <p class="text-secondary mb-1">
                                for 6 months
                            </p>

                            <p
                                class="
                                    fs-13
                                    fw-semibold
                                    text-danger
                                    mb-0
                                ">
                                Just ₹417/month
                            </p>

                        </div>

                        <p
                            class="
                                text-center
                                text-secondary
                                lh-lg
                                mb-4
                            ">

                            More time, more verified profiles
                            and more opportunities to connect.
                        </p>

                        <div class="border-top pt-4">

                            <div
                                class="
                                    d-flex
                                    align-items-start
                                    gap-3
                                    mb-4
                                ">

                                <i
                                    class="
                                        ri-shield-check-line
                                        text-danger
                                        fs-24
                                        flex-shrink-0
                                    "
                                    aria-hidden="true">
                                </i>

                                <div>
                                    <h3 class="fs-16 fw-semibold mb-1">
                                        Up to 100 Verified Profiles
                                    </h3>

                                    <p
                                        class="
                                            fs-13
                                            text-secondary
                                            mb-0
                                        ">
                                        View up to 10 per day
                                    </p>
                                </div>
                            </div>

                            <div
                                class="
                                    d-flex
                                    align-items-start
                                    gap-3
                                    mb-4
                                ">

                                <i
                                    class="
                                        ri-video-line
                                        text-danger
                                        fs-24
                                        flex-shrink-0
                                    "
                                    aria-hidden="true">
                                </i>

                                <div>
                                    <h3 class="fs-16 fw-semibold mb-1">
                                        30 Live Introductions
                                    </h3>

                                    <p
                                        class="
                                            fs-13
                                            text-secondary
                                            mb-0
                                        ">
                                        Watch up to 30
                                        Live Introductions
                                    </p>
                                </div>
                            </div>

                        </div>

                        <div class="mt-auto">

                            <p
                                class="
                                    fs-13
                                    fw-medium
                                    text-center
                                    mb-3
                                ">
                                Best value for most members.
                            </p>

                            <a
                                href="<?= site_url('/') ?>"
                                class="btn btn-danger w-100">
                                Choose Plus
                            </a>

                        </div>
                    </div>
                </article>
            </div>

            <!-- Pro -->
            <div class="col-12 col-lg-4">
                <article
                    class="
                        card
                        border
                        border-danger
                        border-opacity-25
                        shadow-sm
                        h-100
                    ">

                    <div
                        class="
                            card-body
                            p-4
                            d-flex
                            flex-column
                        ">

                        <div class="text-center mb-4">

                            <p
                                class="
        fs-13
        fw-semibold
        text-danger
        text-uppercase
        mb-3
    ">
                                Personalised Assistance
                            </p>

                            <img
                                src="<?= base_url(
                                            'assets/images/plan_pro_short_removebg.png'
                                        ) ?>"
                                alt="Sikhanandkaraj Pro"
                                class="img-fluid mb-3"
                                width="200"
                                height="90"
                                loading="lazy">

                            <div class="mb-1">
                                <span class="fs-36 fw-bold">
                                    ₹9,999
                                </span>
                            </div>

                            <p class="text-secondary mb-0">
                                for 12 months
                            </p>

                        </div>

                        <p
                            class="
                                text-center
                                text-secondary
                                lh-lg
                                mb-4
                            ">

                            For members and families who want
                            personalised assistance throughout
                            their matrimonial search.
                        </p>

                        <div class="border-top pt-4">

                            <div
                                class="
                                    d-flex
                                    align-items-start
                                    gap-3
                                    mb-4
                                ">

                                <i
                                    class="
                                        ri-shield-check-line
                                        text-danger
                                        fs-24
                                        flex-shrink-0
                                    "
                                    aria-hidden="true">
                                </i>

                                <div>
                                    <h3 class="fs-16 fw-semibold mb-1">
                                        Up to 300 Verified Profiles
                                    </h3>

                                    <p
                                        class="
                                            fs-13
                                            text-secondary
                                            mb-0
                                        ">
                                        View up to 20 per day
                                    </p>
                                </div>
                            </div>

                            <div
                                class="
                                    d-flex
                                    align-items-start
                                    gap-3
                                    mb-4
                                ">

                                <i
                                    class="
                                        ri-video-line
                                        text-danger
                                        fs-24
                                        flex-shrink-0
                                    "
                                    aria-hidden="true">
                                </i>

                                <div>
                                    <h3 class="fs-16 fw-semibold mb-1">
                                        80 Live Introductions
                                    </h3>

                                    <p
                                        class="
                                            fs-13
                                            text-secondary
                                            mb-0
                                        ">
                                        Watch up to 80
                                        Live Introductions
                                    </p>
                                </div>
                            </div>

                            <div
                                class="
                                    d-flex
                                    align-items-start
                                    gap-3
                                    mb-4
                                ">

                                <i
                                    class="
                                        ri-customer-service-2-line
                                        text-danger
                                        fs-24
                                        flex-shrink-0
                                    "
                                    aria-hidden="true">
                                </i>

                                <div>
                                    <h3 class="fs-16 fw-semibold mb-1">
                                        Dedicated Match Manager
                                    </h3>

                                    <p
                                        class="
                                            fs-13
                                            text-secondary
                                            mb-0
                                        ">
                                        Personalised assistance
                                        throughout your membership.
                                    </p>
                                </div>
                            </div>

                        </div>

                        <div class="mt-auto">

                            <p
                                class="
                                    fs-13
                                    text-secondary
                                    text-center
                                    mb-3
                                ">
                                All essential matchmaking
                                features included.
                            </p>

                            <a
                                href="<?= site_url('/') ?>"
                                class="
                                    btn
                                    btn-outline-danger
                                    w-100
                                ">
                                Choose Pro
                            </a>

                        </div>
                    </div>
                </article>
            </div>

        </div>

        <!-- Common features -->
        <div class="row justify-content-center mb-4">
            <div class="col-12 col-xl-10">

                <header class="text-center mb-4">
                    <p
                        class="
                            fs-13
                            fw-semibold
                            text-danger
                            text-uppercase
                            mb-2
                        ">
                        Included With Every Plan
                    </p>

                    <h2 class="fs-28 fw-bold mb-3">
                        Everything You Need to Find
                        the Right Match
                    </h2>

                    <p class="text-secondary lh-lg mb-0">
                        Every membership includes the essential
                        tools to discover, evaluate and connect
                        with potential matches.
                    </p>
                </header>

                <div class="row g-4">

                    <?php foreach (
                        $commonFeatures as $feature
                    ): ?>

                        <div class="col-12 col-md-6">
                            <article
                                class="
                                    card
                                    border
                                    border-danger
                                    border-opacity-25
                                    h-100
                                ">

                                <div
                                    class="
                                        card-body
                                        p-4
                                        d-flex
                                        align-items-start
                                        gap-3
                                    ">

                                    <i
                                        class="
                                            <?= esc(
                                                $feature['icon'],
                                                'attr'
                                            ) ?>
                                            text-danger
                                            fs-24
                                            flex-shrink-0
                                        "
                                        aria-hidden="true">
                                    </i>

                                    <div>
                                        <h3
                                            class="
                                                fs-17
                                                fw-semibold
                                                mb-2
                                            ">
                                            <?= esc(
                                                $feature['title']
                                            ) ?>
                                        </h3>

                                        <p
                                            class="
                                                text-secondary
                                                lh-lg
                                                mb-0
                                            ">
                                            <?= esc(
                                                $feature['description']
                                            ) ?>
                                        </p>
                                    </div>

                                </div>
                            </article>
                        </div>

                    <?php endforeach; ?>

                </div>
            </div>
        </div>

        <!-- Trust and verification -->
        <div class="row justify-content-center mb-4">
            <div class="col-12 col-xl-10">

                <article
                    class="
                        card
                        border
                        border-danger
                        border-opacity-25
                        shadow-sm
                    ">

                    <div class="card-body p-4 p-lg-4">

                        <div class="text-center mb-4">

                            <p
                                class="
                                    fs-13
                                    fw-semibold
                                    text-danger
                                    text-uppercase
                                    mb-2
                                ">
                                Build Trust
                            </p>

                            <h2 class="fs-28 fw-bold mb-3">
                                Verification Options
                            </h2>

                            <p class="text-secondary lh-lg mb-0">
                                Build confidence in your profile
                                through Sikhanandkaraj verification
                                options.
                            </p>

                        </div>

                        <div class="row g-3">

                            <?php foreach (
                                $verificationFeatures as $feature
                            ): ?>

                                <div class="col-12 col-sm-6">

                                    <div
                                        class="
                                            d-flex
                                            align-items-center
                                            gap-3
                                            border
                                            rounded
                                            p-3
                                            h-100
                                        ">

                                        <i
                                            class="
                                                <?= esc(
                                                    $feature['icon'],
                                                    'attr'
                                                ) ?>
                                                text-danger
                                                fs-22
                                                flex-shrink-0
                                            "
                                            aria-hidden="true">
                                        </i>

                                        <span class="fw-medium">
                                            <?= esc(
                                                $feature['title']
                                            ) ?>
                                        </span>

                                    </div>
                                </div>

                            <?php endforeach; ?>

                        </div>
                    </div>
                </article>
            </div>
        </div>

        <!-- Comparison -->
        <div class="row justify-content-center mb-4">
            <div class="col-12 col-xl-10">

                <article
                    class="
                        card
                        border
                        border-danger
                        border-opacity-25
                        shadow-sm
                    ">

                    <div class="card-body p-4 p-lg-4">

                        <header class="text-center mb-4">
                            <p
                                class="
                                    fs-13
                                    fw-semibold
                                    text-danger
                                    text-uppercase
                                    mb-2
                                ">
                                Compare Plans
                            </p>

                            <h2 class="fs-28 fw-bold mb-0">
                                Find the Membership
                                That Works for You
                            </h2>
                        </header>

                        <div class="table-responsive">

                            <table
                                class="
                                    table
                                    table-bordered
                                    align-middle
                                    mb-0
                                ">

                                <thead>
                                    <tr class="bg-info-subtle">
                                        <th scope="col">
                                            Benefit
                                        </th>

                                        <th
                                            scope="col"
                                            class="text-center">
                                            Go
                                        </th>

                                        <th
                                            scope="col"
                                            class="text-center">

                                            <span class="d-block">
                                                Plus
                                            </span>

                                            <span
                                                class="
                    badge
                    bg-danger
                    fs-10
                    mt-1
                ">
                                                Most Popular
                                            </span>

                                        </th>

                                        <th
                                            scope="col"
                                            class="text-center">
                                            Pro
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <tr>
                                        <th scope="row">
                                            Membership
                                        </th>

                                        <td class="text-center">
                                            3 months
                                        </td>

                                        <td
                                            class="
                                                text-center
                                                fw-semibold
                                            ">
                                            6 months
                                        </td>

                                        <td
                                            class="
                                                text-center
                                                fw-semibold
                                            ">
                                            12 months
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            Verified Profiles
                                        </th>

                                        <td class="text-center">
                                            Up to 50
                                        </td>

                                        <td
                                            class="
                                                text-center
                                                fw-semibold
                                            ">
                                            Up to 100
                                        </td>

                                        <td
                                            class="
                                                text-center
                                                fw-semibold
                                            ">
                                            Up to 300
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            Daily Profile Limit
                                        </th>

                                        <td class="text-center">
                                            Up to 5
                                        </td>

                                        <td class="text-center">
                                            Up to 10
                                        </td>

                                        <td class="text-center">
                                            Up to 20
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            Live Introductions
                                        </th>

                                        <td class="text-center">
                                            Up to 10
                                        </td>

                                        <td class="text-center">
                                            Up to 30
                                        </td>

                                        <td class="text-center">
                                            Up to 80
                                        </td>
                                    </tr>

                                    <?php
                                    $includedBenefits = [
                                        'Browse Profiles',
                                        'Advanced Search',
                                        'Preference Match Count',
                                        'Send Interests',
                                        'Shortlist Profiles',
                                        'Mobile Verification',
                                        'Email Verification',
                                        'Aadhaar Authentication',
                                        'Live Introduction',
                                    ];
                                    ?>

                                    <?php foreach (
                                        $includedBenefits as $benefit
                                    ): ?>

                                        <tr>
                                            <th scope="row">
                                                <?= esc($benefit) ?>
                                            </th>

                                            <?php for (
                                                $column = 0;
                                                $column < 3;
                                                $column++
                                            ): ?>

                                                <td class="text-center">

                                                    <i
                                                        class="
                                                            ri-check-line
                                                            text-success
                                                            fs-20
                                                        "
                                                        aria-label="Included">
                                                    </i>

                                                </td>

                                            <?php endfor; ?>

                                        </tr>

                                    <?php endforeach; ?>

                                    <tr>
                                        <th scope="row">
                                            Dedicated Match Manager
                                        </th>

                                        <td
                                            class="
                                                text-center
                                                text-secondary
                                            ">
                                            —
                                        </td>

                                        <td
                                            class="
                                                text-center
                                                text-secondary
                                            ">
                                            —
                                        </td>

                                        <td
                                            class="
                                                text-center
                                                fw-semibold
                                                text-success
                                            ">

                                            <i
                                                class="
                                                    ri-check-line
                                                    fs-20
                                                "
                                                aria-hidden="true">
                                            </i>

                                            Included
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>

                    </div>
                </article>
            </div>
        </div>

        <!-- Verified profile explanation -->
        <div class="row justify-content-center mb-4">
            <div class="col-12 col-xl-10">

                <article
                    class="
                        card
                        border
                        border-danger
                        border-opacity-25
                    ">

                    <div class="card-body p-4">

                        <div
                            class="
                                d-flex
                                align-items-start
                                gap-3
                            ">

                            <i
                                class="
                                    ri-shield-check-line
                                    text-danger
                                    fs-28
                                    flex-shrink-0
                                "
                                aria-hidden="true">
                            </i>

                            <div>

                                <h2 class="fs-20 fw-semibold mb-2">
                                    What is a Verified Profile?
                                </h2>

                                <p class="lh-lg mb-2">
                                    A
                                    <strong>Verified Profile</strong>
                                    is a profile where the member
                                    has completed at least one of
                                    the following verifications:
                                </p>

                                <p class="fw-medium mb-2">
                                    Mobile · Email · Aadhaar ·
                                    Live Introduction
                                </p>

                                <p
                                    class="
                                        fs-13
                                        text-secondary
                                        lh-lg
                                        mb-0
                                    ">
                                    Verification helps members make
                                    more informed decisions while
                                    connecting. It does not constitute
                                    an endorsement or guarantee by
                                    Sikhanandkaraj.
                                </p>

                            </div>
                        </div>

                    </div>
                </article>
            </div>
        </div>

        <!-- Plan guidance -->
        <div class="row justify-content-center mb-4">
            <div class="col-12 col-xl-10">

                <header class="text-center mb-4">
                    <h2 class="fs-28 fw-bold mb-2">
                        Which Plan is Right for Me?
                    </h2>

                    <p class="text-secondary mb-0">
                        Choose based on how you would like
                        to approach your search.
                    </p>
                </header>

                <div class="row g-4">

                    <div class="col-12 col-md-4">
                        <article
                            class="
                                card
                                border
                                border-danger
                                border-opacity-25
                                h-100
                            ">

                            <div class="card-body p-4">

                                <h3 class="fs-18 fw-semibold mb-3">
                                    Choose Go
                                </h3>

                                <p
                                    class="
                                        text-secondary
                                        lh-lg
                                        mb-0
                                    ">
                                    If you're beginning your search
                                    and want an affordable way to
                                    start connecting with verified
                                    profiles.
                                </p>

                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-4">
                        <article
                            class="
                                card
                                border
                                border-danger
                                h-100
                            ">

                            <div class="card-body p-4">

                                <span
                                    class="
                                        badge
                                        bg-danger
                                        mb-3
                                    ">
                                    Most Popular
                                </span>

                                <h3 class="fs-18 fw-semibold mb-3">
                                    Choose Plus
                                </h3>

                                <p
                                    class="
                                        text-secondary
                                        lh-lg
                                        mb-0
                                    ">
                                    If you're actively looking for
                                    a match and want more time,
                                    more profiles and more
                                    Live Introductions.
                                </p>

                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-4">
                        <article
                            class="
                                card
                                border
                                border-danger
                                border-opacity-25
                                h-100
                            ">

                            <div class="card-body p-4">

                                <h3 class="fs-18 fw-semibold mb-3">
                                    Choose Pro
                                </h3>

                                <p
                                    class="
                                        text-secondary
                                        lh-lg
                                        mb-0
                                    ">
                                    If you or your family would
                                    prefer personalised assistance
                                    and support from a Dedicated
                                    Match Manager throughout
                                    the search.
                                </p>

                            </div>
                        </article>
                    </div>

                </div>
            </div>
        </div>

        <!-- Pricing reassurance -->
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">

                <div
                    class="
                        alert
                        alert-warning
                        text-center
                        mb-0
                    "
                    role="note">

                    <i
                        class="ri-price-tag-3-line me-2"
                        aria-hidden="true">
                    </i>

                    <strong>
                        All prices are inclusive of GST.
                    </strong>

                    No hidden charges.

                </div>

            </div>
        </div>

    </div>
</section>

<?php $this->endSection(); ?>