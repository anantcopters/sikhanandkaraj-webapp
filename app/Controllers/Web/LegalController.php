<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;

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
            'Terms and Conditions | Sikhanandkaraj'
        );
    }

    /**
     * Display the Privacy Policy page.
     */
    public function privacyPolicy(): string
    {
        return $this->renderLegalPage(
            'Pages/Legal/PrivacyPolicy',
            'Privacy Policy | Sikhanandkaraj'
        );
    }

    /**
     * Display the grievance redressal page.
     */
    public function grievances(): string
    {
        return $this->renderLegalPage(
            'Pages/Legal/Grievances',
            'Grievance Redressal | Sikhanandkaraj'
        );
    }

    /**
     * Display fraud-prevention guidance.
     */
    public function fraudAlert(): string
    {
        return $this->renderLegalPage(
            'Pages/Legal/FraudAlert',
            'Fraud Alert | Sikhanandkaraj'
        );
    }

    /**
     * Display the Cookie Policy.
     */
    public function cookiePolicy(): string
    {
        return $this->renderLegalPage(
            'Pages/Legal/CookiePolicy',
            'Cookie Policy | Sikhanandkaraj'
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
        string $pageTitle
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
            ]
        );
    }
}
