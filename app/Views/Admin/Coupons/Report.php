<?php

declare(strict_types=1);

/**
 * @var array<string, mixed>       $coupon
 * @var list<array<string, mixed>> $redemptions
 * @var array<string, mixed>       $summary
 */

$resolvedCoupon =
    isset($coupon)
    && is_array($coupon)
    ? $coupon
    : [];

$resolvedRedemptions =
    isset($redemptions)
    && is_array($redemptions)
    ? $redemptions
    : [];

$resolvedSummary =
    isset($summary)
    && is_array($summary)
    ? $summary
    : [];

$completedCount =
    (int) (
        $resolvedSummary['completed_count']
        ?? 0
    );

$usageLimit =
    (int) (
        $resolvedSummary['usage_limit']
        ?? 0
    );

$totalOriginalPrice =
    ((int) (
        $resolvedSummary['total_original_price_paise']
        ?? 0
    )) / 100;

$totalDiscount =
    ((int) (
        $resolvedSummary['total_discount_paise']
        ?? 0
    )) / 100;

$totalFinalPayable =
    ((int) (
        $resolvedSummary['total_final_payable_paise']
        ?? 0
    )) / 100;

$this->extend(
    'Admin/Layouts/Main'
);

$this->section(
    'content'
);
?>

<div class="container-fluid">

    <div class="row">
        <div class="col-12">

            <div
                class="
                    page-title-box
                    d-sm-flex
                    align-items-sm-center
                    justify-content-between
                    gap-3
                ">

                <div>
                    <h4 class="mb-sm-0">
                        Coupon Report
                    </h4>

                    <p class="text-muted mb-0">

                        <?= esc(
                            $resolvedCoupon['code']
                                ?? ''
                        ) ?>

                        · Redemption and financial history

                    </p>
                </div>

                <a
                    href="<?= route_to(
                                'admin.coupons.index'
                            ) ?>"
                    class="
                        btn
                        btn-light
                        d-inline-flex
                        align-items-center
                        gap-1
                    ">

                    <i
                        class="ri-arrow-left-line"
                        aria-hidden="true">
                    </i>

                    Back to Coupons

                </a>

            </div>

        </div>
    </div>

    <div class="row g-3 mb-3">

        <div class="col-12 col-md-3">

            <div
                class="
                    card
                    border
                    border-danger
                    border-opacity-25
                    h-100
                ">

                <div class="card-body">

                    <div class="text-muted fs-13 mb-1">
                        Successful Redemptions
                    </div>

                    <div class="fs-22 fw-semibold">

                        <?= $completedCount ?>

                        <span
                            class="
                                fs-14
                                fw-normal
                                text-muted
                            ">
                            / <?= $usageLimit ?>
                        </span>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-12 col-md-3">

            <div
                class="
                    card
                    border
                    border-danger
                    border-opacity-25
                    h-100
                ">

                <div class="card-body">

                    <div class="text-muted fs-13 mb-1">
                        Original Gross
                    </div>

                    <div class="fs-22 fw-semibold">

                        ₹<?= number_format(
                                $totalOriginalPrice,
                                2
                            ) ?>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-12 col-md-3">

            <div
                class="
                    card
                    border
                    border-danger
                    border-opacity-25
                    h-100
                ">

                <div class="card-body">

                    <div class="text-muted fs-13 mb-1">
                        Total Discount
                    </div>

                    <div class="fs-22 fw-semibold">

                        ₹<?= number_format(
                                $totalDiscount,
                                2
                            ) ?>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-12 col-md-3">

            <div
                class="
                    card
                    border
                    border-danger
                    border-opacity-25
                    h-100
                ">

                <div class="card-body">

                    <div class="text-muted fs-13 mb-1">
                        Final Payable
                    </div>

                    <div class="fs-22 fw-semibold">

                        ₹<?= number_format(
                                $totalFinalPayable,
                                2
                            ) ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <div
        class="
            card
            border
            border-danger
            border-opacity-25
        ">

        <div class="card-header bg-info-subtle">

            <h5 class="card-title mb-0">
                Redemption History
            </h5>

        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table
                    class="
                        table
                        table-hover
                        table-nowrap
                        align-middle
                        mb-0
                    ">

                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Plan</th>
                            <th>Original Price</th>
                            <th>Discount</th>
                            <th>Final Payable</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Redeemed On</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (
                            $resolvedRedemptions === []
                        ): ?>

                            <tr>
                                <td
                                    colspan="8"
                                    class="
                                        text-center
                                        text-muted
                                        py-4
                                    ">

                                    <i
                                        class="
                                            ri-file-chart-line
                                            fs-24
                                            d-block
                                            mb-2
                                        "
                                        aria-hidden="true">
                                    </i>

                                    This coupon has not been
                                    redeemed yet.

                                </td>
                            </tr>

                        <?php endif; ?>

                        <?php foreach (
                            $resolvedRedemptions
                            as $redemption
                        ): ?>

                            <?php

                            $memberName =
                                trim(
                                    (string) (
                                        $redemption['first_name']
                                        ?? ''
                                    )
                                        . ' '
                                        . (string) (
                                            $redemption['last_name']
                                            ?? ''
                                        )
                                );

                            $profileId =
                                trim(
                                    (string) (
                                        $redemption['profile_id']
                                        ?? ''
                                    )
                                );

                            $status =
                                mb_strtoupper(
                                    trim(
                                        (string) (
                                            $redemption['status']
                                            ?? ''
                                        )
                                    )
                                );

                            $statusClass =
                                $status === 'COMPLETED'
                                ? 'bg-success-subtle text-success'
                                : 'bg-secondary-subtle text-body';

                            $paymentMethod =
                                trim(
                                    (string) (
                                        $redemption['payment_method']
                                        ?? ''
                                    )
                                );

                            $paymentMethodDisplay =
                                $paymentMethod !== ''
                                ? ucwords(
                                    strtolower(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $paymentMethod
                                        )
                                    )
                                )
                                : '—';

                            ?>

                            <tr>

                                <td>

                                    <div class="fw-medium">
                                        <?= esc(
                                            $memberName !== ''
                                                ? $memberName
                                                : 'Member'
                                        ) ?>
                                    </div>

                                    <?php if (
                                        $profileId !== ''
                                    ): ?>

                                        <div
                                            class="
                                                small
                                                text-muted
                                            ">

                                            <?= esc(
                                                $profileId
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?= esc(
                                        $redemption['plan_name']
                                            ?? $redemption['plan_code']
                                            ?? '—'
                                    ) ?>

                                </td>

                                <td>

                                    ₹<?= number_format(
                                            ((int) (
                                                $redemption['plan_price_paise']
                                                ?? 0
                                            )) / 100,
                                            2
                                        ) ?>

                                </td>

                                <td>

                                    ₹<?= number_format(
                                            ((int) (
                                                $redemption['discount_amount_paise']
                                                ?? 0
                                            )) / 100,
                                            2
                                        ) ?>

                                </td>

                                <td class="fw-semibold">

                                    ₹<?= number_format(
                                            ((int) (
                                                $redemption['final_payable_paise']
                                                ?? 0
                                            )) / 100,
                                            2
                                        ) ?>

                                </td>

                                <td>

                                    <?= esc(
                                        $paymentMethodDisplay
                                    ) ?>

                                </td>

                                <td>

                                    <span
                                        class="
                                            badge
                                            <?= esc(
                                                $statusClass,
                                                'attr'
                                            ) ?>
                                            p-2
                                        ">

                                        <?= esc(
                                            $status !== ''
                                                ? $status
                                                : '—'
                                        ) ?>

                                    </span>

                                </td>

                                <td>

                                    <?= esc(
                                        $redemption['redeemed_at']
                                            ?? '—'
                                    ) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<?php
$this->endSection();
