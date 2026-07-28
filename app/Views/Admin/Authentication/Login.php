<?php

declare(strict_types=1);

/**
 * @var string|null                $pageTitle
 * @var array<string, string>|null $validationErrors
 * @var array<string, string>|null $formAlert
 * @var string|null                $adminLoginIdentifier
 */

$pageTitle = isset($pageTitle)
    ? trim((string) $pageTitle)
    : 'Administrator Login';

$validationErrors = isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$formAlert = isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$adminLoginIdentifier = isset($adminLoginIdentifier)
    ? trim((string) $adminLoginIdentifier)
    : '';

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>

<div class="auth-page-wrapper min-vh-100">
    <div class="auth-page-content d-flex align-items-center py-5">
        <div class="container">

            <div class="row justify-content-center">
                <div
                    class="col-md-8 col-lg-6 col-xl-5">

                    <div class="card border border-danger border-opacity-25 mb-0">
                        <div class="card-body p-4">

                            <div class="text-center mt-2">

                                <div
                                    class="avatar-md mx-auto mb-3">
                                    <div
                                        class="avatar-title
                                            rounded-circle
                                            bg-primary-subtle
                                            text-primary fs-24">
                                        <i
                                            class="ri-shield-user-line">
                                        </i>
                                    </div>
                                </div>

                                <h1 class="fs-20">
                                    Administrator Login
                                </h1>

                                <p class="text-muted mb-0">
                                    Sign in using your verified
                                    email address or mobile number.
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
                                                        $adminLoginIdentifier,
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

                        </div>
                    </div>

                    <p
                        class="text-center text-muted
                            mt-4 mb-0">
                        <i
                            class="ri-lock-2-line
                                text-danger me-1">
                        </i>
                        Restricted administration access
                    </p>

                </div>
            </div>

        </div>
    </div>
</div>

<?php $this->endSection(); ?>