<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Email\EmailRegistry;
use App\Services\Email\EmailRenderer;
use App\Services\Email\TestEmailService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use InvalidArgumentException;
use Throwable;

final class EmailPreviewController
extends BaseController
{
    public function index(): string
    {
        /** @var EmailRegistry $registry */
        $registry =
            service('emailRegistry');

        return view(
            'Admin/EmailPreview/Index',
            [
                'pageTitle' =>
                'Email Preview Centre',

                'definitions' =>
                $registry->all(),
            ]
        );
    }

    public function preview(
        string $key
    ): string {
        try {
            /** @var EmailRegistry $registry */
            $registry =
                service('emailRegistry');

            /** @var EmailRenderer $renderer */
            $renderer =
                service('emailRenderer');

            $definition =
                $registry->get($key);

            return view(
                'Admin/EmailPreview/Preview',
                [
                    'pageTitle' =>
                    $definition->name,

                    'definition' =>
                    $definition,

                    'renderedEmail' =>
                    $renderer
                        ->renderPreview(
                            $definition
                        ),

                    'pageScripts' => [
                        'assets/js/components/submit-loader.js',
                    ],
                ]
            );
        } catch (InvalidArgumentException) {
            throw PageNotFoundException
                ::forPageNotFound();
        }
    }

    public function sendTest(
        string $key
    ): RedirectResponse {
        $recipientEmail =
            mb_strtolower(
                trim(
                    (string) $this->request
                        ->getPost(
                            'recipient_email'
                        )
                )
            );

        $validation =
            service('validation');

        $validation->setRules([
            'recipient_email' => [
                'label' =>
                'Test email address',

                'rules' => [
                    'required',
                    'valid_email',
                    'max_length[254]',
                ],
            ],
        ]);

        if (
            !$validation->run([
                'recipient_email' =>
                $recipientEmail,
            ])
        ) {
            return redirect()
                ->to(
                    route_to(
                        'admin.email-preview.preview',
                        $key
                    )
                )
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            /** @var TestEmailService $service */
            $service =
                service('testEmailService');

            $service->queue(
                $key,
                $recipientEmail
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.email-preview.preview',
                        $key
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Test email queued',

                        'message' =>
                        'The test email was added '
                            . 'to the normal email queue.',
                    ]
                );
        } catch (InvalidArgumentException) {
            throw PageNotFoundException
                ::forPageNotFound();
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.email-preview.preview',
                        $key
                    )
                )
                ->withInput()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Test email not queued',

                        'message' =>
                        'The test email could not '
                            . 'be queued.',
                    ]
                );
        }
    }
}
