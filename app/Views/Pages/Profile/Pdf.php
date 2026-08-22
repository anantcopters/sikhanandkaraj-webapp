<?php

declare(strict_types=1);

use Config\ProfilePdf;

/*
 * =========================================================
 * View-local declarations
 * =========================================================
 *
 * Keep the PDF view defensive and presentation-only.
 *
 * The service is responsible for business/privacy rules.
 * This block only normalizes values supplied to the view.
 */

$config = config(
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

/*
 * PDF brand colours are intentionally local because
 * this document is a standalone print layout and does
 * not load the application's CSS bundle.
 */
$purple = '#310a57';
$red = '#ce102c';

/*
 * Resolve an already embedded PDF icon.
 */
$icon = static function (
    string $name
) use ($icons): string {
    return trim(
        (string) (
            $icons[$name]
            ?? ''
        )
    );
};

/*
 * Resolve an already embedded common PDF asset.
 */
$asset = static function (
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

$logoUrl =
    $asset(
        'logo'
    );

$marriageMotifUrl =
    $asset(
        'marriageMotif'
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

/**
 * Render a list of PDF detail rows.
 *
 * Business data has already been transformed by
 * MemberProfilePdfDataService. The view only renders
 * presentation-ready values.
 *
 * @param array<int,mixed> $rows
 */
$renderRows =
    static function (
        array $rows
    ) use (
        $icon
    ): void {
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
?>
        <div class="detail-row">

            <div class="detail-icon">

                <?php if (
                    $icon($rowIcon)
                    !== ''
                ): ?>

                    <img
                        src="<?= esc(
                                    $icon(
                                        $rowIcon
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

        @font-face {
            font-family: 'Inter';
            src: url('<?= esc($fontRegularUrl, 'attr') ?>') format('truetype');
            font-style: normal;
            font-weight: 400;
        }

        @font-face {
            font-family: 'Inter';
            src: url('<?= esc($fontMediumUrl, 'attr') ?>') format('truetype');
            font-style: normal;
            font-weight: 500;
        }

        @font-face {
            font-family: 'Inter';
            src: url('<?= esc($fontSemiBoldUrl, 'attr') ?>') format('truetype');
            font-style: normal;
            font-weight: 600;
        }

        @font-face {
            font-family: 'Inter';
            src: url('<?= esc($fontBoldUrl, 'attr') ?>') format('truetype');
            font-style: normal;
            font-weight: 700;
        }

        * {
            box-sizing: border-box;
        }

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
                Inter,
                Arial,
                sans-serif;

            color: #241d28;
            background: #ffffff;
        }

        .pdf-page {
            position: relative;

            width: 210mm;
            height: 297mm;

            overflow: hidden;

            background:
                linear-gradient(180deg,
                    #ffffff 0%,
                    #ffffff 84%,
                    #fff8fa 100%);
        }

        .pdf-content {
            padding:
                6mm 7mm 21mm;
        }

        /* =========================================================
   HEADER
   ========================================================= */

        .pdf-header {
            height: 24mm;

            display: flex;
            align-items: center;
            justify-content: space-between;

            border-bottom:
                .45mm solid <?= $red ?>;
        }

        .brand-logo {
            width: 67mm;
            height: 18mm;

            object-fit: contain;
            object-position: left center;
        }

        .header-contact {
            display: flex;
            flex-direction: column;

            gap: 1.8mm;

            font-size: 2.45mm;
        }

        .header-contact-row {
            display: flex;
            align-items: center;

            gap: 2mm;
        }

        .header-contact-row img {
            width: 4.5mm;
            height: 4.5mm;
        }

        .header-contact strong {
            color: <?= $red ?>;
        }

        .header-site {
            color: <?= $purple ?>;
            font-weight: 600;
        }

        /* =========================================================
   PROFILE HERO
   ========================================================= */

        .profile-hero {
            display: grid;

            grid-template-columns:
                46mm 1fr 46mm;

            gap: 5mm;

            margin-top: 5mm;
        }

        .profile-photo {
            position: relative;

            height: 66mm;

            padding: 1.5mm;

            border:
                .35mm solid rgba(206,
                    16,
                    44,
                    .65);

            border-radius: 3mm;

            background: #fff;
        }

        .profile-photo>img {
            width: 100%;
            height: 100%;

            object-fit: cover;

            border-radius: 2.2mm;
        }

        .profile-reference {
            position: absolute;

            left: 3mm;
            right: 3mm;
            bottom: 3mm;

            padding:
                2mm 1.5mm;

            color: #fff;

            font-size: 2.55mm;
            font-weight: 600;

            text-align: center;

            border-radius: 1.5mm;

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

            font-size: 6.4mm;
            font-weight: 700;

            line-height: 1.12;
        }

        .profile-summary {
            margin-top: 2.5mm;

            color: #615667;

            font-size: 2.8mm;
            font-weight: 500;

            line-height: 1.45;
        }

        .profile-divider {
            height: .3mm;

            margin:
                4mm 0 2mm;

            background:
                linear-gradient(90deg,
                    #eadfea,
                    <?= $red ?>,
                    #eadfea);
        }

        .quick-grid {
            display: grid;

            grid-template-columns:
                repeat(2,
                    minmax(0,
                        1fr));
        }

        .quick-item {
            min-height: 12mm;

            display: grid;

            grid-template-columns:
                5mm minmax(0,
                    1fr);

            gap: 1.5mm;

            padding:
                2mm 1mm;

            border-bottom:
                .2mm solid #eee8f0;
        }

        .quick-item img {
            width: 4.6mm;
            height: 4.6mm;
        }

        .quick-label {
            color: <?= $purple ?>;

            font-size: 1.9mm;
            font-weight: 600;
        }

        .quick-value {
            margin-top: .5mm;

            font-size: 2.3mm;
            font-weight: 500;

            line-height: 1.25;
        }

        /* =========================================================
   VERIFIED PROFILE
   ========================================================= */

        .verified-card {
            height: 66mm;

            padding: 3mm;

            border:
                .3mm solid #e4dce7;

            border-radius: 3mm;

            background: #fff;
        }

        .verified-heading {
            padding:
                2.3mm 1mm;

            color: #fff;

            font-size: 2.8mm;
            font-weight: 700;

            text-align: center;

            border-radius: 1.5mm;

            background:
                linear-gradient(90deg,
                    <?= $purple ?>,
                    <?= $red ?>);
        }

        .verify-row {
            display: grid;

            grid-template-columns:
                5.5mm minmax(0,
                    1fr);

            gap: 2mm;

            padding:
                2.8mm .5mm;

            border-bottom:
                .2mm solid #ece5ee;
        }

        .verify-row:last-child {
            border-bottom: 0;
        }

        .verify-row img {
            width: 5mm;
            height: 5mm;
        }

        .verify-title {
            color: <?= $purple ?>;

            font-size: 2.3mm;
            font-weight: 600;
        }

        .verify-value {
            margin-top: .5mm;

            color: #665b69;

            font-size: 2mm;

            overflow-wrap: anywhere;
        }

        .verify-success {
            color: #198754;
        }

        /* =========================================================
   CARDS
   ========================================================= */

        .card-grid {
            display: grid;

            grid-template-columns:
                repeat(3,
                    minmax(0,
                        1fr));

            gap: 3mm;

            margin-top: 4mm;
        }

        .pdf-card {
            overflow: hidden;

            border:
                .28mm solid #e4dce7;

            border-radius: 2.5mm;

            background: #fff;
        }

        .main-card {
            height: 80mm;
        }

        .card-heading {
            height: 10mm;

            display: flex;
            align-items: center;

            gap: 2mm;

            padding:
                0 3mm;

            color: <?= $purple ?>;

            font-size: 2.7mm;
            font-weight: 700;

            border-bottom:
                .22mm solid #e7e0e9;
        }

        .card-heading.red {
            color: <?= $red ?>;
        }

        .card-heading img {
            width: 5mm;
            height: 5mm;
        }

        .detail-row {
            min-height: 10.8mm;

            display: grid;

            grid-template-columns:
                5mm minmax(0,
                    1fr);

            gap: 1.8mm;

            padding:
                1.8mm 3mm;

            border-bottom:
                .2mm solid #f0ebf2;
        }

        .detail-row:last-child {
            border-bottom: 0;
        }

        .detail-icon img {
            width: 4.5mm;
            height: 4.5mm;
        }

        .detail-label {
            color: <?= $purple ?>;

            font-size: 1.8mm;
            font-weight: 600;
        }

        .detail-value {
            margin-top: .5mm;

            color: #302833;

            font-size: 2.2mm;
            font-weight: 500;

            line-height: 1.25;

            overflow-wrap: anywhere;
        }

        /* =========================================================
   PREFERENCE + ABOUT
   ========================================================= */

        .lower-grid {
            display: grid;

            grid-template-columns:
                1.1fr .9fr;

            gap: 3mm;

            margin-top: 3mm;
        }

        .lower-card {
            height: 54mm;
        }

        .preference-grid {
            display: grid;

            grid-template-columns:
                repeat(2,
                    minmax(0,
                        1fr));
        }

        .preference-grid .detail-row {
            min-height: 14.5mm;
        }

        .about-body {
            position: relative;

            height:
                calc(54mm - 10mm);

            padding: 3.5mm;

            overflow: hidden;
        }

        .about-copy {
            position: relative;

            z-index: 2;

            color: #403744;

            font-size: 2.35mm;

            line-height: 1.5;
        }

        .marriage-motif {
            position: absolute;

            left: 6mm;
            right: 6mm;
            bottom: 1mm;

            z-index: 1;

            width:
                calc(100% - 12mm);

            height: 25mm;

            object-fit: contain;

            opacity: .25;
        }

        /* =========================================================
   FOOTER
   ========================================================= */

        .pdf-footer {
            position: absolute;

            left: 0;
            right: 0;
            bottom: 0;

            height: 18mm;

            display: grid;

            grid-template-columns:
                1fr .9fr;

            align-items: center;

            gap: 6mm;

            padding:
                0 8mm;

            color: #fff;

            background:
                linear-gradient(90deg,
                    <?= $purple ?> 0%,
                    #68134f 50%,
                    <?= $red ?> 100%);
        }

        .footer-privacy {
            font-size: 2.1mm;
            line-height: 1.4;
        }

        .footer-brand {
            text-align: right;

            font-size: 2.2mm;
            line-height: 1.45;
        }

        .footer-brand strong {
            color: #ffd86a;
        }
    </style>

</head>

<body>

    <div class="pdf-page">

        <div class="pdf-content">

            <header class="pdf-header">

                <img
                    class="brand-logo"
                    src="<?= esc(
                                $logoUrl,
                                'attr'
                            ) ?>"
                    alt="SikhAnandKaraj">

                <div class="header-contact">

                    <?php if (
                        $config
                        ->supportPhone
                        !== ''
                    ): ?>

                        <div class="header-contact-row">

                            <img
                                src="<?= esc(
                                            $icon(
                                                'phone'
                                            ),
                                            'attr'
                                        ) ?>"
                                alt="">

                            <div>
                                24x7 Help &amp; Support
                                <br>

                                <strong>
                                    <?= esc(
                                        $config
                                            ->supportPhone
                                    ) ?>
                                </strong>
                            </div>

                        </div>

                    <?php endif; ?>

                    <div class="header-contact-row">

                        <img
                            src="<?= esc(
                                        $icon(
                                            'location'
                                        ),
                                        'attr'
                                    ) ?>"
                            alt="">

                        <span class="header-site">
                            sikhanandkaraj.com
                        </span>

                    </div>

                </div>

            </header>


            <section class="profile-hero">

                <div class="profile-photo">

                    <?php if (
                        $thumbnail !== ''
                    ): ?>

                        <img
                            src="<?= esc(
                                        $thumbnail,
                                        'attr'
                                    ) ?>"
                            alt="<?= esc(
                                        $fullName,
                                        'attr'
                                    ) ?>">

                    <?php endif; ?>

                    <div class="profile-reference">
                        Profile ID:
                        <?= esc(
                            $profileReference
                        ) ?>
                    </div>

                </div>


                <div class="profile-identity">

                    <h1 class="profile-name">
                        <?= esc(
                            $fullName
                        ) ?>
                    </h1>

                    <div class="profile-summary">
                        <?= esc(
                            $summary
                        ) ?>
                    </div>

                    <div class="profile-divider"></div>

                    <div class="quick-grid">

                        <?php foreach (
                            array_slice(
                                $quickDetails,
                                0,
                                6
                            )
                            as $item
                        ): ?>

                            <div class="quick-item">

                                <img
                                    src="<?= esc(
                                                $icon(
                                                    $item['icon']
                                                ),
                                                'attr'
                                            ) ?>"
                                    alt="">

                                <div>

                                    <div class="quick-label">
                                        <?= esc(
                                            $item['label']
                                        ) ?>
                                    </div>

                                    <div class="quick-value">
                                        <?= esc(
                                            $item['value']
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

                            <img
                                src="<?= esc(
                                            $icon(
                                                'shield-check'
                                            ),
                                            'attr'
                                        ) ?>"
                                alt="">

                            <div>

                                <div class="verify-title">
                                    Phone Verified
                                </div>

                                <div class="verify-value">
                                    <?= esc(
                                        $maskedMobile
                                    ) ?>
                                </div>

                            </div>

                        </div>

                    <?php endif; ?>


                    <?php if (
                        $isEmailVerified
                    ): ?>

                        <div class="verify-row">

                            <img
                                src="<?= esc(
                                            $icon(
                                                'shield-check'
                                            ),
                                            'attr'
                                        ) ?>"
                                alt="">

                            <div>

                                <div class="verify-title">
                                    Email Verified
                                </div>

                                <div class="verify-value">
                                    <?= esc(
                                        $maskedEmail
                                    ) ?>
                                </div>

                            </div>

                        </div>

                    <?php endif; ?>


                    <?php if (
                        $isAadhaarVerified
                    ): ?>

                        <div class="verify-row">

                            <img
                                src="<?= esc(
                                            $icon(
                                                'shield-check'
                                            ),
                                            'attr'
                                        ) ?>"
                                alt="">

                            <div>

                                <div class="verify-title">
                                    Aadhaar Verified
                                </div>

                                <div
                                    class="
                            verify-value
                            verify-success">

                                    Identity verified

                                </div>

                            </div>

                        </div>

                    <?php endif; ?>


                    <?php if (
                        $hasVideoIntroduction
                    ): ?>

                        <div class="verify-row">

                            <img
                                src="<?= esc(
                                            $icon(
                                                'video'
                                            ),
                                            'attr'
                                        ) ?>"
                                alt="">

                            <div>

                                <div class="verify-title">
                                    Video Introduction
                                </div>

                                <div
                                    class="
                            verify-value
                            verify-success">

                                    Available

                                </div>

                            </div>

                        </div>

                    <?php endif; ?>

                </aside>

            </section>


            <section class="card-grid">

                <article
                    class="
            pdf-card
            main-card">

                    <div class="card-heading">

                        <img
                            src="<?= esc(
                                        $icon(
                                            'education'
                                        ),
                                        'attr'
                                    ) ?>"
                            alt="">

                        EDUCATION &amp; CAREER

                    </div>

                    <?php
                    $renderRows(
                        $educationRows
                    );
                    ?>

                </article>


                <article
                    class="
            pdf-card
            main-card">

                    <div
                        class="
                card-heading
                red">

                        <img
                            src="<?= esc(
                                        $icon(
                                            'family'
                                        ),
                                        'attr'
                                    ) ?>"
                            alt="">

                        FAMILY DETAILS

                    </div>

                    <?php
                    $renderRows(
                        $familyRows
                    );
                    ?>

                </article>


                <article
                    class="
            pdf-card
            main-card">

                    <div class="card-heading">

                        <img
                            src="<?= esc(
                                        $icon(
                                            'heart'
                                        ),
                                        'attr'
                                    ) ?>"
                            alt="">

                        LIFESTYLE DETAILS

                    </div>

                    <?php
                    $renderRows(
                        $lifestyleRows
                    );
                    ?>

                </article>

            </section>


            <section class="lower-grid">

                <article
                    class="
            pdf-card
            lower-card">

                    <div
                        class="
                card-heading
                red">

                        <img
                            src="<?= esc(
                                        $icon(
                                            'preference'
                                        ),
                                        'attr'
                                    ) ?>"
                            alt="">

                        PREFERENCES

                    </div>

                    <div class="preference-grid">

                        <?php
                        $renderRows(
                            $preferences
                        );
                        ?>

                    </div>

                </article>


                <article
                    class="
            pdf-card
            lower-card">

                    <div class="card-heading">

                        <img
                            src="<?= esc(
                                        $icon(
                                            'user'
                                        ),
                                        'attr'
                                    ) ?>"
                            alt="">

                        ABOUT ME

                    </div>

                    <div class="about-body">

                        <div class="about-copy">

                            <?= nl2br(
                                esc(
                                    $aboutMe !== ''
                                        ? $aboutMe
                                        : (
                                            'About me information '
                                            . 'has not been added.'
                                        )
                                )
                            ) ?>

                        </div>

                        <img
                            class="marriage-motif"
                            src="<?= esc(
                                        $marriageMotifUrl,
                                        'attr'
                                    ) ?>"
                            alt="">

                    </div>

                </article>

            </section>

        </div>


        <footer class="pdf-footer">

            <div class="footer-privacy">

                Your privacy is important to us.
                <br>

                Contact and identity information
                is protected in this profile PDF.

            </div>

            <div class="footer-brand">

                SikhAnandKaraj Matrimonial Services
                <br>

                <strong>
                    sikhanandkaraj.com
                </strong>

                <?php if (
                    $config
                    ->supportPhone
                    !== ''
                ): ?>

                    <br>

                    24x7 Help:
                    <?= esc(
                        $config
                            ->supportPhone
                    ) ?>

                <?php endif; ?>

            </div>

        </footer>

    </div>

</body>

</html>