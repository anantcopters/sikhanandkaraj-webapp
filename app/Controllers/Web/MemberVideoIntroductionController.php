<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Video\MemberVideoIntroductionService;
use App\Validation\Member\VideoIntroductionValidation;
use App\Exceptions\MembershipLiveIntroductionQuotaExceededException;
use Config\VideoIntroduction;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

final class MemberVideoIntroductionController extends BaseController
{
    public function record(): string|RedirectResponse
    {
        /** @var MemberVideoIntroductionService $service */
        $service = service(
            'memberVideoIntroductionService'
        );

        $settings = $service->settingsForMember(
            $this->authenticatedUserId()
        );

        /*
        * Recording is a paid membership capability.
        *
        * Keep this controller check for customer-facing navigation while submit()
        * independently repeats the entitlement check as the actual security
        * boundary.
        */
        if (
            !service(
                'membershipEntitlementService'
            )->canCreateLiveIntroduction(
                $this->authenticatedUserId()
            )
        ) {
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
                        'warning',

                        'title' =>
                        'Membership required',

                        'message' =>
                        'A paid membership is required to '
                            . 'create a Live Introduction.',

                        'logoutAfterClose' =>
                        false,
                    ]
                );
        }

        if (
            ($settings['hasApprovedProfilePhoto'] ?? false)
            !== true
        ) {
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
                        'type' => 'warning',

                        'title' =>
                        'Approved profile photo required',

                        'message' =>
                        'Add a profile photo and wait for '
                            . 'approval before recording your '
                            . 'Video Introduction.',

                        'logoutAfterClose' =>
                        false,
                    ]
                );
        }

        return view(
            'Pages/VideoIntroduction/Record',
            array_merge(
                $settings,
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
        $privacyConsent = trim(
            (string) $this->request->getPost(
                'privacy_consent'
            )
        );

        /** @var \Config\VideoIntroduction $videoConfig */
        $videoConfig = config(
            'VideoIntroduction'
        );

        $allowedMimeTypes = array_keys(
            $videoConfig->allowedMimeTypes
        );

        $validation = service(
            'validation'
        );

        $validation->setRules([
            'video_introduction' => [
                'label' => 'Video Introduction',

                /*
                * CI4 file rules read video_introduction directly from
                * the current HTTP request. Do not pass UploadedFile to
                * Validation::run().
                */
                'rules' => [
                    'uploaded[video_introduction]',

                    'max_size[video_introduction,'
                        . $videoConfig->maximumUploadSizeKb
                        . ']',

                    'mime_in[video_introduction,'
                        . implode(
                            ',',
                            $allowedMimeTypes
                        )
                        . ']',
                ],

                'errors' => [
                    'uploaded' =>
                    'Please record your Video Introduction.',

                    'max_size' =>
                    'The Video Introduction exceeds the '
                        . 'maximum allowed file size.',

                    'mime_in' =>
                    'This browser video format is not supported.',
                ],
            ],

            'privacy_consent' => [
                'label' => 'Privacy consent',
                'rules' => [
                    'required',
                    'in_list[1]',
                ],
                'errors' => [
                    'required' =>
                    'You must accept the Video Introduction '
                        . 'guidelines and privacy conditions.',

                    'in_list' =>
                    'You must accept the Video Introduction '
                        . 'guidelines and privacy conditions.',
                ],
            ],
        ]);

        /*
        * Pass only scalar POST data. FileRules reads the uploaded video
        * directly from the request's uploaded-file collection.
        */
        if (
            !$validation->run([
                'privacy_consent' =>
                $privacyConsent,
            ])
        ) {
            $validationErrors =
                $validation->getErrors();

            $validationMessage =
                $validationErrors !== []
                ? (string) reset(
                    $validationErrors
                )
                : 'Please check the recording and try again.';

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'validationErrors',
                    $validationErrors
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'warning',

                        'title' =>
                        'Video not submitted',

                        'message' =>
                        $validationMessage,
                    ]
                );
        }

        $uploadedFile = $this->request->getFile(
            'video_introduction'
        );

        /*
        * This remains necessary even after validation so an invalid or
        * unexpectedly missing upload can never reach the service.
        */
        if (
            $uploadedFile === null
            || !$uploadedFile->isValid()
            || $uploadedFile->hasMoved()
        ) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'formAlert',
                    [
                        'type' => 'warning',

                        'title' =>
                        'Video not submitted',

                        'message' =>
                        $uploadedFile?->getErrorString()
                            ?: 'Please record your Video '
                            . 'Introduction again.',
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
                $uploadedFile,
                $privacyConsent === '1'
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
                        'Video Introduction saved',

                        'message' =>
                        'Processing and moderation will '
                            . 'continue in the background. '
                            . 'You can safely leave this page.',

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
                        'type' => 'warning',

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
                        'type' => 'danger',

                        'title' =>
                        'Video not submitted',

                        'message' =>
                        'We could not save your Video '
                            . 'Introduction. Please try again.',
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

            return $this->response
                ->setJSON(
                    [
                        'url' =>
                        $service
                            ->viewerPlaybackUrlByProfileReference(
                                $this->authenticatedUserId(),
                                $profileReference
                            ),
                    ]
                );
        } catch (
            MembershipLiveIntroductionQuotaExceededException
            $exception
        ) {
            /*
         * HTTP 429 communicates exhausted purchased usage rather than an
         * authorization failure.
         *
         * The browser receives only the customer-safe quota message.
         */
            return $this->response
                ->setStatusCode(429)
                ->setJSON(
                    [
                        'message' =>
                        $exception
                            ->getMessage(),
                    ]
                );
        } catch (DomainException $exception) {
            /*
         * Ordinary privacy/membership authorization denial.
         *
         * No signed CloudFront URL has been generated when this branch runs.
         */
            return $this->response
                ->setStatusCode(403)
                ->setJSON(
                    [
                        'message' =>
                        $exception
                            ->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Viewer Live Introduction playback '
                    . 'failed: {message}',
                [
                    'message' =>
                    $exception
                        ->getMessage(),
                ]
            );

            return $this->response
                ->setStatusCode(503)
                ->setJSON(
                    [
                        'message' =>
                        'The Live Introduction is '
                            . 'temporarily unavailable.',
                    ]
                );
        }
    }
}
