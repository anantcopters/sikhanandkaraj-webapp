<?php

declare(strict_types=1);

/**
 * @var string $userName
 * @var string $heading
 * @var string $message
 * @var string $reason
 * @var string $actionUrl
 * @var string $actionLabel
 */

$resolvedName =
    trim(
        (string) ($userName ?? '')
    );

if ($resolvedName === '') {
    $resolvedName = 'Member';
}

$resolvedHeading =
    trim(
        (string) ($heading ?? '')
    );

$resolvedMessage =
    trim(
        (string) ($message ?? '')
    );

$resolvedReason =
    trim(
        (string) ($reason ?? '')
    );

$resolvedActionUrl =
    trim(
        (string) ($actionUrl ?? '')
    );

$resolvedActionLabel =
    trim(
        (string) ($actionLabel ?? '')
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

<?php if ($resolvedReason !== ''): ?>

    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        border="0"
        style="
            margin:0 0 20px;
            background:#fff7f7;
            border-left:3px solid #ce102c;
        ">

        <tr>
            <td
                style="
                    padding:14px 16px;
                    color:#51475a;
                    font-size:13px;
                    line-height:1.6;
                ">

                <strong
                    style="
                        color:#310a57;
                    ">
                    Reason:
                </strong>

                <?= esc($resolvedReason) ?>

            </td>
        </tr>

    </table>

<?php endif; ?>

<?= view(
    'Emails/Components/Button',
    [
        'url' =>
        $resolvedActionUrl,

        'label' =>
        $resolvedActionLabel,
    ]
) ?>

<p
    style="
        margin:20px 0 0;
        color:#6c757d;
        font-size:13px;
        line-height:1.6;
    ">
    For your privacy, sensitive verification
    information is not included in this email.
    Sign in to Sikhanandkaraj to review the
    latest status securely.
</p>

<?php

$emailContent =
    (string) ob_get_clean();

echo view(
    'Emails/Layouts/Base',
    [
        'emailTitle' =>
        $resolvedHeading,

        'emailSubtitle' =>
        'Verification & Profile Update',

        'emailContent' =>
        $emailContent,
    ]
);
