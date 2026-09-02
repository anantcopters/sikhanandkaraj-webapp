<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Support\SeoMetadata;

/**
 * Displays publicly accessible legal, privacy and safety information.
 */
final class LegalController extends BaseController
{
    /**
     * Effective date of the current published documents.
     *
     * Change this only when the legal documents are formally revised.
     */
    private const EFFECTIVE_DATE = '06 August 2026';

    /**
     * Display the Terms and Conditions page.
     */
    public function termsAndConditions(): string
    {
        return $this->renderLegalPage(
            'Pages/Legal/TermsAndConditions',
            'Terms and Conditions | SikhanandKaraj',
            'Read the terms governing registration, profiles, membership, '
                . 'conduct and use of the SikhanandKaraj matrimonial platform.',
            'web.legal.terms'
        );
    }

    /**
     * Display the Privacy Policy page.
     */
    public function privacyPolicy(): string
    {
        return $this->renderLegalPage(
            'Pages/Legal/PrivacyPolicy',
            'Privacy Policy | SikhanandKaraj',
            'Learn how SikhanandKaraj collects, uses, stores and protects '
                . 'personal information across its matrimonial services.',
            'web.legal.privacy'
        );
    }

    /**
     * Display the grievance redressal page.
     */
    public function grievances(): string
    {
        return $this->renderLegalPage(
            'Pages/Legal/Grievances',
            'Grievance Redressal | SikhanandKaraj',
            'Find the SikhanandKaraj grievance redressal process and the '
                . 'appropriate way to raise a concern about the platform.',
            'web.legal.grievances'
        );
    }

    /**
     * Display fraud-prevention guidance.
     */
    public function fraudAlert(): string
    {
        return $this->renderLegalPage(
            'Pages/Legal/FraudAlert',
            'Matrimonial Fraud Alert | SikhanandKaraj',
            'Read practical matrimonial fraud-prevention and account-safety '
                . 'guidance from SikhanandKaraj before interacting or making '
                . 'payments.',
            'web.legal.fraud-alert'
        );
    }

    /**
     * Display the Cookie Policy.
     */
    public function cookiePolicy(): string
    {
        return $this->renderLegalPage(
            'Pages/Legal/CookiePolicy',
            'Cookie Policy | SikhanandKaraj',
            'Review how SikhanandKaraj uses cookies and similar technologies '
                . 'to operate and secure its matrimonial platform.',
            'web.legal.cookie-policy'
        );
    }

    /**
     * Render one public legal page using the shared application layout.
     *
     * @param string $view      View path relative to app/Views.
     * @param string $pageTitle Browser-page title.
     */
    private function renderLegalPage(
        string $view,
        string $pageTitle,
        string $description,
        string $routeName
    ): string {
        /*
         * Avoid retaining an outdated policy document after a revised version
         * has been deployed.
         */
        $this->response
            ->setHeader(
                'Cache-Control',
                'no-cache, must-revalidate, max-age=0'
            )
            ->setHeader('Pragma', 'no-cache');

        return view(
            $view,
            [
                'pageTitle' => $pageTitle,
                'effectiveDate' => self::EFFECTIVE_DATE,
                'seo' => SeoMetadata::publicPage(
                    $pageTitle,
                    $description,
                    $routeName
                ),
                'minimalPublicPage' => true,
            ]
        );
    }
}
