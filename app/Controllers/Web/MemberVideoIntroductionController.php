<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Video\MemberVideoIntroductionService;
use App\Validation\Member\VideoIntroductionValidation;
use Config\VideoIntroduction;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

final class MemberVideoIntroductionController extends BaseController
{
    public function record(): string
    {
        /** @var MemberVideoIntroductionService $service */
        $service = service('memberVideoIntroductionService');

        return view(
            'Pages/VideoIntroduction/Record',
            array_merge(
                $service->settingsForMember(
                    $this->authenticatedUserId()
                ),
                [
                    'pageTitle' =>
                    'Record Video Introduction',

                    'formAlert' =>
                    $this->readFormAlert(),

                    'pageScripts' => [
                        'assets/js/components/submit-loader.js',
                        'assets/js/pages/video-introduction-recorder.js',
                    ],
                ]
            )
        );
    }

    public function submit(): RedirectResponse
    {
        $config = config(
            VideoIntroduction::class
        );

        $input = [
            'privacy_consent' =>
            (string) $this->request->getPost(
                'privacy_consent'
            ),

            'video_introduction' =>
            $this->request->getFile(
                'video_introduction'
            ),
        ];

        $validation = service(
            'validation'
        );

        $validation->setRules(
            VideoIntroductionValidation::submissionRules(
                $config->maximumUploadSizeKb
            )
        );

        if (! $validation->run($input)) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'warning',

                        'title' =>
                        'Video not submitted',

                        'message' =>
                        'Please correct the highlighted '
                            . 'Video Introduction details.',
                    ]
                );
        }

        try {
            /** @var MemberVideoIntroductionService $service */
            $service = service(
                'memberVideoIntroductionService'
            );

            $service->submit(
                $this->authenticatedUserId(),
                $input['video_introduction'],
                $input['privacy_consent'] === '1'
            );

            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'video-introduction'
                    )
                )
                ->with(
                    'accountNotice',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Video Introduction saved',

                        'message' =>
                        'Your Video Introduction has been saved. '
                            . 'Processing and moderation will continue '
                            . 'in the background. You can safely leave '
                            . 'this page.',

                        'logoutAfterClose' =>
                        false,
                    ]
                );
        } catch (DomainException $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'warning',

                        'title' =>
                        'Video not submitted',

                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Video Introduction submission failed '
                    . 'for member {memberId}: {message}',
                [
                    'memberId' =>
                    $this->authenticatedUserId(),

                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Video not submitted',

                        'message' =>
                        'We could not save your '
                            . 'Video Introduction. Please try again.',
                    ]
                );
        }
    }

    public function visibility(): RedirectResponse
    {
        $input = [
            'video_visibility' =>
            mb_strtoupper(
                trim(
                    (string) $this->request->getPost(
                        'video_visibility'
                    )
                )
            ),
        ];

        $validation = service(
            'validation'
        );

        $validation->setRules(
            VideoIntroductionValidation::visibilityRules()
        );

        if (! $validation->run($input)) {
            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'video-introduction'
                    )
                )
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'warning',

                        'title' =>
                        'Privacy not updated',

                        'message' =>
                        'Please select a valid video privacy setting.',
                    ]
                );
        }

        try {
            /** @var MemberVideoIntroductionService $service */
            $service = service(
                'memberVideoIntroductionService'
            );

            $validated = $validation->getValidated();

            $service->updateVisibility(
                $this->authenticatedUserId(),
                (string) $validated['video_visibility']
            );

            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'video-introduction'
                    )
                )
                ->with(
                    'accountNotice',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Video privacy updated',

                        'message' =>
                        'Your Video Introduction privacy '
                            . 'has been updated.',

                        'logoutAfterClose' =>
                        false,
                    ]
                );
        } catch (DomainException $exception) {
            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'video-introduction'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'warning',

                        'title' =>
                        'Privacy not updated',

                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Video privacy update failed '
                    . 'for member {memberId}: {message}',
                [
                    'memberId' =>
                    $this->authenticatedUserId(),

                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'video-introduction'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Privacy not updated',

                        'message' =>
                        'Please try again.',
                    ]
                );
        }
    }

    public function delete(): RedirectResponse
    {
        try {
            /** @var MemberVideoIntroductionService $service */
            $service = service(
                'memberVideoIntroductionService'
            );

            $service->delete(
                $this->authenticatedUserId()
            );

            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'video-introduction'
                    )
                )
                ->with(
                    'accountNotice',
                    [
                        'type' => 'success',

                        'title' =>
                        'Video Introduction deleted',

                        'message' =>
                        'The video is no longer available '
                            . 'and its badge has been removed.',

                        'logoutAfterClose' => false,
                    ]
                );
        } catch (DomainException $exception) {
            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' => 'warning',

                        'title' =>
                        'Video not deleted',

                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Video deletion failed '
                    . 'for member {memberId}: {message}',
                [
                    'memberId' =>
                    $this->authenticatedUserId(),

                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' => 'danger',

                        'title' =>
                        'Video not deleted',

                        'message' =>
                        'Please try again.',
                    ]
                );
        }
    }

    public function ownerPlayback(): ResponseInterface
    {
        try {
            /** @var MemberVideoIntroductionService $service */
            $service = service(
                'memberVideoIntroductionService'
            );

            return $this->response->setJSON(
                [
                    'url' =>
                    $service->ownerPlaybackUrl(
                        $this->authenticatedUserId()
                    ),
                ]
            );
        } catch (DomainException $exception) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON(
                    [
                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Owner Video Introduction playback '
                    . 'failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return $this->response
                ->setStatusCode(503)
                ->setJSON(
                    [
                        'message' =>
                        'The Video Introduction is '
                            . 'temporarily unavailable.',
                    ]
                );
        }
    }

    public function viewerPlayback(
        string $profileReference
    ): ResponseInterface {
        try {
            /** @var MemberVideoIntroductionService $service */
            $service = service(
                'memberVideoIntroductionService'
            );

            return $this->response->setJSON(
                [
                    'url' =>
                    $service
                        ->viewerPlaybackUrlByProfileReference(
                            $this->authenticatedUserId(),
                            $profileReference
                        ),
                ]
            );
        } catch (DomainException $exception) {
            return $this->response
                ->setStatusCode(403)
                ->setJSON(
                    [
                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Viewer Video Introduction playback '
                    . 'failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return $this->response
                ->setStatusCode(503)
                ->setJSON(
                    [
                        'message' =>
                        'The Video Introduction is '
                            . 'temporarily unavailable.',
                    ]
                );
        }
    }
}
