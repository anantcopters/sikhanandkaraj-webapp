<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Seo;

/**
 * Prevents private, operational and error responses from entering indexes.
 */
final class SeoRobotsFilter implements FilterInterface
{
    public function before(
        RequestInterface $request,
        $arguments = null
    ): mixed {
        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ): void {
        $path = trim($request->getUri()->getPath(), '/');

        /** @var Seo $seoConfig */
        $seoConfig = config(Seo::class);

        $isGetRequest = strtoupper($request->getMethod()) === 'GET';

        $isIndexableGetRequest = $isGetRequest
            && $seoConfig->indexingEnabled
            && $response->getStatusCode() < 400
            && in_array($path, $seoConfig->indexablePaths, true);

        $isSitemapRequest = $isGetRequest
            && $response->getStatusCode() < 400
            && $path === 'sitemap.xml';

        if ($isIndexableGetRequest || $isSitemapRequest) {
            return;
        }

        $response->setHeader(
            'X-Robots-Tag',
            'noindex, nofollow, noarchive'
        );
    }
}
