<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Profile\MemberAadhaarService;
use App\Support\ProfileErrorContext;
use App\Validation\Profile\MemberAadhaarValidation;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

/**
 * Handles authenticated member Aadhaar uploads.
 */
final class MemberAadhaarController extends BaseController
{
    private const RETURN_ACCOUNT_SETTINGS =
    'ACCOUNT_SETTINGS';

    /**
     * Upload an Aadhaar document.
     *
     * Aadhaar upload/re-upload is intentionally managed only
     * from Account Settings. The submitted return context is
     * never interpreted as a URL.
     */
    public function upload(): RedirectResponse
    {
        $memberId =
            $this->authenticatedUserId();

        $returnContext =
            $this->normaliseReturnContext(
                $this->request->getPost(
                    'return_context'
                )
            );

        $redirectUrl =
            $this->returnUrl(
                $returnContext
            );

        $input = [
            'return_context' =>
            $returnContext,
        ];

        $validation =
            service(
                'validation'
            );

        $validation->setRules(
            MemberAadhaarValidation::uploadRules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to($redirectUrl)
                ->with(
                    'aadhaarValidationErrors',
                    $validation->getErrors()
                )
                ->with(
                    'openAadhaarModal',
                    true
                );
        }

        $file =
            $this->request->getFile(
                'aadhaar_document'
            );

        if (
            $file === null
            || !$file->isValid()
        ) {
            return redirect()
                ->to($redirectUrl)
                ->with(
                    'aadhaarValidationErrors',
                    [
                        'aadhaar_document' =>
                        'Please select a valid '
                            . 'Aadhaar document.',
                    ]
                )
                ->with(
                    'openAadhaarModal',
                    true
                );
        }

        try {
            /** @var MemberAadhaarService $service */
            $service =
                service(
                    'memberAadhaarService'
                );

            $service->upload(
                $memberId,
                $file
            );

            return redirect()
                ->to($redirectUrl)
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Aadhaar uploaded',

                        'message' =>
                        'Your Aadhaar document is '
                            . 'now under review.',
                    ]
                );
        } catch (DomainException $exception) {
            return redirect()
                ->to($redirectUrl)
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'warning',

                        'title' =>
                        'Aadhaar not uploaded',

                        'message' =>
                        $exception->getMessage(),
                    ]
                )
                ->with(
                    'openAadhaarModal',
                    true
                );
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                ProfileErrorContext::forMember(
                    memberId: $memberId,

                    operation: 'member_aadhaar_upload',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'return_context' =>
                        $returnContext,
                    ]
                )
            );

            return redirect()
                ->to($redirectUrl)
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Aadhaar not uploaded',

                        'message' =>
                        'The Aadhaar document could '
                            . 'not be uploaded. Please '
                            . 'try again.',
                    ]
                )
                ->with(
                    'openAadhaarModal',
                    true
                );
        }
    }

    /**
     * Resolve only supported return contexts.
     *
     * Aadhaar upload is intentionally restricted to
     * Account Settings.
     */
    private function normaliseReturnContext(
        mixed $returnContext
    ): string {
        $resolvedContext =
            mb_strtoupper(
                trim(
                    (string) $returnContext
                )
            );

        if (
            $resolvedContext
            === self::RETURN_ACCOUNT_SETTINGS
        ) {
            return self::RETURN_ACCOUNT_SETTINGS;
        }

        /*
         * Fail closed to the only supported member
         * Aadhaar upload location.
         */
        return self::RETURN_ACCOUNT_SETTINGS;
    }

    /**
     * Resolve the return route internally.
     *
     * Never redirect to a URL supplied by the request.
     */
    private function returnUrl(
        string $returnContext
    ): string {
        return route_to(
            'web.account.settings.section',
            'aadhaar-verification'
        );
    }
}
