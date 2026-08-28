<?php

declare(strict_types=1);

/**
 * Partner Preference overview.
 *
 * @var list<array<string, mixed>> $sections
 * @var array<string, string>|null $formAlert
 */

$this->extend('Layouts/Main');

$this->section('content');

$resolvedSections = is_array($sections ?? null)
    ? $sections
    : [];

$leftColumnSections = [];

$rightColumnSections = [];

foreach ($resolvedSections as $section) {
    if (!is_array($section)) {
        continue;
    }

    $sectionKey = trim(
        (string) (
            $section['key']
            ?? ''
        )
    );

    if (
        $sectionKey === 'basic'
        || $sectionKey === 'lifestyle'
    ) {
        $leftColumnSections[] =
            $section;

        continue;
    }

    $rightColumnSections[] =
        $section;
}
?>

<section class="py-3 py-lg-3">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-11">

                <?= view(
                    'Pages/Profile/Partials/_feedback_alert',
                    [
                        'formAlert' =>
                        $formAlert ?? null,
                    ]
                ) ?>

                <div
                    class="d-flex align-items-start
                        gap-3 mb-3">

                    <div>
                        <a
                            href="<?= url_to(
                                        'web.dashboard'
                                    ) ?>"
                            class="d-inline-flex
                                align-items-center
                                gap-1 text-primary
                                fw-medium mb-2">

                            <i
                                class="ri-arrow-left-line"
                                aria-hidden="true"></i>

                            Back to Dashboard
                        </a>

                        <div
                            class="d-flex align-items-center
                                gap-2 mt-2">

                            <div
                                class="avatar-sm flex-shrink-0"
                                aria-hidden="true">

                                <span
                                    class="avatar-title
                                        rounded-circle
                                        bg-primary-subtle
                                        text-primary">

                                    <i
                                        class="ri-user-heart-line
                                            fs-20"></i>
                                </span>
                            </div>

                            <div>
                                <h2
                                    class="fs-16 fw-semibold mb-1">
                                    Partner Preference
                                </h2>

                                <p
                                    class="text-muted fs-13 mb-0">
                                    Define the criteria you prefer
                                    in your partner.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 align-items-start">

                    <div class="col-12 col-lg-6">
                        <div class="d-flex flex-column gap-3">

                            <?php foreach (
                                $leftColumnSections
                                as $section
                            ): ?>

                                <?= view(
                                    'Pages/PartnerPreference/_section_card',
                                    [
                                        'section' =>
                                        $section,
                                    ]
                                ) ?>

                            <?php endforeach; ?>

                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="d-flex flex-column gap-3">

                            <?php foreach (
                                $rightColumnSections as $section
                            ): ?>
                                <?= view(
                                    'Pages/PartnerPreference/_section_card',
                                    [
                                        'section' =>
                                        $section,
                                    ]
                                ) ?>
                            <?php endforeach; ?>

                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>