<?php

declare(strict_types=1);

namespace App\Support;

use Config\Seo;
use Config\Site;

/**
 * Builds normalized metadata for explicitly indexable public pages.
 */
final class SeoMetadata
{
    /**
     * @return array{
     *     title:string,
     *     description:string,
     *     canonical:string,
     *     robots:string,
     *     ogType:string,
     *     ogImage:string,
     *     structuredData:list<array<string, mixed>>
     * }
     *
     * @param list<array{label:string, routeName:string}> $breadcrumbs
     * @param list<array<string, mixed>> $additionalStructuredData
     */
    public static function publicPage(
        string $title,
        string $description,
        string $routeName,
        bool $includeWebsiteSchema = false,
        array $breadcrumbs = [],
        array $additionalStructuredData = []
    ): array {
        /** @var Site $siteConfig */
        $siteConfig = config(Site::class);

        /** @var Seo $seoConfig */
        $seoConfig = config(Seo::class);

        $canonical = url_to($routeName);

        $logoUrl = base_url(
            'assets/images/logo_sak_header.png'
        );

        $structuredData = [];

        /*
         * Organization and WebSite schema is currently rendered only on the
         * homepage. Other pages receive their relevant page-level schema.
         */
        if ($includeWebsiteSchema) {
            $homeUrl = url_to('web.home');

            $structuredData = [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    'name' => $siteConfig->name,
                    'url' => $homeUrl,
                    'logo' => $logoUrl,
                    'email' => $siteConfig->supportEmail,
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => $siteConfig->name,
                    'url' => $homeUrl,
                    'description' => $description,
                ],
            ];
        }

        /*
         * Breadcrumb structured data is generated from the same breadcrumb
         * collection displayed visibly by the landing-page view.
         */
        if ($breadcrumbs !== []) {
            $breadcrumbItems = [];

            foreach ($breadcrumbs as $position => $breadcrumb) {
                $breadcrumbItems[] = [
                    '@type' => 'ListItem',
                    'position' => $position + 1,
                    'name' => $breadcrumb['label'],
                    'item' => url_to(
                        $breadcrumb['routeName']
                    ),
                ];
            }

            $structuredData[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $breadcrumbItems,
            ];
        }

        /*
         * The landing-page controller uses this collection for FAQPage schema.
         * Only visible FAQs supplied to the page are added.
         */
        foreach ($additionalStructuredData as $schema) {
            if ($schema !== []) {
                $structuredData[] = $schema;
            }
        }

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,

            /*
             * QA and development remain noindex. Indexing is enabled only
             * when APP_DEPLOYMENT is explicitly production.
             */
            'robots' => $seoConfig->indexingEnabled
                ? 'index,follow,max-image-preview:large'
                : 'noindex,nofollow,noarchive',

            'ogType' => 'website',
            'ogImage' => $logoUrl,
            'structuredData' => $structuredData,
        ];
    }
}
