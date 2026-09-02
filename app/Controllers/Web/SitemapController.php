<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Seo;

/**
 * Returns the allowlisted public-content URLs as an XML sitemap.
 *
 * The sitemap is intentionally available only on the production deployment.
 * Development and QA environments remain noindex and must not advertise
 * crawlable URLs through a sitemap.
 */
final class SitemapController extends BaseController
{
    public function index(): ResponseInterface
    {
        /** @var Seo $seoConfig */
        $seoConfig = config(Seo::class);

        /*
         * A sitemap advertises URLs intended for search-engine discovery.
         *
         * Development and QA are deliberately noindex, so they must not
         * expose their own sitemap. Reuse the same production-indexing flag
         * that controls the rest of the SEO implementation.
         */
        if (! $seoConfig->indexingEnabled) {
            throw PageNotFoundException::forPageNotFound();
        }

        $urls = [];

        foreach ($seoConfig->sitemapRouteNames as $routeName) {
            $urls[] = url_to($routeName);
        }

        return $this->response
            ->setContentType('application/xml', 'UTF-8')
            ->setHeader(
                'Cache-Control',
                'public, max-age=3600, s-maxage=3600'
            )
            ->setBody(
                view(
                    'Seo/Sitemap',
                    [
                        'urls' => $urls,
                    ]
                )
            );
    }
}
