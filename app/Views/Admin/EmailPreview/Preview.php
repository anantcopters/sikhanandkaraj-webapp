<?php

declare(strict_types=1);

use App\Services\Email\EmailDefinition;

/**
 * @var EmailDefinition $definition
 * @var string $renderedEmail
 */

$alert =
    session('formAlert');

$formAlert =
    is_array($alert)
    ? $alert
    : null;

$validationErrors =
    session('validationErrors');

$validationErrors =
    is_array($validationErrors)
    ? $validationErrors
    : [];

$this->extend(
    'Admin/Layouts/Main'
);

$this->section('content');
?>

<div class="container-fluid">

    <div class="row">
        <div class="col-12">

            <div
                class="page-title-box
                d-sm-flex
                align-items-center
                justify-content-between">

                <div>
                    <h4 class="mb-sm-0">
                        <?= esc(
                            $definition->name
                        ) ?>
                    </h4>

                    <p class="text-muted mb-0 mt-1">
                        <?= esc(
                            $definition->subject
                        ) ?>
                    </p>
                </div>

                <div class="page-title-right">
                    <a
                        href="<?= route_to(
                                    'admin.email-preview.index'
                                ) ?>"
                        class="btn btn-light">
                        <i
                            class="ri-arrow-left-line
                            align-middle me-1">
                        </i>
                        Back
                    </a>
                </div>

            </div>

        </div>
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $formAlert,
        ]
    ) ?>

    <div class="row">

        <div class="col-xl-8">

            <div class="card">

                <div
                    class="card-header
                    d-flex
                    align-items-center
                    justify-content-between">

                    <div>
                        <h5 class="card-title mb-1">
                            Template Preview
                        </h5>

                        <p
                            class="text-muted
                            fs-13 mb-0">
                            Actual rendered email template.
                        </p>
                    </div>

                    <span
                        class="badge
                        bg-primary-subtle
                        text-primary">
                        <?= esc(
                            $definition->category
                        ) ?>
                    </span>

                </div>

                <div class="card-body bg-light">

                    <div
                        class="mx-auto bg-white"
                        style="
                            max-width:620px;
                            overflow:auto;
                        ">

                        <?= $renderedEmail ?>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-xl-4">

            <div class="card">

                <div class="card-header">
                    <h5 class="card-title mb-0">
                        Send Test Email
                    </h5>
                </div>

                <div class="card-body">

                    <p class="text-muted fs-13">
                        The test uses the same template,
                        queue, worker and configured mail
                        provider as normal application
                        email.
                    </p>

                    <form
                        action="<?= route_to(
                                    'admin.email-preview.send-test',
                                    $definition->key
                                ) ?>"
                        method="post"
                        novalidate>

                        <?= csrf_field() ?>

                        <div class="mb-3">

                            <label
                                for="recipient_email"
                                class="form-label">
                                Test Email Address
                            </label>

                            <input
                                type="email"
                                id="recipient_email"
                                name="recipient_email"
                                value="<?= esc(
                                            old(
                                                'recipient_email'
                                            ),
                                            'attr'
                                        ) ?>"
                                class="form-control
                                <?= isset(
                                    $validationErrors['recipient_email']
                                )
                                    ? 'is-invalid'
                                    : '' ?>"
                                maxlength="254"
                                required>

                            <?php if (
                                isset(
                                    $validationErrors['recipient_email']
                                )
                            ): ?>

                                <div
                                    class="invalid-feedback">
                                    <?= esc(
                                        $validationErrors['recipient_email']
                                    ) ?>
                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="text-end">

                            <button
                                type="submit"
                                class="btn
                                registration-form__submit
                                fs-14 fw-medium
                                text-uppercase"
                                data-submit-button>

                                <span
                                    class="registration-submit__idle"
                                    data-submit-idle>

                                    <i
                                        class="mdi
                                        mdi-cloud-upload-outline
                                        fs-20
                                        align-middle me-1">
                                    </i>

                                    Send Test
                                </span>

                                <span
                                    class="registration-submit__loading
                                    d-none"
                                    data-submit-loading>

                                    <span
                                        class="spinner-border
                                        spinner-border-sm
                                        me-1"
                                        role="status"
                                        aria-hidden="true">
                                    </span>

                                    Sending...
                                </span>

                            </button>

                        </div>

                    </form>

                </div>

            </div>

            <div class="card">

                <div class="card-body">

                    <h6 class="mb-3">
                        Template Information
                    </h6>

                    <div class="mb-2">
                        <span class="text-muted">
                            Key:
                        </span>

                        <div class="fw-medium">
                            <?= esc(
                                $definition->key
                            ) ?>
                        </div>
                    </div>

                    <div class="mb-2">
                        <span class="text-muted">
                            Template:
                        </span>

                        <div class="fw-medium">
                            <?= esc(
                                $definition->viewName
                            ) ?>
                        </div>
                    </div>

                    <div>
                        <span class="text-muted">
                            Queue Priority:
                        </span>

                        <div class="fw-medium">
                            <?= esc(
                                (string)
                                $definition->priority
                            ) ?>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php $this->endSection(); ?>