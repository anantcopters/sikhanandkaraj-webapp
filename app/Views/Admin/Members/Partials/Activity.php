<?php

declare(strict_types=1);

use App\Services\Admin\AdminMemberActivityService;

/**
 * @var int                    $memberId
 * @var array<string, int>     $activityStats
 */

$memberId = max(
    0,
    (int) (
        $memberId
        ?? 0
    )
);

$activityStats =
    isset($activityStats)
    && is_array($activityStats)
    ? $activityStats
    : [];

$cards = [
    [
        'type' =>
        AdminMemberActivityService::INTEREST_RECEIVED,

        'label' =>
        'Interest Received',

        'icon' =>
        'ri-mail-download-line',
    ],

    [
        'type' =>
        AdminMemberActivityService::INTEREST_SENT,

        'label' =>
        'Interest Sent',

        'icon' =>
        'ri-send-plane-line',
    ],

    [
        'type' =>
        AdminMemberActivityService::PROFILES_SHORTLISTED,

        'label' =>
        'Profiles Shortlisted',

        'icon' =>
        'ri-bookmark-line',
    ],

    [
        'type' =>
        AdminMemberActivityService::SHORTLISTED_BY,

        'label' =>
        'Shortlisted By',

        'icon' =>
        'ri-bookmark-3-line',
    ],

    [
        'type' =>
        AdminMemberActivityService::INTERESTS_ACCEPTED,

        'label' =>
        'Interests Accepted',

        'icon' =>
        'ri-checkbox-circle-line',
    ],

    [
        'type' =>
        AdminMemberActivityService::INTERESTS_REJECTED,

        'label' =>
        'Interests Rejected',

        'icon' =>
        'ri-close-circle-line',
    ],

    [
        'type' =>
        AdminMemberActivityService::PROFILES_VIEWED,

        'label' =>
        'Profiles Viewed',

        'icon' =>
        'ri-eye-line',
    ],

    [
        'type' =>
        AdminMemberActivityService::PROFILE_VIEWED_BY,

        'label' =>
        'Profile Viewed By',

        'icon' =>
        'ri-user-follow-line',
    ],

    [
        'type' =>
        AdminMemberActivityService::VIDEOS_WATCHED,

        'label' =>
        'Videos Watched',

        'icon' =>
        'ri-video-line',
    ],

    [
        'type' =>
        AdminMemberActivityService::VIDEO_VIEWED_BY,

        'label' =>
        'Video Viewed By',

        'icon' =>
        'ri-movie-line',
    ],

    [
        'type' =>
        AdminMemberActivityService::MUTUAL_INTERESTS,

        'label' =>
        'Mutual Interests',

        'icon' =>
        'ri-heart-2-line',
    ],

    [
        'type' =>
        AdminMemberActivityService::PROFILES_BLOCKED,

        'label' =>
        'Profiles Blocked',

        'icon' =>
        'ri-forbid-line',
    ],

    [
        'type' =>
        AdminMemberActivityService::BLOCKED_BY,

        'label' =>
        'Blocked By',

        'icon' =>
        'ri-user-forbid-line',
    ],
];
?>

<div
    class="card
        border
        border-danger
        border-opacity-25
        mb-4">

    <div class="card-header">

        <h5 class="card-title mb-0">

            <i
                class="ri-line-chart-line me-1"
                aria-hidden="true"></i>

            Member Activity

        </h5>

    </div>

    <div class="card-body">

        <div class="row g-2">

            <?php foreach (
                $cards as $card
            ): ?>

                <?php
                $activityType =
                    (string) $card['type'];

                $count = max(
                    0,
                    (int) (
                        $activityStats[$activityType]
                        ?? 0
                    )
                );
                ?>

                <div
                    class="col-6
                        col-md-4
                        col-lg-3
                        col-xl-2">

                    <a
                        href="<?= route_to(
                                    'admin.members.activity',
                                    $memberId,
                                    $activityType
                                ) ?>"
                        class="d-block
                            border
                            rounded
                            p-2
                            text-center
                            h-100
                            text-body
                            text-decoration-none">

                        <i
                            class="<?= esc(
                                        (string) $card['icon'],
                                        'attr'
                                    ) ?>
                                fs-20
                                text-primary
                                d-block
                                mb-1"
                            aria-hidden="true"></i>

                        <strong
                            class="fs-20
                                d-block
                                mb-1">

                            <?= esc(
                                (string) $count
                            ) ?>

                        </strong>

                        <span
                            class="text-muted
                                fs-12">

                            <?= esc(
                                (string) $card['label']
                            ) ?>

                        </span>

                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</div>