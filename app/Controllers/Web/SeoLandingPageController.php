<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Support\SeoLandingPageCatalog;
use App\Support\SeoMetadata;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Renders public SEO content through the existing website layout.
 */
final class SeoLandingPageController extends BaseController
{
    public function sikhMatrimony(): string
    {
        return $this->renderPage('sikh-matrimony');
    }

    public function howItWorks(): string
    {
        return $this->renderPage('how-it-works');
    }

    public function verificationAndSafety(): string
    {
        return $this->renderPage('verification-and-safety');
    }

    public function faq(): string
    {
        return $this->renderPage('faq');
    }

    public function delhi(): string
    {
        return $this->renderPage('delhi');
    }

    public function punjab(): string
    {
        return $this->renderPage('punjab');
    }

    public function chandigarh(): string
    {
        return $this->renderPage('chandigarh');
    }

    public function canada(): string
    {
        return $this->renderPage('canada');
    }

    public function toronto(): string
    {
        return $this->renderPage('toronto');
    }

    public function vancouver(): string
    {
        return $this->renderPage('vancouver');
    }

    private function renderPage(string $pageKey): string
    {
        $page = SeoLandingPageCatalog::find($pageKey);

        if ($page === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $additionalStructuredData = [];

        if ($page['faqs'] !== []) {
            $additionalStructuredData[] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',

                'mainEntity' => array_map(
                    static fn(array $faq): array => [
                        '@type' => 'Question',
                        'name' => $faq['question'],

                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $faq['answer'],
                        ],
                    ],
                    $page['faqs']
                ),
            ];
        }

        return view(
            'Pages/Seo/LandingPage',
            [
                'pageTitle' => $page['title'],
                'seo' => SeoMetadata::publicPage(
                    $page['title'],
                    $page['description'],
                    $page['routeName'],
                    false,
                    $page['breadcrumbs'],
                    $additionalStructuredData
                ),
                'minimalPublicPage' => true,
                'page' => $page,
            ]
        );
    }
}
