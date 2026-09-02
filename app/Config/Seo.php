<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Defines the deliberately small public-search surface of the application.
 *
 * Every application URL not listed here is treated as private or operational
 * and receives an X-Robots-Tag noindex response header.
 */
final class Seo extends BaseConfig
{
    /**
     * Public indexing is enabled only on the real production deployment.
     */
    public bool $indexingEnabled = false;

    /**
     * Public HTML paths that search engines may index.
     *
     * @var list<string>
     */
    public array $indexablePaths = [
        '',
        'about-us',
        'advertise-with-us',
        'payment-options',
        'membership-plans',
        'careers',
        'terms-and-conditions',
        'privacy-policy',
        'grievances',
        'fraud-alert',
        'cookie-policy',
        'sikh-matrimony',
        'how-it-works',
        'verification-and-safety',
        'faq',
        'sikh-matrimony/delhi',
        'sikh-matrimony/punjab',
        'sikh-matrimony/chandigarh',
        'sikh-matrimony/madhya-pradesh',
        'sikh-matrimony/jaipur',
        'sikh-matrimony/indore',
        'sikh-matrimony/kota',
    ];

    /**
     * Named public routes included in the XML sitemap.
     *
     * @var list<string>
     */
    public array $sitemapRouteNames = [
        'web.home',
        'web.information.about',
        'web.information.membership-plans',
        'web.information.payment-options',
        'web.information.advertise',
        'web.information.careers',
        'web.legal.terms',
        'web.legal.privacy',
        'web.legal.grievances',
        'web.legal.fraud-alert',
        'web.legal.cookie-policy',
        'web.seo.sikh-matrimony',
        'web.seo.how-it-works',
        'web.seo.verification-safety',
        'web.seo.faq',
        'web.seo.location.delhi',
        'web.seo.location.punjab',
        'web.seo.location.chandigarh',
        'web.seo.location.madhya-pradesh',
        'web.seo.location.jaipur',
        'web.seo.location.indore',
        'web.seo.location.kota',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->indexingEnabled = strtolower(
            trim(
                (string) env(
                    'APP_DEPLOYMENT',
                    'development'
                )
            )
        ) === 'production';
    }
}
