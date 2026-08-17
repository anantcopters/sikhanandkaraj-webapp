<?php

declare(strict_types=1);

$activeSection = $activeSection ?? 'email';

$primaryEmail =
    isset($primaryEmail)
    && is_array($primaryEmail)
    ? $primaryEmail
    : null;

$pendingEmail =
    isset($pendingEmail)
    && is_array($pendingEmail)
    ? $pendingEmail
    : null;

$errors =
    isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$menuItems = [
    'password' => [
        'label' => 'Change Password',
        'icon' => 'ri-lock-password-line',
    ],
    'email' => [
        'label' => 'Add/Edit Email',
        'icon' => 'ri-mail-settings-line',
    ],
    'visibility' => [
        'label' => 'Profile Visibility',
        'icon' => 'ri-eye-line',
    ],
    'report-profile' => [
        'label' => 'Report Profile',
        'icon' => 'ri-flag-line',
    ],
    'plans' => [
        'label' => 'View Plans',
        'icon' => 'ri-vip-crown-line',
    ],
    'contact' => [
        'label' => 'Contact Us',
        'icon' => 'ri-customer-service-2-line',
    ],
];

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="py-3 py-lg-4">
    <div class="container">

        <?= view(
            'Components/Alerts/FormAlert',
            [
                'alert' =>
                $formAlert ?? null,
            ]
        ) ?>

        <div class="mb-4">
            <h1 class="fs-24 fw-semibold mb-1">
                Account Settings
            </h1>

            <p class="text-muted mb-0">
                Manage account security, contact information
                and profile visibility.
            </p>
        </div>

        <div class="row g-4 align-items-start">

            <aside class="col-12 col-lg-4 col-xl-3">
                <div
                    class="list-group shadow-sm"
                    aria-label="Account Settings">

                    <?php foreach (
                        $menuItems as $key => $item
                    ): ?>
                        <a
                            href="<?= route_to(
                                        'web.account.settings.section',
                                        $key
                                    ) ?>"
                            class="list-group-item
                                list-group-item-action
                                d-flex
                                align-items-center
                                gap-2
                                py-3
                                <?= $activeSection === $key
                                    ? 'active'
                                    : '' ?>"
                            <?= $activeSection === $key
                                ? 'aria-current="page"'
                                : '' ?>>

                            <i
                                class="<?= esc(
                                            $item['icon'],
                                            'attr'
                                        ) ?> fs-18"
                                aria-hidden="true">
                            </i>

                            <?= esc($item['label']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>

            <div class="col-12 col-lg-8 col-xl-9">
                <div
                    class="card border border-danger
                        border-opacity-25 shadow-sm">

                    <div class="card-body p-3 p-lg-4">

                        <?php if (
                            $activeSection === 'password'
                        ): ?>

                            <h2 class="fs-18 fw-semibold">
                                Change Password
                            </h2>

                            <p class="text-muted fs-13">
                                Use your current password to protect
                                this account change.
                            </p>

                            <form
                                method="post"
                                action="<?= route_to(
                                            'web.account.settings.password'
                                        ) ?>"
                                data-account-password-form
                                data-submit-loader
                                novalidate>

                                <?= csrf_field() ?>

                                <?php foreach (
                                    [
                                        'current_password' =>
                                        'Current password',
                                        'password' =>
                                        'New password',
                                        'password_confirmation' =>
                                        'Confirm new password',
                                    ] as $name => $label
                                ): ?>
                                    <div class="mb-3">
                                        <label
                                            for="<?= esc(
                                                        $name,
                                                        'attr'
                                                    ) ?>"
                                            class="form-label">

                                            <?= esc($label) ?>
                                        </label>

                                        <input
                                            type="password"
                                            id="<?= esc(
                                                    $name,
                                                    'attr'
                                                ) ?>"
                                            name="<?= esc(
                                                        $name,
                                                        'attr'
                                                    ) ?>"
                                            class="form-control
                                                <?= isset($errors[$name])
                                                    ? 'is-invalid'
                                                    : '' ?>"
                                            maxlength="128"
                                            required>

                                        <div class="invalid-feedback">
                                            <?= esc(
                                                $errors[$name]
                                                    ?? 'This field is required.'
                                            ) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="form-text mb-3">
                                    Use at least 10 characters with uppercase,
                                    lowercase, number and special character.
                                </div>

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    data-submit-button>

                                    <span data-submit-idle>
                                        Change Password
                                    </span>

                                    <span
                                        data-submit-loading
                                        class="d-none">

                                        <span
                                            class="spinner-border
                                                spinner-border-sm">
                                        </span>

                                        Saving...
                                    </span>
                                </button>
                            </form>

                        <?php elseif (
                            $activeSection === 'email'
                        ): ?>

                            <h2 class="fs-18 fw-semibold">
                                Email Address
                            </h2>

                            <?php if ($primaryEmail !== null): ?>
                                <div class="border rounded p-3 mb-3">
                                    <div class="text-muted fs-12">
                                        Current email
                                    </div>

                                    <div
                                        class="d-flex align-items-center
                                            flex-wrap gap-2">

                                        <strong>
                                            <?= esc(
                                                $primaryEmail['email']
                                            ) ?>
                                        </strong>

                                        <?php if (
                                            $primaryEmail['isVerified']
                                        ): ?>
                                            <span
                                                class="badge
                                                    bg-success-subtle
                                                    text-success p-2">

                                                Verified
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="badge
                                                    bg-warning-subtle
                                                    text-warning p-2">

                                                Verification pending
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($pendingEmail !== null): ?>
                                <div
                                    class="alert alert-warning"
                                    role="status">

                                    <strong>New email awaiting verification</strong>

                                    <div>
                                        <?= esc(
                                            $pendingEmail['email']
                                        ) ?>
                                    </div>

                                    <div class="fs-12 mt-1">
                                        Your current verified email remains
                                        active until this address is verified.
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php
                            $editableEmail =
                                $pendingEmail
                                ?? $primaryEmail;

                            $canEditEmail =
                                $editableEmail === null
                                || (
                                    $editableEmail['canChange']
                                    ?? false
                                ) === true;
                            ?>

                            <?php if ($canEditEmail): ?>
                                <form
                                    method="post"
                                    action="<?= route_to(
                                                'web.account.settings.email'
                                            ) ?>"
                                    data-account-email-form
                                    data-submit-loader
                                    novalidate>

                                    <?= csrf_field() ?>

                                    <div class="mb-3">
                                        <label
                                            for="email_address"
                                            class="form-label">

                                            <?= $primaryEmail === null
                                                ? 'Email address'
                                                : 'New email address' ?>
                                        </label>

                                        <input
                                            type="email"
                                            id="email_address"
                                            name="email_address"
                                            class="form-control
                                                <?= isset(
                                                    $errors['email_address']
                                                )
                                                    ? 'is-invalid'
                                                    : '' ?>"
                                            value="<?= esc(
                                                        old('email_address'),
                                                        'attr'
                                                    ) ?>"
                                            maxlength="254"
                                            autocomplete="email"
                                            required>

                                        <div class="invalid-feedback">
                                            <?= esc(
                                                $errors['email_address']
                                                    ?? 'Please enter a valid email address.'
                                            ) ?>
                                        </div>
                                    </div>

                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        data-submit-button>

                                        <span data-submit-idle>
                                            Save and Send Verification
                                        </span>

                                        <span
                                            data-submit-loading
                                            class="d-none">

                                            Sending...
                                        </span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <div
                                    class="alert alert-info mb-0"
                                    role="status">

                                    <i
                                        class="ri-time-line me-1"
                                        aria-hidden="true">
                                    </i>

                                    The email cannot be changed or resent
                                    until the current 24-hour verification
                                    period ends.
                                </div>
                            <?php endif; ?>

                            <?php if (
                                $editableEmail !== null
                                && (
                                    $editableEmail['canChange']
                                    ?? false
                                ) === true
                                && !(
                                    $editableEmail['isVerified']
                                    ?? false
                                )
                            ): ?>
                                <form
                                    method="post"
                                    action="<?= route_to(
                                                'web.account.settings.email.resend'
                                            ) ?>"
                                    class="mt-3"
                                    data-submit-loader>

                                    <?= csrf_field() ?>

                                    <button
                                        type="submit"
                                        class="btn btn-outline-primary"
                                        data-submit-button>

                                        <span data-submit-idle>
                                            Resend Verification Email
                                        </span>

                                        <span
                                            data-submit-loading
                                            class="d-none">

                                            Sending...
                                        </span>
                                    </button>
                                </form>
                            <?php endif; ?>

                        <?php elseif (
                            $activeSection === 'visibility'
                        ): ?>

                            <h2 class="fs-18 fw-semibold">
                                Profile Visibility
                            </h2>

                            <p class="text-muted fs-13">
                                Choose which registered members can open
                                your complete profile.
                            </p>

                            <form
                                method="post"
                                action="<?= route_to(
                                            'web.account.settings.visibility'
                                        ) ?>"
                                data-account-visibility-form
                                data-submit-loader>

                                <?= csrf_field() ?>

                                <div class="form-check border rounded p-3 ps-5 mb-3">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="profile_visibility"
                                        id="visibilityAll"
                                        value="ALL_MEMBERS"
                                        <?= $profileVisibility
                                            === 'ALL_MEMBERS'
                                            ? 'checked'
                                            : '' ?>>

                                    <label
                                        class="form-check-label"
                                        for="visibilityAll">

                                        <strong class="d-block">
                                            Visible to all registered members
                                        </strong>

                                        <span class="text-muted fs-13">
                                            Any authenticated member may open
                                            your complete profile.
                                        </span>
                                    </label>
                                </div>

                                <div class="form-check border rounded p-3 ps-5 mb-3">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="profile_visibility"
                                        id="visibilityPaid"
                                        value="PAID_MEMBERS_ONLY"
                                        <?= $profileVisibility
                                            === 'PAID_MEMBERS_ONLY'
                                            ? 'checked'
                                            : '' ?>>

                                    <label
                                        class="form-check-label"
                                        for="visibilityPaid">

                                        <strong class="d-block">
                                            Visible only to paid members
                                        </strong>

                                        <span class="text-muted fs-13">
                                            Free members will see a membership
                                            prompt instead of your details.
                                        </span>
                                    </label>
                                </div>

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    data-submit-button>

                                    <span data-submit-idle>
                                        Save Visibility
                                    </span>

                                    <span
                                        data-submit-loading
                                        class="d-none">

                                        Saving...
                                    </span>
                                </button>
                            </form>

                        <?php elseif (
                            $activeSection === 'report-profile'
                        ): ?>

                            <h2 class="fs-18 fw-semibold">
                                Report a Profile
                            </h2>

                            <div class="alert alert-warning">
                                Reports should be used only for fake identity,
                                fraud, impersonation, abusive behaviour,
                                inappropriate content or safety concerns.
                            </div>

                            <p>
                                Open the member’s full profile and select
                                <strong>Report Profile</strong>. The correct
                                Profile ID will be added automatically.
                            </p>

                            <a
                                href="<?= route_to(
                                            'web.search'
                                        ) ?>"
                                class="btn btn-outline-primary">

                                Find a Profile
                            </a>

                        <?php elseif (
                            $activeSection === 'plans'
                        ): ?>

                            <h2 class="fs-18 fw-semibold">
                                Membership Plans
                            </h2>

                            <div class="text-center py-5">
                                <i
                                    class="ri-vip-crown-line
                                        fs-36 text-primary">
                                </i>

                                <p class="mt-3 mb-0">
                                    Membership plans will be available soon.
                                </p>
                            </div>

                        <?php else: ?>

                            <h2 class="fs-18 fw-semibold">
                                Contact Us
                            </h2>

                            <form
                                method="post"
                                action="<?= route_to(
                                            'web.account.settings.contact'
                                        ) ?>"
                                data-account-contact-form
                                data-submit-loader
                                novalidate>

                                <?= csrf_field() ?>

                                <div class="mb-3">
                                    <label
                                        for="contactMessage"
                                        class="form-label">

                                        Message
                                    </label>

                                    <textarea
                                        id="contactMessage"
                                        name="message"
                                        rows="6"
                                        minlength="10"
                                        maxlength="2000"
                                        class="form-control
                                            <?= isset($errors['message'])
                                                ? 'is-invalid'
                                                : '' ?>"
                                        required><?= esc(
                                                        old('message')
                                                    ) ?></textarea>

                                    <div class="invalid-feedback">
                                        <?= esc(
                                            $errors['message']
                                                ?? 'Please enter between 10 and 2000 characters.'
                                        ) ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label
                                        for="contactCaptcha"
                                        class="form-label">

                                        What is
                                        <strong>
                                            <?= esc(
                                                $contactCaptcha
                                            ) ?>
                                        </strong>
                                        ?
                                    </label>

                                    <input
                                        type="text"
                                        inputmode="numeric"
                                        id="contactCaptcha"
                                        name="captcha_answer"
                                        class="form-control
                                            <?= isset(
                                                $errors['captcha_answer']
                                            )
                                                ? 'is-invalid'
                                                : '' ?>"
                                        maxlength="2"
                                        required>

                                    <div class="invalid-feedback">
                                        <?= esc(
                                            $errors['captcha_answer']
                                                ?? 'Please enter the security answer.'
                                        ) ?>
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    data-submit-button>

                                    <span data-submit-idle>
                                        Send Message
                                    </span>

                                    <span
                                        data-submit-loading
                                        class="d-none">

                                        Sending...
                                    </span>
                                </button>
                            </form>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <form
            method="post"
            action="<?= route_to('web.logout') ?>"
            id="accountSettingsLogoutForm"
            class="d-none">

            <?= csrf_field() ?>
        </form>

        <?php if (
            isset($accountNotice)
            && is_array($accountNotice)
        ): ?>
            <span
                class="d-none"
                data-account-notice
                data-notice-type="<?= esc(
                                        $accountNotice['type']
                                            ?? 'success',
                                        'attr'
                                    ) ?>"
                data-notice-title="<?= esc(
                                        $accountNotice['title']
                                            ?? '',
                                        'attr'
                                    ) ?>"
                data-notice-message="<?= esc(
                                            $accountNotice['message']
                                                ?? '',
                                            'attr'
                                        ) ?>"
                data-logout-after-close="<?= (
                                                $accountNotice['logoutAfterClose']
                                                ?? false
                                            ) === true
                                                ? '1'
                                                : '0' ?>">
            </span>
        <?php endif; ?>
    </div>
</section>

<?php $this->endSection(); ?>