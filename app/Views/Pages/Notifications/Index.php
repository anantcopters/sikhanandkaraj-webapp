<?php

declare(strict_types=1);

use App\Support\DateTimeFormatter;

$pageTitle = $pageTitle ?? 'Notifications';

$notifications = isset($notifications)
    && is_array($notifications)
    ? $notifications
    : [];

$unreadNotificationCount = isset(
    $unreadNotificationCount
)
    ? max(0, (int) $unreadNotificationCount)
    : 0;
?>

<?= $this->extend('Layouts/Main') ?>

<?= $this->section('content') ?>

<section class="py-4 py-lg-4">
    <div class="container">

        <div
            class="d-flex
            flex-column
            flex-sm-row
            align-items-sm-center
            justify-content-between
            gap-3 mb-3">

            <div>
                <h1 class="h3 mb-1">
                    Notifications
                </h1>

                <p class="text-muted mb-0">
                    View your latest profile, interest and message updates.
                </p>
            </div>

            <?php if (
                $unreadNotificationCount > 0
            ): ?>
                <form
                    method="post"
                    action="<?= url_to(
                                'web.notifications.read-all'
                            ) ?>"
                    class="m-0">

                    <?= csrf_field() ?>

                    <button
                        type="submit"
                        class="btn btn-outline-primary">

                        <i
                            class="ri-check-double-line
                            align-middle me-1"
                            aria-hidden="true">
                        </i>

                        Mark All as Read
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card border border-danger border-opacity-25">

            <?php if ($notifications === []): ?>

                <div class="card-body text-center py-5">

                    <div class="avatar-lg mx-auto mb-3">
                        <span
                            class="avatar-title
                            rounded-circle
                            bg-primary-subtle
                            text-primary">

                            <i
                                class="ri-notification-off-line
                                fs-2"
                                aria-hidden="true">
                            </i>
                        </span>
                    </div>

                    <h2 class="h5 mb-2">
                        No notifications
                    </h2>

                    <p class="text-muted mb-0">
                        New notifications will appear here.
                    </p>
                </div>

            <?php else: ?>

                <div class="list-group list-group-flush">

                    <?php foreach (
                        $notifications
                        as $notification
                    ): ?>
                        <?php
                        $notificationId = isset(
                            $notification['id']
                        )
                            ? (int) $notification['id']
                            : 0;

                        $title = trim(
                            (string) (
                                $notification['title']
                                ?? 'Notification'
                            )
                        );

                        $message = trim(
                            (string) (
                                $notification['message']
                                ?? ''
                            )
                        );

                        $createdAt = DateTimeFormatter::indianDate(
                            $notification['created_at'] ?? null
                        );
                        $readAt =
                            $notification['read_at']
                            ?? null;

                        $isUnread =
                            $readAt === null
                            || $readAt === '';

                        $notificationType = trim(
                            (string) (
                                $notification['notification_type']
                                ?? ''
                            )
                        );

                        $iconClass = match ($notificationType) {
                            'MESSAGE' =>
                            'ri-message-3-line',

                            'INTEREST_RECEIVED',
                            'INTEREST_ACCEPTED',
                            'INTEREST_REJECTED' =>
                            'ri-heart-3-line text-danger',

                            'PROFILE_VIEW' =>
                            'ri-eye-line',

                            'SHORTLISTED' =>
                            'ri-bookmark-line',

                            'PHOTO_REJECTED' =>
                            'ri-image-line text-danger',

                            default =>
                            'ri-notification-3-line',
                        };
                        ?>

                        <a
                            href="<?= url_to(
                                        'web.notifications.open',
                                        $notificationId
                                    ) ?>"
                            class="list-group-item
                            px-3 py-3
                            <?= $isUnread
                                ? ''
                                : '' ?>">

                            <div
                                class="d-flex
                                align-items-start
                                gap-3">

                                <div
                                    class="avatar-sm
                                    flex-shrink-0">

                                    <span
                                        class="avatar-title
                                        rounded-circle
                                        bg-primary-subtle
                                        text-primary">

                                        <i
                                            class="<?= esc(
                                                        $iconClass
                                                    ) ?> fs-22"
                                            aria-hidden="true">
                                        </i>
                                    </span>
                                </div>

                                <div
                                    class="flex-grow-1
                                    overflow-hidden">

                                    <div
                                        class="d-flex
                                        align-items-start
                                        justify-content-between
                                        gap-3">

                                        <div class="overflow-hidden">
                                            <h2
                                                class="h6
                                                text-truncate
                                                mb-1
                                                <?= $isUnread
                                                    ? 'fw-bold'
                                                    : '' ?>">

                                                <?= esc($title) ?>
                                            </h2>

                                            <?php if (
                                                $message !== ''
                                            ): ?>
                                                <p
                                                    class="text-muted
                                                    mb-1
                                                    text-wrap">
                                                    <?= esc($message) ?>
                                                </p>
                                            <?php endif; ?>

                                            <?php if (
                                                $createdAt !== ''
                                            ): ?>
                                                <small
                                                    class="text-muted">
                                                    <?= esc(
                                                        $createdAt
                                                    ) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($isUnread): ?>
                                            <span
                                                class="badge
                                                
                                                bg-primary
                                                flex-shrink-0 p-2">
                                                New
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <i
                                    class="ri-arrow-right-s-line
                                    text-muted
                                    flex-shrink-0"
                                    aria-hidden="true">
                                </i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>
    </div>
</section>

<?= $this->endSection() ?>