<?php

declare(strict_types=1);

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

<section class="admin-auth-section">
    <div class="container">
        <div
            class="row justify-content-center
                align-items-center min-vh-100 py-4">

            <div
                class="col-12 col-sm-10
                    col-md-7 col-lg-5">

                <div class="admin-auth-card">

                    <div class="admin-auth-card__header">
                        <div class="admin-auth-icon">
                            <i class="ri-key-2-line"></i>
                        </div>

                        <h1>Create Your Password</h1>

                        <p>
                            Welcome,
                            <?= esc(
                                $admin['full_name']
                            ) ?>.
                            Create your password to verify and
                            activate your administrator account.
                        </p>
                    </div>

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
                            class="btn registration-form__submit"
                            data-submit-button>

                            <span data-submit-idle>
                                Verify Account and Set Password
                            </span>

                            <span
                                class="registration-submit__loading
                                    d-none"
                                data-submit-loading>
                                <span
                                    class="spinner-border
                                        spinner-border-sm">
                                </span>
                                Activating account...
                            </span>
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>