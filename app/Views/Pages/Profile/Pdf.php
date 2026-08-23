<?php

declare(strict_types=1);

use Config\ProfilePdf;

/*
 * =========================================================
 * VIEW-LOCAL DECLARATIONS
 * =========================================================
 */

$config =
    config(
        ProfilePdf::class
    );

$profileReference = trim(
    (string) (
        $profileReference
        ?? ''
    )
);

$fullName = trim(
    (string) (
        $fullName
        ?? ''
    )
);

if ($fullName === '') {
    $fullName =
        'Member Profile';
}

$age =
    isset($age)
    && is_numeric($age)
    ? max(
        0,
        (int) $age
    )
    : null;

$height = trim(
    (string) (
        $height
        ?? ''
    )
);

$location = trim(
    (string) (
        $location
        ?? ''
    )
);

$thumbnail = trim(
    (string) (
        $thumbnail
        ?? ''
    )
);

$aboutMe = trim(
    (string) (
        $aboutMe
        ?? ''
    )
);

$maskedMobile = trim(
    (string) (
        $maskedMobile
        ?? ''
    )
);

$maskedEmail = trim(
    (string) (
        $maskedEmail
        ?? ''
    )
);

$isMobileVerified =
    isset($isMobileVerified)
    && $isMobileVerified === true;

$isEmailVerified =
    isset($isEmailVerified)
    && $isEmailVerified === true;

$isAadhaarVerified =
    isset($isAadhaarVerified)
    && $isAadhaarVerified === true;

$hasVideoIntroduction =
    isset($hasVideoIntroduction)
    && $hasVideoIntroduction === true;

$quickDetails =
    isset($quickDetails)
    && is_array($quickDetails)
    ? $quickDetails
    : [];

$educationRows =
    isset($educationRows)
    && is_array($educationRows)
    ? $educationRows
    : [];

$familyRows =
    isset($familyRows)
    && is_array($familyRows)
    ? $familyRows
    : [];

$lifestyleRows =
    isset($lifestyleRows)
    && is_array($lifestyleRows)
    ? $lifestyleRows
    : [];

$preferences =
    isset($preferences)
    && is_array($preferences)
    ? $preferences
    : [];

$assets =
    isset($assets)
    && is_array($assets)
    ? $assets
    : [];

$icons =
    isset($icons)
    && is_array($icons)
    ? $icons
    : [];

$purple =
    '#310a57';

$red =
    '#ce102c';

$asset =
    static function (
        string $name
    ) use ($assets): string {
        return trim(
            (string) (
                $assets[$name]
                ?? ''
            )
        );
    };

$icon =
    static function (
        string $name,
        string $colour = 'purple'
    ) use ($icons): string {
        $value =
            $icons[$name]
            ?? '';

        /*
         * Backwards-compatible with already prepared data
         * during rollout.
         */
        if (is_string($value)) {
            return trim($value);
        }

        if (!is_array($value)) {
            return '';
        }

        return trim(
            (string) (
                $value[$colour]
                ?? $value['purple']
                ?? ''
            )
        );
    };

$fontRegularUrl =
    $asset(
        'fontRegular'
    );

$fontMediumUrl =
    $asset(
        'fontMedium'
    );

$fontSemiBoldUrl =
    $asset(
        'fontSemiBold'
    );

$fontBoldUrl =
    $asset(
        'fontBold'
    );

$fontRegularCssUrl =
    $fontRegularUrl !== ''
    ? 'url("'
    . $fontRegularUrl
    . '")'
    : 'local("Arial")';

$fontMediumCssUrl =
    $fontMediumUrl !== ''
    ? 'url("'
    . $fontMediumUrl
    . '")'
    : 'local("Arial")';

$fontSemiBoldCssUrl =
    $fontSemiBoldUrl !== ''
    ? 'url("'
    . $fontSemiBoldUrl
    . '")'
    : 'local("Arial")';

$fontBoldCssUrl =
    $fontBoldUrl !== ''
    ? 'url("'
    . $fontBoldUrl
    . '")'
    : 'local("Arial")';

$fontFaceCss = <<<CSS
@font-face {
    font-family: 'InterPDF';
    src: {$fontRegularCssUrl} format('truetype');
    font-style: normal;
    font-weight: 400;
}

@font-face {
    font-family: 'InterPDF';
    src: {$fontMediumCssUrl} format('truetype');
    font-style: normal;
    font-weight: 500;
}

@font-face {
    font-family: 'InterPDF';
    src: {$fontSemiBoldCssUrl} format('truetype');
    font-style: normal;
    font-weight: 600;
}

@font-face {
    font-family: 'InterPDF';
    src: {$fontBoldCssUrl} format('truetype');
    font-style: normal;
    font-weight: 700;
}
CSS;

$logoUrl =
    $asset(
        'logo'
    );

$marriageMotifUrl =
    $asset(
        'marriageMotif'
    );

$headerCornerUrl =
    $asset(
        'headerCorner'
    );

$summary = implode(
    ' • ',
    array_filter(
        [
            $age !== null
                ? $age . ' Years'
                : '',

            $height,

            $location,
        ],
        static fn(
            string $value
        ): bool =>
        trim($value) !== ''
    )
);

$renderRows =
    static function (
        array $rows
    ) use (
        $icon
    ): void {
        $index = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $rowIcon = trim(
                (string) (
                    $row['icon']
                    ?? 'user'
                )
            );

            $rowLabel = trim(
                (string) (
                    $row['label']
                    ?? ''
                )
            );

            $rowValue = trim(
                (string) (
                    $row['value']
                    ?? ''
                )
            );

            if (
                $rowLabel === ''
                || $rowValue === ''
            ) {
                continue;
            }

            $colour =
                $index % 2 === 0
                ? 'purple'
                : 'red';

            $index++;
?>
        <div class="detail-row">

            <div class="detail-icon">

                <?php if (
                    $icon(
                        $rowIcon,
                        $colour
                    ) !== ''
                ): ?>

                    <img
                        src="<?= esc(
                                    $icon(
                                        $rowIcon,
                                        $colour
                                    ),
                                    'attr'
                                ) ?>"
                        alt="">

                <?php endif; ?>

            </div>

            <div class="detail-copy">

                <div class="detail-label">
                    <?= esc(
                        $rowLabel
                    ) ?>
                </div>

                <div class="detail-value">
                    <?= esc(
                        $rowValue
                    ) ?>
                </div>

            </div>

        </div>
<?php
        }
    };
?>
<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <title>
        <?= esc(
            $fullName
        ) ?> - SikhAnandKaraj
    </title>

    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        <?= $fontFaceCss ?>

        /* CSS STart*/
        html,
        body {
            width: 210mm;
            height: 297mm;

            margin: 0;
            padding: 0;

            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family:
                'InterPDF',
                Arial,
                sans-serif;

            color: #201a23;
            background: #fff;
        }

        .pdf-page {
            position: relative;

            width: 210mm;
            height: 297mm;

            overflow: hidden;

            background:
                linear-gradient(180deg,
                    #fff 0%,
                    #fff 87%,
                    #fff9fb 100%);
        }

        .header-corner {
            position: absolute;

            top: 0;
            right: 0;

            width: 42mm;
            height: 42mm;

            opacity: .42;

            z-index: 0;
        }

        .pdf-content {
            position: relative;

            z-index: 1;

            padding:
                5mm 7mm 19mm;
        }

        /*
         * =====================================================
         * HEADER
         * =====================================================
         */

        .pdf-header {
            height: 24mm;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-logo {
            width: 72mm;
            height: 18mm;

            object-fit: contain;
            object-position: left center;
        }

        .header-contact {
            min-width: 54mm;

            display: flex;
            flex-direction: column;

            gap: 1.5mm;

            font-size: 2.75mm;
            font-weight: 500;
        }

        .header-contact-row {
            display: flex;
            align-items: center;

            gap: 2mm;
        }

        .header-contact-row img {
            width: 5mm;
            height: 5mm;
        }

        .header-contact strong {
            color: <?= $red ?>;

            font-size: 3mm;
            font-weight: 700;
        }

        .header-site {
            color: <?= $purple ?>;

            font-weight: 600;
        }

        .header-divider {
            height: 5mm;

            display: flex;
            align-items: center;
        }

        .header-divider-line {
            width: 100%;
            height: .35mm;

            background:
                linear-gradient(90deg,
                    <?= $purple ?>,
                    <?= $red ?>);
        }

        .header-divider img {
            width: 18mm;
            height: 8mm;

            object-fit: contain;
        }

        /*
         * =====================================================
         * PROFILE HERO
         * =====================================================
         */

        .profile-hero {
            display: grid;

            grid-template-columns:
                62mm minmax(0, 1fr) 51mm;

            gap: 4mm;

            margin-top: 2mm;

            align-items: start;
        }

        .profile-photo {
            position: relative;

            width: 62mm;
            height: 54mm;

            padding: 1.8mm;

            overflow: hidden;

            border:
                .35mm solid rgba(206,
                    16,
                    44,
                    .72);

            border-radius: 3.5mm;

            background: #fff;
        }

        .profile-photo-image {
            width: 100%;
            height: 100%;

            object-fit: cover;
            object-position: center;

            border-radius: 2.5mm;

            background: #fff;
        }

        .profile-photo-empty {
            width: 100%;
            height: 100%;

            display: flex;
            align-items: center;
            justify-content: center;

            color: #aa9ead;

            font-size: 2.6mm;
            font-weight: 500;

            border-radius: 2.5mm;

            background:
                linear-gradient(145deg,
                    #faf7fb,
                    #fff);
        }

        .profile-reference {
            position: absolute;

            left: 3mm;
            right: 3mm;
            bottom: 3mm;

            padding:
                1.8mm 1.5mm;

            color: #fff;

            font-size: 2.55mm;
            font-weight: 700;

            text-align: center;

            border-radius: 1.8mm;

            background:
                linear-gradient(90deg,
                    <?= $purple ?>,
                    <?= $red ?>);
        }

        .profile-identity {
            padding-top: 2mm;
        }

        .profile-name {
            margin: 0;

            color: <?= $purple ?>;

            font-size: 7.2mm;
            font-weight: 700;

            line-height: 1.08;
        }

        .profile-summary {
            margin-top: 2.5mm;

            color: #352d39;

            font-size: 3.35mm;
            font-weight: 500;

            line-height: 1.4;
        }

        .profile-divider {
            height: .28mm;

            margin:
                3mm 0 2mm;

            background:
                linear-gradient(90deg,
                    #e8dce9,
                    <?= $red ?>,
                    #e8dce9);
        }

        .quick-grid {
            display: grid;

            grid-template-columns:
                repeat(2,
                    minmax(0, 1fr));
        }

        .quick-item {
            min-height: 13.5mm;

            display: grid;

            grid-template-columns:
                6mm minmax(0, 1fr);

            gap: 1.7mm;

            padding:
                2.2mm 1mm;

            border-bottom:
                .2mm solid #eee8f0;
        }

        .quick-item img {
            width: 5.2mm;
            height: 5.2mm;
        }

        .quick-label {
            color: <?= $purple ?>;

            font-size: 2.6mm;
            font-weight: 600;

            line-height: 1.2;
        }

        .quick-value {
            margin-top: .7mm;

            color: #211b24;

            font-size: 3mm;
            font-weight: 500;

            line-height: 1.3;
        }


        /*
         * =====================================================
         * VERIFIED PROFILE
         * =====================================================
         */

        .verified-card {
            min-height: 54mm;

            padding: 3.3mm;

            border:
                .3mm solid #e1d8e4;

            border-radius: 3.5mm;

            background: #fff;
        }

        .verified-heading {
            padding:
                2.7mm 1mm;

            color: #fff;

            font-size: 3mm;
            font-weight: 700;

            text-align: center;

            border-radius: 1.8mm;

            background:
                linear-gradient(90deg,
                    <?= $purple ?>,
                    <?= $red ?>);
        }

        .verify-row {
            display: grid;

            grid-template-columns:
                6.5mm minmax(0, 1fr);

            gap: 2.2mm;

            padding:
                3.2mm .5mm;

            border-bottom:
                .2mm solid #ece5ee;
        }

        .verify-row:last-child {
            border-bottom: 0;
        }

        .verify-row img {
            width: 6mm;
            height: 6mm;
        }

        .verify-title {
            color: #211b24;

            font-size: 2.9mm;
            font-weight: 600;

            line-height: 1.2;
        }

        .verify-value {
            margin-top: .7mm;

            color: #4f4553;

            font-size: 2.65mm;
            font-weight: 500;

            line-height: 1.3;

            overflow-wrap: anywhere;
        }

        .verify-success {
            color: #198754;
        }

        /*
         * =====================================================
         * CARDS
         * =====================================================
         */

        .card-grid {
            display: grid;

            grid-template-columns:
                repeat(3,
                    minmax(0, 1fr));

            gap: 3mm;

            margin-top: 4mm;

            align-items: stretch;
        }

        .pdf-card {
            overflow: hidden;

            border:
                .28mm solid #dfd6e2;

            border-radius: 2.8mm;

            background: #fff;
        }

        .main-card {
            min-height: 67mm;
        }

        .card-heading {
            min-height: 10.5mm;

            display: flex;
            align-items: center;

            gap: 2.2mm;

            padding:
                1.5mm 3mm;

            color: <?= $purple ?>;

            font-size: 3.2mm;
            font-weight: 700;

            line-height: 1.2;

            border-bottom:
                .22mm solid #e7e0e9;
        }

        .card-heading.red {
            color: <?= $red ?>;
        }

        .card-heading img {
            width: 5.8mm;
            height: 5.8mm;

            object-fit: contain;
        }

        .detail-row {
            min-height: 10mm;

            display: grid;

            grid-template-columns:
                6mm minmax(0, 1fr);

            gap: 2mm;

            padding:
                2mm 3mm;

            border-bottom:
                .18mm solid #eee8f0;
        }

        .detail-row:last-child {
            border-bottom: 0;
        }

        .detail-icon img {
            width: 5mm;
            height: 5mm;

            object-fit: contain;
        }

        .detail-label {
            color: <?= $purple ?>;

            font-size: 2.45mm;
            font-weight: 600;

            line-height: 1.2;
        }

        .detail-value {
            margin-top: .7mm;

            color: #211b24;

            font-size: 2.85mm;
            font-weight: 500;

            line-height: 1.3;

            overflow-wrap: anywhere;
        }


        /*
         * =====================================================
         * BOTTOM GRID
         * =====================================================
         */

        .bottom-grid {
            display: grid;

            grid-template-columns:
                1.05fr .95fr;

            gap: 3mm;

            margin-top: 4mm;
        }

        .bottom-card {
            min-height: 56mm;
        }

        .preference-grid {
            display: grid;

            grid-template-columns:
                repeat(2,
                    minmax(0, 1fr));
        }

        .preference-grid .detail-row {
            min-height: 13mm;
        }

        .about-body {
            position: relative;

            min-height: 45mm;

            padding:
                3.5mm 4mm 16mm;

            overflow: hidden;
        }

        .about-copy {
            position: relative;

            z-index: 2;

            margin: 0;

            color: #2d2630;

            font-size: 2.9mm;
            font-weight: 400;

            line-height: 1.5;

            white-space: pre-line;
        }

        .about-motif {
            position: absolute;

            right: 3mm;
            bottom: -1mm;

            width: 46mm;
            height: 20mm;

            object-fit: contain;

            opacity: .11;

            z-index: 1;
        }

        /*
         * =====================================================
         * FOOTER
         * =====================================================
         */

        .pdf-footer {
            position: absolute;

            left: 0;
            right: 0;
            bottom: 0;

            height: 17mm;

            display: grid;

            grid-template-columns:
                1fr 34mm 1fr;

            align-items: center;

            padding:
                2mm 8mm;

            color: #fff;

            background:
                linear-gradient(90deg,
                    <?= $purple ?> 0%,
                    #77114c 52%,
                    <?= $red ?> 100%);
        }

        .footer-copy {
            font-size: 2.25mm;
            font-weight: 500;

            line-height: 1.45;
        }

        .footer-copy.right {
            text-align: right;
        }

        .footer-copy strong {
            color: #ffd65a;
        }

        .footer-mark {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer-mark img {
            width: 23mm;
            height: 11mm;

            object-fit: contain;
        }
    </style>

</head>

<body>

    <div class="pdf-page">

        <?php if (
            $headerCornerUrl !== ''
        ): ?>

            <img
                class="header-corner"
                src="<?= esc(
                            $headerCornerUrl,
                            'attr'
                        ) ?>"
                alt="">

        <?php endif; ?>

        <div class="pdf-content">

            <header class="pdf-header">

                <div>

                    <?php if (
                        $logoUrl !== ''
                    ): ?>

                        <img
                            class="brand-logo"
                            src="<?= esc(
                                        $logoUrl,
                                        'attr'
                                    ) ?>"
                            alt="SikhAnandKaraj">

                    <?php endif; ?>

                </div>

                <div class="header-contact">

                    <div class="header-contact-row">

                        <?php if (
                            $icon(
                                'phone',
                                'purple'
                            ) !== ''
                        ): ?>

                            <img
                                src="<?= esc(
                                            $icon(
                                                'phone',
                                                'purple'
                                            ),
                                            'attr'
                                        ) ?>"
                                alt="">

                        <?php endif; ?>

                        <div>
                            24x7 Help &amp; Support<br>

                            <strong>
                                <?= esc(
                                    (string) (
                                        $config
                                        ->supportPhone
                                        ?? ''
                                    )
                                ) ?>
                            </strong>
                        </div>

                    </div>

                    <div class="header-contact-row">

                        <?php if (
                            $icon(
                                'location',
                                'purple'
                            ) !== ''
                        ): ?>

                            <img
                                src="<?= esc(
                                            $icon(
                                                'location',
                                                'purple'
                                            ),
                                            'attr'
                                        ) ?>"
                                alt="">

                        <?php endif; ?>

                        <div class="header-site">
                            <?= esc(
                                (string) (
                                    $config
                                    ->website
                                    ?? 'www.sikhanandkaraj.com'
                                )
                            ) ?>
                        </div>

                    </div>

                </div>

            </header>

            <div class="header-divider">
                <div class="header-divider-line"></div>
            </div>

            <section class="profile-hero">

                <div class="profile-photo">

                    <?php if (
                        $thumbnail !== ''
                    ): ?>

                        <img
                            class="profile-photo-image"
                            src="<?= esc(
                                        $thumbnail,
                                        'attr'
                                    ) ?>"
                            alt="<?= esc(
                                        $fullName,
                                        'attr'
                                    ) ?>">

                    <?php else: ?>

                        <div class="profile-photo-empty">
                            Profile photo
                        </div>

                    <?php endif; ?>

                    <?php if (
                        $profileReference !== ''
                    ): ?>

                        <div class="profile-reference">

                            Profile ID:
                            <?= esc(
                                $profileReference
                            ) ?>

                        </div>

                    <?php endif; ?>

                </div>

                <div class="profile-identity">

                    <h1 class="profile-name">
                        <?= esc(
                            $fullName
                        ) ?>
                    </h1>

                    <?php if (
                        $summary !== ''
                    ): ?>

                        <div class="profile-summary">
                            <?= esc(
                                $summary
                            ) ?>
                        </div>

                    <?php endif; ?>

                    <div class="profile-divider"></div>

                    <div class="quick-grid">

                        <?php
                        $quickIndex = 0;

                        foreach (
                            $quickDetails
                            as $detail
                        ):
                            if (!is_array($detail)) {
                                continue;
                            }

                            $label = trim(
                                (string) (
                                    $detail['label']
                                    ?? ''
                                )
                            );

                            $value = trim(
                                (string) (
                                    $detail['value']
                                    ?? ''
                                )
                            );

                            $iconName = trim(
                                (string) (
                                    $detail['icon']
                                    ?? 'user'
                                )
                            );

                            if (
                                $label === ''
                                || $value === ''
                            ) {
                                continue;
                            }

                            $quickColour =
                                $quickIndex % 2 === 0
                                ? 'red'
                                : 'purple';

                            $quickIndex++;
                        ?>

                            <div class="quick-item">

                                <div>

                                    <?php if (
                                        $icon(
                                            $iconName,
                                            $quickColour
                                        ) !== ''
                                    ): ?>

                                        <img
                                            src="<?= esc(
                                                        $icon(
                                                            $iconName,
                                                            $quickColour
                                                        ),
                                                        'attr'
                                                    ) ?>"
                                            alt="">

                                    <?php endif; ?>

                                </div>

                                <div>

                                    <div class="quick-label">
                                        <?= esc(
                                            $label
                                        ) ?>
                                    </div>

                                    <div class="quick-value">
                                        <?= esc(
                                            $value
                                        ) ?>
                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

                <aside class="verified-card">

                    <div class="verified-heading">
                        VERIFIED PROFILE
                    </div>

                    <?php if (
                        $isMobileVerified
                    ): ?>

                        <div class="verify-row">

                            <div>

                                <?php if (
                                    $icon(
                                        'shield-check',
                                        'purple'
                                    ) !== ''
                                ): ?>

                                    <img
                                        src="<?= esc(
                                                    $icon(
                                                        'shield-check',
                                                        'purple'
                                                    ),
                                                    'attr'
                                                ) ?>"
                                        alt="">

                                <?php endif; ?>

                            </div>

                            <div>

                                <div class="verify-title">
                                    Phone Verified
                                </div>

                                <?php if (
                                    $maskedMobile !== ''
                                ): ?>

                                    <div class="verify-value">
                                        <?= esc(
                                            $maskedMobile
                                        ) ?>
                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endif; ?>

                    <?php if (
                        $isEmailVerified
                    ): ?>

                        <div class="verify-row">

                            <div>

                                <?php if (
                                    $icon(
                                        'shield-check',
                                        'purple'
                                    ) !== ''
                                ): ?>

                                    <img
                                        src="<?= esc(
                                                    $icon(
                                                        'shield-check',
                                                        'purple'
                                                    ),
                                                    'attr'
                                                ) ?>"
                                        alt="">

                                <?php endif; ?>

                            </div>

                            <div>

                                <div class="verify-title">
                                    Email Verified
                                </div>

                                <?php if (
                                    $maskedEmail !== ''
                                ): ?>

                                    <div class="verify-value">
                                        <?= esc(
                                            $maskedEmail
                                        ) ?>
                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endif; ?>

                    <?php if (
                        $isAadhaarVerified
                    ): ?>

                        <div class="verify-row">

                            <div>

                                <?php if (
                                    $icon(
                                        'shield-check',
                                        'purple'
                                    ) !== ''
                                ): ?>

                                    <img
                                        src="<?= esc(
                                                    $icon(
                                                        'shield-check',
                                                        'purple'
                                                    ),
                                                    'attr'
                                                ) ?>"
                                        alt="">

                                <?php endif; ?>

                            </div>

                            <div>

                                <div class="verify-title">
                                    Aadhaar Verified
                                </div>

                                <div class="verify-value">
                                    Verified
                                </div>

                            </div>

                        </div>

                    <?php endif; ?>

                    <?php if (
                        $hasVideoIntroduction
                    ): ?>

                        <div class="verify-row">

                            <div>

                                <?php if (
                                    $icon(
                                        'video',
                                        'red'
                                    ) !== ''
                                ): ?>

                                    <img
                                        src="<?= esc(
                                                    $icon(
                                                        'video',
                                                        'red'
                                                    ),
                                                    'attr'
                                                ) ?>"
                                        alt="">

                                <?php endif; ?>

                            </div>

                            <div>

                                <div class="verify-title">
                                    Video Introduction
                                </div>

                                <div
                                    class="verify-value verify-success">
                                    Available
                                </div>

                            </div>

                        </div>

                    <?php endif; ?>

                </aside>

            </section>

            <section class="card-grid">

                <article class="pdf-card main-card">

                    <div class="card-heading">

                        <?php if (
                            $icon(
                                'education',
                                'purple'
                            ) !== ''
                        ): ?>

                            <img
                                src="<?= esc(
                                            $icon(
                                                'education',
                                                'purple'
                                            ),
                                            'attr'
                                        ) ?>"
                                alt="">

                        <?php endif; ?>

                        EDUCATION &amp; CAREER

                    </div>

                    <?php
                    $renderRows(
                        $educationRows
                    );
                    ?>

                </article>

                <article class="pdf-card main-card">

                    <div class="card-heading red">

                        <?php if (
                            $icon(
                                'family',
                                'red'
                            ) !== ''
                        ): ?>

                            <img
                                src="<?= esc(
                                            $icon(
                                                'family',
                                                'red'
                                            ),
                                            'attr'
                                        ) ?>"
                                alt="">

                        <?php endif; ?>

                        FAMILY DETAILS

                    </div>

                    <?php
                    $renderRows(
                        $educationRows
                    );
                    ?>

                </article>

                <article class="pdf-card main-card">

                    <div class="card-heading">

                        <?php if (
                            $icon(
                                'heart',
                                'purple'
                            ) !== ''
                        ): ?>

                            <img
                                src="<?= esc(
                                            $icon(
                                                'heart',
                                                'purple'
                                            ),
                                            'attr'
                                        ) ?>"
                                alt="">

                        <?php endif; ?>

                        LIFESTYLE DETAILS

                    </div>

                    <?php
                    $renderRows(
                        $educationRows
                    );
                    ?>

                </article>

            </section>

            <section class="bottom-grid">

                <article class="pdf-card bottom-card">

                    <div class="card-heading red">

                        <?php if (
                            $icon(
                                'heart',
                                'red'
                            ) !== ''
                        ): ?>

                            <img
                                src="<?= esc(
                                            $icon(
                                                'heart',
                                                'red'
                                            ),
                                            'attr'
                                        ) ?>"
                                alt="">

                        <?php endif; ?>

                        PREFERENCES

                    </div>

                    <div class="preference-grid">

                        <?php
                        $renderRows(
                            $educationRows
                        );
                        ?>

                    </div>

                </article>

                <article class="pdf-card bottom-card">

                    <div class="card-heading">

                        <?php if (
                            $icon(
                                'user',
                                'purple'
                            ) !== ''
                        ): ?>

                            <img
                                src="<?= esc(
                                            $icon(
                                                'user',
                                                'purple'
                                            ),
                                            'attr'
                                        ) ?>"
                                alt="">

                        <?php endif; ?>

                        ABOUT ME

                    </div>

                    <div class="about-body">

                        <p class="about-copy">
                            <?=
                            $aboutMe !== ''
                                ? esc(
                                    $aboutMe
                                )
                                : 'About me information has not been added.'
                            ?>
                        </p>

                        <?php if (
                            $marriageMotifUrl !== ''
                        ): ?>

                            <img
                                class="about-motif"
                                src="<?= esc(
                                            $marriageMotifUrl,
                                            'attr'
                                        ) ?>"
                                alt="">

                        <?php endif; ?>

                    </div>

                </article>

            </section>

        </div>

        <footer class="pdf-footer">

            <div class="footer-copy">

                Your privacy is important to us.<br>

                Contact and identity information is protected
                in this profile PDF.

            </div>

            <div class="footer-mark">

                

            </div>

            <div class="footer-copy right">

                SikhAnandKaraj Matrimonial Services<br>

                <?= esc(
                    (string) (
                        $config
                        ->website
                        ?? 'sikhanandkaraj.com'
                    )
                ) ?><br>

                24x7 Help:
                <strong>
                    <?= esc(
                        (string) (
                            $config
                            ->supportPhone
                            ?? ''
                        )
                    ) ?>
                </strong>

            </div>

        </footer>

    </div>

</body>

</html>