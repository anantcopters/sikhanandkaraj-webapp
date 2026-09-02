<?php

declare(strict_types=1);

/**
 * @var string $userName
 * @var int $profileViewCount
 * @var int $uniqueViewerCount
 * @var int $shortlistCount
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

$resolvedProfileViewCount =
    max(
        0,
        (int) (
            $profileViewCount
            ?? 0
        )
    );

$resolvedUniqueViewerCount =
    max(
        0,
        (int) (
            $uniqueViewerCount
            ?? 0
        )
    );

$resolvedShortlistCount =
    max(
        0,
        (int) (
            $shortlistCount
            ?? 0
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
    Your profile has new activity
</h2>

<p
    style="
        margin:0 0 20px;
        font-size:15px;
        line-height:1.7;
    ">
    Here is a summary of recent activity
    on your Sikhanandkaraj profile.
</p>

<table
    role="presentation"
    width="100%"
    cellpadding="0"
    cellspacing="0"
    border="0"
    style="
        margin:0 0 20px;
        border-collapse:collapse;
    ">

    <?php if (
        $resolvedProfileViewCount > 0
    ): ?>

        <tr>
            <td
                style="
                    padding:12px 14px;
                    border:1px solid #eadfe9;
                    font-size:14px;
                    line-height:1.5;
                ">
                Profile views
            </td>

            <td
                align="right"
                style="
                    padding:12px 14px;
                    border:1px solid #eadfe9;
                    color:#310a57;
                    font-size:16px;
                    font-weight:600;
                    line-height:1.5;
                ">
                <?= esc(
                    (string)
                    $resolvedProfileViewCount
                ) ?>
            </td>
        </tr>

        <tr>
            <td
                style="
                    padding:12px 14px;
                    border:1px solid #eadfe9;
                    font-size:14px;
                    line-height:1.5;
                ">
                Members who viewed your profile
            </td>

            <td
                align="right"
                style="
                    padding:12px 14px;
                    border:1px solid #eadfe9;
                    color:#310a57;
                    font-size:16px;
                    font-weight:600;
                    line-height:1.5;
                ">
                <?= esc(
                    (string)
                    $resolvedUniqueViewerCount
                ) ?>
            </td>
        </tr>

    <?php endif; ?>

    <?php if (
        $resolvedShortlistCount > 0
    ): ?>

        <tr>
            <td
                style="
                    padding:12px 14px;
                    border:1px solid #eadfe9;
                    font-size:14px;
                    line-height:1.5;
                ">
                New shortlists
            </td>

            <td
                align="right"
                style="
                    padding:12px 14px;
                    border:1px solid #eadfe9;
                    color:#310a57;
                    font-size:16px;
                    font-weight:600;
                    line-height:1.5;
                ">
                <?= esc(
                    (string)
                    $resolvedShortlistCount
                ) ?>
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

    For your privacy, member names, profile
    details and contact information are not
    included in this email. Sign in to
    Sikhanandkaraj to view the latest activity
    available to your account.

</div>

<?php

$emailContent =
    (string) ob_get_clean();

echo view(
    'Emails/Layouts/Base',
    [
        'emailTitle' =>
        'Your profile has new activity',

        'emailSubtitle' =>
        'Profile Activity',

        'emailContent' =>
        $emailContent,
    ]
);
