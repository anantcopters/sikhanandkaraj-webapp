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
    public function upload(): RedirectResponse
    {
        $memberId = $this->authenticatedUserId();
        $validation = service('validation');
        $validation->setRules(MemberAadhaarValidation::uploadRules());

        if (!$validation->run([])) {
            return redirect()
                ->to(route_to('web.dashboard'))
                ->with('aadhaarValidationErrors', $validation->getErrors())
                ->with('openAadhaarModal', true);
        }

        $file = $this->request->getFile('aadhaar_document');

        if ($file === null || !$file->isValid()) {
            return redirect()
                ->to(route_to('web.dashboard'))
                ->with('aadhaarValidationErrors', [
                    'aadhaar_document' => 'Please select a valid Aadhaar document.',
                ])
                ->with('openAadhaarModal', true);
        }

        try {
            /** @var MemberAadhaarService $service */
            $service = service('memberAadhaarService');
            $service->upload($memberId, $file);

            return redirect()
                ->to(route_to('web.dashboard'))
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Aadhaar uploaded',
                    'message' => 'Your Aadhaar document is now under review.',
                ]);
        } catch (DomainException $exception) {
            return redirect()
                ->to(route_to('web.dashboard'))
                ->with('formAlert', [
                    'type' => 'warning',
                    'title' => 'Aadhaar not uploaded',
                    'message' => $exception->getMessage(),
                ])
                ->with('openAadhaarModal', true);
        } catch (Throwable $exception) {
            service('applicationErrorLogger')->exception(
                $exception,
                'error',
                ProfileErrorContext::forMember(
                    memberId: $memberId,
                    operation: 'member_aadhaar_upload',
                    component: self::class,
                    method: __FUNCTION__
                )
            );

            return redirect()
                ->to(route_to('web.dashboard'))
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Aadhaar not uploaded',
                    'message' => 'The Aadhaar document could not be uploaded. Please try again.',
                ])
                ->with('openAadhaarModal', true);
        }
    }
}
