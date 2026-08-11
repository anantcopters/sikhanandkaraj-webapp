<?php

declare(strict_types=1);

/**
 * SAK Volunteer self-registration success screen.
 *
 * Controller supplied variables.
 *
 * @var string|null $pageTitle
 * @var string|null $officerCode
 */

$pageTitle = trim(
    (string) (
        $pageTitle
        ?? 'Registration Submitted'
    )
);

if ($pageTitle === '') {
    $pageTitle =
        'Registration Submitted';
}

$officerCode = strtoupper(
    trim(
        (string) (
            $officerCode
            ?? ''
        )
    )
);

$hasOfficerCode =
    $officerCode !== '';

$loginUrl =
    route_to(
        'field-officer.login'
    );

$this->extend(
    'FieldOfficer/Layouts/Main'
);

$this->section('content');
?>

<div class="py-5">

    <div class="container">

        <div
            class="row
            justify-content-center">

            <div
                class="col-12
                col-md-8
                col-lg-6">

                <div
                    class="card
                    border
                    border-danger
                    border-opacity-25">

                    <div
                        class="card-body
                        p-4
                        text-center">

                        <div
                            class="avatar-md
                            mx-auto
                            mb-3">

                            <div
                                class="avatar-title
                                rounded-circle
                                bg-success-subtle
                                text-success
                                fs-24">

                                <i
                                    class="ri-checkbox-circle-line"
                                    aria-hidden="true">
                                </i>

                            </div>

                        </div>

                        <h1
                            class="fs-20
                            mb-2">

                            Registration Submitted

                        </h1>

                        <p
                            class="text-muted
                            mb-4">

                            Your details have been saved
                            successfully. They will be checked
                            and approved in due time.

                        </p>

                        <?php if (
                            $hasOfficerCode
                        ): ?>

                            <div
                                class="border
                                rounded
                                bg-light
                                p-3
                                mb-3">

                                <div
                                    class="text-muted
                                    fs-13
                                    mb-1">

                                    Your SAK Volunteer Code

                                </div>

                                <div
                                    class="fs-20
                                    fw-bold
                                    text-primary">

                                    <?= esc(
                                        $officerCode
                                    ) ?>

                                </div>

                            </div>

                            <p
                                class="text-muted
                                mb-4">

                                Please note this code and
                                mention it in any future
                                communication regarding your
                                SAK Volunteer registration.

                            </p>

                        <?php else: ?>

                            <p
                                class="text-muted
                                mb-4">

                                Your registration has been
                                recorded successfully.

                            </p>

                        <?php endif; ?>

                        <a
                            href="<?= esc(
                                        $loginUrl,
                                        'attr'
                                    ) ?>"
                            class="btn
                            btn-soft-primary
                            btn-md">

                            <i
                                class="ri-login-box-line
                                me-1"
                                aria-hidden="true">
                            </i>

                            Back to Login

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php $this->endSection(); ?>