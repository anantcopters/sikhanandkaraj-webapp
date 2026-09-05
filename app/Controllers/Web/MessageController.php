<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Matchmaking\MemberProfileViewService;
use App\Services\Messaging\MemberMessagingService;
use App\Validation\Member\MemberMessageValidation;
use App\Validation\Member\MemberMessageReportValidation;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

final class MessageController extends BaseController
{
    public function index(): string|RedirectResponse
    {
        $userId =
            $this->authenticatedUserId();

        /** @var MemberMessagingService $service */
        $service = service(
            'memberMessagingService'
        );

        $activeConversation =
            null;

        $profileReference = trim(
            (string) $this
                ->request
                ->getGet(
                    'member'
                )
        );

        if ($profileReference !== '') {
            try {
                $target = $this
                    ->resolveTarget(
                        $userId,
                        $profileReference
                    );

                $existingConversation =
                    $service
                    ->existingConversationBetween(
                        $userId,
                        (int) $target['id']
                    );

                if (
                    is_array(
                        $existingConversation
                    )
                ) {
                    return redirect()
                        ->to(
                            route_to(
                                'web.messages.conversation',
                                (int) $existingConversation['id']
                            )
                        );
                }

                $activeConversation =
                    $service
                    ->draftConversation(
                        $userId,
                        (int) $target['id']
                    );
            } catch (DomainException) {
                throw PageNotFoundException
                    ::forPageNotFound();
            }
        }

        return view(
            'Pages/Messages/Index',
            [
                'pageTitle' =>
                'Messages',

                'conversations' =>
                $service
                    ->conversations(
                        $userId
                    ),

                'activeConversation' =>
                $activeConversation,

                'formAlert' =>
                $this->readFormAlert(),

                'validationErrors' =>
                $this->readValidationErrors(),

                'pageScripts' => [
                    'assets/js/components/form-validator.js',
                    'assets/js/components/submit-loader.js',
                    'assets/js/pages/member-messages.js',
                ],
            ]
        );
    }

    public function conversation(
        int $conversationId
    ): string {
        $userId =
            $this->authenticatedUserId();

        try {
            /** @var MemberMessagingService $service */
            $service = service(
                'memberMessagingService'
            );

            return view(
                'Pages/Messages/Index',
                [
                    'pageTitle' =>
                    'Messages',

                    'conversations' =>
                    $service
                        ->conversations(
                            $userId
                        ),

                    'activeConversation' =>
                    $service
                        ->conversation(
                            $conversationId,
                            $userId,
                            true
                        ),

                    'formAlert' =>
                    $this->readFormAlert(),

                    'validationErrors' =>
                    $this->readValidationErrors(),

                    'pageScripts' => [
                        'assets/js/components/form-validator.js',
                        'assets/js/components/submit-loader.js',
                        'assets/js/pages/member-messages.js',
                    ],
                ]
            );
        } catch (DomainException) {
            throw PageNotFoundException
                ::forPageNotFound();
        }
    }

    public function start(
        string $profileReference
    ): RedirectResponse {
        $userId =
            $this->authenticatedUserId();

        try {
            $target = $this
                ->resolveTarget(
                    $userId,
                    $profileReference
                );

            /*
             * Free users keep the feature discoverable but cannot create an
             * empty manual conversation merely by opening the action.
             */
            if (
                !service(
                    'membershipEntitlementService'
                )->canSendMessage(
                    $userId
                )
            ) {
                return redirect()
                    ->to(
                        route_to(
                            'web.account.settings.section',
                            'plans'
                        )
                    )
                    ->with(
                        'formAlert',
                        [
                            'type' =>
                            'info',

                            'title' =>
                            'Member Messaging',

                            'message' =>
                            'Messaging is available with membership. '
                                . 'You can receive and read messages from members. '
                                . 'Upgrade to start conversations and reply.',
                        ]
                    );
            }

            /*
             * If a conversation already exists, open it.
             */
            $conversation = service(
                'memberMessagingService'
            )->existingConversationBetween(
                $userId,
                (int) $target['id']
            );

            if (is_array($conversation)) {
                return redirect()
                    ->to(
                        route_to(
                            'web.messages.conversation',
                            (int) $conversation['id']
                        )
                    );
            }

            /*
             * No DB row is created until the first valid message is sent.
             */
            return redirect()
                ->to(
                    route_to(
                        'web.messages'
                    )
                        . '?member='
                        . rawurlencode(
                            $profileReference
                        )
                );
        } catch (PageNotFoundException) {
            throw PageNotFoundException
                ::forPageNotFound();
        }
    }

    public function send(
        string $profileReference
    ): RedirectResponse {
        $userId =
            $this->authenticatedUserId();

        $input = [
            'message' =>
            trim(
                (string) $this
                    ->request
                    ->getPost(
                        'message'
                    )
            ),

            'client_request_id' =>
            trim(
                (string) $this
                    ->request
                    ->getPost(
                        'client_request_id'
                    )
            ),
        ];

        $validation =
            service('validation');

        $validation->setRules(
            MemberMessageValidation
                ::rules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation
                        ->getErrors()
                );
        }

        try {
            $target = $this
                ->resolveTarget(
                    $userId,
                    $profileReference
                );

            service(
                'memberMessagingService'
            )->send(
                senderUserId: $userId,

                recipientUserId: (int) $target['id'],

                message: $input['message'],

                clientRequestId: $input['client_request_id']
            );

            $conversation = service(
                'memberMessagingService'
            )->existingConversationBetween(
                $userId,
                (int) $target['id']
            );

            if (!is_array($conversation)) {
                throw new DomainException(
                    'The conversation could not be resolved.'
                );
            }

            return redirect()
                ->to(
                    route_to(
                        'web.messages.conversation',
                        (int) $conversation['id']
                    )
                );
        } catch (DomainException $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Message not sent',

                        'message' =>
                        $exception
                            ->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Member message send failed. '
                    . 'Member: {memberId}; '
                    . 'profile: {profileReference}; '
                    . 'reason: {message}',
                [
                    'memberId' =>
                    $userId,

                    'profileReference' =>
                    $profileReference,

                    'message' =>
                    $exception
                        ->getMessage(),
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
                        'Message not sent',

                        'message' =>
                        'We could not send your message. Please try again.',
                    ]
                );
        }
    }

    public function report(
        int $messageId
    ): RedirectResponse {
        $userId =
            $this->authenticatedUserId();

        $input = [
            'reason' =>
            mb_strtoupper(
                trim(
                    (string) $this
                        ->request
                        ->getPost(
                            'reason'
                        )
                )
            ),

            'comment' =>
            trim(
                (string) $this
                    ->request
                    ->getPost(
                        'comment'
                    )
            ),
        ];

        $validation =
            service(
                'validation'
            );

        $validation->setRules(
            MemberMessageReportValidation
                ::rules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation
                        ->getErrors()
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Message not reported',

                        'message' =>
                        'Please correct the report details and try again.',
                    ]
                );
        }

        try {
            service(
                'memberMessagingService'
            )->reportMessage(
                reporterUserId: $userId,

                messageId: $messageId,

                reason: $input['reason'],

                comment: $input['comment']
            );

            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Message Reported',

                        'message' =>
                        'Thank you. The message has been reported for review.',
                    ]
                );
        } catch (DomainException $exception) {
            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Message not reported',

                        'message' =>
                        $exception
                            ->getMessage(),
                    ]
                );
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function resolveTarget(
        int $viewerUserId,
        string $profileReference
    ): array {
        /** @var MemberProfileViewService $service */
        $service = service(
            'memberProfileViewService'
        );

        $target = $service
            ->targetForAction(
                $viewerUserId,
                $profileReference
            );

        if (
            !is_array($target)
            || (int) (
                $target['id']
                ?? 0
            ) <= 0
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        return $target;
    }
}
