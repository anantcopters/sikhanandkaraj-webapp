<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\ShortUrlService;
use CodeIgniter\HTTP\RedirectResponse;

final class ShortUrlController extends BaseController
{
    public function redirect(
        string $shortCode
    ): RedirectResponse {
        $service =
            new ShortUrlService();

        $record =
            $service->resolve(
                $shortCode
            );

        if (
            $record === null
            || trim(
                (string) (
                    $record['destination_url']
                    ?? ''
                )
            ) === ''
        ) {
            /*
             * Do not expose whether a partially guessed code exists.
             * Send invalid/unknown codes to the normal application home.
             */
            return redirect()
                ->to(
                    route_to(
                        'web.home'
                    )
                );
        }

        return redirect()
            ->to(
                (string)
                $record['destination_url']
            );
    }
}
