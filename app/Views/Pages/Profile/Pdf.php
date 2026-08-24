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

$purple =
    '#442254';

$header = '#495057';

$red =
    '#ae1536';

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

$remixIconFont =
    isset($remixIconFont)
    && is_array($remixIconFont)
    ? $remixIconFont
    : [];

$remixIconFontUrl = trim(
    (string) (
        $remixIconFont['uri']
        ?? ''
    )
);

$remixIconFontFormat = trim(
    (string) (
        $remixIconFont['format']
        ?? ''
    )
);

$remixLibraryCss = trim(
    (string) (
        $remixIconCss
        ?? ''
    )
);

$remixIconCssUrl =
    $remixIconFontUrl !== ''
    ? 'url("'
    . $remixIconFontUrl
    . '")'
    : '';

$remixIconCssUrl =
    $remixIconFontUrl !== ''
    ? 'url("'
    . $remixIconFontUrl
    . '")'
    : '';

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

$remixFontFaceCss = '';

if (
    $remixIconCssUrl !== ''
    && $remixIconFontFormat !== ''
) {
    $remixFontFaceCss = <<<CSS
@font-face {
    font-family: 'remixicon';
    src: {$remixIconCssUrl} format('{$remixIconFontFormat}');
    font-style: normal;
    font-weight: normal;
}
CSS;
}

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

$renderIcon =
    static function (
        string $iconClass,
        string $className = ''
    ): string {
        $iconClass =
            trim($iconClass);

        if ($iconClass === '') {
            return '';
        }

        if (
            !str_starts_with(
                $iconClass,
                'ri-'
            )
        ) {
            return '';
        }

        $classes = trim(
            $iconClass
                . ' '
                . $className
        );

        return '<i class="'
            . esc(
                $classes,
                'attr'
            )
            . '" aria-hidden="true"></i>';
    };

$renderRows =
    static function (
        array $rows
    ): void {
        $index = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $rowIcon = trim(
                (string) (
                    $row['icon']
                    ?? 'ri-user-line'
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

            $colourClass =
                $index % 2 === 0
                ? 'detail-icon-purple'
                : 'detail-icon-red';

            $index++;
?>
        <div class="detail-row">

            <div
                class="detail-icon <?= esc(
                                        $colourClass,
                                        'attr'
                                    ) ?>">

                <i
                    class="<?= esc(
                                $rowIcon,
                                'attr'
                            ) ?>"
                    aria-hidden="true"></i>

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
        ) ?> - Sikhanandkaraj
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
        /* remix STart*/
        <?= $remixFontFaceCss ?>
        /* remix STart*/
        <?= $remixLibraryCss ?>

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
                0mm 7mm 19mm;
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

            font-size: 2.95mm;
            font-weight: 500;
        }

        .header-contact-row {
            display: flex;
            align-items: center;

            gap: 2mm;
        }

        .header-contact-icon {
            width: 5mm;

            color: <?= $purple ?>;

            font-size: 4mm;

            line-height: 1;

            text-align: center;
        }

        .header-contact strong {
            color: <?= $red ?>;

            font-size: 3.2mm;
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

            font-size: 2.8mm;
            font-weight: 600;

            text-align: center;

            border-radius: 1.8mm;

            background:
                linear-gradient(90deg,
                    <?= $purple ?>,
                    <?= $red ?>);
            letter-spacing: 0.08mm;
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

        [class^="ri-"],
        [class*=" ri-"] {
            display: inline-block;

            font-family: 'remixicon' !important;

            font-style: normal;
            font-weight: normal;
            font-variant: normal;

            line-height: 1;

            text-transform: none;

            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .quick-icon {
            width: 5.2mm;

            color: <?= $red ?>;

            font-size: 4.4mm;

            line-height: 1;

            text-align: center;
        }

        .quick-item:nth-child(even) .quick-icon {
            color: <?= $purple ?>;
        }

        .quick-label {
            color: <?= $header ?>;

            font-size: 3mm;
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

        .verify-icon {
            width: 6mm;

            color: <?= $purple ?>;

            font-size: 4.5mm;

            line-height: 1;

            text-align: center;
        }

        .verify-icon-red {
            color: <?= $red ?>;
        }

        .verify-icon-success {
            color: #198754;
        }

        .verify-title {
            color: #211b24;

            font-size: 2.8mm;
            font-weight: 600;

            line-height: 1.2;
        }

        .verify-value {
            margin-top: .7mm;

            color: #4f4553;

            font-size: 2.4mm;
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

        .card-heading>i {
            flex:
                0 0 auto;

            color: inherit;

            font-size: 4.4mm;

            line-height: 1;
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

        .detail-label {
            color: <?= $header ?>;

            font-size: 3mm;
            font-weight: 600;

            line-height: 1.2;
        }

        .detail-value {
            margin-top: .7mm;

            color: #211b24;

            font-size: 3mm;
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

            font-size: 3.1mm;
            font-weight: 500;

            line-height: 1.5;
        }

        .about-motif {
            position: absolute;

            right: 3mm;
            bottom: -1mm;

            width: 46mm;
            height: 20mm;

            object-fit: contain;

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
            font-size: 2.5mm;
            font-weight: 500;

            line-height: 1.45;
        }

        .footer-copy-left {
            display: flex;

            align-items: center;

            gap: 2mm;
        }

        .footer-shield {
            flex: 0 0 auto;

            color: #ffd65a;

            font-size: 24px;

            line-height: 1;
        }

        .footer-shield i {
            display: block;

            font-size: inherit;

            line-height: 1;
        }

        .footer-copy-text {
            min-width: 0;
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
            width: 13mm;
            height: 11mm;

            object-fit: contain;
        }

        .text-gurmukhi {
            color: <?= $red ?>;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.4);
            font-weight: 700;
            font-size: 4.3mm;
            text-align: center;
            padding-bottom: 5px;
            text-decoration: underline;
        }

        .detail-icon {
            width: 5mm;

            flex: 0 0 5mm;

            font-size: 4.2mm;

            line-height: 1;

            text-align: center;
        }

        .detail-icon i {
            display: inline-block;

            font-size: inherit;

            line-height: 1;
        }

        .detail-icon-purple {
            color: <?= $purple ?>;
        }

        .detail-icon-red {
            color: <?= $red ?>;
        }

        .card-heading>i {
            flex:
                0 0 auto;

            color: inherit;

            font-size: 4.4mm;
        }

        .header-contact-text {
            font-size: 10px;
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
                            alt="Sikhanandkaraj">

                    <?php endif; ?>

                </div>

                <div class="header-contact">

                    <div class="header-contact-row">

                        <div class="header-contact-icon">

                            <?= $renderIcon(
                                'ri-phone-line'
                            ) ?>

                        </div>

                        <div class="header-contact-text">
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

                        <div class="header-contact-icon">

                            <?= $renderIcon(
                                'ri-global-line'
                            ) ?>

                        </div>

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
            <div class="text-gurmukhi">
                ਸਿੱਖ ਅਨੰਦ ਕਾਰਜ
            </div>
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

                        ?>

                            <div class="quick-item">

                                <div class="quick-icon">

                                    <?= $renderIcon(
                                        $iconName
                                    ) ?>

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

                            <div class="verify-icon verify-icon-success">

                                <?= $renderIcon(
                                    'ri-checkbox-circle-fill'
                                ) ?>

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

                            <div class="verify-icon verify-icon-success">

                                <?= $renderIcon(
                                    'ri-checkbox-circle-fill'
                                ) ?>

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

                            <div class="verify-icon verify-icon-success">

                                <?= $renderIcon(
                                    'ri-shield-check-fill'
                                ) ?>

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

                            <div class="verify-icon verify-icon-red">

                                <?= $renderIcon(
                                    'ri-video-line'
                                ) ?>

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

                    <div class="card-heading red">

                        <?= $renderIcon(
                            'ri-group-line'
                        ) ?>

                        FAMILY DETAILS

                    </div>

                    <?php
                    $renderRows(
                        $familyRows
                    );
                    ?>

                </article>

                <article class="pdf-card main-card">

                    <div class="card-heading">

                        <?= $renderIcon(
                            'ri-graduation-cap-line'
                        ) ?>

                        EDUCATION &amp; CAREER

                    </div>

                    <?php
                    $renderRows(
                        $educationRows
                    );
                    ?>

                </article>



                <article class="pdf-card main-card">

                    <div class="card-heading">

                        <?= $renderIcon(
                            'ri-heart-pulse-line'
                        ) ?>

                        LIFESTYLE DETAILS

                    </div>

                    <?php
                    $renderRows(
                        $lifestyleRows
                    );
                    ?>

                </article>

            </section>

            <section class="bottom-grid">

                <article class="pdf-card bottom-card">

                    <div class="card-heading red">

                        <?= $renderIcon(
                            'ri-user-heart-line'
                        ) ?>

                        PARTNER PREFERENCES

                    </div>

                    <div class="preference-grid">

                        <?php
                        $renderRows(
                            $preferences
                        );
                        ?>

                    </div>

                </article>

                <article class="pdf-card bottom-card">

                    <div class="card-heading">

                        <?= $renderIcon(
                            'ri-user-line'
                        ) ?>

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

            <div class="footer-copy footer-copy-left">

                <span class="footer-shield">

                    <?= $renderIcon(
                        'ri-shield-check-fill'
                    ) ?>

                </span>

                <div class="footer-copy-text">

                    Your privacy is important to us.<br>

                    Contact and identity information is protected
                    in this profile PDF.

                </div>

            </div>

            <div class="footer-mark">



            </div>

            <div class="footer-copy right">

                <?= esc(
                    (string) (
                        $config
                        ->website
                        ?? 'www.sikhanandkaraj.com'
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