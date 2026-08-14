<?php

declare(strict_types=1);

$errors = session('validationErrors');
$validationErrors = is_array($errors)
    ? $errors
    : [];

$input = session('adminFormInput');
$formInput = is_array($input)
    ? $input
    : [];

$alert = session('formAlert');
$formAlert = is_array($alert)
    ? $alert
    : null;

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>


<div class="container-fluid">

    <div class="row">
        <div class="col-12">
            <div
                class="page-title-box
                d-sm-flex align-items-center
                justify-content-between">

                <div>
                    <h4 class="mb-sm-0">
                        Add Administrator
                    </h4>

                    <p class="text-muted mb-0 mt-1">
                        The mobile number will be marked verified.
                        The administrator must verify the email and
                        create a password using the invitation link.
                    </p>
                </div>

                <div class="page-title-right mt-3 mt-sm-0">
                    <a
                        href="<?= route_to(
                                    'admin.users.index'
                                ) ?>"
                        class="btn btn-soft-secondary">

                        <i
                            class="ri-arrow-left-line
                            align-middle me-1">
                        </i>

                        Back to Administrators
                    </a>
                </div>

            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">

            <div class="card border border-danger border-opacity-25">

                <div class="card-header">
                    <h4 class="card-title mb-0">
                        Administrator Details
                    </h4>
                </div>

                <div class="card-body">
                    <?= view(
                        'Components/Alerts/FormAlert',
                        [
                            'alert' => $formAlert,
                        ]
                    ) ?>

                    <form
                        action="<?= route_to(
                                    'admin.users.store'
                                ) ?>"
                        method="post"
                        data-validate
                        data-submit-loader
                        novalidate>

                        <?= csrf_field() ?>

                        <div class="row g-3">

                            <div class="col-12">
                                <label
                                    for="adminFullName"
                                    class="form-label">
                                    Name
                                </label>

                                <input
                                    type="text"
                                    id="adminFullName"
                                    name="full_name"
                                    class="form-control
                                        <?= isset(
                                            $validationErrors['full_name']
                                        )
                                            ? 'is-invalid'
                                            : '' ?>"
                                    value="<?= esc(
                                                $formInput['full_name'] ?? '',
                                                'attr'
                                            ) ?>"
                                    maxlength="150"
                                    required>

                                <div class="invalid-feedback">
                                    <?= esc(
                                        $validationErrors['full_name'] ?? ''
                                    ) ?>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label
                                    for="adminMobile"
                                    class="form-label">
                                    Mobile Number
                                </label>

                                <div class="input-group">
                                    <span class="input-group-text">
                                        +91
                                    </span>

                                    <input
                                        type="tel"
                                        id="adminMobile"
                                        name="mobile_number"
                                        class="form-control
                                            <?= isset(
                                                $validationErrors['mobile_number']
                                            )
                                                ? 'is-invalid'
                                                : '' ?>"
                                        value="<?= esc(
                                                    $formInput['mobile_number'] ?? '',
                                                    'attr'
                                                ) ?>"
                                        inputmode="numeric"
                                        maxlength="10"
                                        required>
                                </div>

                                <div class="form-text color-pink">
                                    Enter the member’s active mobile or WhatsApp number.
                                </div>

                                <?php if (
                                    isset(
                                        $validationErrors['mobile_number']
                                    )
                                ): ?>
                                    <div
                                        class="text-danger fs-13 mt-1">
                                        <?= esc(
                                            $validationErrors['mobile_number']
                                        ) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-12 col-md-6">
                                <label
                                    for="adminEmail"
                                    class="form-label">
                                    Email Address
                                </label>

                                <input
                                    type="email"
                                    id="adminEmail"
                                    name="email_address"
                                    class="form-control
                                        <?= isset(
                                            $validationErrors['email_address']
                                        )
                                            ? 'is-invalid'
                                            : '' ?>"
                                    value="<?= esc(
                                                $formInput['email_address'] ?? '',
                                                'attr'
                                            ) ?>"
                                    maxlength="254"
                                    required>

                                <div class="invalid-feedback">
                                    <?= esc(
                                        $validationErrors['email_address'] ?? ''
                                    ) ?>
                                </div>
                            </div>

                        </div>

                        <div
                            class="alert alert-primary
        d-flex align-items-start gap-2 mt-4 mb-0"
                            role="alert">

                            <i
                                class="ri-mail-send-line
            fs-20 flex-shrink-0">
                            </i>

                            <div>
                                <h6 class="alert-heading mb-1">
                                    Invitation email
                                </h6>

                                <p class="mb-0">
                                    A one-time link valid for 24 hours
                                    will be queued after the administrator
                                    is created.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 d-grid d-sm-flex justify-content-sm-end">

                            <button
                                type="submit"
                                class="btn registration-form__submit
                                fs-16 fw-semibold w-100 w-sm-auto"
                                data-submit-button>

                                <span data-submit-idle>
                                    Add and Send Invitation
                                </span>

                                <span
                                    class="registration-submit__loading
                                        d-none"
                                    data-submit-loading>
                                    <span
                                        class="spinner-border
                                            spinner-border-sm">
                                    </span>
                                    Creating administrator...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>


<?php $this->endSection(); ?>