<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;

/**
 * Displays publicly accessible company and service information.
 */
final class InformationController extends BaseController
{
    /**
     * Display the About Us page.
     */
    public function aboutUs(): string
    {
        return $this->renderInformationPage(
            'Pages/Information/AboutUs',
            'About Us | Sikhanandkaraj'
        );
    }

    /**
     * Display advertising and partnership information.
     */
    public function advertiseWithUs(): string
    {
        return $this->renderInformationPage(
            'Pages/Information/AdvertiseWithUs',
            'Advertise With Us | Sikhanandkaraj'
        );
    }

    /**
     * Display supported payment options and payment-safety information.
     */
    public function paymentOptions(): string
    {
        return $this->renderInformationPage(
            'Pages/Information/PaymentOptions',
            'Payment Options | Sikhanandkaraj'
        );
    }

    /**
     * Display career and employment information.
     */
    public function career(): string
    {
        return $this->renderInformationPage(
            'Pages/Information/Career',
            'Careers | Sikhanandkaraj'
        );
    }

    /**
     * Render an information page through the shared public layout.
     *
     * @param string $view      View path relative to app/Views.
     * @param string $pageTitle Browser title for the current page.
     */
    private function renderInformationPage(
        string $view,
        string $pageTitle
    ): string {
        return view(
            $view,
            [
                'pageTitle' => $pageTitle,
            ]
        );
    }
}
