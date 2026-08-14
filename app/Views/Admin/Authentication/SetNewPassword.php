<?php

declare(strict_types=1);

/**
 * @var string|null                $pageTitle
 * @var array<string, string>|null $validationErrors
 * @var array<string, string>|null $formAlert
 */

$pageTitle =
    isset($pageTitle)
    ? trim((string) $pageTitle)
    : 'Set New Administrator Password';

$validationErrors =
    isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$formAlert =
    isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$this->extend(
    'Admin/Layouts/Main'
);

$this->section(
    'content'
);
?>

<div class="auth-page-wrapper min-vh-100">

    <div
        class="auth-page-content
            d-flex
            align-items-center
            py-5">

        <div class="container">

            <div class="row justify-content-center">

                <div
                    class="col-md-8
                        col-lg-6
                        col-xl-5">

                    <div
                        class="card
                            border
                            border-danger
                            border-opacity-25
                            mb-0">

                        <div class="card-body p-4">

                            <div class="text-center mt-2">

                                <div
                                    class="avatar-md
                                        mx-auto
                                        mb-3">

                                    <div
                                        class="avatar-title
                                            rounded-circle
                                            bg-primary-subtle
                                            text-primary
                                            fs-24">

                                        <i
                                            class="ri-lock-password-line"
                                            aria-hidden="true">
                                        </i>

                                    </div>

                                </div>

                                <h1 class="fs-20">
                                    <?= esc($pageTitle) ?>
                                </h1>

                                <p class="text-muted mb-0">
                                    Create a new password for your
                                    administrator account.
                                </p>

                            </div>

                            <div class="p-2 mt-4">

                                <?= view(
                                    'Components/Alerts/FormAlert',
                                    [
                                        'alert' =>
                                        $formAlert,
                                    ]
                                ) ?>

                                <form
                                    action="<?= esc(
                                                route_to(
                                                    'admin.forgot-password.password.update'
                                                ),
                                                'attr'
                                            ) ?>"
                                    method="post"
                                    data-submit-loader
                                    novalidate>

                                    <?= csrf_field() ?>

                                    <div class="mb-3">

                                        <label
                                            class="form-label"
                                            for="adminNewPassword">

                                            New Password

                                        </label>

                                        <div
                                            class="password-field">

                                            <input
                                                type="password"
                                                id="adminNewPassword"
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
                                                data-password-toggle="adminNewPassword"
                                                aria-label="Show password">

                                                <span
                                                    class="mdi
                                                        mdi-eye-off-outline"
                                                    aria-hidden="true">
                                                </span>

                                            </button>

                                        </div>

                                        <div
                                            class="invalid-feedback
                                                d-block">

                                            <?= esc(
                                                $validationErrors['password']
                                                    ?? ''
                                            ) ?>

                                        </div>

                                    </div>

                                    <div class="mb-4">

                                        <label
                                            class="form-label"
                                            for="adminConfirmPassword">

                                            Confirm Password

                                        </label>

                                        <div
                                            class="password-field">

                                            <input
                                                type="password"
                                                id="adminConfirmPassword"
                                                name="password_confirmation"
                                                class="form-control
                                                    password-field__input
                                                    <?= isset(
                                                        $validationErrors['password_confirmation']
                                                    )
                                                        ? 'is-invalid'
                                                        : '' ?>"
                                                maxlength="128"
                                                autocomplete="new-password"
                                                required>

                                            <button
                                                type="button"
                                                class="password-field__toggle"
                                                data-password-toggle="adminConfirmPassword"
                                                aria-label="Show password">

                                                <span
                                                    class="mdi
                                                        mdi-eye-off-outline"
                                                    aria-hidden="true">
                                                </span>

                                            </button>

                                        </div>

                                        <div
                                            class="invalid-feedback
                                                d-block">

                                            <?= esc(
                                                $validationErrors['password_confirmation']
                                                    ?? ''
                                            ) ?>

                                        </div>

                                    </div>

                                    <div
                                        class="alert
                                            alert-light
                                            border
                                            mb-4">

                                        <p
                                            class="mb-1
                                                fw-semibold">

                                            Password requirements

                                        </p>

                                        <ul
                                            class="mb-0
                                                ps-3
                                                text-muted
                                                fs-13">

                                            <li>
                                                Minimum 10 characters
                                            </li>

                                            <li>
                                                At least one uppercase letter
                                            </li>

                                            <li>
                                                At least one lowercase letter
                                            </li>

                                            <li>
                                                At least one number
                                            </li>

                                            <li>
                                                At least one special character
                                            </li>

                                        </ul>

                                    </div>

                                    <button
                                        type="submit"
                                        class="btn
                                            registration-form__submit
                                            fs-16
                                            fw-semibold"
                                        data-submit-button>

                                        <span
                                            data-submit-idle>

                                            Change Password

                                        </span>

                                        <span
                                            class="registration-submit__loading
                                                d-none"
                                            data-submit-loading>

                                            <span
                                                class="spinner-border
                                                    spinner-border-sm">
                                            </span>

                                            Changing Password...

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

<?php

$this->endSection();
