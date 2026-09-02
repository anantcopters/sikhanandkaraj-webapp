<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\ShortUrlService;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

final class ShortUrlController extends BaseController
{
    public function index(): string
    {
        $service =
            new ShortUrlService();

        $createdShortUrl =
            session(
                'createdShortUrl'
            );

        $createdShortUrl =
            is_array(
                $createdShortUrl
            )
            ? $createdShortUrl
            : null;

        return view(
            'Admin/ShortUrls/Index',
            [
                'pageTitle' =>
                'Short URLs',

                'shortUrls' =>
                $service->recent(),

                'createdShortUrl' =>
                $createdShortUrl,

                'validationErrors' =>
                session(
                    'shortUrlValidationErrors'
                )
                    ?? [],

                'formAlert' =>
                session(
                    'shortUrlFormAlert'
                ),
            ]
        );
    }

    public function store(): RedirectResponse
    {
        $destinationUrl =
            trim(
                (string)
                $this
                    ->request
                    ->getPost(
                        'destination_url'
                    )
            );

        $service =
            new ShortUrlService();

        $validation =
            $service->validateDestination(
                $destinationUrl
            );

        if (
            $validation['valid']
            !== true
        ) {
            return redirect()
                ->to(
                    route_to(
                        'admin.short-urls.index'
                    )
                )
                ->withInput()
                ->with(
                    'shortUrlValidationErrors',
                    [
                        'destination_url' =>
                        (string)
                        $validation['message'],
                    ]
                )
                ->with(
                    'shortUrlFormAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Unable to create short URL',

                        'message' =>
                        'Please correct the highlighted field.',
                    ]
                );
        }

        $adminUserId =
            (int)
            session(
                'admin_user_id'
            );

        if ($adminUserId <= 0) {
            /*
             * adminAuth/superAdmin should already prevent this state,
             * but the write operation still refuses to proceed without
             * a valid authenticated administrator identity.
             */
            return redirect()
                ->to(
                    route_to(
                        'admin.short-urls.index'
                    )
                )
                ->with(
                    'shortUrlFormAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Unable to create short URL',

                        'message' =>
                        'Administrator identity could not be verified.',
                    ]
                );
        }

        try {
            $result =
                $service->createOrFind(
                    $validation['url'],
                    $adminUserId
                );

            $record =
                $result['record'];

            $shortUrl =
                $service->shortUrl(
                    (string)
                    $record['short_code']
                );

            return redirect()
                ->to(
                    route_to(
                        'admin.short-urls.index'
                    )
                )
                ->with(
                    'createdShortUrl',
                    [
                        'short_url' =>
                        $shortUrl,

                        'destination_url' =>
                        (string)
                        $record['destination_url'],

                        'created' =>
                        $result['created'],
                    ]
                )
                ->with(
                    'shortUrlFormAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        $result['created']
                            ? 'Short URL created'
                            : 'Existing short URL found',

                        'message' =>
                        $result['created']
                            ? 'The short URL is ready to use.'
                            : 'This destination already has a short URL. '
                            . 'The existing URL has been returned.',
                    ]
                );
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.short-urls.index'
                    )
                )
                ->withInput()
                ->with(
                    'shortUrlFormAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Unable to create short URL',

                        'message' =>
                        'The short URL could not be created. '
                            . 'Please try again.',
                    ]
                );
        }
    }
}
