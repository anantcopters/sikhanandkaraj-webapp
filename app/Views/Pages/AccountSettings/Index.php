<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * @var string                   $activeSection
 * @var array<string, mixed>|null $primaryEmail
 * @var array<string, mixed>|null $pendingEmail
 * @var string                   $contactCaptcha
 * @var array<string, string>    $validationErrors
 * @var array<string, mixed>|null $formAlert
 * @var array<string, mixed>|null $accountNotice
 */
/**
 * @var bool $isMigratedPrelaunchMember
 */

$membershipCapabilities =
    isset($membershipCapabilities)
    && is_array(
        $membershipCapabilities
    )
    ? $membershipCapabilities
    : [];

$profileReports =
    isset($profileReports)
    && is_array($profileReports)
    ? $profileReports
    : [];

$contactRequests =
    isset($contactRequests)
    && is_array($contactRequests)
    ? $contactRequests
    : [];

$activeSection = isset($activeSection)
    && is_string($activeSection)
    ? $activeSection
    : 'email';

$primaryEmail = isset($primaryEmail)
    && is_array($primaryEmail)
    ? $primaryEmail
    : null;

$pendingEmail = isset($pendingEmail)
    && is_array($pendingEmail)
    ? $pendingEmail
    : null;

$contactCaptcha = isset($contactCaptcha)
    ? trim((string) $contactCaptcha)
    : '';

$errors = isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$formAlert = isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$accountNotice = isset($accountNotice)
    && is_array($accountNotice)
    ? $accountNotice
    : null;

$requiresMigratedPasswordSetup =
    ($requiresMigratedPasswordSetup ?? false)
    === true;

$menuItems = [
    'password' => [
        'label' =>
        $requiresMigratedPasswordSetup
            ? 'Set Password'
            : 'Change Password',
        'icon' => 'ri-lock-password-line',
    ],
    'email' => [
        'label' => 'Add/Edit Email',
        'icon' => 'ri-mail-settings-line',
    ],
    'aadhaar-verification' => [
        'label' =>
        'Aadhaar Verification',

        'icon' =>
        'ri-fingerprint-line',

        /*
     * Keep the section accessible so members can review existing Aadhaar
     * verification status/history. Only new paid operations are restricted
     * inside the section by the backend-resolved capability.
     */
        'paidFeature' =>
        !(
            $membershipCapabilities['aadhaar']
            ?? false
        ),
    ],
    'video-introduction' => [
        'label' =>
        'Video Introduction',

        'icon' =>
        'ri-video-line',

        /*
     * The section remains navigable so an existing Live Introduction and its
     * moderation state can still be reviewed. Recording/replacement remains
     * controlled by the server-side membership capability.
     */
        'paidFeature' =>
        !(
            $membershipCapabilities['liveIntroduction']
            ?? false
        ),
    ],
    'report-profile' => [
        'label' =>
        'Report Profile',

        'icon' =>
        'ri-flag-line',

        /*
     * Safety actions remain available to both Free and Paid members and are
     * intentionally independent of commercial membership capabilities.
     */
    ],
    'plans' => [
        'label' => 'Membership Plans',
        'icon' => 'ri-vip-crown-line',
    ],
    'membership-history' => [
        'label' =>
        'Membership & Usage',

        'icon' =>
        'ri-history-line',
    ],
    'communication-preferences' => [
        'label' =>
        'Communication Preferences',

        'icon' =>
        'ri-notification-3-line',
    ],
    'contact' => [
        'label' => 'Contact Us',
        'icon' => 'ri-customer-service-2-line',
    ],
];

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="py-3 py-lg-3">
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
                Manage account security, verification,
                membership and support settings.
            </p>
        </div>

        <div class="row g-4 align-items-start">

            <aside class="col-12 col-lg-4 col-xl-3">
                <div
                    class="list-group shadow-sm border border-danger
        border-opacity-25"
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
                                py-3 fs-14
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
                            <?php if (
                                ($item['paidFeature'] ?? false)
                                === true
                            ): ?>

                                <!--
        This is a paid-feature indicator, not a disabled navigation state.
        Members may enter the section to review existing status/history;
        restricted actions inside the section remain server-authorized.
    -->
                                <span
                                    class="
            badge
            bg-danger-subtle
            text-danger
            ms-auto
        "
                                    title="Paid membership feature">

                                    <i
                                        class="ri-vip-crown-line fs-14 fw-normal"
                                        aria-hidden="true">
                                    </i>

                                    <span class="visually-hidden">
                                        Paid membership feature
                                    </span>

                                </span>

                            <?php endif; ?>
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
                            $activeSection === 'aadhaar-verification'
                        ): ?>

                            <?= view(
                                'Pages/AccountSettings/_AadhaarVerification',
                                [
                                    'aadhaarSettings' =>
                                    $aadhaarSettings
                                        ?? [],

                                    'aadhaarValidationErrors' =>
                                    $aadhaarValidationErrors
                                        ?? [],

                                    'openAadhaarModal' =>
                                    $openAadhaarModal
                                        ?? false,
                                ]
                            ) ?>


                        <?php elseif (
                            $activeSection === 'video-introduction'
                        ): ?>

                            <?= view(
                                'Pages/AccountSettings/_VideoIntroduction',
                                [
                                    'videoIntroduction' =>
                                    $videoIntroduction
                                        ?? null,

                                    'activeVideoIntroduction' =>
                                    $activeVideoIntroduction
                                        ?? null,

                                    'videoIntroductionHistory' =>
                                    $videoIntroductionHistory
                                        ?? [],

                                    'videoStatus' =>
                                    $videoStatus
                                        ?? 'NOT_SUBMITTED',

                                    'videoStatusLabel' =>
                                    $videoStatusLabel
                                        ?? 'Not submitted',

                                    'isFemaleMember' =>
                                    $isFemaleMember
                                        ?? false,

                                    'isProMember' =>
                                    $isProMember
                                        ?? false,

                                    'canRecord' =>
                                    $canRecord
                                        ?? false,

                                    'canDelete' =>
                                    $canDelete
                                        ?? false,

                                    'canHide' =>
                                    $canHide
                                        ?? false,

                                    'isHidden' =>
                                    $isHidden
                                        ?? false,

                                    'lockRemainingSeconds' =>
                                    $lockRemainingSeconds
                                        ?? 0,

                                    'allowedVisibilities' =>
                                    $allowedVisibilities
                                        ?? [],

                                    'hasApprovedProfilePhoto' =>
                                    $hasApprovedProfilePhoto
                                        ?? false,

                                    'videoMemberName' =>
                                    $videoMemberName
                                        ?? '',

                                    'videoProfileReference' =>
                                    $videoProfileReference
                                        ?? '',
                                ]
                            ) ?>

                        <?php elseif (
                            $activeSection === 'password'
                        ): ?>

                            <h2 class="fs-18 fw-semibold">
                                <?= $requiresMigratedPasswordSetup
                                    ? 'Set Password'
                                    : 'Change Password' ?>
                            </h2>

                            <p class="text-muted fs-13">
                                <?= $requiresMigratedPasswordSetup
                                    ? 'Set your first password securely using your verified mobile number.'
                                    : 'Use your current password to protect this account change.' ?>
                            </p>

                            <?php if ($requiresMigratedPasswordSetup): ?>
                                <div class="alert alert-light border mb-4">
                                    <h3 class="fs-16 fw-semibold mb-2 color-pink">
                                        Created during prelaunch?
                                    </h3>

                                    <p class="fs-13 mb-2 text-body">
                                        Create your password using the verified mobile
                                        number already registered with your account.
                                    </p>

                                    <ol class="fs-13 text-body ps-3 mb-3">
                                        <li class="mb-1">
                                            Select Set Password Using OTP.
                                        </li>

                                        <li class="mb-1">
                                            Verify the OTP sent to your registered
                                            mobile number.
                                        </li>

                                        <li>
                                            Create and confirm your new password.
                                        </li>
                                    </ol>
                                    <form
                                        method="post"
                                        action="<?= esc(
                                                    route_to(
                                                        'web.account.settings.password.setup'
                                                    ),
                                                    'attr'
                                                ) ?>"
                                        data-submit-loader>

                                        <?= csrf_field() ?>
                                        <button
                                            type="submit"
                                            class="btn btn-outline-danger
                fs-14 fw-semibold"
                                            data-submit-button>

                                            <span data-submit-idle>
                                                <i
                                                    class="ri-key-2-line me-1"
                                                    aria-hidden="true">
                                                </i>

                                                Set Password Using OTP
                                            </span>

                                            <span
                                                class="d-none"
                                                data-submit-loading
                                                aria-hidden="true">

                                                <span
                                                    class="spinner-border
                        spinner-border-sm me-1"
                                                    role="status"
                                                    aria-hidden="true">
                                                </span>

                                                Sending OTP...
                                            </span>
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <!-- Existing Change Password form -->
                                <form
                                    method="post"
                                    action="<?= route_to(
                                                'web.account.settings.password'
                                            ) ?>"
                                    data-validate
                                    data-account-password-form
                                    data-submit-loader
                                    novalidate>

                                    <?= csrf_field() ?>

                                    <div class="mb-3">
                                        <label
                                            for="currentPassword"
                                            class="form-label">

                                            Current Password
                                        </label>

                                        <div class="password-field">
                                            <input
                                                type="password"
                                                id="currentPassword"
                                                name="current_password"
                                                class="form-control
                    password-field__input
                    <?= isset(
                                    $errors['current_password']
                                )
                                    ? 'is-invalid'
                                    : '' ?>"
                                                maxlength="128"
                                                autocomplete="current-password"
                                                data-error-required="Please enter your current password."
                                                data-error-maxlength="The current password is invalid."
                                                required>

                                            <button
                                                type="button"
                                                class="password-field__toggle"
                                                data-password-toggle="currentPassword"
                                                aria-label="Show password">

                                                <span
                                                    class="mdi mdi-eye-off-outline"
                                                    aria-hidden="true">
                                                </span>
                                            </button>
                                        </div>

                                        <div
                                            class="invalid-feedback
                <?= isset($errors['current_password'])
                                    ? 'd-block'
                                    : '' ?>"
                                            data-validation-error="current_password">

                                            <?= esc(
                                                $errors['current_password']
                                                    ?? ''
                                            ) ?>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label
                                            for="newPassword"
                                            class="form-label">

                                            New Password
                                        </label>

                                        <div class="password-field">
                                            <input
                                                type="password"
                                                id="newPassword"
                                                name="password"
                                                class="form-control
                    password-field__input
                    <?= isset($errors['password'])
                                    ? 'is-invalid'
                                    : '' ?>"
                                                minlength="10"
                                                maxlength="128"
                                                pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{10,128}"
                                                autocomplete="new-password"
                                                data-error-required="Please enter a new password."
                                                data-error-minlength="Password must contain at least 10 characters."
                                                data-error-maxlength="Password cannot exceed 128 characters."
                                                data-error-pattern="Use uppercase, lowercase, number and special character."
                                                required>

                                            <button
                                                type="button"
                                                class="password-field__toggle"
                                                data-password-toggle="newPassword"
                                                aria-label="Show password">

                                                <span
                                                    class="mdi mdi-eye-off-outline"
                                                    aria-hidden="true">
                                                </span>
                                            </button>
                                        </div>

                                        <div
                                            class="invalid-feedback
                <?= isset($errors['password'])
                                    ? 'd-block'
                                    : '' ?>"
                                            data-validation-error="password">

                                            <?= esc(
                                                $errors['password']
                                                    ?? ''
                                            ) ?>
                                        </div>

                                        <div class="form-text color-pink">
                                            Use at least 10 characters with uppercase,
                                            lowercase, number and special character.
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label
                                            for="passwordConfirmation"
                                            class="form-label">

                                            Confirm New Password
                                        </label>

                                        <div class="password-field">
                                            <input
                                                type="password"
                                                id="passwordConfirmation"
                                                name="password_confirmation"
                                                class="form-control
                    password-field__input
                    <?= isset(
                                    $errors['password_confirmation']
                                )
                                    ? 'is-invalid'
                                    : '' ?>"
                                                maxlength="128"
                                                autocomplete="new-password"
                                                data-error-required="Please confirm the new password."
                                                data-error-password-match="The passwords do not match."
                                                required>

                                            <button
                                                type="button"
                                                class="password-field__toggle"
                                                data-password-toggle="passwordConfirmation"
                                                aria-label="Show password">

                                                <span
                                                    class="mdi mdi-eye-off-outline"
                                                    aria-hidden="true">
                                                </span>
                                            </button>
                                        </div>

                                        <div
                                            class="invalid-feedback
                <?= isset(
                                    $errors['password_confirmation']
                                )
                                    ? 'd-block'
                                    : '' ?>"
                                            data-validation-error="password_confirmation">

                                            <?= esc(
                                                $errors['password_confirmation']
                                                    ?? ''
                                            ) ?>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button
                                            type="submit"
                                            class="btn
                registration-form__submit
                fs-14
                fw-semibold w-25 text-uppercase"
                                            data-submit-button>

                                            <span data-submit-idle>
                                                <i
                                                    class="ri-save-line me-1 fw-medium"
                                                    aria-hidden="true">
                                                </i>

                                                Change Password
                                            </span>

                                            <span
                                                class="registration-submit__loading d-none"
                                                data-submit-loading>

                                                <span
                                                    class="spinner-border spinner-border-sm"
                                                    aria-hidden="true">
                                                </span>

                                                Saving...
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>



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
                                                    text-body p-2">

                                                Verified
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="badge
                                                    bg-warning-subtle
                                                    text-body p-2">

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
                                    data-validate
                                    data-account-email-form
                                    data-submit-loader
                                    novalidate>

                                    <?= csrf_field() ?>

                                    <div class="mb-4">
                                        <label
                                            for="emailAddress"
                                            class="form-label">

                                            <?= $primaryEmail === null
                                                ? 'Email Address'
                                                : 'New Email Address' ?>
                                        </label>

                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i
                                                    class="ri-mail-line"
                                                    aria-hidden="true">
                                                </i>
                                            </span>

                                            <input
                                                type="email"
                                                id="emailAddress"
                                                name="email_address"
                                                class="form-control
                    <?= isset($errors['email_address'])
                                    ? 'is-invalid'
                                    : '' ?>"
                                                value="<?= esc(
                                                            old('email_address'),
                                                            'attr'
                                                        ) ?>"
                                                maxlength="254"
                                                autocomplete="email"
                                                placeholder="Enter your email address"
                                                data-error-required="Please enter an email address."
                                                data-error-email="Please enter a valid email address."
                                                data-error-maxlength="Email address cannot exceed 254 characters."
                                                required>
                                        </div>

                                        <div
                                            class="invalid-feedback
                <?= isset($errors['email_address'])
                                    ? 'd-block'
                                    : '' ?>"
                                            data-validation-error="email_address">

                                            <?= esc(
                                                $errors['email_address']
                                                    ?? ''
                                            ) ?>
                                        </div>

                                        <div class="form-text color-pink">
                                            We will send a verification link that remains
                                            valid for 24 hours.
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button
                                            type="submit"
                                            class="btn
                registration-form__submit
                fs-14
                fw-semibold w-35 text-uppercase"
                                            data-submit-button>

                                            <span data-submit-idle>
                                                <i
                                                    class="ri-mail-send-line me-1 fw-medium"
                                                    aria-hidden="true">
                                                </i>

                                                Save and Send Verification
                                            </span>

                                            <span
                                                class="registration-submit__loading d-none"
                                                data-submit-loading>

                                                <span
                                                    class="spinner-border spinner-border-sm"
                                                    aria-hidden="true">
                                                </span>

                                                Sending...
                                            </span>
                                        </button>
                                    </div>
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
                                    class="d-flex justify-content-end mt-3"
                                    data-submit-loader>

                                    <?= csrf_field() ?>

                                    <button
                                        type="submit"
                                        class="btn btn-outline-primary"
                                        data-submit-button>

                                        <span data-submit-idle>
                                            <i
                                                class="ri-refresh-line me-1"
                                                aria-hidden="true">
                                            </i>

                                            Resend Verification Email
                                        </span>

                                        <span
                                            class="d-none"
                                            data-submit-loading>

                                            <span
                                                class="spinner-border spinner-border-sm"
                                                aria-hidden="true">
                                            </span>

                                            Sending...
                                        </span>
                                    </button>
                                </form>
                            <?php endif; ?>

                        <?php elseif (
                            $activeSection === 'report-profile'
                        ): ?>

                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div>
                                    <h2 class="fs-18 fw-semibold mb-0">
                                        Reported Profiles
                                    </h2>

                                    <p class="text-muted fs-13 mb-0">
                                        Review profiles you have reported
                                        to the support team.
                                    </p>
                                </div>
                            </div>

                            <hr class="my-4">

                            <?php if ($profileReports === []): ?>
                                <div
                                    class="border
                rounded
                text-center
                text-muted
                py-4">

                                    <i
                                        class="ri-flag-line
                    fs-24
                    d-block
                    mb-2"
                                        aria-hidden="true">
                                    </i>

                                    You have not reported any profiles.

                                    <div class="mt-3">
                                        <a
                                            href="<?= route_to(
                                                        'web.search'
                                                    ) ?>"
                                            class="btn btn-outline-primary">

                                            Find a Profile
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table
                                        class="table
                    table-hover
                    align-middle
                    mb-0">

                                        <thead class="bg-info-subtle">
                                            <tr>
                                                <th scope="col">
                                                    Member ID
                                                </th>

                                                <th scope="col">
                                                    Why Reported
                                                </th>

                                                <th scope="col">
                                                    Current Status
                                                </th>

                                                <th scope="col">
                                                    Reported On
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php foreach (
                                                $profileReports as $report
                                            ): ?>
                                                <?php
                                                $status = mb_strtoupper(
                                                    trim(
                                                        (string) (
                                                            $report['status']
                                                            ?? 'OPEN'
                                                        )
                                                    )
                                                );

                                                $statusLabel = match ($status) {
                                                    'REVIEWED' =>
                                                    'Reviewed',

                                                    'DISMISSED' =>
                                                    'Dismissed',

                                                    'ACTION_TAKEN' =>
                                                    'Action Taken',

                                                    default =>
                                                    'Open',
                                                };

                                                $statusClass = match ($status) {
                                                    'DISMISSED' =>
                                                    'bg-secondary-subtle text-body',

                                                    'ACTION_TAKEN' =>
                                                    'bg-danger-subtle text-body',

                                                    'REVIEWED' =>
                                                    'bg-success-subtle text-body',

                                                    default =>
                                                    'bg-warning-subtle text-body',
                                                };

                                                $reportedAt =
                                                    DateDisplay::formatUtcDateTime(
                                                        $report['created_at']
                                                            ?? null
                                                    );

                                                $reportedAtIso =
                                                    DateDisplay::utcToDisplayIso(
                                                        $report['created_at']
                                                            ?? null
                                                    );
                                                ?>

                                                <tr>
                                                    <td>
                                                        <span
                                                            class="badge
                                        bg-primary-subtle
                                        text-body
                                        p-2">

                                                            <?= esc(
                                                                $report['reported_profile_reference'] ?? '—'
                                                            ) ?>
                                                        </span>
                                                    </td>

                                                    <td class="text-break">
                                                        <?= esc(
                                                            $report['description']
                                                                ?? '—'
                                                        ) ?>
                                                    </td>

                                                    <td>
                                                        <span
                                                            class="badge
                                        <?= esc(
                                                    $statusClass,
                                                    'attr'
                                                ) ?>
                                        p-2">

                                                            <?= esc($statusLabel) ?>
                                                        </span>
                                                    </td>

                                                    <td class="text-nowrap">
                                                        <?php if (
                                                            $reportedAtIso !== ''
                                                        ): ?>
                                                            <time
                                                                datetime="<?= esc(
                                                                                $reportedAtIso,
                                                                                'attr'
                                                                            ) ?>">

                                                                <?= esc(
                                                                    $reportedAt
                                                                ) ?>
                                                            </time>
                                                        <?php else: ?>
                                                            <?= esc(
                                                                $reportedAt
                                                            ) ?>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                        <?php elseif (
                            $activeSection === 'plans'
                        ): ?>

                            <div class="text-center mb-4">

                                <p
                                    class="
                fs-13
                fw-semibold
                text-danger
                text-uppercase
                mb-2
            ">
                                    Membership Plans
                                </p>

                                <h2 class="fs-22 fw-semibold mb-2">
                                    Choose Your Membership
                                </h2>

                                <p class="text-muted mb-0">
                                    Find the plan that best fits
                                    your matrimonial search.
                                </p>

                            </div>

                            <?= view(
                                'Components/Membership/PlanCards',
                                [
                                    /*
         * All commercial values come from membership_plans.
         */
                                    'plans' =>
                                    $membershipPlans['plans']
                                        ?? [],

                                    /*
         * Current account determines Current Plan presentation.
         */
                                    'currentAccount' =>
                                    $membershipPlans['currentAccount'] ?? [],

                                    'context' =>
                                    'member',
                                ]
                            ) ?>

                        <?php elseif (
                            $activeSection === 'membership-history'
                        ): ?>

                            <!--
                                Membership & Usage is intentionally one Account Settings section.

                                Membership Usage:
                                    Current paid membership allowance consumption.

                                Membership History:
                                    Current and previous membership lifecycle/purchase history.

                                Keeping both here avoids creating two overlapping navigation items.
                            -->

                            <?php if (
                                ($membershipUsage['isPaid'] ?? false)
                                === true
                            ): ?>

                                <?= view(
                                    'Pages/AccountSettings/_MembershipUsage',
                                    [
                                        'membershipUsage' =>
                                        $membershipUsage
                                            ?? [],
                                    ]
                                ) ?>

                                <hr class="my-4">

                            <?php endif; ?>

                            <?= view(
                                'Pages/AccountSettings/_MembershipHistory',
                                [
                                    'membershipHistory' =>
                                    $membershipHistory
                                        ?? [],
                                ]
                            ) ?>

                        <?php elseif (
                            $activeSection ===
                            'communication-preferences'
                        ): ?>

                            <?= view(
                                'Pages/AccountSettings/CommunicationPreferences',
                                [
                                    'communicationPreferences' =>
                                    $communicationPreferences
                                        ?? [],
                                ]
                            ) ?>

                        <?php else: ?>

                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span
                                    class="avatar-sm flex-shrink-0">

                                    <span
                                        class="avatar-title
                    rounded-circle
                    bg-primary-subtle
                    text-primary">

                                        <i
                                            class="ri-customer-service-2-line"
                                            aria-hidden="true">
                                        </i>
                                    </span>
                                </span>

                                <div>
                                    <h2 class="fs-18 fw-semibold mb-0">
                                        Contact Us
                                    </h2>

                                    <p class="text-muted fs-13 mb-0">
                                        Review your previous requests or send
                                        a new message to our support team.
                                    </p>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h3 class="fs-16 fw-semibold mb-3">
                                Request History
                            </h3>

                            <?php if ($contactRequests === []): ?>
                                <div
                                    class="border rounded
                text-center text-muted py-4 mb-4">

                                    <i
                                        class="ri-inbox-line fs-24 d-block mb-2"
                                        aria-hidden="true">
                                    </i>

                                    You have not raised any support requests.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive mb-4">
                                    <table
                                        class="table
                    table-hover
                    align-middle
                    mb-0">

                                        <thead class="bg-info-subtle">
                                            <tr>
                                                <th scope="col">
                                                    Request ID
                                                </th>

                                                <th scope="col">
                                                    Message
                                                </th>

                                                <th scope="col">
                                                    Status
                                                </th>

                                                <th scope="col">
                                                    Resolution
                                                </th>

                                                <th scope="col">
                                                    Raised On
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php foreach (
                                                $contactRequests as $request
                                            ): ?>
                                                <?php
                                                $requestStatus = mb_strtoupper(
                                                    trim(
                                                        (string) (
                                                            $request['status']
                                                            ?? 'OPEN'
                                                        )
                                                    )
                                                );

                                                $isResolved =
                                                    $requestStatus === 'RESOLVED';

                                                $raisedDateTime =
                                                    DateDisplay::formatUtcDateTime(
                                                        $request['created_at']
                                                            ?? null
                                                    );

                                                $raisedIso =
                                                    DateDisplay::utcToDisplayIso(
                                                        $request['created_at']
                                                            ?? null
                                                    );

                                                $resolvedDateTime =
                                                    DateDisplay::formatUtcDateTime(
                                                        $request['reviewed_at']
                                                            ?? null,
                                                        ''
                                                    );

                                                $resolvedIso =
                                                    DateDisplay::utcToDisplayIso(
                                                        $request['reviewed_at']
                                                            ?? null
                                                    );

                                                $resolutionMessage = trim(
                                                    (string) (
                                                        $request['response_note']
                                                        ?? ''
                                                    )
                                                );
                                                ?>

                                                <tr>
                                                    <td>
                                                        <span
                                                            class="badge
                                        bg-primary-subtle
                                        text-body
                                        p-2">

                                                            <?= esc(
                                                                $request['request_reference'] ?? '—'
                                                            ) ?>
                                                        </span>
                                                    </td>

                                                    <td class="text-break">
                                                        <?= esc(
                                                            $request['message']
                                                                ?? '—'
                                                        ) ?>
                                                    </td>

                                                    <td>
                                                        <span
                                                            class="badge
                                        <?= $isResolved
                                                    ? 'bg-success-subtle text-body'
                                                    : 'bg-warning-subtle text-body' ?>
                                        p-2">

                                                            <?= $isResolved
                                                                ? 'Resolved'
                                                                : 'Open' ?>
                                                        </span>
                                                    </td>

                                                    <td class="text-break">
                                                        <?php if (
                                                            $isResolved
                                                            && $resolutionMessage !== ''
                                                        ): ?>
                                                            <div>
                                                                <?= esc($resolutionMessage) ?>
                                                            </div>

                                                            <?php if (
                                                                $resolvedDateTime !== ''
                                                            ): ?>
                                                                <div class="small text-muted mt-1">
                                                                    Resolved on

                                                                    <time
                                                                        datetime="<?= esc(
                                                                                        $resolvedIso,
                                                                                        'attr'
                                                                                    ) ?>">

                                                                        <?= esc($resolvedDateTime) ?>
                                                                    </time>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php elseif ($isResolved): ?>
                                                            <span class="text-muted">
                                                                Resolved by the support team
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">
                                                                Awaiting response
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>

                                                    <td class="text-nowrap">
                                                        <time
                                                            datetime="<?= esc(
                                                                            $raisedIso,
                                                                            'attr'
                                                                        ) ?>">

                                                            <?= esc($raisedDateTime) ?>
                                                        </time>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                            <div class="border-top pt-4">
                                <h3 class="fs-16 fw-semibold mb-1">
                                    Create New Request
                                </h3>

                                <p class="text-muted fs-13">
                                    Describe how our support team can help you.
                                </p>

                                <form
                                    method="post"
                                    action="<?= route_to(
                                                'web.account.settings.contact'
                                            ) ?>"
                                    data-validate
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
                                            rows="4"
                                            minlength="10"
                                            maxlength="255"
                                            class="form-control
                        <?= isset($errors['message'])
                                ? 'is-invalid'
                                : '' ?>"
                                            placeholder="Enter your message"
                                            data-error-required="Please enter your message."
                                            data-error-minlength="Please enter at least 10 characters."
                                            data-error-maxlength="Message cannot exceed 255 characters."
                                            required><?= esc(
                                                            old('message')
                                                        ) ?></textarea>

                                        <div
                                            class="invalid-feedback
                        <?= isset($errors['message'])
                                ? 'd-block'
                                : '' ?>"
                                            data-validation-error="message">

                                            <?= esc(
                                                $errors['message']
                                                    ?? ''
                                            ) ?>
                                        </div>

                                        <div class="form-text text-end">
                                            Maximum 255 characters
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label
                                            for="contactCaptchaAnswer"
                                            class="form-label">

                                            Security Verification
                                        </label>

                                        <div
                                            class="border rounded p-2 mb-2
                        bg-light border-primary-subtle">

                                            <div
                                                class="d-flex
                            align-items-center
                            justify-content-between">

                                                <span class="text-muted">
                                                    Solve this question
                                                </span>

                                                <span class="fw-bold fs-18">
                                                    <?= esc($contactCaptcha) ?> = ?
                                                </span>
                                            </div>
                                        </div>

                                        <input
                                            type="text"
                                            id="contactCaptchaAnswer"
                                            name="captcha_answer"
                                            class="form-control
                        <?= isset(
                                $errors['captcha_answer']
                            )
                                ? 'is-invalid'
                                : '' ?>"
                                            placeholder="Enter answer"
                                            inputmode="numeric"
                                            autocomplete="off"
                                            maxlength="2"
                                            pattern="[0-9]{1,2}"
                                            data-error-required="Please enter the security answer."
                                            data-error-pattern="Please enter a valid security answer."
                                            required>

                                        <div
                                            class="invalid-feedback
                        <?= isset(
                                $errors['captcha_answer']
                            )
                                ? 'd-block'
                                : '' ?>"
                                            data-validation-error="captcha_answer">

                                            <?= esc(
                                                $errors['captcha_answer']
                                                    ?? ''
                                            ) ?>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button
                                            type="submit"
                                            class="btn
                        registration-form__submit
                        fs-14 fw-semibold text-uppercase w-25"
                                            data-submit-button>

                                            <span data-submit-idle>
                                                <i
                                                    class="ri-send-plane-line me-1 fw-medium"
                                                    aria-hidden="true">
                                                </i>

                                                Send Request
                                            </span>

                                            <span
                                                class="registration-submit__loading
                            d-none"
                                                data-submit-loading>

                                                <span
                                                    class="spinner-border
                                spinner-border-sm">
                                                </span>

                                                Sending...
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            </div>

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