<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Seo;

/**
 * Returns the allowlisted public-content URLs as an XML sitemap.
 */
final class SitemapController extends BaseController
{
    public function index(): ResponseInterface
    {
        /** @var Seo $seoConfig */
        $seoConfig = config(Seo::class);

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
