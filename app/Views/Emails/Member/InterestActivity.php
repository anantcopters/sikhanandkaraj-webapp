<?php

declare(strict_types=1);

/**
 * @var string $userName
 * @var string $heading
 * @var string $message
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

<?= view(
    'Emails/Components/Button',
    [
        'url' =>
        $resolvedActionUrl,

        'label' =>
        $resolvedActionLabel,
    ]
) ?>

<div
    style="
        margin:20px 0 0;
        padding:14px 16px;
        background:#f7f2fa;
        border-left:3px solid #310a57;
        color:#51475a;
        font-size:13px;
        line-height:1.6;
    ">

    For your privacy, profile and contact
    details are not included in this email.
    Sign in to Sikhanandkaraj to view the
    latest information available to your
    account.

</div>

<?php

$emailContent =
    (string) ob_get_clean();

echo view(
    'Emails/Layouts/Base',
    [
        'emailTitle' =>
        $resolvedHeading,

        'emailSubtitle' =>
        'Interest Update',

        'emailContent' =>
        $emailContent,
    ]
);
