<?php

declare(strict_types=1);

/**
 * Membership lifecycle transactional email.
 *
 * Used by:
 *
 * - MEMBER_MEMBERSHIP_ACTIVATED
 * - MEMBER_MEMBERSHIP_EXPIRED
 *
 * Payment-provider identifiers and raw provider responses are deliberately
 * excluded. Only Sikhanandkaraj's own transaction reference may be shown.
 *
 * @var string $userName
 * @var string $heading
 * @var string $message
 * @var string $planName
 * @var string $amount
 * @var string $transactionReference
 * @var string $expiresAt
 * @var bool   $isExpired
 * @var string $actionUrl
 * @var string $actionLabel
 */

$resolvedName =
    trim(
        (string) (
            $userName
            ?? ''
        )
    );

if ($resolvedName === '') {
    $resolvedName =
        'Member';
}

$resolvedHeading =
    trim(
        (string) (
            $heading
            ?? ''
        )
    );

$resolvedMessage =
    trim(
        (string) (
            $message
            ?? ''
        )
    );

$resolvedPlanName =
    trim(
        (string) (
            $planName
            ?? ''
        )
    );

$resolvedAmount =
    trim(
        (string) (
            $amount
            ?? ''
        )
    );

$resolvedTransactionReference =
    trim(
        (string) (
            $transactionReference
            ?? ''
        )
    );

$resolvedExpiresAt =
    trim(
        (string) (
            $expiresAt
            ?? ''
        )
    );

$resolvedIsExpired =
    (bool) (
        $isExpired
        ?? false
    );

$resolvedActionUrl =
    trim(
        (string) (
            $actionUrl
            ?? ''
        )
    );

$resolvedActionLabel =
    trim(
        (string) (
            $actionLabel
            ?? ''
        )
    );

ob_start();
?>

<p
    style="
        margin:0 0 18px;
        font-size:15px;
        line-height:1.7;
    ">
    Sat Sri Akal
    <?= esc($resolvedName) ?>,
</p>

<h2
    style="
        margin:0 0 14px;
        color:#310a57;
        font-size:20px;
        line-height:1.35;
    ">
    <?= esc($resolvedHeading) ?>
</h2>

<p
    style="
        margin:0 0 20px;
        font-size:15px;
        line-height:1.7;
    ">
    <?= esc($resolvedMessage) ?>
</p>

<!--
    Commercial summary uses only the immutable membership/payment snapshots.
    Never resolve current pricing here because plan-master values may change
    after the member's purchase.
-->
<table
    role="presentation"
    width="100%"
    cellspacing="0"
    cellpadding="0"
    border="0"
    style="
        margin:0 0 22px;
        border:1px solid #eee8f2;
        border-radius:6px;
    ">

    <?php if ($resolvedPlanName !== ''): ?>

        <tr>
            <td
                style="
                    padding:12px 16px;
                    color:#6c757d;
                    font-size:13px;
                    border-bottom:1px solid #eee8f2;
                ">
                Membership
            </td>

            <td
                align="right"
                style="
                    padding:12px 16px;
                    color:#310a57;
                    font-size:14px;
                    font-weight:600;
                    border-bottom:1px solid #eee8f2;
                ">
                <?= esc($resolvedPlanName) ?>
            </td>
        </tr>

    <?php endif; ?>

    <?php if ($resolvedAmount !== ''): ?>

        <tr>
            <td
                style="
                    padding:12px 16px;
                    color:#6c757d;
                    font-size:13px;
                    border-bottom:1px solid #eee8f2;
                ">
                Amount Paid
            </td>

            <td
                align="right"
                style="
                    padding:12px 16px;
                    color:#310a57;
                    font-size:14px;
                    font-weight:600;
                    border-bottom:1px solid #eee8f2;
                ">
                <?= esc($resolvedAmount) ?>
            </td>
        </tr>

    <?php endif; ?>

    <?php if ($resolvedTransactionReference !== ''): ?>

        <tr>
            <td
                style="
                    padding:12px 16px;
                    color:#6c757d;
                    font-size:13px;
                    border-bottom:1px solid #eee8f2;
                ">
                Transaction Reference
            </td>

            <td
                align="right"
                style="
                    padding:12px 16px;
                    color:#310a57;
                    font-size:13px;
                    font-weight:600;
                    border-bottom:1px solid #eee8f2;
                ">
                <?= esc($resolvedTransactionReference) ?>
            </td>
        </tr>

    <?php endif; ?>

    <?php if ($resolvedExpiresAt !== ''): ?>

        <tr>
            <td
                style="
                    padding:12px 16px;
                    color:#6c757d;
                    font-size:13px;
                ">
                <?= $resolvedIsExpired
                    ? 'Expired On'
                    : 'Valid Until' ?>
            </td>

            <td
                align="right"
                style="
                    padding:12px 16px;
                    color:#310a57;
                    font-size:14px;
                    font-weight:600;
                ">
                <?= esc($resolvedExpiresAt) ?>
            </td>
        </tr>

    <?php endif; ?>

</table>

<?= view(
    'Emails/Components/Button',
    [
        'url' =>
        $resolvedActionUrl,

        'label' =>
        $resolvedActionLabel,
    ]
) ?>

<?php if ($resolvedIsExpired): ?>

    <p
        style="
            margin:20px 0 0;
            color:#6c757d;
            font-size:13px;
            line-height:1.6;
        ">
        Your profile remains on Sikhanandkaraj subject
        to your normal profile settings. Features that
        require a paid membership will follow your
        current membership entitlement.
    </p>

<?php endif; ?>

<?php

$emailContent =
    (string) ob_get_clean();

echo view(
    'Emails/Layouts/Base',
    [
        'emailTitle' =>
        $resolvedHeading,

        'emailSubtitle' =>
        'Membership Update',

        'emailContent' =>
        $emailContent,
    ]
);
