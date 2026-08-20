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
    'bg-primary-subtle text-primary',

    'PROCESSING_FAILED' =>
    'bg-danger-subtle text-danger',

    'PENDING_REVIEW' =>
    'bg-warning-subtle text-warning',

    'APPROVED' =>
    'bg-success-subtle text-success',

    'REJECTED' =>
    'bg-danger-subtle text-danger',

    'RESUBMISSION_REQUESTED' =>
    'bg-warning-subtle text-warning',

    'REPLACED' =>
    'bg-secondary-subtle text-secondary',

    'DELETED' =>
    'bg-dark-subtle text-dark',
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
                    Previous submissions and moderation
                    decisions are shown here.
                </p>
            </div>

            <i
                class="ri-history-line fs-20 text-muted"
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
                            <th>Version</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Reason/details</th>
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

                            $rejectionReason = trim(
                                (string) (
                                    $video['rejection_reason']
                                    ?? ''
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
                            ?>

                            <tr>
                                <td>
                                    <?= esc(
                                        (string) (
                                            $video['version_number']
                                            ?? '—'
                                        )
                                    ) ?>
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
                                        class="badge <?= esc(
                                                            $statusClasses[$status]
                                                                ?? 'bg-light text-body',
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
                                        $rejectionReason !== ''
                                    ): ?>
                                        <?= esc(
                                            $rejectionReason
                                        ) ?>
                                    <?php elseif (
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

                                <tr class="table-light">
                                    <td>
                                        <span
                                            class="text-muted fs-12">

                                            Decision
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
                                            class="badge <?= esc(
                                                                $statusClasses[$decisionStatus]
                                                                    ?? 'bg-light text-body',
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
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>