<?php

declare(strict_types=1);

namespace App\Support;

use Config\Site;
use Config\Seo;

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
     */
    public static function publicPage(
        string $title,
        string $description,
        string $routeName,
        bool $includeWebsiteSchema = false
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

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $seoConfig->indexingEnabled
                ? 'index,follow,max-image-preview:large'
                : 'noindex,nofollow,noarchive',
            'ogType' => 'website',
            'ogImage' => $logoUrl,
            'structuredData' => $structuredData,
        ];
    }
}
