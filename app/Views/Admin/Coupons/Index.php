<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $coupons
 * @var array<string, string>|null $formAlert
 */

$resolvedCoupons =
    isset($coupons)
    && is_array($coupons)
    ? $coupons
    : [];

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
                        Coupon Management
                    </h4>

                    <p class="text-muted mb-0">
                        Create and manage membership
                        promotional pricing.
                    </p>
                </div>

                <div class="page-title-right mt-3 mt-sm-0">

                    <a
                        href="<?= route_to(
                                    'admin.coupons.create'
                                ) ?>"
                        class="
                            btn
                            btn-primary
                            d-inline-flex
                            align-items-center
                            gap-1
                        ">

                        <i
                            class="ri-add-line"
                            aria-hidden="true">
                        </i>

                        Create Coupon

                    </a>

                </div>

            </div>

        </div>
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $formAlert ?? null,
        ]
    ) ?>

    <div
        class="
            card
            border
            border-danger
            border-opacity-25
        ">

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

                    <thead class="bg-info-subtle">
                        <tr>
                            <th>Coupon</th>
                            <th>Discount</th>
                            <th>Plans</th>
                            <th>Eligibility</th>
                            <th>Validity</th>
                            <th>Usage</th>
                            <th>Status</th>

                            <th class="text-end">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (
                            $resolvedCoupons === []
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
                                            ri-coupon-3-line
                                            fs-24
                                            d-block
                                            mb-2
                                        "
                                        aria-hidden="true">
                                    </i>

                                    No coupons have been created.

                                </td>
                            </tr>

                        <?php endif; ?>

                        <?php foreach (
                            $resolvedCoupons
                            as $coupon
                        ): ?>

                            <?php

                            $discountType =
                                (string) (
                                    $coupon['discount_type']
                                    ?? ''
                                );

                            $discountValue =
                                (int) (
                                    $coupon['discount_value']
                                    ?? 0
                                );

                            $discount =
                                $discountType
                                === 'PERCENTAGE'
                                ? $discountValue . '%'
                                : '₹'
                                . number_format(
                                    $discountValue / 100,
                                    2
                                );

                            $planNames =
                                array_values(
                                    array_filter(
                                        array_map(
                                            static fn(
                                                mixed $planName
                                            ): string =>
                                            trim(
                                                (string) $planName
                                            ),
                                            (array) (
                                                $coupon['plan_names']
                                                ?? []
                                            )
                                        ),
                                        static fn(
                                            string $planName
                                        ): bool =>
                                        $planName !== ''
                                    )
                                );

                            $eligibility =
                                match ((string) (
                                    $coupon['eligibility_type']
                                    ?? ''
                                )) {
                                    'ALL' =>
                                    'All Members',

                                    'SELECTED' =>
                                    'Selected Members',

                                    'GENDER' =>
                                    ucfirst(
                                        strtolower(
                                            (string) (
                                                $coupon['eligible_gender']
                                                ?? ''
                                            )
                                        )
                                    )
                                        . ' Members',

                                    default =>
                                    '—',
                                };

                            $status =
                                (string) (
                                    $coupon['effective_status']
                                    ?? ''
                                );

                            $statusClass =
                                match ($status) {
                                    'ACTIVE' =>
                                    'bg-success-subtle text-success',

                                    'SCHEDULED' =>
                                    'bg-info-subtle text-info',

                                    'EXHAUSTED' =>
                                    'bg-warning-subtle text-dark',

                                    'EXPIRED' =>
                                    'bg-secondary-subtle text-body',

                                    default =>
                                    'bg-danger-subtle text-danger',
                                };

                            ?>

                            <tr>

                                <td>
                                    <span class="fw-semibold">
                                        <?= esc(
                                            $coupon['code']
                                                ?? ''
                                        ) ?>
                                    </span>

                                    <?php if (
                                        trim(
                                            (string) (
                                                $coupon['description']
                                                ?? ''
                                            )
                                        ) !== ''
                                    ): ?>

                                        <div class="small text-muted">
                                            <?= esc(
                                                $coupon['description']
                                            ) ?>
                                        </div>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= esc($discount) ?>
                                </td>

                                <td>

                                    <?php if (
                                        $planNames !== []
                                    ): ?>

                                        <?php foreach (
                                            $planNames
                                            as $planName
                                        ): ?>

                                            <span
                                                class="
                                                    badge
                                                    bg-light
                                                    text-body
                                                    border
                                                    me-1
                                                ">

                                                <?= esc(
                                                    $planName
                                                ) ?>

                                            </span>

                                        <?php endforeach; ?>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <?= esc(
                                        $eligibility
                                    ) ?>
                                </td>

                                <td>
                                    <div class="small">
                                        <?= esc(
                                            $coupon['starts_at']
                                                ?? ''
                                        ) ?>
                                    </div>

                                    <div class="small text-muted">
                                        to
                                        <?= esc(
                                            $coupon['expires_at']
                                                ?? ''
                                        ) ?>
                                    </div>
                                </td>

                                <td>
                                    <strong>
                                        <?= (int) (
                                            $coupon['used_count']
                                            ?? 0
                                        ) ?>
                                    </strong>

                                    /

                                    <?= (int) (
                                        $coupon['usage_limit']
                                        ?? 0
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

                                        <?= esc($status) ?>

                                    </span>
                                </td>

                                <td class="text-end">

                                    <a
                                        href="<?= route_to(
                                                    'admin.coupons.edit',
                                                    (int) $coupon['id']
                                                ) ?>"
                                        class="
                                            btn
                                            btn-sm
                                            btn-soft-primary
                                        "
                                        title="Edit coupon">

                                        <i
                                            class="ri-edit-line"
                                            aria-hidden="true">
                                        </i>

                                    </a>

                                    <form
                                        method="post"
                                        action="<?= route_to(
                                                    'admin.coupons.status',
                                                    (int) $coupon['id']
                                                ) ?>"
                                        class="d-inline">

                                        <?= csrf_field() ?>

                                        <?php

                                        $administrativelyActive =
                                            (int) (
                                                $coupon['is_active']
                                                ?? 0
                                            ) === 1;

                                        ?>

                                        <input
                                            type="hidden"
                                            name="status"
                                            value="<?= $administrativelyActive
                                                        ? 'INACTIVE'
                                                        : 'ACTIVE' ?>">

                                        <button
                                            type="submit"
                                            class="
                                                btn
                                                btn-sm
                                                <?= $administrativelyActive
                                                    ? 'btn-soft-danger'
                                                    : 'btn-soft-success' ?>
                                            "
                                            title="<?= $administrativelyActive
                                                        ? 'Deactivate coupon'
                                                        : 'Activate coupon' ?>">

                                            <i
                                                class="<?= $administrativelyActive
                                                            ? 'ri-pause-circle-line'
                                                            : 'ri-play-circle-line' ?>"
                                                aria-hidden="true">
                                            </i>

                                        </button>

                                    </form>

                                    <a
                                        href="<?= route_to(
                                                    'admin.coupons.report',
                                                    (int) $coupon['id']
                                                ) ?>"
                                        class="
                                            btn
                                            btn-sm
                                            btn-soft-info
                                        "
                                        title="Coupon report">

                                        <i
                                            class="ri-file-chart-line"
                                            aria-hidden="true">
                                        </i>

                                    </a>

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
