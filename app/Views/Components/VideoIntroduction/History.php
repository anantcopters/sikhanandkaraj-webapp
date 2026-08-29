<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * @var list<array<string, mixed>> $videoHistory
 * @var bool $showTechnicalErrors
 */

$videoHistory =
    isset($videoHistory)
    && is_array($videoHistory)
    ? $videoHistory
    : [];

$showTechnicalErrors =
    isset($showTechnicalErrors)
    && $showTechnicalErrors === true;

$statusLabels = [
    'PROCESSING' =>
    'Processing',

    'PROCESSING_FAILED' =>
    'Processing failed',

    'PENDING_REVIEW' =>
    'Under review',

    'APPROVED' =>
    'Approved',

    'REJECTED' =>
    'Rejected',

    'RESUBMISSION_REQUESTED' =>
    'Resubmission requested',

    'REPLACED' =>
    'Replaced',

    'DELETED' =>
    'Deleted',
];

$statusClasses = [
    'PROCESSING' =>
    'bg-primary-subtle text-body',

    'PROCESSING_FAILED' =>
    'bg-danger-subtle text-body',

    'PENDING_REVIEW' =>
    'bg-warning-subtle text-body',

    'APPROVED' =>
    'bg-success-subtle text-body',

    'REJECTED' =>
    'bg-danger-subtle text-body',

    'RESUBMISSION_REQUESTED' =>
    'bg-warning-subtle text-body',

    'REPLACED' =>
    'bg-secondary-subtle text-body',

    'DELETED' =>
    'bg-dark-subtle text-body',
];
?>

<div class="card border shadow-none mb-0">
    <div class="card-header bg-transparent">
        <div
            class="d-flex align-items-center
                justify-content-between gap-2">

            <div>
                <h3 class="fs-15 fw-semibold mb-1">
                    Video Introduction History
                </h3>

                <p class="text-muted fs-12 mb-0">
                    Previous submissions, processing updates
                    and moderation decisions are shown here.
                </p>
            </div>

            <i
                class="ri-history-line
                    fs-20 text-muted"
                aria-hidden="true">
            </i>
        </div>
    </div>

    <div class="card-body">
        <?php if ($videoHistory === []): ?>
            <p class="text-muted fs-13 mb-0">
                No Video Introduction history is available.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table
                    class="table table-sm
                        align-middle mb-0">

                    <thead>
                        <tr>
                            <th>Version/event</th>

                            <th>
                                Date
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Reason/details
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach (
                            $videoHistory as $video
                        ): ?>
                            <?php
                            $status = mb_strtoupper(
                                trim(
                                    (string) (
                                        $video['moderation_status']
                                        ?? ''
                                    )
                                )
                            );

                            $processingError = trim(
                                (string) (
                                    $video['processing_error']
                                    ?? ''
                                )
                            );

                            $moderationHistory =
                                isset(
                                    $video['moderation_history']
                                )
                                && is_array(
                                    $video['moderation_history']
                                )
                                ? $video['moderation_history']
                                : [];

                            $hasModerationHistory =
                                $moderationHistory !== [];
                            ?>

                            <!-- Submission event -->
                            <tr>
                                <td>
                                    <strong>
                                        Version
                                        <?= esc(
                                            (string) (
                                                $video['version_number']
                                                ?? '—'
                                            )
                                        ) ?>
                                    </strong>
                                </td>

                                <td class="text-nowrap">
                                    <?= esc(
                                        DateDisplay::formatUtcDateTime(
                                                $video['submitted_at']
                                                    ?? null
                                            )
                                    ) ?>
                                </td>

                                <td>
                                    <span
                                        class="badge
                                            bg-primary-subtle
                                            text-body p-2">

                                        Submitted
                                    </span>
                                </td>

                                <td class="text-wrap">
                                    Video Introduction submitted.
                                </td>
                            </tr>

                            <?php if (
                                $status === 'PROCESSING_FAILED'
                                && ! $hasModerationHistory
                            ): ?>
                                <!-- Processing-failure event -->
                                <tr class="table-light">
                                    <td>
                                        <span
                                            class="text-muted fs-12">

                                            Processing
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <?= esc(
                                            DateDisplay::formatUtcDateTime(
                                                    $video['updated_at']
                                                        ?? null
                                                )
                                        ) ?>
                                    </td>

                                    <td>
                                        <span
                                            class="badge
                                                bg-danger-subtle
                                                text-body p-2">

                                            Processing failed
                                        </span>
                                    </td>

                                    <td class="text-wrap">
                                        <?php if (
                                            $showTechnicalErrors
                                            && $processingError !== ''
                                        ): ?>
                                            <?= esc(
                                                $processingError
                                            ) ?>
                                        <?php else: ?>
                                            Video processing could
                                            not be completed.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php elseif (
                                ! $hasModerationHistory
                            ): ?>
                                <!-- Current non-moderated status -->
                                <tr class="table-light">
                                    <td>
                                        <span
                                            class="text-muted fs-12">

                                            Current status
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <?= esc(
                                            DateDisplay::formatUtcDateTime(
                                                    $video['updated_at']
                                                        ?? null
                                                )
                                        ) ?>
                                    </td>

                                    <td>
                                        <span
                                            class="badge p-2 <?= esc(
                                                                $statusClasses[$status]
                                                                    ?? 'bg-light',
                                                                'attr'
                                                            ) ?>">

                                            <?= esc(
                                                $statusLabels[$status]
                                                    ?? 'Unknown'
                                            ) ?>
                                        </span>
                                    </td>

                                    <td class="text-wrap">
                                        <?php if (
                                            $showTechnicalErrors
                                            && $processingError !== ''
                                        ): ?>
                                            <?= esc(
                                                $processingError
                                            ) ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach (
                                $moderationHistory as $decision
                            ): ?>
                                <?php
                                $decisionStatus =
                                    mb_strtoupper(
                                        trim(
                                            (string) (
                                                $decision['to_status']
                                                ?? ''
                                            )
                                        )
                                    );

                                $decisionReason = trim(
                                    (string) (
                                        $decision['reason']
                                        ?? ''
                                    )
                                );
                                ?>

                                <!-- Moderation event -->
                                <tr class="table-light">
                                    <td>
                                        <span
                                            class="text-muted fs-12">

                                            Moderation decision
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <?= esc(
                                            DateDisplay::formatUtcDateTime(
                                                    $decision['created_at']
                                                        ?? null
                                                )
                                        ) ?>
                                    </td>

                                    <td>
                                        <span
                                            class="badge p-2 <?= esc(
                                                                $statusClasses[$decisionStatus]
                                                                    ?? 'bg-light',
                                                                'attr'
                                                            ) ?>">

                                            <?= esc(
                                                $statusLabels[$decisionStatus]
                                                    ?? 'Updated'
                                            ) ?>
                                        </span>
                                    </td>

                                    <td class="text-wrap">
                                        <?= $decisionReason !== ''
                                            ? esc($decisionReason)
                                            : '—' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (
                                $status === 'REPLACED'
                            ): ?>
                                <!-- Replacement event -->
                                <tr class="table-light">
                                    <td>
                                        <span
                                            class="text-muted fs-12">

                                            Lifecycle
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <?= esc(
                                            DateDisplay::formatUtcDateTime(
                                                    $video['updated_at']
                                                        ?? null
                                                )
                                        ) ?>
                                    </td>

                                    <td>
                                        <span
                                            class="badge
                                                bg-secondary-subtle
                                                text-body p-2">

                                            Replaced
                                        </span>
                                    </td>

                                    <td class="text-wrap">
                                        Replaced by a newer approved
                                        Video Introduction.
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php if (
                                $status === 'DELETED'
                            ): ?>
                                <!-- Deletion event -->
                                <tr class="table-light">
                                    <td>
                                        <span
                                            class="text-muted fs-12">

                                            Lifecycle
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <?= esc(
                                            DateDisplay::formatUtcDateTime(
                                                    $video['deleted_at']
                                                        ?? $video['updated_at']
                                                        ?? null
                                                )
                                        ) ?>
                                    </td>

                                    <td>
                                        <span
                                            class="badge
                                                bg-dark-subtle
                                                text-body p-2">

                                            Deleted
                                        </span>
                                    </td>

                                    <td class="text-wrap">
                                        Video Introduction deleted
                                        by the member.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>