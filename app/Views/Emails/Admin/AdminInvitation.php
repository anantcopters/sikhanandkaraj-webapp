<?php

declare(strict_types=1);

/**
 * @var string $adminName
 * @var string $invitationUrl
 * @var int|string $expiresInHours
 */

$resolvedName =
    trim(
        (string) ($adminName ?? '')
    );

if ($resolvedName === '') {
    $resolvedName =
        'Administrator';
}

$resolvedUrl =
    trim(
        (string) ($invitationUrl ?? '')
    );

$resolvedHours =
    max(
        1,
        (int) ($expiresInHours ?? 24)
    );

ob_start();
?>

<p
    style="
        margin:0 0 18px;
        font-size:15px;
        font-weight:500;
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
    You have been invited to become
    an administrator for Sikhanandkaraj.
</p>

<p
    style="
        margin:0 0 18px;
        font-size:15px;
        line-height:1.7;
    ">
    Use the button below to verify
    your email address and create
    your administrator password.
</p>

<?= view(
    'Emails/Components/Button',
    [
        'url' =>
        $resolvedUrl,

        'label' =>
        'Verify and Create Password',
    ]
) ?>

<p
    style="
        margin:0 0 10px;
        color:#6c757d;
        font-size:13px;
        line-height:1.6;
    ">
    This secure invitation is valid for
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
    If you were not expecting this
    invitation, you may safely ignore
    this email.
</p>

<?php

$emailContent =
    (string) ob_get_clean();

echo view(
    'Emails/Layouts/Base',
    [
        'emailTitle' =>
        'Administrator Invitation',

        'emailSubtitle' =>
        'Administrator Invitation',

        'emailContent' =>
        $emailContent,
    ]
);
