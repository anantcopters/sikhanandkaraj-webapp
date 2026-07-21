<?php

declare(strict_types=1);

/**
 * @var string|null $profileReference
 * @var string|null $loggedInUserName
 * @var string|null $primaryEmail
 * @var string|null $primaryMobile
 * @var bool $isEmailVerified
 * @var bool $isMobileVerified
 * @var string|null $profileImage
 * @var array<string, mixed> $accountPlan
 * @var array<string, mixed> $profileCompletion
 * @var array<int, array<string, string>> $profileShortcuts
 * @var array<int, array<string, mixed>> $dailyRecommendations
 * @var array<int, array<string, mixed>> $allMatches
 * @var array<int, array<string, mixed>> $newMatches
 * @var array<int, array<string, mixed>> $profileVisitors
 * @var array<int, array<string, mixed>> $shortlistedProfiles
 * @var array<int, array<string, mixed>> $shortlistedByProfiles
 */

$this->extend('Layouts/Main');
$this->section('content');

$memberName = trim((string) ($loggedInUserName ?? 'Member'));
$reference  = trim((string) ($profileReference ?? ''));

$resolvedName = trim(
    (string) ($loggedInUserName ?? 'Member')
);

$resolvedReference = trim(
    (string) ($profileReference ?? '')
);

$completionPercentage = max(
    0,
    min(
        100,
        (int) ($profileCompletion['percentage'] ?? 0)
    )
);
?>
<section class="py-4 py-lg-5">
    <div class="container">

        <?php if (
            is_string($primaryEmail)
            && $primaryEmail !== ''
            && !$isEmailVerified
        ): ?>
            <div
                class="email-verification-alert mb-4"
                role="alert">

                <div class="email-verification-alert__content">
                    <div class="email-verification-alert__icon">
                        <i class="ri-mail-warning-line"></i>
                    </div>

                    <div>
                        <h2 class="email-verification-alert__title">
                            Verify your email address
                        </h2>

                        <p class="email-verification-alert__message">
                            Your email address
                            <strong><?= esc($primaryEmail) ?></strong>
                            has not been verified. Verify it to keep your
                            account secure and receive important updates.
                        </p>
                    </div>
                </div>

                <form
                    method="post"
                    action="<?= url_to(
                                'web.email.verification.send'
                            ) ?>"
                    class="email-verification-alert__form"
                    id="emailVerificationForm"
                    novalidate>

                    <?= csrf_field() ?>

                    <button
                        type="submit"
                        class="btn email-verification-alert__action"
                        id="emailVerificationSubmit">

                        <span
                            class="email-verification-submit__label fw-normal">
                            Send verification email
                        </span>

                        <span
                            class="registration-submit__loading d-none"
                            aria-hidden="true">

                            <span
                                class="spinner-border spinner-border-sm"
                                role="status"
                                aria-hidden="true">
                            </span>

                            <span>Sending email...</span>
                        </span>
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <div class="mb-4">
            <h1 class="fs-24 fw-semibold mb-1">
                Welcome, <?= esc($memberName) ?>
            </h1>

            <p class="text-muted mb-0">
                Complete your profile and discover suitable matches.
            </p>
        </div>

        <div class="row g-4">
            <aside class="col-12 col-lg-4 col-xl-3">
                <div class="dashboard-sidebar">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <div
                                class="dashboard-avatar mx-auto mb-3"
                                aria-hidden="true">
                                <i class="ri-user-3-line"></i>
                            </div>

                            <h2 class="fs-18 fw-semibold mb-1">
                                <?= esc($memberName) ?>
                            </h2>

                            <?php if ($reference !== ''): ?>
                                <p class="text-muted fs-13 mb-2">
                                    Reference:
                                    <strong><?= esc($reference) ?></strong>
                                </p>
                            <?php endif; ?>

                            <span class="badge bg-light text-dark mb-4">
                                Free Account
                            </span>

                            <div class="border-top pt-3 text-start">
                                <div
                                    class="d-flex align-items-center justify-content-between gap-3 mb-3">

                                    <span class="d-flex align-items-center gap-2">
                                        <i class="ri-mail-line fs-18"></i>
                                        Email
                                    </span>

                                    <?php if ($isEmailVerified): ?>
                                        <span class="badge bg-success-subtle text-success">
                                            Verified
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning">
                                            Pending
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div
                                    class="d-flex align-items-center justify-content-between gap-3">

                                    <span class="d-flex align-items-center gap-2">
                                        <i class="ri-smartphone-line fs-18"></i>
                                        Mobile
                                    </span>

                                    <span class="badge bg-success-subtle text-success">
                                        Verified
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="list-group list-group-flush">
                            <a
                                href="<?= url_to('web.profile.edit') ?>"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-3">

                                <i class="ri-user-settings-line fs-18"></i>
                                <span>Edit Profile</span>
                            </a>

                            <span
                                class="list-group-item d-flex align-items-center gap-2 py-3 text-muted"
                                aria-disabled="true"
                                title="Preference management will be available soon">

                                <i class="ri-equalizer-line fs-18"></i>
                                <span>Edit Preferences</span>
                            </span>

                            <a
                                href="<?= url_to('web.account.settings') ?>"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-3">

                                <i class="ri-settings-3-line fs-18"></i>
                                <span>Account Settings</span>
                            </a>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="col-12 col-lg-8 col-xl-9">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div
                            class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">

                            <div>
                                <h2 class="fs-18 fw-semibold mb-1">
                                    Complete Your Profile
                                </h2>

                                <p class="text-muted fs-13 mb-0">
                                    A complete profile improves match quality.
                                </p>
                            </div>

                            <strong class="fs-18">
                                <?= esc(
                                    (string) $profileCompletion['percentage']
                                ) ?>%
                            </strong>
                        </div>

                        <div
                            class="progress mb-4"
                            role="progressbar"
                            aria-label="Profile completion"
                            aria-valuenow="<?= esc(
                                                (string) $profileCompletion['percentage']
                                            ) ?>"
                            aria-valuemin="0"
                            aria-valuemax="100">

                            <div
                                class="progress-bar"
                                style="width: <?= esc(
                                                    (string) $profileCompletion['percentage']
                                                ) ?>%">
                            </div>
                        </div>

                        <div class="row g-3">
                            <?php foreach (
                                $profileCompletion['items']
                                as $completionItem
                            ): ?>
                                <div class="col-12 col-md-4">
                                    <a
                                        href="<?= esc($completionItem['url']) ?>"
                                        class="card h-100 border text-decoration-none">

                                        <div class="card-body p-3">
                                            <div
                                                class="d-flex align-items-start gap-3">

                                                <div
                                                    class="dashboard-shortcut-icon bg-light rounded-circle">

                                                    <i class="<?= esc(
                                                                    $completionItem['icon']
                                                                ) ?>"></i>
                                                </div>

                                                <div>
                                                    <h3
                                                        class="fs-14 fw-semibold text-body mb-1">
                                                        <?= esc(
                                                            $completionItem['title']
                                                        ) ?>
                                                    </h3>

                                                    <p
                                                        class="text-muted fs-12 mb-0">
                                                        <?= esc(
                                                            $completionItem['description']
                                                        ) ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <?php foreach ($dashboardSections as $section): ?>
                    <section class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div
                                class="d-flex align-items-start justify-content-between gap-3 mb-3">

                                <div>
                                    <h2 class="fs-18 fw-semibold mb-1">
                                        <?= esc($section['title']) ?>
                                    </h2>

                                    <p class="text-muted fs-13 mb-0">
                                        <?= esc($section['description']) ?>
                                    </p>
                                </div>

                                <a
                                    href="#"
                                    class="text-decoration-none text-nowrap">
                                    View all
                                </a>
                            </div>

                            <?php if ($section['members'] !== []): ?>
                                <div class="dashboard-profile-scroll pb-2">
                                    <?php foreach (
                                        $section['members']
                                        as $member
                                    ): ?>
                                        <article
                                            class="card dashboard-profile-card border">

                                            <div class="card-body p-3">
                                                <div
                                                    class="position-relative mx-auto mb-3">

                                                    <div
                                                        class="dashboard-profile-photo"
                                                        aria-hidden="true">
                                                        <i class="ri-user-3-line"></i>
                                                    </div>

                                                    <span
                                                        class="dashboard-online-status <?= $member['isOnline']
                                                                                            ? 'bg-success'
                                                                                            : 'bg-secondary' ?>"
                                                        title="<?= $member['isOnline']
                                                                    ? 'Online'
                                                                    : 'Offline' ?>">
                                                    </span>
                                                </div>

                                                <h3
                                                    class="fs-14 fw-semibold text-center mb-1 text-truncate">
                                                    <?= esc(
                                                        $member['name']
                                                    ) ?>
                                                </h3>

                                                <p
                                                    class="text-muted fs-12 text-center mb-2">
                                                    <?= esc(
                                                        (string) $member['age']
                                                    ) ?>
                                                    years,
                                                    <?= esc(
                                                        $member['height']
                                                    ) ?>
                                                </p>

                                                <p
                                                    class="text-muted fs-12 text-center mb-2 text-truncate">
                                                    <i class="ri-map-pin-line"></i>
                                                    <?= esc(
                                                        $member['location']
                                                    ) ?>
                                                </p>

                                                <p
                                                    class="fs-12 text-center mb-3">
                                                    <?= esc(
                                                        $member['reference']
                                                    ) ?>
                                                </p>

                                                <a
                                                    href="#"
                                                    class="btn btn-outline-primary btn-sm w-100">
                                                    View Profile
                                                </a>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i
                                        class="ri-user-search-line fs-32 text-muted">
                                    </i>

                                    <p class="text-muted mb-0 mt-2">
                                        No profiles are available yet.
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Required by dashboard-security.js. -->
        <form
            method="post"
            action="<?= url_to('web.logout') ?>"
            id="dashboardLogoutForm"
            class="d-none">

            <?= csrf_field() ?>
        </form>
    </div>
</section>

<?php $this->endSection(); ?>