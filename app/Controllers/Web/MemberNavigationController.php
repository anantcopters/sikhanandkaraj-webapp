<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;

/**
 * Provides authenticated member navigation destinations.
 *
 * Replace each action with its feature-specific controller when the
 * corresponding module is implemented.
 */
final class MemberNavigationController extends BaseController
{
    public function matches(): string
    {
        return $this->renderPage(
            'Matches',
            'ri-heart-search-line',
            'Suitable matches will appear here.'
        );
    }

    public function interests(): string
    {
        return $this->renderPage(
            'Interests',
            'ri-heart-add-line',
            'Sent and received interests will appear here.'
        );
    }

    public function messages(): string
    {
        return $this->renderPage(
            'Messages',
            'ri-message-3-line',
            'Your member conversations will appear here.'
        );
    }

    private function renderPage(
        string $title,
        string $icon,
        string $description
    ): string {
        return view(
            'Pages/MemberNavigation/Placeholder',
            [
                'pageTitle' => $title,
                'heading' => $title,
                'icon' => $icon,
                'description' => $description,
            ]
        );
    }
}
