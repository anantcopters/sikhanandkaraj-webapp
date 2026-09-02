<?php

declare(strict_types=1);

/**
 * @var string|null $pageTitle
 * @var array<string, mixed>|null $seo
 * @var bool|null $minimalPublicPage
 * @var array<string, mixed>|null $page
 */

$pageData = is_array($page ?? null)
    ? $page
    : [];

$eyebrow = trim((string) ($pageData['eyebrow'] ?? 'SikhanandKaraj'));
$heading = trim((string) ($pageData['heading'] ?? ''));
$introduction = trim((string) ($pageData['introduction'] ?? ''));
$sections = is_array($pageData['sections'] ?? null)
    ? $pageData['sections']
    : [];
$faqs = is_array($pageData['faqs'] ?? null)
    ? $pageData['faqs']
    : [];
$relatedLinks = is_array($pageData['relatedLinks'] ?? null)
    ? $pageData['relatedLinks']
    : [];
$breadcrumbs = is_array($pageData['breadcrumbs'] ?? null)
    ? $pageData['breadcrumbs']
    : [];

$this->setVar(
    'footerView',
    'Components/Home/Footer'
)->extend(
    'Layouts/Main'
);

$this->section('content');
?>

<!-- Public SEO landing-page header and breadcrumb navigation. -->
<section class="section py-5 light-yellowish">
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-12 col-xl-10 text-center">
                <p class="fs-13 fw-semibold text-danger text-uppercase mb-2">
                    <?= esc($eyebrow) ?>
                </p>

                <h1 class="fs-36 fw-bold mb-3">
                    <?= esc($heading) ?>
                </h1>

                <p class="fs-16 text-secondary lh-lg mx-auto mb-0">
                    <?= esc($introduction) ?>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Unique informational sections supplied by the page catalog. -->
<section class="section py-0 light-yellowish">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <?php foreach ($sections as $sectionIndex => $section): ?>
                    <?php
                    $sectionTitle = trim(
                        (string) ($section['title'] ?? '')
                    );
                    $sectionParagraphs = is_array(
                        $section['paragraphs'] ?? null
                    ) ? $section['paragraphs'] : [];
                    $sectionItems = is_array($section['items'] ?? null)
                        ? $section['items']
                        : [];
                    $sectionId = 'content-section-' . ($sectionIndex + 1);
                    ?>
                    <article
                        class="card border border-danger border-opacity-25
                            shadow-sm mb-4"
                        aria-labelledby="<?= esc($sectionId, 'attr') ?>">
                        <div class="card-body p-4 p-lg-5">
                            <h2
                                id="<?= esc($sectionId, 'attr') ?>"
                                class="fs-24 fw-semibold mb-3">
                                <?= esc($sectionTitle) ?>
                            </h2>

                            <?php foreach (
                                $sectionParagraphs as $paragraphIndex => $paragraph
                            ): ?>
                                <p class="lh-lg<?= $sectionItems === []
                                                    && $paragraphIndex === array_key_last(
                                                        $sectionParagraphs
                                                    )
                                                    ? ' mb-0'
                                                    : '' ?>">
                                    <?= esc((string) $paragraph) ?>
                                </p>
                            <?php endforeach; ?>

                            <?php if ($sectionItems !== []): ?>
                                <ul class="lh-lg mb-0">
                                    <?php foreach ($sectionItems as $item): ?>
                                        <li class="mb-2">
                                            <?= esc((string) $item) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>

                <?php if ($faqs !== []): ?>
                    <!-- Visible FAQs match the FAQPage structured data. -->
                    <section class="card border border-danger border-opacity-25
                            shadow-sm mb-4" aria-labelledby="page-faq-title">
                        <div class="card-body p-4 p-lg-5">
                            <h2 id="page-faq-title" class="fs-24 fw-semibold mb-4">
                                Frequently asked questions
                            </h2>

                            <div class="accordion" id="seo-page-faq">
                                <?php foreach ($faqs as $faqIndex => $faq): ?>
                                    <?php
                                    $faqHeadingId = 'seo-faq-heading-' . $faqIndex;
                                    $faqPanelId = 'seo-faq-panel-' . $faqIndex;
                                    ?>
                                    <div class="accordion-item">
                                        <h3
                                            class="accordion-header"
                                            id="<?= esc($faqHeadingId, 'attr') ?>">
                                            <button
                                                class="accordion-button<?= $faqIndex === 0
                                                                            ? ''
                                                                            : ' collapsed' ?>"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#<?= esc($faqPanelId, 'attr') ?>"
                                                aria-expanded="<?= $faqIndex === 0
                                                                    ? 'true'
                                                                    : 'false' ?>"
                                                aria-controls="<?= esc($faqPanelId, 'attr') ?>">
                                                <?= esc((string) ($faq['question'] ?? '')) ?>
                                            </button>
                                        </h3>
                                        <div
                                            id="<?= esc($faqPanelId, 'attr') ?>"
                                            class="accordion-collapse collapse<?= $faqIndex === 0
                                                                                    ? ' show'
                                                                                    : '' ?>"
                                            aria-labelledby="<?= esc($faqHeadingId, 'attr') ?>"
                                            data-bs-parent="#seo-page-faq">
                                            <div class="accordion-body lh-lg text-body">
                                                <?= esc((string) ($faq['answer'] ?? '')) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Contextual links connect core and location content. -->
                <?php if ($relatedLinks !== []): ?>
                    <section
                        class="card bg-light border border-danger border-opacity-25"
                        aria-labelledby="related-pages-title">
                        <div class="card-body p-4 p-lg-5">
                            <h2 id="related-pages-title" class="fs-22 fw-semibold mb-3">
                                Continue exploring
                            </h2>

                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($relatedLinks as $link): ?>
                                    <a
                                        href="<?= esc(
                                                    url_to((string) ($link['routeName'] ?? '')),
                                                    'attr'
                                                ) ?>"
                                        class="btn btn-outline-primary">
                                        <?= esc((string) ($link['label'] ?? '')) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="card border-0 bg-primary text-white text-center">
                    <div class="card-body p-4 p-lg-5">
                        <h2 class="fs-24 fw-semibold text-white mb-3">
                            Begin your matrimonial journey
                        </h2>

                        <p class="mb-4">
                            Create your profile and discover compatible Sikh
                            matrimonial connections while retaining the
                            platform's controlled profile-access safeguards.
                        </p>

                        <a href="<?= url_to('web.home') ?>#registration" class="btn btn-light">
                            Register Free
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>