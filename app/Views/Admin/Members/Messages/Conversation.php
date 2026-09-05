<?php

declare(strict_types=1);

/**
 * @var int                      $memberId
 * @var array<string,mixed>      $conversation
 * @var array<string,mixed>|null $formAlert
 */

$memberId =
    isset($memberId)
    && is_numeric($memberId)
    ? max(
        0,
        (int) $memberId
    )
    : 0;

$conversation =
    isset($conversation)
    && is_array($conversation)
    ? $conversation
    : [];

$formAlert =
    isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$this->extend(
    'Admin/Layouts/Main'
);

$this->section(
    'content'
);

$conversationRow =
    is_array(
        $conversation['conversation']
            ?? null
    )
    ? $conversation['conversation']
    : [];

$messages =
    is_array(
        $conversation['messages']
            ?? null
    )
    ? $conversation['messages']
    : [];
?>

<div class="container-fluid">

    <div
        class="page-title-box
            d-sm-flex
            align-items-center
            justify-content-between">

        <div>
            <h4 class="mb-sm-0">
                Conversation Review
            </h4>

            <p class="text-muted mb-0 mt-1">
                Conversation
                #<?= esc(
                        (string) (
                            $conversationRow['id']
                            ?? ''
                        )
                    ) ?>
            </p>
        </div>

        <a
            href="<?= route_to(
                        'admin.members.messages',
                        $memberId
                    ) ?>"
            class="btn btn-light">

            <i
                class="ri-arrow-left-line me-1"
                aria-hidden="true">
            </i>

            Back to Conversations

        </a>

    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $formAlert
                ?? null,
        ]
    ) ?>

    <div class="alert alert-warning">
        <i
            class="ri-lock-2-line me-1"
            aria-hidden="true">
        </i>

        Private-message inspection is audited.
        Administrator viewing does not change
        member read/unread state.
    </div>

    <div class="card">

        <div class="card-header">

            <div
                class="d-flex
                    align-items-center
                    justify-content-between">

                <h5 class="card-title mb-0">
                    Messages
                </h5>

                <span
                    class="badge
                        bg-primary-subtle
                        text-primary">

                    <?= esc(
                        (string) (
                            $conversationRow['status']
                            ?? ''
                        )
                    ) ?>

                </span>

            </div>

        </div>

        <div class="card-body">

            <?php if ($messages === []): ?>

                <p class="text-muted mb-0">
                    No messages are available.
                </p>

            <?php else: ?>

                <?php foreach (
                    $messages
                    as $message
                ): ?>

                    <?php
                    $isSystem =
                        (
                            $message['message_type']
                            ?? ''
                        ) === 'SYSTEM';

                    $isRemoved =
                        !empty($message['removed_at']);
                    ?>

                    <div
                        class="border
                            rounded-3
                            p-3
                            mb-3">

                        <div
                            class="d-flex
                                justify-content-between
                                gap-3
                                mb-2">

                            <div>

                                <?php if ($isSystem): ?>

                                    <span
                                        class="badge
                                            bg-primary-subtle
                                            text-primary">

                                        System Event

                                    </span>

                                <?php else: ?>

                                    <span class="fw-semibold">
                                        Sender User ID:
                                        <?= esc(
                                            (string) (
                                                $message['sender_user_id']
                                                ?? ''
                                            )
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                            </div>

                            <span class="text-muted fs-12">
                                <?= esc(
                                    (string) (
                                        $message['created_at']
                                        ?? ''
                                    )
                                ) ?>
                            </span>

                        </div>

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

                        <?php if ($isRemoved): ?>

                            <div
                                class="alert
                                    alert-danger
                                    py-2
                                    mt-3
                                    mb-0">

                                Removed by Admin
                                <?= esc(
                                    (string) (
                                        $message['removed_by_admin_id']
                                        ?? ''
                                    )
                                ) ?>

                                <?php if (
                                    trim(
                                        (string) (
                                            $message['removal_reason']
                                            ?? ''
                                        )
                                    ) !== ''
                                ): ?>

                                    <div class="mt-1">
                                        Reason:
                                        <?= esc(
                                            (string) $message['removal_reason']
                                        ) ?>
                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php elseif (!$isSystem): ?>

                            <form
                                method="post"
                                action="<?= route_to(
                                            'admin.members.messages.remove',
                                            $memberId,
                                            (int) (
                                                $message['id']
                                                ?? 0
                                            )
                                        ) ?>"
                                class="mt-3"
                                data-validation-form>

                                <?= csrf_field() ?>

                                <div class="mb-2">

                                    <label
                                        class="form-label"
                                        for="moderation-reason-<?= (int) (
                                                                    $message['id']
                                                                    ?? 0
                                                                ) ?>">

                                        Moderation reason

                                    </label>

                                    <textarea
                                        id="moderation-reason-<?= (int) (
                                                                    $message['id']
                                                                    ?? 0
                                                                ) ?>"
                                        name="reason"
                                        rows="2"
                                        maxlength="500"
                                        class="form-control"
                                        required></textarea>

                                </div>

                                <div class="text-end">

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
                                                class="ri-shield-cross-line
                fs-18
                me-1"
                                                aria-hidden="true">
                                            </i>

                                            Remove / Moderate

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

                                            Saving...

                                        </span>

                                    </button>

                                </div>

                            </form>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php
$this->endSection();
