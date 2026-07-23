<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $admin
 * @var string $token
 */

$errors = session('validationErrors');

$validationErrors = is_array($errors)
    ? $errors
    : [];

$alert = session('formAlert');

$formAlert = is_array($alert)
    ? $alert
    : null;

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>
<div class="auth-page-wrapper pt-5">
    <div class="auth-page-content">
        <div class="container">
            <div class="row justify-content-center">
                <div
                    class="col-md-8 col-lg-6 col-xl-5">

                    <div class="card border-danger border-opacity-25 mt-4">
                        <div class="card-body p-4">

                            <div class="text-center mt-2">
                                <div
                                    class="avatar-md
                                        mx-auto mb-3">
                                    <div
                                        class="avatar-title
                                            rounded-circle
                                            bg-primary-subtle
                                            text-primary fs-24">
                                        <i class="ri-key-2-line"></i>
                                    </div>
                                </div>

                                <h1 class="fs-20">
                                    Create Your Password
                                </h1>

                                <p class="text-muted">
                                    Welcome,
                                    <?= esc(
                                        $admin['full_name']
                                    ) ?>.
                                </p>
                            </div>

                            <div class="p-2 mt-4">
                                <?= view(
                                    'Components/Alerts/FormAlert',
                                    [
                                        'alert' => $formAlert,
                                    ]
                                ) ?>

                                <form
                                    action="<?= route_to(
                                                'admin.invitation.accept',
                                                $token
                                            ) ?>"
                                    method="post"
                                    data-validate
                                    data-submit-loader
                                    novalidate>

                                    <?= csrf_field() ?>

                                    <div class="mb-3">
                                        <label
                                            for="newAdminPassword"
                                            class="form-label">
                                            Password
                                        </label>

                                        <div class="password-field">
                                            <input
                                                type="password"
                                                id="newAdminPassword"
                                                name="password"
                                                class="form-control
                                        password-field__input
                                        <?= isset(
                                            $validationErrors['password']
                                        )
                                            ? 'is-invalid'
                                            : '' ?>"
                                                maxlength="128"
                                                autocomplete="new-password"
                                                required>

                                            <button
                                                type="button"
                                                class="password-field__toggle"
                                                data-password-toggle="newAdminPassword"
                                                aria-label="Show password">
                                                <span
                                                    class="mdi
                                            mdi-eye-off-outline">
                                                </span>
                                            </button>
                                        </div>

                                        <div class="form-text">
                                            Minimum 10 characters with
                                            uppercase, lowercase, number and
                                            special character.
                                        </div>

                                        <?php if (
                                            isset(
                                                $validationErrors['password']
                                            )
                                        ): ?>
                                            <div
                                                class="text-danger
                                        fs-13 mt-1">
                                                <?= esc(
                                                    $validationErrors['password']
                                                ) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-4">
                                        <label
                                            for="confirmAdminPassword"
                                            class="form-label">
                                            Confirm Password
                                        </label>

                                        <input
                                            type="password"
                                            id="confirmAdminPassword"
                                            name="password_confirmation"
                                            class="form-control
                                    <?= isset(
                                        $validationErrors['password_confirmation']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                                            maxlength="128"
                                            autocomplete="new-password"
                                            required>

                                        <div class="invalid-feedback">
                                            <?= esc(
                                                $validationErrors['password_confirmation'] ?? ''
                                            ) ?>
                                        </div>
                                    </div>

                                    <button
                                        type="submit"
                                        class="btn registration-form__submit fs-16 fw-semibold text-uppercase"
                                        data-submit-button>

                                        <span data-submit-idle aria-hidden="false">
                                            Verify Account and Set Password
                                        </span>

                                        <span
                                            class="registration-submit__loading d-none"
                                            data-submit-loading
                                            aria-hidden="true">

                                            <span
                                                class="spinner-border spinner-border-sm"
                                                role="status"
                                                aria-hidden="true">
                                            </span>

                                            <span>
                                                Activating account...
                                            </span>
                                        </span>
                                    </button>
                                </form>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>