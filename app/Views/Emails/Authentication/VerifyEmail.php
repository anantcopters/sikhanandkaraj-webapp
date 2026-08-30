<?php

declare(strict_types=1);

/**
 * @var string $userName
 * @var string $emailAddress
 * @var string $verificationUrl
 * @var int    $expiresInHours
 * @var bool   $isReplacement
 */

$resolvedName =
    trim(
        (string) ($userName ?? '')
    );

if ($resolvedName === '') {
    $resolvedName = 'Member';
}

$resolvedEmail =
    trim(
        (string) ($emailAddress ?? '')
    );

$resolvedUrl =
    trim(
        (string) ($verificationUrl ?? '')
    );

$resolvedHours =
    max(
        1,
        (int) ($expiresInHours ?? 24)
    );

$isReplacement =
    ($isReplacement ?? false) === true;

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

<p
    style="
        margin:0 0 18px;
        font-size:15px;
        line-height:1.7;
    ">
    Please confirm that
    <strong>
        <?= esc($resolvedEmail) ?>
    </strong>
    should be linked with your
    Sikhanandkaraj account.
</p>

<?php if ($isReplacement): ?>

    <div
        style="
            margin:20px 0;
            padding:14px 16px;
            background:#f7f2fa;
            border-left:3px solid #310a57;
            color:#51475a;
            font-size:14px;
            line-height:1.6;
        ">

        Your current verified email
        will remain active until this
        new email address is confirmed.

    </div>

<?php endif; ?>

<?= view(
    'Emails/Components/Button',
    [
        'url' =>
        $resolvedUrl,

        'label' =>
        'Verify Email Address',
    ]
) ?>

<p
    style="
        margin:0 0 10px;
        color:#6c757d;
        font-size:13px;
        line-height:1.6;
    ">
    This secure link is valid for
    <?= esc(
        (string) $resolvedHours
    ) ?>
    hours and can be used only once.
</p>

<p
    style="
        margin:0;
        color:#6c757d;
        font-size:13px;
        line-height:1.6;
    ">
    If you did not request this change,
    you can safely ignore this email.
</p>

<?php

$emailContent =
    (string) ob_get_clean();

echo view(
    'Emails/Layouts/Base',
    [
        'emailTitle' =>
        'Verify Your Email Address',

        'emailSubtitle' =>
        'Email Verification',

        'emailContent' =>
        $emailContent,
    ]
);
