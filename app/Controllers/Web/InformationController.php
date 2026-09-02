<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Support\SeoMetadata;

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
            'About SikhanandKaraj | Sikh Matrimonial Platform',
            'Learn how SikhanandKaraj helps Sikh individuals and families '
                . 'discover meaningful matrimonial connections with privacy, '
                . 'shared values and trust.',
            'web.information.about'
        );
    }

    /**
     * Display advertising and partnership information.
     */
    public function advertiseWithUs(): string
    {
        return $this->renderInformationPage(
            'Pages/Information/AdvertiseWithUs',
            'Advertise With SikhanandKaraj',
            'Explore responsible advertising and partnership opportunities '
                . 'with SikhanandKaraj and reach marriage-focused Sikh '
                . 'individuals and families.',
            'web.information.advertise'
        );
    }

    /**
     * Display supported payment options and payment-safety information.
     */
    public function paymentOptions(): string
    {
        return $this->renderInformationPage(
            'Pages/Information/PaymentOptions',
            'Membership Payment Options | SikhanandKaraj',
            'Review the available SikhanandKaraj membership payment options '
                . 'and important payment-safety guidance before making a '
                . 'payment.',
            'web.information.payment-options'
        );
    }

    /**
     * Display Sikhanandkaraj membership plans.
     *
     * Pricing is intentionally resolved from membership_plans.
     *
     * The public pricing page must never maintain an independent copy of
     * prices, duration or commercial allowances.
     */
    public function membershipPlans(): string
    {
        $plans = service(
            'membershipPlanPresentationService'
        )->publicPlans();

        return view(
            'Pages/Information/MembershipPlans',
            [
                'pageTitle' =>
                'Sikh Matrimony Membership Plans | SikhanandKaraj',

                'seo' => SeoMetadata::publicPage(
                    'Sikh Matrimony Membership Plans | SikhanandKaraj',
                    'Compare SikhanandKaraj matrimonial membership plans, '
                        . 'features and durations to choose the plan that '
                        . 'supports your search for a compatible life partner.',
                    'web.information.membership-plans'
                ),

                'minimalPublicPage' => true,

                'plans' =>
                $plans,
            ]
        );
    }

    /**
     * Display career and employment information.
     */
    public function career(): string
    {
        return $this->renderInformationPage(
            'Pages/Information/Career',
            'Careers at SikhanandKaraj',
            'Explore career opportunities with SikhanandKaraj and help build '
                . 'a trusted, privacy-conscious matrimonial platform for the '
                . 'Sikh community.',
            'web.information.careers'
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
        string $pageTitle,
        string $description,
        string $routeName
    ): string {
        return view(
            $view,
            [
                'pageTitle' =>
                $pageTitle,

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
