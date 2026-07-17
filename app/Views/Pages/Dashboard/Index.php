<?php

declare(strict_types=1);

/**
 * @var string|null $profileReference
 * @var string|null $loggedInUserName
 */

/**
 * @var string|null $primaryEmail
 * @var bool $isEmailVerified
 */

$formAlert = session('formAlert');

$formAlert = is_array($formAlert)
    ? $formAlert
    : null;

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="pb-5 pt-3">
    <div class="container">

        <?= view('Components/Alerts/FormAlert', [
            'alert' => $formAlert,
        ]) ?>

        <div class="card">
            <div class="card-body p-4">
                <h1 class="fs-22 mb-2">
                    Welcome,
                    <?= esc(
                        $loggedInUserName ?? 'Member'
                    ) ?>
                </h1>

                <?php if (
                    is_string($profileReference)
                    && $profileReference !== ''
                ): ?>
                    <p class="text-muted mb-0">
                        Profile reference:
                        <strong>
                            <?= esc($profileReference) ?>
                        </strong>
                    </p>
                <?php endif; ?>

                <!-- Keep this form for dashboard-security.js. -->
                <form
                    method="post"
                    action="<?= url_to('web.logout') ?>"
                    id="dashboardLogoutForm"
                    class="d-none">

                    <?= csrf_field() ?>
                </form>
            </div>
        </div>
        <?php if (
            is_string($primaryEmail)
            && $primaryEmail !== ''
            && !$isEmailVerified
        ): ?>
            <div
                class="email-verification-alert"
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
                            <strong>
                                <?= esc($primaryEmail) ?>
                            </strong>
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
                    class="email-verification-alert__form">

                    <?= csrf_field() ?>

                    <button
                        type="submit"
                        class="btn email-verification-alert__action">
                        Send verification email
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php $this->endSection(); ?>