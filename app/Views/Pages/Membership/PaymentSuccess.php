<?php

declare(strict_types=1);

/**
 * @var string|null $pageTitle
 * @var array<string, mixed> $payment
 * @var string $amountDisplay
 * @var string $expiresAtDisplay
 */

$planName =
    trim(
        (string) (
            $payment['plan_name_snapshot']
            ?? ''
        )
    );

$planCode =
    mb_strtoupper(
        trim(
            (string) (
                $payment['plan_code_snapshot']
                ?? ''
            )
        )
    );

$transactionReference =
    trim(
        (string) (
            $payment['transaction_reference']
            ?? ''
        )
    );

$this
    ->setVar(
        'footerView',
        'Components/Home/Footer'
    )
    ->extend(
        'Layouts/Main'
    );

$this->section(
    'content'
);
?>

<section class="section py-5 light-yellowish">

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-12 col-md-9 col-lg-7 col-xl-6">

                <article
                    class="
                        card
                        border
                        border-danger
                        border-opacity-25
                        shadow-sm
                        mb-0
                    ">

                    <div class="card-body p-4 p-lg-5">

                        <div class="text-center mb-4">

                            <div class="mb-3">

                                <i
                                    class="
                                        ri-checkbox-circle-fill
                                        text-success
                                        fs-48
                                    "
                                    aria-hidden="true">
                                </i>

                            </div>

                            <p
                                class="
                                    fs-13
                                    fw-semibold
                                    text-success
                                    text-uppercase
                                    mb-2
                                ">
                                Payment Successful
                            </p>

                            <h1 class="fs-28 fw-bold mb-3">
                                Membership Activated
                            </h1>

                            <p
                                class="
                                    text-secondary
                                    lh-lg
                                    mb-0
                                ">

                                Your Sikhanandkaraj

                                <strong class="text-body">
                                    <?= esc(
                                        $planName !== ''
                                            ? $planName
                                            : $planCode
                                    ) ?>
                                </strong>

                                membership has been activated successfully.

                            </p>

                        </div>

                        <div
                            class="
                                border
                                rounded
                                p-3
                                mb-4
                            ">

                            <div
                                class="
                                    d-flex
                                    justify-content-between
                                    align-items-center
                                    gap-3
                                    py-2
                                    border-bottom
                                ">

                                <span class="text-muted">
                                    Plan
                                </span>

                                <strong>
                                    <?= esc(
                                        $planCode
                                    ) ?>
                                </strong>

                            </div>

                            <div
                                class="
                                    d-flex
                                    justify-content-between
                                    align-items-center
                                    gap-3
                                    py-2
                                    border-bottom
                                ">

                                <span class="text-muted">
                                    Amount
                                </span>

                                <strong>
                                    ₹<?= esc(
                                            $amountDisplay
                                        ) ?>
                                </strong>

                            </div>

                            <?php if (
                                $expiresAtDisplay !== ''
                            ): ?>

                                <div
                                    class="
                                        d-flex
                                        justify-content-between
                                        align-items-center
                                        gap-3
                                        py-2
                                        border-bottom
                                    ">

                                    <span class="text-muted">
                                        Membership valid till
                                    </span>

                                    <strong>
                                        <?= esc(
                                            $expiresAtDisplay
                                        ) ?>
                                    </strong>

                                </div>

                            <?php endif; ?>

                            <div
                                class="
                                    d-flex
                                    justify-content-between
                                    align-items-center
                                    gap-3
                                    py-2
                                ">

                                <span class="text-muted">
                                    Transaction ID
                                </span>

                                <strong class="text-break text-end">
                                    <?= esc(
                                        $transactionReference
                                    ) ?>
                                </strong>

                            </div>

                        </div>

                        <?php if (
                            ENVIRONMENT === 'development'
                        ): ?>

                            <div
                                class="
                                    alert
                                    alert-info
                                    fs-12
                                    mb-4
                                "
                                role="status">

                                <i
                                    class="ri-information-line me-1"
                                    aria-hidden="true">
                                </i>

                                Development environment:
                                payment was completed using the payment
                                simulator.

                            </div>

                        <?php endif; ?>

                        <div
                            class="
                                d-flex
                                flex-column
                                flex-sm-row
                                justify-content-center
                                gap-2
                            ">

                            <a
                                href="<?= route_to(
                                            'web.dashboard'
                                        ) ?>"
                                class="
                                    btn
                                    btn-danger
                                ">

                                <i
                                    class="
                                        ri-home-4-line
                                        me-1
                                    "
                                    aria-hidden="true">
                                </i>

                                Go to Dashboard

                            </a>

                            <a
                                href="<?= route_to(
                                            'web.account.settings.section',
                                            'plans'
                                        ) ?>"
                                class="
                                    btn
                                    btn-outline-danger
                                ">

                                <i
                                    class="
                                        ri-vip-crown-line
                                        me-1
                                    "
                                    aria-hidden="true">
                                </i>

                                View Membership

                            </a>

                        </div>

                    </div>

                </article>

            </div>

        </div>

    </div>

</section>

<?= $this->endSection() ?>