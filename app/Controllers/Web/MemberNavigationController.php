<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Provides authenticated member navigation destinations.
 *
 * Implemented feature destinations redirect to their actual module.
 * Placeholder rendering remains only for features that are not yet active.
 */
final class MemberNavigationController extends BaseController
{
    /**
     * Open the member's complete Partner Preference based Match collection.
     *
     * Match listings intentionally reuse the Search Results page so profile
     * cards, Quick Links, sorting, pagination, photo authorization and member
     * interaction behaviour remain identical.
     */
    public function matches(): RedirectResponse
    {
        /*
         * ------------------------------------------------------------------
         * Local navigation variables
         * ------------------------------------------------------------------
         */

        $resultsUrl =
            route_to(
                'web.search.results'
            );

        $query =
            http_build_query(
                [
                    'activity' =>
                    'all-matches',
                ]
            );

        return redirect()
            ->to(
                $resultsUrl
                    . '?'
                    . $query
            );
    }
}
