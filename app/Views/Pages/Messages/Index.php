<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * @var list<array<string,mixed>> $conversations
 * @var array<string,mixed>|null  $activeConversation
 * @var array<string,mixed>|null  $formAlert
 * @var array<string,string>      $validationErrors
 * @var list<string>              $pageScripts
 */

$this->extend(
    'Layouts/Main'
);

$this->section(
    'content'
);

$formAlert =
    isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$validationErrors =
    isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$conversations =
    isset($conversations)
    && is_array($conversations)
    ? $conversations
    : [];

$activeConversation =
    isset($activeConversation)
    && is_array($activeConversation)
    ? $activeConversation
    : null;

$activeMember =
    is_array($activeConversation)
    && isset($activeConversation['member'])
    && is_array($activeConversation['member'])
    ? $activeConversation['member']
    : null;

$activeConversationRow =
    is_array($activeConversation)
    && isset($activeConversation['conversation'])
    && is_array($activeConversation['conversation'])
    ? $activeConversation['conversation']
    : null;

$messages =
    is_array($activeConversation)
    && isset($activeConversation['messages'])
    && is_array($activeConversation['messages'])
    ? $activeConversation['messages']
    : [];

$nextBeforeMessageId =
    is_array($activeConversation)
    && isset(
        $activeConversation['nextBeforeMessageId']
    )
    && is_numeric(
        $activeConversation['nextBeforeMessageId']
    )
    ? (int) $activeConversation['nextBeforeMessageId']
    : null;

$composer =
    is_array($activeConversation)
    && isset($activeConversation['composer'])
    && is_array($activeConversation['composer'])
    ? $activeConversation['composer']
    : [];

$profileActions =
    is_array($activeConversation)
    && isset(
        $activeConversation['profileActions']
    )
    && is_array(
        $activeConversation['profileActions']
    )
    ? $activeConversation['profileActions']
    : [];

$activeProfileReference = trim(
    (string) (
        $activeMember['referenceId']
        ?? ''
    )
);

$activeReportStatus =
    mb_strtoupper(
        trim(
            (string) (
                $profileActions['reportedProfileStatus']
                ?? ''
            )
        )
    );

$activeReportStatusLabel =
    match ($activeReportStatus) {
        'OPEN' =>
        'Open',

        'REVIEWED' =>
        'Reviewed',

        'DISMISSED' =>
        'Dismissed',

        'ACTION_TAKEN' =>
        'Action Taken',

        default =>
        '',
    };

$activeHasReportedProfile =
    ($profileActions['hasReportedProfile']
        ?? false) === true;

$activeCanReport =
    ($profileActions['canReport']
        ?? false) === true;

$activeCanBlock =
    ($profileActions['canBlock']
        ?? false) === true;

$messageReportModalId =
    'messageConversationReportProfile';

$messageBlockModalId =
    'messageConversationBlockProfile';

$messageMaximumLength =
    max(
        1,
        (int) (
            $activeConversation['maximumMessageLength']
            ?? 200
        )
    );

$isDraft =
    ($activeConversation['isDraft'] ?? false)
    === true;

$plansUrl =
    route_to(
        'web.account.settings.section',
        'plans'
    );
?>

<div class="container py-4">

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $formAlert
                ?? null,
        ]
    ) ?>

    <div class="row g-0 border rounded-3 overflow-hidden bg-white">

        <!-- Conversation list -->
        <div
            class="col-12 col-lg-4 border-end
                <?= $activeConversation !== null
                    ? 'd-none d-lg-block'
                    : '' ?>">

            <div class="p-3 border-bottom">

                <h1 class="fs-20 fw-semibold mb-3">
                    Messages
                </h1>

                <div
                    class="btn-group btn-group-sm mb-3"
                    role="group"
                    aria-label="Message filters">

                    <button
                        type="button"
                        class="btn btn-outline-primary active"
                        data-message-filter="all">
                        All
                    </button>

                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        data-message-filter="unread">
                        Unread
                    </button>

                </div>

                <div class="position-relative">

                    <i
                        class="ri-search-line
                            position-absolute
                            top-50
                            translate-middle-y
                            ms-3
                            text-muted"
                        aria-hidden="true">
                    </i>

                    <input
                        type="search"
                        class="form-control ps-5"
                        placeholder="Search Name / Profile ID"
                        aria-label="Search conversations"
                        data-message-search>

                </div>

            </div>

            <div class="overflow-auto">

                <?php if ($conversations === []): ?>

                    <div class="text-center p-4">

                        <i
                            class="ri-chat-3-line
                                fs-32
                                text-muted
                                d-block
                                mb-2"
                            aria-hidden="true">
                        </i>

                        <p class="text-muted mb-3">
                            Your conversations will appear here.
                            Send an Interest or explore profiles
                            to start connecting.
                        </p>

                        <a
                            href="<?= route_to(
                                        'web.matches'
                                    ) ?>"
                            class="btn btn-primary btn-sm">
                            Explore Matches
                        </a>

                    </div>

                <?php else: ?>

                    <?php foreach (
                        $conversations
                        as $conversation
                    ): ?>

                        <?php
                        $member =
                            is_array(
                                $conversation['member']
                                    ?? null
                            )
                            ? $conversation['member']
                            : [];

                        $conversationId =
                            (int) (
                                $conversation['id']
                                ?? 0
                            );

                        $unreadCount =
                            max(
                                0,
                                (int) (
                                    $conversation['unreadCount']
                                    ?? 0
                                )
                            );

                        $name =
                            trim(
                                (string) (
                                    $member['name']
                                    ?? ''
                                )
                            );

                        $reference =
                            trim(
                                (string) (
                                    $member['referenceId']
                                    ?? ''
                                )
                            );

                        $image =
                            trim(
                                (string) (
                                    $member['image']
                                    ?? ''
                                )
                            );

                        $lastMessageAt =
                            trim(
                                (string) (
                                    $conversation['lastMessageAt']
                                    ?? ''
                                )
                            );

                        $displayLastMessageAt =
                            DateDisplay::formatUtcDateTime(
                                $lastMessageAt,
                                ''
                            );
                        ?>

                        <a
                            href="<?= route_to(
                                        'web.messages.conversation',
                                        $conversationId
                                    ) ?>"
                            class="d-block
                                text-decoration-none
                                text-body
                                border-bottom
                                p-3"
                            data-conversation-item
                            data-conversation-unread="<?= $unreadCount > 0
                                                            ? '1'
                                                            : '0' ?>"
                            data-conversation-search="<?= esc(
                                                            $name
                                                                . ' '
                                                                . $reference,
                                                            'attr'
                                                        ) ?>">

                            <div class="d-flex gap-3">

                                <img
                                    src="<?= esc(
                                                $image,
                                                'attr'
                                            ) ?>"
                                    alt="<?= esc(
                                                $name,
                                                'attr'
                                            ) ?>"
                                    width="52"
                                    height="52"
                                    class="rounded-circle
                                        flex-shrink-0
                                        object-fit-cover"
                                    style="object-position:
                                        <?= (int) (
                                            $member['photoFocalX']
                                            ?? 50
                                        ) ?>%
                                        <?= (int) (
                                            $member['photoFocalY']
                                            ?? 20
                                        ) ?>%;">

                                <div class="flex-grow-1 min-w-0">

                                    <div
                                        class="d-flex
                                            align-items-start
                                            justify-content-between
                                            gap-2">

                                        <div>
                                            <div class="fw-semibold">
                                                <?= esc($name) ?>
                                            </div>

                                            <div
                                                class="fs-12
                                                    text-primary">
                                                <?= esc(
                                                    $reference
                                                ) ?>
                                            </div>
                                        </div>

                                        <div
                                            class="text-end
        flex-shrink-0">

                                            <?php if (
                                                $displayLastMessageAt !== ''
                                            ): ?>

                                                <div
                                                    class="fs-11
                text-muted
                mb-1">

                                                    <?= esc(
                                                        $displayLastMessageAt
                                                    ) ?>

                                                </div>

                                            <?php endif; ?>

                                            <?php if (
                                                $unreadCount > 0
                                            ): ?>

                                                <span
                                                    class="badge
                rounded-pill
                bg-danger">

                                                    <?= esc(
                                                        (string) min(
                                                            99,
                                                            $unreadCount
                                                        )
                                                    ) ?>

                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                    <div
                                        class="text-muted
                                            fs-13
                                            text-truncate
                                            mt-1">

                                        <?= esc(
                                            (string) (
                                                $conversation['preview']
                                                ?? ''
                                            )
                                        ) ?>

                                    </div>

                                </div>

                            </div>

                        </a>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

            <div
                class="d-none
                    text-center
                    text-muted
                    p-4"
                data-no-unread-message>

                You're all caught up.
                You have no unread messages.

            </div>

        </div>

        <!-- Active conversation -->
        <div
            class="col-12 col-lg-8
                <?= $activeConversation === null
                    ? 'd-none d-lg-block'
                    : '' ?>">

            <?php if (
                $activeConversation === null
                || !is_array($activeMember)
            ): ?>

                <div
                    class="d-none d-lg-flex
                        align-items-center
                        justify-content-center
                        text-center
                        p-5"
                    style="min-height: 520px;">

                    <div>

                        <i
                            class="ri-message-3-line
                                fs-40
                                text-muted
                                d-block
                                mb-3"
                            aria-hidden="true">
                        </i>

                        <h2 class="fs-18 fw-semibold">
                            Select a conversation
                        </h2>

                        <p class="text-muted mb-0">
                            Choose a conversation to view your messages.
                        </p>

                    </div>

                </div>

            <?php else: ?>

                <!-- Mobile back -->
                <div
                    class="d-lg-none
                        p-2
                        border-bottom">

                    <a
                        href="<?= route_to(
                                    'web.messages'
                                ) ?>"
                        class="btn btn-sm btn-light">

                        <i
                            class="ri-arrow-left-line me-1"
                            aria-hidden="true">
                        </i>

                        Back to Messages

                    </a>

                </div>

                <!-- Conversation header -->
                <div
                    class="p-3
                        border-bottom
                        d-flex
                        align-items-center
                        gap-3">

                    <img
                        src="<?= esc(
                                    (string) (
                                        $activeMember['image']
                                        ?? ''
                                    ),
                                    'attr'
                                ) ?>"
                        alt="<?= esc(
                                    (string) (
                                        $activeMember['name']
                                        ?? 'Member'
                                    ),
                                    'attr'
                                ) ?>"
                        width="56"
                        height="56"
                        class="rounded-circle
    object-fit-cover
    flex-shrink-0"
                        style="object-position:
    <?= max(
                    0,
                    min(
                        100,
                        (int) (
                            $activeMember['photoFocalX']
                            ?? 50
                        )
                    )
                ) ?>%
    <?= max(
                    0,
                    min(
                        100,
                        (int) (
                            $activeMember['photoFocalY']
                            ?? 20
                        )
                    )
                ) ?>%;">

                    <div class="flex-grow-1">

                        <div class="fw-semibold">
                            <?= esc(
                                (string) (
                                    $activeMember['name']
                                    ?? ''
                                )
                            ) ?>
                        </div>

                        <div class="fs-12 text-primary">
                            <?= esc(
                                (string) (
                                    $activeMember['referenceId']
                                    ?? ''
                                )
                            ) ?>
                        </div>

                        <div class="fs-12 text-muted">

                            <?php if (
                                !empty($activeMember['age'])
                            ): ?>
                                <?= esc(
                                    (string) $activeMember['age']
                                ) ?>
                                yrs
                                ·
                            <?php endif; ?>

                            <?= esc(
                                (string) (
                                    $activeMember['location']
                                    ?? ''
                                )
                            ) ?>

                        </div><?php
                                $activeVerification =
                                    isset(
                                        $activeMember['verification']
                                    )
                                    && is_array(
                                        $activeMember['verification']
                                    )
                                    ? $activeMember['verification']
                                    : [];
                                ?>

                        <?php if (
                            $activeVerification !== []
                        ): ?>

                            <div class="mt-2">

                                <?= view(
                                    'Components/Member/VerificationBadges',
                                    [
                                        'verification' =>
                                        $activeVerification,
                                    ]
                                ) ?>

                            </div>

                        <?php endif; ?>

                    </div>

                    <div
                        class="d-flex
        align-items-center
        gap-2
        flex-shrink-0">

                        <a
                            href="<?= esc(
                                        (string) (
                                            $activeMember['profileUrl']
                                            ?? '#'
                                        ),
                                        'attr'
                                    ) ?>"
                            class="btn
            btn-outline-primary
            btn-sm
            d-inline-flex
            align-items-center
            gap-1">

                            <i
                                class="ri-user-line"
                                aria-hidden="true">
                            </i>

                            <span class="d-none d-sm-inline">
                                View Profile
                            </span>

                        </a>

                        <?php if (
                            $activeProfileReference !== ''
                            && (
                                $activeCanReport
                                || $activeCanBlock
                            )
                        ): ?>

                            <?= view(
                                'Components/Member/ProfileActions',
                                [
                                    'profileReference' =>
                                    $activeProfileReference,

                                    /*
                 * We are already inside Messaging.
                 * Do not display Message again.
                 */
                                    'showMessageAction' =>
                                    false,

                                    'canSendMessage' =>
                                    false,

                                    'isShortlisted' =>
                                    false,

                                    'canShortlist' =>
                                    false,

                                    'canReport' =>
                                    $activeCanReport,

                                    'canBlock' =>
                                    $activeCanBlock,

                                    'hasReportedProfile' =>
                                    $activeHasReportedProfile,

                                    'reportedProfileStatusLabel' =>
                                    $activeReportStatusLabel,

                                    'shortlistUrl' =>
                                    '',

                                    'reportModalId' =>
                                    $messageReportModalId,

                                    'blockModalId' =>
                                    $messageBlockModalId,
                                ]
                            ) ?>

                        <?php endif; ?>

                    </div>

                </div>

                <!-- Messages -->
                <div
                    class="p-3 overflow-auto"
                    style="min-height: 360px;
        max-height: 55vh;"
                    data-message-scroll>
                    <?php if (
                        $nextBeforeMessageId !== null
                        && $nextBeforeMessageId > 0
                        && is_array(
                            $activeConversationRow
                        )
                    ): ?>

                        <div
                            class="text-center
            mb-3">

                            <a
                                href="<?= route_to(
                                            'web.messages.conversation',
                                            (int) (
                                                $activeConversationRow['id']
                                                ?? 0
                                            )
                                        )
                                            . '?before='
                                            . $nextBeforeMessageId ?>"
                                class="btn
                btn-outline-primary
                btn-sm">

                                <i
                                    class="ri-history-line
                    me-1"
                                    aria-hidden="true">
                                </i>

                                Load Earlier Messages

                            </a>

                        </div>

                    <?php endif; ?>
                    <?php if ($messages === []): ?>

                        <div class="text-center text-muted py-5">
                            <p class="mb-0">
                                Start the conversation with a respectful message.
                            </p>
                        </div>

                    <?php else: ?>

                        <?php foreach (
                            $messages
                            as $message
                        ): ?>

                            <?php
                            $isSystem =
                                ($message['isSystem']
                                    ?? false)
                                === true;

                            $isMine =
                                ($message['isMine']
                                    ?? false)
                                === true;

                            $messageCreatedAt =
                                DateDisplay::formatUtcDateTime(
                                    $message['created_at']
                                        ?? null,
                                    ''
                                );
                            ?>

                            <?php if ($isSystem): ?>

                                <div
                                    class="text-center
                                        my-3">

                                    <span
                                        class="badge
                                            bg-primary-subtle
                                            text-primary
                                            p-2">

                                        <i
                                            class="ri-heart-line me-1"
                                            aria-hidden="true">
                                        </i>

                                        <?= esc(
                                            (string) (
                                                $message['message_text']
                                                ?? ''
                                            )
                                        ) ?>

                                    </span>
                                    <?php if (
                                        $messageCreatedAt !== ''
                                    ): ?>

                                        <div
                                            class="fs-11
            text-muted
            mt-1">

                                            <?= esc(
                                                $messageCreatedAt
                                            ) ?>

                                        </div>

                                    <?php endif; ?>
                                </div>

                            <?php else: ?>

                                <div
                                    class="d-flex
                                        mb-3
                                        <?= $isMine
                                            ? 'justify-content-end'
                                            : 'justify-content-start' ?>">

                                    <div
                                        class="rounded-3
                                            px-3
                                            py-2
                                            <?= $isMine
                                                ? 'bg-primary text-white'
                                                : 'bg-light' ?>"
                                        style="max-width: 78%;">

                                        <div
                                            class="fs-14"
                                            style="white-space:
                                                pre-wrap;
                                                overflow-wrap:
                                                anywhere;"><?= esc(
                                                                (string) (
                                                                    $message['message_text']
                                                                    ?? ''
                                                                )
                                                            ) ?></div>
                                        <?php if (
                                            $messageCreatedAt !== ''
                                        ): ?>

                                            <div
                                                class="fs-11
            mt-1
            <?= $isMine
                                                ? 'text-end'
                                                : 'text-muted' ?>">

                                                <?= esc(
                                                    $messageCreatedAt
                                                ) ?>

                                            </div>

                                        <?php endif; ?>

                                        <?php if ($isMine): ?>

                                            <div
                                                class="fs-11
                                                    text-end
                                                    mt-1">

                                                <?= esc(
                                                    (string) (
                                                        $message['state']
                                                        ?? 'Sent'
                                                    )
                                                ) ?>

                                            </div>

                                        <?php elseif (
                                            !($message['isRemoved']
                                                ?? false)
                                        ): ?>

                                            <?php
                                            $reportMessageId =
                                                max(
                                                    0,
                                                    (int) (
                                                        $message['id']
                                                        ?? 0
                                                    )
                                                );

                                            $reportMessageModalId =
                                                'reportMessageModal'
                                                . $reportMessageId;
                                            ?>

                                            <?php if (
                                                $reportMessageId > 0
                                            ): ?>

                                                <button
                                                    type="button"
                                                    class="btn
                btn-link
                btn-sm
                p-0
                text-danger
                mt-2"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#<?= esc(
                                                                            $reportMessageModalId,
                                                                            'attr'
                                                                        ) ?>">

                                                    <i
                                                        class="ri-flag-line me-1"
                                                        aria-hidden="true">
                                                    </i>

                                                    Report message

                                                </button>

                                                <?= view(
                                                    'Pages/Messages/_ReportMessageModal',
                                                    [
                                                        'messageId' =>
                                                        $reportMessageId,
                                                    ]
                                                ) ?>

                                            <?php endif; ?>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

                <!-- Composer / state -->
                <div class="border-top p-3">

                    <?php if (
                        ($composer['enabled']
                            ?? false)
                        === true
                    ): ?>

                        <div
                            class="alert
                                alert-warning
                                py-2
                                fs-12
                                mb-3">

                            <i
                                class="ri-shield-check-line me-1"
                                aria-hidden="true">
                            </i>

                            <?= esc(
                                (string) (
                                    $activeConversation['safetyWarning']
                                    ?? ''
                                )
                            ) ?>

                        </div>

                        <form
                            method="post"
                            action="<?= route_to(
                                        'web.members.message.send',
                                        (string) (
                                            $activeMember['referenceId']
                                            ?? ''
                                        )
                                    ) ?>"
                            data-message-form>

                            <?= csrf_field() ?>

                            <input
                                type="hidden"
                                name="client_request_id"
                                value="">

                            <textarea
                                name="message"
                                rows="3"
                                maxlength="<?= esc(
                                                (string) $messageMaximumLength,
                                                'attr'
                                            ) ?>"
                                class="form-control"
                                placeholder="Write a message..."
                                required
                                data-message-input></textarea>

                            <div
                                class="alert
        alert-info
        py-2
        fs-12
        mt-2
        mb-0
        d-none"
                                role="status"
                                data-message-privacy-warning>

                                <i
                                    class="ri-information-line me-1"
                                    aria-hidden="true">
                                </i>

                                For your privacy, avoid sharing phone numbers,
                                email addresses or other contact information until
                                you are comfortable with the member.

                            </div>

                            <div
                                class="d-flex
                                    align-items-center
                                    justify-content-between
                                    mt-2">

                                <span
                                    class="fs-12
        text-muted"
                                    data-message-counter>

                                    0/<?= esc(
                                            (string) $messageMaximumLength
                                        ) ?>

                                </span>

                                <button
                                    type="submit"
                                    class="btn
                                        registration-form__submit
                                        fs-14
                                        fw-medium
                                        text-uppercase"
                                    data-submit-button>

                                    <span
                                        class="registration-submit__idle"
                                        data-submit-idle>

                                        <i
                                            class="ri-send-plane-line
                                                fs-18
                                                me-1"
                                            aria-hidden="true">
                                        </i>

                                        Send

                                    </span>

                                    <span
                                        class="registration-submit__loading
                                            d-none"
                                        data-submit-loading>

                                        <span
                                            class="spinner-border
                                                spinner-border-sm
                                                me-1"
                                            aria-hidden="true">
                                        </span>

                                        Sending...

                                    </span>

                                </button>

                            </div>

                        </form>

                    <?php else: ?>

                        <div
                            class="alert
                                alert-light
                                border
                                mb-0">

                            <div>
                                <?= esc(
                                    (string) (
                                        $composer['reason']
                                        ?? ''
                                    )
                                ) ?>
                            </div>

                            <?php if (
                                ($composer['showUpgrade']
                                    ?? false)
                                === true
                            ): ?>

                                <a
                                    href="<?= $plansUrl ?>"
                                    class="btn
                                        btn-primary
                                        btn-sm
                                        mt-2">

                                    <?= esc(
                                        (string) (
                                            $composer['upgradeLabel']
                                            ?? 'View Plans'
                                        )
                                    ) ?>

                                </a>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php if (
    $activeProfileReference !== ''
    && $activeCanReport
    && !$activeHasReportedProfile
): ?>

    <?= view(
        'Pages/Profile/_ReportProfileModal',
        [
            'modalId' =>
            $messageReportModalId,

            'viewedProfileReference' =>
            $activeProfileReference,

            'actionUrl' =>
            (string) (
                $profileActions['reportUrl']
                ?? ''
            ),

            'actionSource' =>
            'messages',

            'reportCaptcha' =>
            (string) (
                $profileActions['reportCaptcha']
                ?? ''
            ),

            'reportValidationErrors' =>
            is_array(
                $profileActions['reportValidationErrors']
                    ?? null
            )
                ? $profileActions['reportValidationErrors']
                : [],

            'reopenReportModal' => ($profileActions['reopenReportModal'] ?? false) === true,
        ]
    ) ?>

<?php endif; ?>

<?php if (
    $activeProfileReference !== ''
    && $activeCanBlock
): ?>

    <?= view(
        'Components/Member/ProfileBlockModal',
        [
            'modalId' =>
            $messageBlockModalId,

            'profileReference' =>
            $activeProfileReference,

            'actionUrl' =>
            (string) (
                $profileActions['blockUrl']
                ?? ''
            ),

            'actionSource' =>
            'messages',

            'validationErrors' =>
            is_array(
                $profileActions['blockValidationErrors']
                    ?? null
            )
                ? $profileActions['blockValidationErrors']
                : [],

            'reopenModal' => ($profileActions['reopenBlockModal'] ?? false) === true,
        ]
    ) ?>

<?php endif; ?>

<?php
$this->endSection();
