<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Admin\AdminMemberMessagingService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

final class MemberMessageController extends BaseController
{
    public function index(
        int $memberId
    ): string {
        if ($memberId <= 0) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        /** @var AdminMemberMessagingService $service */
        $service = service(
            'adminMemberMessagingService'
        );

        return view(
            'Admin/Members/Messages/Index',
            [
                'memberId' =>
                $memberId,

                'conversations' =>
                $service
                    ->conversationsForMember(
                        $memberId
                    ),

                'formAlert' =>
                $this->readFormAlert(),
            ]
        );
    }

    public function conversation(
        int $memberId,
        int $conversationId
    ): string {
        if (
            $memberId <= 0
            || $conversationId <= 0
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        try {
            /** @var AdminMemberMessagingService $service */
            $service = service(
                'adminMemberMessagingService'
            );

            $conversation = $service
                ->conversationForMember(
                    $memberId,
                    $conversationId
                );

            return view(
                'Admin/Members/Messages/Conversation',
                [
                    'memberId' =>
                    $memberId,

                    'conversation' =>
                    $conversation,

                    'formAlert' =>
                    $this->readFormAlert(),
                ]
            );
        } catch (DomainException) {
            throw PageNotFoundException
                ::forPageNotFound();
        }
    }

    public function remove(
        int $memberId,
        int $messageId
    ): RedirectResponse {
        if (
            $memberId <= 0
            || $messageId <= 0
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        $reason = trim(
            (string) $this
                ->request
                ->getPost(
                    'reason'
                )
        );

        try {
            service(
                'adminMemberMessagingService'
            )->removeMessageForMember(
                memberId: $memberId,

                messageId: $messageId,

                reason: $reason
            );

            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Message Moderated',

                        'message' =>
                        'The message has been removed from member-facing display.',
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
                        'danger',

                        'title' =>
                        'Message not moderated',

                        'message' =>
                        $exception
                            ->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Admin message moderation failed. '
                    . 'Member: {memberId}; '
                    . 'Message: {messageId}; '
                    . 'Reason: {error}',
                [
                    'memberId' =>
                    $memberId,

                    'messageId' =>
                    $messageId,

                    'error' =>
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
                        'Message not moderated',

                        'message' =>
                        'The message could not be moderated. Please try again.',
                    ]
                );
        }
    }
}
