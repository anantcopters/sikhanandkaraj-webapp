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
                    col-md-7 col-lg-5 col-xl-4">

                <div class="admin-auth-card">

                    <div class="admin-auth-card__header">
                        <div class="admin-auth-icon">
                            <i
                                class="ri-shield-user-line"
                                aria-hidden="true">
                            </i>
                        </div>

                        <h1>Administrator Login</h1>

                        <p>
                            Sign in using your verified email
                            address or mobile number.
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
                                    'admin.login.submit'
                                ) ?>"
                        method="post"
                        data-validate
                        data-submit-loader
                        novalidate>

                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label
                                class="form-label"
                                for="adminIdentifier">
                                Email or Mobile Number
                            </label>

                            <input
                                class="form-control
                                    <?= isset(
                                        $validationErrors['identifier']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                                type="text"
                                id="adminIdentifier"
                                name="identifier"
                                value="<?= esc(
                                            session(
                                                'adminLoginIdentifier'
                                            ) ?? '',
                                            'attr'
                                        ) ?>"
                                placeholder="Email or mobile number"
                                autocomplete="username"
                                maxlength="254"
                                required>

                            <div class="invalid-feedback">
                                <?= esc(
                                    $validationErrors['identifier'] ?? ''
                                ) ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label
                                class="form-label"
                                for="adminPassword">
                                Password
                            </label>

                            <div class="password-field">
                                <input
                                    class="form-control
                                        password-field__input
                                        <?= isset(
                                            $validationErrors['password']
                                        )
                                            ? 'is-invalid'
                                            : '' ?>"
                                    type="password"
                                    id="adminPassword"
                                    name="password"
                                    placeholder="Enter password"
                                    maxlength="128"
                                    autocomplete="current-password"
                                    required>

                                <button
                                    type="button"
                                    class="password-field__toggle"
                                    data-password-toggle="adminPassword"
                                    aria-label="Show password">
                                    <span
                                        class="mdi mdi-eye-off-outline"
                                        aria-hidden="true">
                                    </span>
                                </button>
                            </div>

                            <div class="invalid-feedback d-block">
                                <?= esc(
                                    $validationErrors['password'] ?? ''
                                ) ?>
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="btn registration-form__submit
                                fs-16 fw-semibold"
                            data-submit-button>

                            <span data-submit-idle>
                                Login to Administration
                            </span>

                            <span
                                class="registration-submit__loading
                                    d-none"
                                data-submit-loading>
                                <span
                                    class="spinner-border
                                        spinner-border-sm">
                                </span>
                                Checking credentials...
                            </span>
                        </button>
                    </form>

                </div>

                <p class="admin-auth-security">
                    <i
                        class="ri-lock-2-line"
                        aria-hidden="true">
                    </i>
                    Restricted administration access
                </p>
            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>
