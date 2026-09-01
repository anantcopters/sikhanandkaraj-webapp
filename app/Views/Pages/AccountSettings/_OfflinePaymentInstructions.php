<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $offlinePayment
 * @var array<string, mixed> $user
 */

$payment = isset($offlinePayment)
    && is_array($offlinePayment)
    ? $offlinePayment
    : [];

$member = isset($user)
    && is_array($user)
    ? $user
    : [];

$memberName = trim(
    (string) (
        $member['full_name']
        ?? ''
    )
);

$profileReference = trim(
    (string) (
        $member['profile_ref_number']
        ?? ''
    )
);

$whatsappNumbers =
    isset($payment['whatsappNumbers'])
    && is_array(
        $payment['whatsappNumbers']
    )
    ? $payment['whatsappNumbers']
    : [];

$hasBankDetails =
    trim(
        (string) (
            $payment['accountNumber']
            ?? ''
        )
    ) !== '';

$hasUpi =
    trim(
        (string) (
            $payment['upiId']
            ?? ''
        )
    ) !== '';

?>

<div
    class="card border shadow-sm mb-4">

    <div class="card-body p-3 p-lg-4">

        <div
            class="d-flex align-items-start
                gap-3 mb-3">

            <div class="flex-shrink-0">

                <div class="avatar-sm flex-shrink-0" aria-hidden="true">

                    <span class="avatar-title rounded-circle
                            bg-primary-subtle
                            text-primary fs-20">
                        <i class="ri-bank-card-line"></i>
                    </span>
                </div>
            </div>

            <div>
                <h2 class="fs-18 fw-semibold mb-1">
                    Purchase Membership
                </h2>

                <p class="text-muted fs-13 mb-0">
                    Online payment is currently unavailable.
                    You can purchase your membership using
                    the payment details below.
                </p>
            </div>
        </div>

        <?php if (
            $hasBankDetails
            || $hasUpi
        ): ?>

            <div class="row g-3 mb-3">

                <?php if ($hasBankDetails): ?>
                    <div class="col-12 col-lg-7">
                        <div
                            class="border rounded p-3 h-100 bg-light-subtle shadow-none bg-opacity-10">

                            <h3
                                class="fs-15 fw-semibold mb-3">
                                Bank Transfer
                            </h3>

                            <dl class="row fs-13 mb-0">

                                <?php if (
                                    trim(
                                        (string) (
                                            $payment['accountName']
                                            ?? ''
                                        )
                                    ) !== ''
                                ): ?>
                                    <dt class="col-5 text-muted">
                                        Account Name
                                    </dt>

                                    <dd class="col-7 fw-medium">
                                        <?= esc(
                                            $payment['accountName']
                                        ) ?>
                                    </dd>
                                <?php endif; ?>

                                <?php if (
                                    trim(
                                        (string) (
                                            $payment['bankName']
                                            ?? ''
                                        )
                                    ) !== ''
                                ): ?>
                                    <dt class="col-5 text-muted">
                                        Bank
                                    </dt>

                                    <dd class="col-7 fw-medium">
                                        <?= esc(
                                            $payment['bankName']
                                        ) ?>
                                    </dd>
                                <?php endif; ?>

                                <dt class="col-5 text-muted">
                                    Account Number
                                </dt>

                                <dd class="col-7 fw-medium">
                                    <?= esc(
                                        $payment['accountNumber']
                                    ) ?>
                                </dd>

                                <?php if (
                                    trim(
                                        (string) (
                                            $payment['ifscCode']
                                            ?? ''
                                        )
                                    ) !== ''
                                ): ?>
                                    <dt class="col-5 text-muted">
                                        IFSC
                                    </dt>

                                    <dd class="col-7 fw-medium mb-0">
                                        <?= esc(
                                            $payment['ifscCode']
                                        ) ?>
                                    </dd>
                                <?php endif; ?>

                            </dl>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($hasUpi): ?>
                    <div class="col-12 col-lg-5">
                        <div
                            class="border rounded p-3 h-100 bg-light-subtle shadow-none bg-opacity-10">

                            <h3
                                class="fs-15 fw-semibold mb-3">
                                UPI
                            </h3>

                            <p class="text-muted fs-13 mb-1">
                                UPI ID
                            </p>

                            <p class="fw-semibold mb-0">
                                <?= esc(
                                    $payment['upiId']
                                ) ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        <?php endif; ?>

        <div
            class="alert alert-warning
                border mb-0">

            <div
                class="d-flex align-items-start gap-2">

                <i
                    class="ri-whatsapp-line fs-22"
                    aria-hidden="true">
                </i>

                <div>
                    <h3
                        class="fs-15 fw-semibold mb-2">
                        After making the payment
                    </h3>

                    <p class="fs-13 mb-2">
                        Please WhatsApp the payment receipt
                        or screenshot along with the member
                        name and Profile ID shown below.
                    </p>

                    <div
                        class="bg-white border rounded p-3 mb-2">

                        <div class="fs-13 mb-1">
                            <span class="text-muted">
                                Member Name:
                            </span>

                            <strong>
                                <?= esc(
                                    $memberName !== ''
                                        ? $memberName
                                        : '—'
                                ) ?>
                            </strong>
                        </div>

                        <div class="fs-13">
                            <span class="text-muted">
                                Profile ID:
                            </span>

                            <strong class="color-pink">
                                <?= esc(
                                    $profileReference !== ''
                                        ? $profileReference
                                        : '—'
                                ) ?>
                            </strong>
                        </div>
                    </div>

                    <?php if (
                        $whatsappNumbers !== []
                    ): ?>

                        <p class="fs-13 mb-2">
                            <strong>
                                WhatsApp:
                            </strong>

                            <?= esc(
                                implode(
                                    ' / ',
                                    $whatsappNumbers
                                )
                            ) ?>
                        </p>

                    <?php endif; ?>

                    <p class="fs-12 text-muted mb-0">
                        Membership will be activated after
                        SikhanandKaraj verifies the payment.
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>