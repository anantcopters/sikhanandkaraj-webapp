<?php

declare(strict_types=1);

/**
 * @var string $fullName
 * @var string $gender
 * @var string $mobileNumber
 * @var string $profileReference
 * @var string $source
 */

$resolvedName =
    trim(
        (string) ($fullName ?? '')
    );

$resolvedGender =
    trim(
        (string) ($gender ?? '')
    );

$resolvedMobile =
    trim(
        (string) ($mobileNumber ?? '')
    );

$resolvedProfileReference =
    trim(
        (string) ($profileReference ?? '')
    );

$resolvedSource =
    trim(
        (string) ($source ?? '')
    );

ob_start();
?>

<p
    style="
        margin:0 0 18px;
        font-size:15px;
        line-height:1.7;
    ">
    A new profile has been created on
    Sikhanandkaraj.
</p>

<table
    role="presentation"
    width="100%"
    cellpadding="0"
    cellspacing="0"
    style="
        width:100%;
        border-collapse:collapse;
        margin:0;
    ">

    <tr>
        <td
            style="
                padding:10px 12px;
                border-bottom:1px solid #eeeeee;
                color:#6c757d;
                font-size:14px;
                width:38%;
            ">
            Name
        </td>

        <td
            style="
                padding:10px 12px;
                border-bottom:1px solid #eeeeee;
                font-size:14px;
                font-weight:600;
            ">
            <?= esc($resolvedName) ?>
        </td>
    </tr>

    <tr>
        <td
            style="
                padding:10px 12px;
                border-bottom:1px solid #eeeeee;
                color:#6c757d;
                font-size:14px;
            ">
            Gender
        </td>

        <td
            style="
                padding:10px 12px;
                border-bottom:1px solid #eeeeee;
                font-size:14px;
                font-weight:600;
            ">
            <?= esc($resolvedGender) ?>
        </td>
    </tr>

    <tr>
        <td
            style="
                padding:10px 12px;
                border-bottom:1px solid #eeeeee;
                color:#6c757d;
                font-size:14px;
            ">
            Mobile Number
        </td>

        <td
            style="
                padding:10px 12px;
                border-bottom:1px solid #eeeeee;
                font-size:14px;
                font-weight:600;
            ">
            <?= esc($resolvedMobile) ?>
        </td>
    </tr>

    <?php if ($resolvedProfileReference !== ''): ?>

        <tr>
            <td
                style="
                    padding:10px 12px;
                    border-bottom:1px solid #eeeeee;
                    color:#6c757d;
                    font-size:14px;
                ">
                Profile ID
            </td>

            <td
                style="
                    padding:10px 12px;
                    border-bottom:1px solid #eeeeee;
                    font-size:14px;
                    font-weight:600;
                ">
                <?= esc($resolvedProfileReference) ?>
            </td>
        </tr>

    <?php endif; ?>

    <?php if ($resolvedSource !== ''): ?>

        <tr>
            <td
                style="
                    padding:10px 12px;
                    color:#6c757d;
                    font-size:14px;
                ">
                Created Via
            </td>

            <td
                style="
                    padding:10px 12px;
                    font-size:14px;
                    font-weight:600;
                ">
                <?= esc($resolvedSource) ?>
            </td>
        </tr>

    <?php endif; ?>

</table>

<?php

$emailContent =
    (string) ob_get_clean();

echo view(
    'Emails/Layouts/Base',
    [
        'emailTitle' =>
        'New Profile Created',

        'emailSubtitle' =>
        'New Profile',

        'emailContent' =>
        $emailContent,
    ]
);
