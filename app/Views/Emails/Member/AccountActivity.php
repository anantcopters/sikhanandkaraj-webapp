<?php

declare(strict_types=1);

/**
 * Generic member account/security communication.
 *
 * Reuses the existing common Email Base layout and Button component.
 *
 * @var string $userName
 * @var string $heading
 * @var string $message
 * @var string $actionUrl
 * @var string $actionLabel
 * @var string $emailSubtitle
 * @var string $securityNotice
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

$resolvedSubtitle =
    trim(
        (string) (
            $emailSubtitle
            ?? ''
        )
    );

if ($resolvedSubtitle === '') {
    $resolvedSubtitle =
        'Account Update';
}

$resolvedSecurityNotice =
    trim(
        (string) (
            $securityNotice
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

<?php if (
    $resolvedActionUrl !== ''
    && $resolvedActionLabel !== ''
): ?>

    <?= view(
        'Emails/Components/Button',
        [
            'url' =>
            $resolvedActionUrl,

            'label' =>
            $resolvedActionLabel,
        ]
    ) ?>

<?php endif; ?>

<?php if (
    $resolvedSecurityNotice !== ''
): ?>

    <div
        style="
            margin:20px 0 0;
            padding:14px 16px;
            background:#fff7f7;
            border-left:3px solid #ce102c;
            color:#51475a;
            font-size:13px;
            line-height:1.6;
        ">

        <?= esc(
            $resolvedSecurityNotice
        ) ?>

    </div>

<?php else: ?>

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

        For your privacy, sensitive account
        information is not included in this email.
        Sign in to Sikhanandkaraj to view the
        latest information available to your
        account.

    </div>

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
        $resolvedSubtitle,

        'emailContent' =>
        $emailContent,
    ]
);
