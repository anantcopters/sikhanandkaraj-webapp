<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * @var array<string, mixed> $operations
 * @var array<string, mixed>|null $formAlert
 */

$operations =
    isset($operations)
    && is_array($operations)
    ? $operations
    : [];

$communicationHealth =
    isset($communicationHealth)
    && is_array(
        $communicationHealth
    )
    ? $communicationHealth
    : [];

$overallCommunicationStatus =
    mb_strtoupper(
        trim(
            (string) (
                $communicationHealth['status']
                ?? 'UNAVAILABLE'
            )
        )
    );

$emailCommunicationHealth =
    isset($communicationHealth['email'])
    && is_array(
        $communicationHealth['email']
    )
    ? $communicationHealth['email']
    : [];

$smsCommunicationHealth =
    isset($communicationHealth['sms'])
    && is_array(
        $communicationHealth['sms']
    )
    ? $communicationHealth['sms']
    : [];

$emailCommunicationStatus =
    mb_strtoupper(
        trim(
            (string) (
                $emailCommunicationHealth['status']
                ?? 'UNAVAILABLE'
            )
        )
    );

$smsCommunicationStatus =
    mb_strtoupper(
        trim(
            (string) (
                $smsCommunicationHealth['status']
                ?? 'UNAVAILABLE'
            )
        )
    );

/**
 * Reuse Bootstrap badge classes already used by Communication Operations.
 */
$healthBadgeClass =
    static function (
        string $status
    ): string {
        return match ($status) {
            'HEALTHY' =>
            'bg-success-subtle text-body p-2',

            'WARNING' =>
            'bg-warning-subtle text-body p-2',

            'CRITICAL' =>
            'bg-danger-subtle text-body p-2',

            default =>
            'bg-secondary-subtle text-body p-2',
        };
    };

$healthIconClass =
    static function (
        string $status
    ): string {
        return match ($status) {
            'HEALTHY' =>
            'ri-checkbox-circle-line text-success',

            'WARNING' =>
            'ri-error-warning-line text-warning',

            'CRITICAL' =>
            'ri-alarm-warning-line text-danger',

            default =>
            'ri-question-line text-secondary',
        };
    };

$rows =
    isset($operations['rows'])
    && is_array($operations['rows'])
    ? $operations['rows']
    : [];

$summary =
    isset($operations['summary'])
    && is_array($operations['summary'])
    ? $operations['summary']
    : [];

$health =
    isset($operations['health'])
    && is_array(
        $operations['health']
    )
    ? $operations['health']
    : [];

$readyNow =
    max(
        0,
        (int) (
            $health['readyNow']
            ?? 0
        )
    );

$retryPending =
    max(
        0,
        (int) (
            $health['retryPending']
            ?? 0
        )
    );

$staleProcessing =
    max(
        0,
        (int) (
            $health['staleProcessing']
            ?? 0
        )
    );

$failedHealth =
    max(
        0,
        (int) (
            $health['failed']
            ?? 0
        )
    );

$oldestPendingAt =
    trim(
        (string) (
            $health['oldestPendingAt']
            ?? ''
        )
    );

$oldestPendingMinutes =
    isset(
        $health['oldestPendingMinutes']
    )
    && is_numeric(
        $health['oldestPendingMinutes']
    )
    ? max(
        0,
        (int) $health['oldestPendingMinutes']
    )
    : null;

$filters =
    isset($operations['filters'])
    && is_array($operations['filters'])
    ? $operations['filters']
    : [];

$pagination =
    isset($operations['pagination'])
    && is_array($operations['pagination'])
    ? $operations['pagination']
    : [];

$statusOptions =
    isset($operations['statusOptions'])
    && is_array(
        $operations['statusOptions']
    )
    ? $operations['statusOptions']
    : [];

$referenceTypeOptions =
    isset(
        $operations['referenceTypeOptions']
    )
    && is_array(
        $operations['referenceTypeOptions']
    )
    ? $operations['referenceTypeOptions']
    : [];

$currentStatus =
    trim(
        (string) (
            $filters['status']
            ?? ''
        )
    );

$currentReferenceType =
    trim(
        (string) (
            $filters['referenceType']
            ?? ''
        )
    );

$currentSearch =
    trim(
        (string) (
            $filters['search']
            ?? ''
        )
    );

$currentPage =
    max(
        1,
        (int) (
            $pagination['page']
            ?? 1
        )
    );

$totalPages =
    max(
        1,
        (int) (
            $pagination['totalPages']
            ?? 1
        )
    );

$totalRows =
    max(
        0,
        (int) (
            $pagination['total']
            ?? 0
        )
    );


$currentChannel =
    isset($channel)
    && $channel === 'sms'
    ? 'sms'
    : 'email';

$emailUrl =
    route_to(
        'admin.communication-operations.index'
    )
    . '?channel=email';

$smsUrl =
    route_to(
        'admin.communication-operations.index'
    )
    . '?channel=sms';

/**
 * Preserve active filters when building pagination links.
 */
$paginationUrl =
    static function (
        int $page
    ) use (
        $currentStatus,
        $currentReferenceType,
        $currentSearch
    ): string {
        $query = [
            'page' =>
            $page,
        ];

        if ($currentStatus !== '') {
            $query['status'] =
                $currentStatus;
        }

        if (
            $currentReferenceType
            !== ''
        ) {
            $query['reference_type'] =
                $currentReferenceType;
        }

        if ($currentSearch !== '') {
            $query['search'] =
                $currentSearch;
        }

        return route_to(
            'admin.communication-operations.index'
        )
            . '?'
            . http_build_query(
                $query
            );
    };

/**
 * Reuse Bootstrap badge classes already used throughout Admin.
 */
$statusBadgeClass =
    static function (
        string $status
    ): string {
        return match ($status) {
            'SENT' =>
            'bg-success-subtle text-body p-2',

            'FAILED' =>
            'bg-danger-subtle text-body p-2',

            'PROCESSING' =>
            'bg-info-subtle text-body p-2',

            default =>
            'bg-warning-subtle text-body p-2',
        };
    };

$this->extend(
    'Admin/Layouts/Main'
);

$this->section(
    'content'
);
?>

<div class="container-fluid">

    <div class="row">
        <div class="col-12">

            <div
                class="page-title-box
                d-sm-flex
                align-items-center
                justify-content-between">

                <div>
                    <h4 class="mb-sm-0">
                        Communication Operations
                    </h4>

                    <p class="text-muted mb-0 mt-1">
                        Monitor application Email and SMS
                        communication health and operations.
                    </p>
                </div>

            </div>

        </div>
    </div>
    <!--
    Phase 6A - Combined Communication Health.

    This section intentionally reuses the existing Email queue and SMS
    operational-health calculations. It is independent of the active tab so
    Super Admin can see both channels immediately.
-->
    <div class="card border border-danger border-opacity-25">

        <div class="card-header">

            <div
                class="d-flex
            align-items-center
            justify-content-between
            gap-3">

                <div>

                    <h5 class="card-title mb-1">
                        Communication Health
                    </h5>

                    <p class="text-muted mb-0">
                        Current operational status of application
                        Email and SMS communication.
                    </p>

                </div>

                <span
                    class="badge
                <?= esc(
                    $healthBadgeClass(
                        $overallCommunicationStatus
                    ),
                    'attr'
                ) ?>">

                    <?= esc(
                        ucwords(
                            strtolower(
                                $overallCommunicationStatus
                            )
                        )
                    ) ?>

                </span>

            </div>

        </div>

        <div class="card-body">

            <div class="row g-3">

                <!-- Overall communication status -->
                <div class="col-xl-4">

                    <div
                        class="border
                    rounded
                    p-3
                    h-100">

                        <div
                            class="d-flex
                        align-items-center
                        gap-2
                        mb-3">

                            <i
                                class="<?= esc(
                                            $healthIconClass(
                                                $overallCommunicationStatus
                                            ),
                                            'attr'
                                        ) ?>
                            fs-24">
                            </i>

                            <div>

                                <div class="fw-medium">
                                    Overall
                                </div>

                                <span
                                    class="badge
                                <?= esc(
                                    $healthBadgeClass(
                                        $overallCommunicationStatus
                                    ),
                                    'attr'
                                ) ?>">

                                    <?= esc(
                                        ucwords(
                                            strtolower(
                                                $overallCommunicationStatus
                                            )
                                        )
                                    ) ?>

                                </span>

                            </div>

                        </div>

                        <p class="text-muted fs-13 mb-0">

                            <?php if (
                                $overallCommunicationStatus
                                === 'HEALTHY'
                            ): ?>

                                Email and SMS communication are
                                operating without a condition
                                requiring attention.

                            <?php elseif (
                                $overallCommunicationStatus
                                === 'WARNING'
                            ): ?>

                                Communication is operating, but
                                one or more conditions should
                                be reviewed.

                            <?php elseif (
                                $overallCommunicationStatus
                                === 'CRITICAL'
                            ): ?>

                                One or more communication
                                conditions require attention.

                            <?php else: ?>

                                Communication health could not
                                be determined.

                            <?php endif; ?>

                        </p>

                    </div>

                </div>

                <!-- Email health -->
                <div class="col-xl-4 col-md-6">

                    <div
                        class="border
                    rounded
                    p-3
                    h-100">

                        <div
                            class="d-flex
                        align-items-center
                        justify-content-between
                        gap-2
                        mb-3">

                            <div
                                class="d-flex
                            align-items-center
                            gap-2">

                                <i
                                    class="ri-mail-line
                                fs-24
                                <?= $emailCommunicationStatus
                                    === 'CRITICAL'
                                    ? 'text-danger'
                                    : (
                                        $emailCommunicationStatus
                                        === 'WARNING'
                                        ? 'text-warning'
                                        : 'text-success'
                                    ) ?>">
                                </i>

                                <span class="fw-medium">
                                    Email
                                </span>

                            </div>

                            <span
                                class="badge
                            <?= esc(
                                $healthBadgeClass(
                                    $emailCommunicationStatus
                                ),
                                'attr'
                            ) ?>">

                                <?= esc(
                                    ucwords(
                                        strtolower(
                                            $emailCommunicationStatus
                                        )
                                    )
                                ) ?>

                            </span>

                        </div>

                        <div
                            class="d-flex
                        flex-wrap
                        gap-3
                        mb-3">

                            <div>

                                <div
                                    class="text-muted
                                fs-12
                                text-uppercase">
                                    Ready
                                </div>

                                <div class="fw-semibold">

                                    <?= number_format(
                                        (int) (
                                            $emailCommunicationHealth['readyNow']
                                            ?? 0
                                        )
                                    ) ?>

                                </div>

                            </div>

                            <div>

                                <div
                                    class="text-muted
                                fs-12
                                text-uppercase">
                                    Retry
                                </div>

                                <div class="fw-semibold">

                                    <?= number_format(
                                        (int) (
                                            $emailCommunicationHealth['retryPending']
                                            ?? 0
                                        )
                                    ) ?>

                                </div>

                            </div>

                            <div>

                                <div
                                    class="text-muted
                                fs-12
                                text-uppercase">
                                    Stale
                                </div>

                                <div
                                    class="fw-semibold
                                <?= (
                                    (int) (
                                        $emailCommunicationHealth['staleProcessing']
                                        ?? 0
                                    )
                                ) > 0
                                    ? 'text-danger'
                                    : '' ?>">

                                    <?= number_format(
                                        (int) (
                                            $emailCommunicationHealth['staleProcessing']
                                            ?? 0
                                        )
                                    ) ?>

                                </div>

                            </div>

                            <div>

                                <div
                                    class="text-muted
                                fs-12
                                text-uppercase">
                                    Failed
                                </div>

                                <div
                                    class="fw-semibold
                                <?= (
                                    (int) (
                                        $emailCommunicationHealth['failed']
                                        ?? 0
                                    )
                                ) > 0
                                    ? 'text-danger'
                                    : '' ?>">

                                    <?= number_format(
                                        (int) (
                                            $emailCommunicationHealth['failed']
                                            ?? 0
                                        )
                                    ) ?>

                                </div>

                            </div>

                        </div>

                        <p class="text-muted fs-13 mb-0">

                            <?= esc(
                                (string) (
                                    $emailCommunicationHealth['message']
                                    ?? 'Email health is unavailable.'
                                )
                            ) ?>

                        </p>

                    </div>

                </div>

                <!-- SMS health -->
                <div class="col-xl-4 col-md-6">

                    <div
                        class="border
                    rounded
                    p-3
                    h-100">

                        <div
                            class="d-flex
                        align-items-center
                        justify-content-between
                        gap-2
                        mb-3">

                            <div
                                class="d-flex
                            align-items-center
                            gap-2">

                                <i
                                    class="ri-message-2-line
                                fs-24
                                <?= $smsCommunicationStatus
                                    === 'CRITICAL'
                                    ? 'text-danger'
                                    : (
                                        $smsCommunicationStatus
                                        === 'WARNING'
                                        ? 'text-warning'
                                        : 'text-success'
                                    ) ?>">
                                </i>

                                <span class="fw-medium">
                                    SMS
                                </span>

                            </div>

                            <span
                                class="badge
                            <?= esc(
                                $healthBadgeClass(
                                    $smsCommunicationStatus
                                ),
                                'attr'
                            ) ?>">

                                <?= esc(
                                    ucwords(
                                        strtolower(
                                            $smsCommunicationStatus
                                        )
                                    )
                                ) ?>

                            </span>

                        </div>

                        <div
                            class="d-flex
                        flex-wrap
                        gap-3
                        mb-3">

                            <div>

                                <div
                                    class="text-muted
                                fs-12
                                text-uppercase">
                                    Requests
                                </div>

                                <div class="fw-semibold">

                                    <?= number_format(
                                        (int) (
                                            $smsCommunicationHealth['totalLast24Hours']
                                            ?? 0
                                        )
                                    ) ?>

                                </div>

                            </div>

                            <div>

                                <div
                                    class="text-muted
                                fs-12
                                text-uppercase">
                                    Accepted
                                </div>

                                <div class="fw-semibold">

                                    <?= number_format(
                                        (int) (
                                            $smsCommunicationHealth['acceptedLast24Hours']
                                            ?? 0
                                        )
                                    ) ?>

                                </div>

                            </div>

                            <div>

                                <div
                                    class="text-muted
                                fs-12
                                text-uppercase">
                                    Failed
                                </div>

                                <div
                                    class="fw-semibold
                                <?= (
                                    (int) (
                                        $smsCommunicationHealth['failedLast24Hours']
                                        ?? 0
                                    )
                                ) > 0
                                    ? 'text-danger'
                                    : '' ?>">

                                    <?= number_format(
                                        (int) (
                                            $smsCommunicationHealth['failedLast24Hours']
                                            ?? 0
                                        )
                                    ) ?>

                                </div>

                            </div>

                            <div>

                                <div
                                    class="text-muted
                                fs-12
                                text-uppercase">
                                    Failure
                                </div>

                                <div
                                    class="fw-semibold">

                                    <?= number_format(
                                        (float) (
                                            $smsCommunicationHealth['failureRate']
                                            ?? 0
                                        ),
                                        1
                                    ) ?>%

                                </div>

                            </div>

                        </div>

                        <p class="text-muted fs-13 mb-0">

                            <?= esc(
                                (string) (
                                    $smsCommunicationHealth['message']
                                    ?? 'SMS health is unavailable.'
                                )
                            ) ?>

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>
    <div class="row mb-3">
        <div class="col-12">

            <ul class="nav nav-pills">

                <li class="nav-item">

                    <a
                        href="<?= esc(
                                    $emailUrl,
                                    'attr'
                                ) ?>"
                        class="nav-link <?= $currentChannel === 'email'
                                            ? 'active'
                                            : '' ?>">

                        <i class="ri-mail-line me-1"></i>

                        Email

                    </a>

                </li>

                <li class="nav-item">

                    <a
                        href="<?= esc(
                                    $smsUrl,
                                    'attr'
                                ) ?>"
                        class="nav-link <?= $currentChannel === 'sms'
                                            ? 'active'
                                            : '' ?>">

                        <i class="ri-message-2-line me-1"></i>

                        SMS

                    </a>

                </li>

            </ul>

        </div>
    </div>
    <?php if (
        isset($formAlert)
        && is_array($formAlert)
    ): ?>

        <div
            class="alert alert-<?= esc(
                                    (string) (
                                        $formAlert['type']
                                        ?? 'danger'
                                    ),
                                    'attr'
                                ) ?>">

            <?php if (
                trim(
                    (string) (
                        $formAlert['title']
                        ?? ''
                    )
                ) !== ''
            ): ?>

                <div class="fw-semibold mb-1">
                    <?= esc(
                        (string) $formAlert['title']
                    ) ?>
                </div>

            <?php endif; ?>

            <?= esc(
                (string) (
                    $formAlert['message']
                    ?? ''
                )
            ) ?>

        </div>

    <?php endif; ?>
    <?php if ($currentChannel === 'email'): ?>

        <!--
        Overall queue-health cards.

        These are intentionally independent of the active table filters.
    -->
        <div class="row">

            <div class="col-xl col-md-4 col-sm-6">
                <div class="card border border-danger border-opacity-25">
                    <div class="card-body">

                        <div
                            class="d-flex
                        align-items-center
                        justify-content-between">

                            <div>
                                <p
                                    class="text-muted
                                text-uppercase
                                fs-12
                                mb-1">
                                    Total
                                </p>

                                <h4 class="mb-0">
                                    <?= number_format(
                                        (int) (
                                            $summary['total']
                                            ?? 0
                                        )
                                    ) ?>
                                </h4>
                            </div>

                            <i
                                class="ri-mail-line
                            fs-24
                            text-primary">
                            </i>

                        </div>

                    </div>
                </div>
            </div>

            <div class="col-xl col-md-4 col-sm-6">
                <div class="card border border-danger border-opacity-25">
                    <div class="card-body">

                        <div
                            class="d-flex
                        align-items-center
                        justify-content-between">

                            <div>
                                <p
                                    class="text-muted
                                text-uppercase
                                fs-12
                                mb-1">
                                    Pending
                                </p>

                                <h4 class="mb-0">
                                    <?= number_format(
                                        (int) (
                                            $summary['PENDING']
                                            ?? 0
                                        )
                                    ) ?>
                                </h4>
                            </div>

                            <i
                                class="ri-time-line
                            fs-24
                            text-warning">
                            </i>

                        </div>

                    </div>
                </div>
            </div>

            <div class="col-xl col-md-4 col-sm-6">
                <div class="card border border-danger border-opacity-25">
                    <div class="card-body">

                        <div
                            class="d-flex
                        align-items-center
                        justify-content-between">

                            <div>
                                <p
                                    class="text-muted
                                text-uppercase
                                fs-12
                                mb-1">
                                    Processing
                                </p>

                                <h4 class="mb-0">
                                    <?= number_format(
                                        (int) (
                                            $summary['PROCESSING']
                                            ?? 0
                                        )
                                    ) ?>
                                </h4>
                            </div>

                            <i
                                class="ri-loader-4-line
                            fs-24
                            text-info">
                            </i>

                        </div>

                    </div>
                </div>
            </div>

            <div class="col-xl col-md-4 col-sm-6">
                <div class="card border border-danger border-opacity-25">
                    <div class="card-body">

                        <div
                            class="d-flex
                        align-items-center
                        justify-content-between">

                            <div>
                                <p
                                    class="text-muted
                                text-uppercase
                                fs-12
                                mb-1">
                                    Sent
                                </p>

                                <h4 class="mb-0">
                                    <?= number_format(
                                        (int) (
                                            $summary['SENT']
                                            ?? 0
                                        )
                                    ) ?>
                                </h4>
                            </div>

                            <i
                                class="ri-checkbox-circle-line
                            fs-24
                            text-success">
                            </i>

                        </div>

                    </div>
                </div>
            </div>

            <div class="col-xl col-md-4 col-sm-6">
                <div class="card border border-danger border-opacity-25">
                    <div class="card-body">

                        <div
                            class="d-flex
                        align-items-center
                        justify-content-between">

                            <div>
                                <p
                                    class="text-muted
                                text-uppercase
                                fs-12
                                mb-1">
                                    Failed
                                </p>

                                <h4 class="mb-0">
                                    <?= number_format(
                                        (int) (
                                            $summary['FAILED']
                                            ?? 0
                                        )
                                    ) ?>
                                </h4>
                            </div>

                            <i
                                class="ri-error-warning-line
                            fs-24
                            text-danger">
                            </i>

                        </div>

                    </div>
                </div>
            </div>

        </div>

        <div class="card border border-danger border-opacity-25">

            <div class="card-header">

                <div
                    class="d-flex
            align-items-center
            justify-content-between
            flex-wrap
            gap-2">

                    <div>

                        <h5 class="card-title mb-1">
                            Queue Health
                        </h5>

                        <p class="text-muted mb-0">
                            Current email work waiting for the queue
                            worker or requiring operational attention.
                        </p>

                    </div>

                    <?php if (
                        $staleProcessing > 0
                        || $failedHealth > 0
                    ): ?>

                        <span
                            class="badge
                    bg-danger-subtle
                    text-body
                    p-2">

                            Attention Required

                        </span>

                    <?php else: ?>

                        <span
                            class="badge
                    bg-success-subtle
                    text-body
                    p-2">

                            Healthy

                        </span>

                    <?php endif; ?>

                </div>

            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-xl-3 col-md-6">

                        <div
                            class="border
                    rounded
                    p-3
                    h-100">

                            <div
                                class="d-flex
                        align-items-center
                        gap-2
                        mb-2">

                                <i
                                    class="ri-send-plane-line
                            fs-20
                            text-primary">
                                </i>

                                <span class="fw-medium">
                                    Ready Now
                                </span>

                            </div>

                            <h4 class="mb-1">
                                <?= number_format(
                                    $readyNow
                                ) ?>
                            </h4>

                            <p class="text-muted fs-12 mb-0">
                                Pending emails currently eligible
                                for worker pickup.
                            </p>

                        </div>

                    </div>

                    <div class="col-xl-3 col-md-6">

                        <div
                            class="border
                    rounded
                    p-3
                    h-100">

                            <div
                                class="d-flex
                        align-items-center
                        gap-2
                        mb-2">

                                <i
                                    class="ri-refresh-line
                            fs-20
                            text-warning">
                                </i>

                                <span class="fw-medium">
                                    Retry Pending
                                </span>

                            </div>

                            <h4 class="mb-1">
                                <?= number_format(
                                    $retryPending
                                ) ?>
                            </h4>

                            <p class="text-muted fs-12 mb-0">
                                Previously attempted emails still
                                within the automatic retry limit.
                            </p>

                        </div>

                    </div>

                    <div class="col-xl-3 col-md-6">

                        <div
                            class="border
                    rounded
                    p-3
                    h-100">

                            <div
                                class="d-flex
                        align-items-center
                        gap-2
                        mb-2">

                                <i
                                    class="ri-loader-4-line
                            fs-20
                            <?= $staleProcessing > 0
                                ? 'text-danger'
                                : 'text-info' ?>">
                                </i>

                                <span class="fw-medium">
                                    Stale Processing
                                </span>

                            </div>

                            <h4
                                class="mb-1
                        <?= $staleProcessing > 0
                            ? 'text-danger'
                            : '' ?>">

                                <?= number_format(
                                    $staleProcessing
                                ) ?>

                            </h4>

                            <p class="text-muted fs-12 mb-0">
                                Processing records older than the
                                queue recovery threshold.
                            </p>

                        </div>

                    </div>

                    <div class="col-xl-3 col-md-6">

                        <div
                            class="border
                    rounded
                    p-3
                    h-100">

                            <div
                                class="d-flex
                        align-items-center
                        gap-2
                        mb-2">

                                <i
                                    class="ri-time-line
                            fs-20
                            text-secondary">
                                </i>

                                <span class="fw-medium">
                                    Oldest Pending
                                </span>

                            </div>

                            <?php if (
                                $oldestPendingAt !== ''
                            ): ?>

                                <h6 class="mb-1">

                                    <?= esc(
                                        DateDisplay
                                            ::formatUtcDate(
                                                $oldestPendingAt,
                                                'd M Y, h:i A'
                                            )
                                    ) ?>

                                </h6>

                                <?php if (
                                    $oldestPendingMinutes
                                    !== null
                                ): ?>

                                    <p
                                        class="text-muted
                                fs-12
                                mb-0">

                                        Waiting approximately

                                        <?php if (
                                            $oldestPendingMinutes
                                            >= 60
                                        ): ?>

                                            <?= number_format(
                                                $oldestPendingMinutes
                                                    / 60,
                                                1
                                            ) ?>
                                            hours.

                                        <?php else: ?>

                                            <?= number_format(
                                                $oldestPendingMinutes
                                            ) ?>
                                            minutes.

                                        <?php endif; ?>

                                    </p>

                                <?php endif; ?>

                            <?php else: ?>

                                <h4 class="mb-1">
                                    —
                                </h4>

                                <p class="text-muted fs-12 mb-0">
                                    No pending email.
                                </p>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- Existing Admin form/card/table patterns are reused. -->
        <div class="card border border-danger border-opacity-25">

            <div class="card-body">

                <form
                    method="get"
                    action="<?= route_to(
                                'admin.communication-operations.index'
                            ) ?>">

                    <div class="row g-3 align-items-end">

                        <div class="col-lg-4">

                            <label
                                for="communicationSearch"
                                class="form-label">
                                Search
                            </label>

                            <input
                                type="text"
                                id="communicationSearch"
                                name="search"
                                class="form-control"
                                maxlength="100"
                                value="<?= esc(
                                            $currentSearch,
                                            'attr'
                                        ) ?>"
                                placeholder="Email, name or subject">

                        </div>

                        <div class="col-lg-3">

                            <label
                                for="communicationStatus"
                                class="form-label">
                                Status
                            </label>

                            <select
                                id="communicationStatus"
                                name="status"
                                class="form-select">

                                <option value="">
                                    All Statuses
                                </option>

                                <?php foreach (
                                    $statusOptions
                                    as $statusOption
                                ): ?>

                                    <option
                                        value="<?= esc(
                                                    $statusOption,
                                                    'attr'
                                                ) ?>"
                                        <?= $currentStatus
                                            === $statusOption
                                            ? 'selected'
                                            : '' ?>>

                                        <?= esc(
                                            ucwords(
                                                strtolower(
                                                    $statusOption
                                                )
                                            )
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-lg-3">

                            <label
                                for="communicationReference"
                                class="form-label">
                                Reference
                            </label>

                            <select
                                id="communicationReference"
                                name="reference_type"
                                class="form-select">

                                <option value="">
                                    All References
                                </option>

                                <?php foreach (
                                    $referenceTypeOptions
                                    as $referenceType
                                ): ?>

                                    <option
                                        value="<?= esc(
                                                    $referenceType,
                                                    'attr'
                                                ) ?>"
                                        <?= $currentReferenceType
                                            === $referenceType
                                            ? 'selected'
                                            : '' ?>>

                                        <?= esc(
                                            $referenceType
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-lg-2">

                            <div
                                class="d-flex
                            gap-2">

                                <button
                                    type="submit"
                                    class="btn btn-primary flex-grow-1">

                                    <i
                                        class="ri-search-line
                                    align-middle me-1">
                                    </i>

                                    Search
                                </button>

                                <a
                                    href="<?= route_to(
                                                'admin.communication-operations.index'
                                            ) ?>"
                                    class="btn btn-soft-secondary"
                                    title="Reset">

                                    <i class="ri-refresh-line"></i>
                                </a>

                            </div>

                        </div>

                    </div>

                </form>

            </div>

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table
                        class="table
                    table-hover
                    table-nowrap
                    align-middle
                    mb-0">

                        <thead class="bg-info-subtle">
                            <tr>
                                <th>Queue</th>
                                <th>Recipient</th>
                                <th>Communication</th>
                                <th>Status</th>
                                <th>Attempts</th>
                                <th>Reference</th>
                                <th>Queued</th>
                                <th>Completed</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if ($rows === []): ?>

                                <tr>
                                    <td
                                        colspan="8"
                                        class="text-center
                                    text-muted
                                    py-5">

                                        <i
                                            class="ri-mail-search-line
                                        fs-24
                                        d-block
                                        mb-2">
                                        </i>

                                        No communication queue
                                        records match the selected
                                        filters.

                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach (
                                    $rows as $row
                                ): ?>

                                    <?php
                                    $status =
                                        trim(
                                            (string) (
                                                $row['status']
                                                ?? ''
                                            )
                                        );

                                    $referenceType =
                                        trim(
                                            (string) (
                                                $row['referenceType']
                                                ?? ''
                                            )
                                        );

                                    $referenceId =
                                        $row['referenceId']
                                        ?? null;

                                    $completedAt =
                                        $status === 'SENT'
                                        ? (
                                            $row['sentAt']
                                            ?? ''
                                        )
                                        : (
                                            $status
                                            === 'FAILED'
                                            ? (
                                                $row['failedAt']
                                                ?? ''
                                            )
                                            : ''
                                        );
                                    ?>

                                    <tr>

                                        <td>
                                            <div class="fw-medium">
                                                #<?= esc(
                                                        (string) (
                                                            $row['id']
                                                            ?? 0
                                                        )
                                                    ) ?>
                                            </div>

                                            <div
                                                class="text-muted
                                            fs-12">
                                                Priority
                                                <?= esc(
                                                    (string) (
                                                        $row['priority']
                                                        ?? 0
                                                    )
                                                ) ?>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="fw-medium">
                                                <?= esc(
                                                    (string) (
                                                        $row['recipient']
                                                        ?? ''
                                                    )
                                                ) ?>
                                            </div>

                                            <?php if (
                                                trim(
                                                    (string) (
                                                        $row['recipientName']
                                                        ?? ''
                                                    )
                                                ) !== ''
                                            ): ?>

                                                <div
                                                    class="text-muted
                                                fs-12">
                                                    <?= esc(
                                                        (string) $row['recipientName']
                                                    ) ?>
                                                </div>

                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <div
                                                class="text-wrap"
                                                style="min-width:220px;
                                            max-width:320px;">

                                                <?= esc(
                                                    (string) (
                                                        $row['subject']
                                                        ?? ''
                                                    )
                                                ) ?>

                                            </div>

                                            <?php if (
                                                trim(
                                                    (string) (
                                                        $row['lastError']
                                                        ?? ''
                                                    )
                                                ) !== ''
                                            ): ?>

                                                <div
                                                    class="text-danger
                                                fs-12
                                                text-wrap
                                                mt-1"
                                                    style="max-width:320px;">

                                                    <?= esc(
                                                        (string) $row['lastError']
                                                    ) ?>

                                                </div>

                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <span
                                                class="badge
                                            <?= esc(
                                                $statusBadgeClass(
                                                    $status
                                                ),
                                                'attr'
                                            ) ?>
                                            p-2">

                                                <?= esc(
                                                    ucwords(
                                                        strtolower(
                                                            $status
                                                        )
                                                    )
                                                ) ?>

                                            </span>
                                        </td>

                                        <td>
                                            <?= esc(
                                                (string) (
                                                    $row['attempts']
                                                    ?? 0
                                                )
                                            ) ?>

                                            /

                                            <?= esc(
                                                (string) (
                                                    $row['maxAttempts']
                                                    ?? 0
                                                )
                                            ) ?>
                                        </td>

                                        <td>
                                            <?php if (
                                                $referenceType
                                                !== ''
                                            ): ?>

                                                <div class="fw-medium">
                                                    <?= esc(
                                                        $referenceType
                                                    ) ?>
                                                </div>

                                                <?php if (
                                                    $referenceId
                                                    !== null
                                                ): ?>

                                                    <div
                                                        class="text-muted
                                                    fs-12">
                                                        #<?= esc(
                                                                (string)
                                                                $referenceId
                                                            ) ?>
                                                    </div>

                                                <?php endif; ?>

                                            <?php else: ?>

                                                <span class="text-muted">
                                                    —
                                                </span>

                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if (
                                                trim(
                                                    (string) (
                                                        $row['createdAt']
                                                        ?? ''
                                                    )
                                                ) !== ''
                                            ): ?>

                                                <?= esc(
                                                    DateDisplay
                                                        ::formatUtcDate(
                                                            (string) $row['createdAt'],
                                                            'd M Y, h:i A'
                                                        )
                                                ) ?>

                                            <?php else: ?>

                                                <span class="text-muted">
                                                    —
                                                </span>

                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if (
                                                trim(
                                                    (string) $completedAt
                                                ) !== ''
                                            ): ?>

                                                <?= esc(
                                                    DateDisplay
                                                        ::formatUtcDate(
                                                            (string)
                                                            $completedAt,
                                                            'd M Y, h:i A'
                                                        )
                                                ) ?>

                                            <?php else: ?>

                                                <span class="text-muted">
                                                    —
                                                </span>

                                            <?php endif; ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

            <?php if ($totalRows > 0): ?>

                <div
                    class="card-footer
                d-flex
                flex-column
                flex-sm-row
                align-items-sm-center
                justify-content-between
                gap-3">

                    <div class="text-muted fs-13">
                        <?= number_format(
                            $totalRows
                        ) ?>
                        communication record<?= $totalRows === 1
                                                ? ''
                                                : 's' ?>
                    </div>

                    <?php if ($totalPages > 1): ?>

                        <nav
                            aria-label="Communication operations pagination">

                            <ul class="pagination mb-0">

                                <li
                                    class="page-item
                                <?= $currentPage <= 1
                                    ? 'disabled'
                                    : '' ?>">

                                    <a
                                        class="page-link"
                                        href="<?= $currentPage > 1
                                                    ? esc(
                                                        $paginationUrl(
                                                            $currentPage - 1
                                                        ),
                                                        'attr'
                                                    )
                                                    : '#' ?>">
                                        Previous
                                    </a>

                                </li>

                                <li
                                    class="page-item
                                disabled">

                                    <span class="page-link">
                                        Page
                                        <?= number_format(
                                            $currentPage
                                        ) ?>
                                        of
                                        <?= number_format(
                                            $totalPages
                                        ) ?>
                                    </span>

                                </li>

                                <li
                                    class="page-item
                                <?= $currentPage
                                    >= $totalPages
                                    ? 'disabled'
                                    : '' ?>">

                                    <a
                                        class="page-link"
                                        href="<?= $currentPage
                                                    < $totalPages
                                                    ? esc(
                                                        $paginationUrl(
                                                            $currentPage + 1
                                                        ),
                                                        'attr'
                                                    )
                                                    : '#' ?>">
                                        Next
                                    </a>

                                </li>

                            </ul>

                        </nav>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>

    <?php else: ?>

        <!-- SMS CONTENT BELOW -->
        <?php

        $smsSummary =
            isset($operations['summary'])
            && is_array(
                $operations['summary']
            )
            ? $operations['summary']
            : [];

        $smsRows =
            isset($operations['rows'])
            && is_array(
                $operations['rows']
            )
            ? $operations['rows']
            : [];

        $otpAlerts =
            isset($operations['otpAlerts'])
            && is_array(
                $operations['otpAlerts']
            )
            ? $operations['otpAlerts']
            : [];

        $smsHealth =
            isset($operations['smsHealth'])
            && is_array(
                $operations['smsHealth']
            )
            ? $operations['smsHealth']
            : [];

        $operationalAlerts =
            isset($operations['operationalAlerts'])
            && is_array(
                $operations['operationalAlerts']
            )
            ? $operations['operationalAlerts']
            : [];

        $smsFailureRate =
            max(
                0.0,
                (float) (
                    $smsHealth['failureRate']
                    ?? 0.0
                )
            );

        $criticalOperationalAlerts =
            count(
                array_filter(
                    $operationalAlerts,
                    static fn(
                        array $alert
                    ): bool => (
                        $alert['severity']
                        ?? ''
                    ) === 'CRITICAL'
                )
            );

        $smsFilters =
            isset($operations['filters'])
            && is_array(
                $operations['filters']
            )
            ? $operations['filters']
            : [];

        $smsPagination =
            isset($operations['pagination'])
            && is_array(
                $operations['pagination']
            )
            ? $operations['pagination']
            : [];

        $smsStatusOptions =
            isset($operations['statusOptions'])
            && is_array(
                $operations['statusOptions']
            )
            ? $operations['statusOptions']
            : [];

        $messageTypeOptions =
            isset($operations['messageTypeOptions'])
            && is_array(
                $operations['messageTypeOptions']
            )
            ? $operations['messageTypeOptions']
            : [];

        ?>

        <div class="row">

            <?php
            $smsCards = [
                [
                    /*
                    * SENT means that the configured SMS provider accepted the request.
                    *
                    * mTalkz does not provide us with a DLR callback, therefore we must
                    * not represent this as confirmed handset delivery.
                    */
                    'label' => 'Accepted Today',
                    'value' => $smsSummary['sentToday'] ?? 0,
                    'icon' => 'ri-send-plane-line',
                    'class' => 'text-success',
                ],
                [
                    'label' => 'Failed Today',
                    'value' => $smsSummary['failedToday'] ?? 0,
                    'icon' => 'ri-error-warning-line',
                    'class' => 'text-danger',
                ],
                [
                    'label' => 'OTP Last 24 Hours',
                    'value' => $smsSummary['otpLast24Hours'] ?? 0,
                    'icon' => 'ri-shield-keyhole-line',
                    'class' => 'text-primary',
                ],
                [
                    'label' => 'Operational Alerts',
                    'value' => count(
                        $operationalAlerts
                    ),
                    'icon' => 'ri-alarm-warning-line',
                    'class' => $operationalAlerts !== []
                        ? 'text-danger'
                        : 'text-success',
                ],
            ];
            ?>

            <?php foreach ($smsCards as $card): ?>

                <div class="col-xl-3 col-md-6">

                    <div
                        class="card
                border
                border-danger
                border-opacity-25">

                        <div class="card-body">

                            <div
                                class="d-flex
                        align-items-center
                        justify-content-between">

                                <div>

                                    <p
                                        class="text-muted
                                text-uppercase
                                fs-12
                                mb-1">

                                        <?= esc(
                                            $card['label']
                                        ) ?>

                                    </p>

                                    <h4 class="mb-0">

                                        <?= number_format(
                                            (int) $card['value']
                                        ) ?>

                                    </h4>

                                </div>

                                <i
                                    class="<?= esc(
                                                $card['icon'],
                                                'attr'
                                            ) ?>
                            fs-24
                            <?= esc(
                                $card['class'],
                                'attr'
                            ) ?>">
                                </i>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>
        <div
            class="card
    border
    border-danger
    border-opacity-25">

            <div class="card-header">

                <div
                    class="d-flex
            align-items-center
            justify-content-between">

                    <div>

                        <h5 class="card-title mb-1">
                            SMS Operational Health
                        </h5>

                        <p class="text-muted mb-0">
                            Provider acceptance and failure activity
                            during the rolling last 24 hours.
                        </p>

                    </div>

                    <?php if ($criticalOperationalAlerts > 0): ?>

                        <span
                            class="badge
                    bg-danger-subtle
                    text-body
                    p-2">

                            Attention Required

                        </span>

                    <?php elseif ($operationalAlerts !== []): ?>

                        <span
                            class="badge
                    bg-warning-subtle
                    text-body
                    p-2">

                            Review Recommended

                        </span>

                    <?php else: ?>

                        <span
                            class="badge
                    bg-success-subtle
                    text-body
                    p-2">

                            Normal

                        </span>

                    <?php endif; ?>

                </div>

            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-xl-3 col-md-6">

                        <div
                            class="border
                    rounded
                    p-3
                    h-100">

                            <div
                                class="d-flex
                        align-items-center
                        gap-2
                        mb-2">

                                <i
                                    class="ri-message-2-line
                            fs-20
                            text-primary">
                                </i>

                                <span class="fw-medium">
                                    Requests
                                </span>

                            </div>

                            <h4 class="mb-1">

                                <?= number_format(
                                    (int) (
                                        $smsHealth['totalLast24Hours']
                                        ?? 0
                                    )
                                ) ?>

                            </h4>

                            <p class="text-muted fs-12 mb-0">
                                SMS requests during the last 24 hours.
                            </p>

                        </div>

                    </div>

                    <div class="col-xl-3 col-md-6">

                        <div
                            class="border
                    rounded
                    p-3
                    h-100">

                            <div
                                class="d-flex
                        align-items-center
                        gap-2
                        mb-2">

                                <i
                                    class="ri-checkbox-circle-line
                            fs-20
                            text-success">
                                </i>

                                <span class="fw-medium">
                                    Accepted
                                </span>

                            </div>

                            <h4 class="mb-1">

                                <?= number_format(
                                    (int) (
                                        $smsHealth['acceptedLast24Hours']
                                        ?? 0
                                    )
                                ) ?>

                            </h4>

                            <p class="text-muted fs-12 mb-0">
                                Requests accepted by the configured provider.
                            </p>

                        </div>

                    </div>

                    <div class="col-xl-3 col-md-6">

                        <div
                            class="border
                    rounded
                    p-3
                    h-100">

                            <div
                                class="d-flex
                        align-items-center
                        gap-2
                        mb-2">

                                <i
                                    class="ri-error-warning-line
                            fs-20
                            <?= (
                                (int) (
                                    $smsHealth['failedLast24Hours']
                                    ?? 0
                                )
                            ) > 0
                                ? 'text-danger'
                                : 'text-success' ?>">
                                </i>

                                <span class="fw-medium">
                                    Failed
                                </span>

                            </div>

                            <h4 class="mb-1">

                                <?= number_format(
                                    (int) (
                                        $smsHealth['failedLast24Hours']
                                        ?? 0
                                    )
                                ) ?>

                            </h4>

                            <p class="text-muted fs-12 mb-0">
                                Requests rejected or failed before
                                provider acceptance.
                            </p>

                        </div>

                    </div>

                    <div class="col-xl-3 col-md-6">

                        <div
                            class="border
                    rounded
                    p-3
                    h-100">

                            <div
                                class="d-flex
                        align-items-center
                        gap-2
                        mb-2">

                                <i
                                    class="ri-percent-line
                            fs-20
                            <?= $smsFailureRate >= 50
                                ? 'text-danger'
                                : (
                                    $smsFailureRate >= 20
                                    ? 'text-warning'
                                    : 'text-success'
                                ) ?>">
                                </i>

                                <span class="fw-medium">
                                    Failure Rate
                                </span>

                            </div>

                            <h4
                                class="mb-1
                        <?= $smsFailureRate >= 50
                            ? 'text-danger'
                            : '' ?>">

                                <?= number_format(
                                    $smsFailureRate,
                                    1
                                ) ?>%

                            </h4>

                            <p class="text-muted fs-12 mb-0">
                                Rolling SMS provider failure percentage.
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <?php if ($operationalAlerts !== []): ?>

            <div
                class="card
        border
        border-danger
        border-opacity-25">

                <div class="card-header">

                    <div
                        class="d-flex
                align-items-center
                justify-content-between">

                        <div>

                            <h5 class="card-title mb-1">
                                Operational Alerts
                            </h5>

                            <p class="text-muted mb-0">
                                Current SMS provider and OTP conditions
                                requiring operational review.
                            </p>

                        </div>

                        <span
                            class="badge
                    <?= $criticalOperationalAlerts > 0
                        ? 'bg-danger-subtle'
                        : 'bg-warning-subtle' ?>
                    text-body
                    p-2">

                            <?= number_format(
                                count(
                                    $operationalAlerts
                                )
                            ) ?>

                            Alert<?= count($operationalAlerts) === 1
                                        ? ''
                                        : 's' ?>

                        </span>

                    </div>

                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table
                            class="table
                    table-hover
                    align-middle
                    mb-0">

                            <thead class="table-light">

                                <tr>
                                    <th>Severity</th>
                                    <th>Alert</th>
                                    <th>Details</th>
                                    <th>Last Seen</th>
                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach (
                                    $operationalAlerts
                                    as $alert
                                ): ?>

                                    <?php

                                    $alertSeverity =
                                        mb_strtoupper(
                                            trim(
                                                (string) (
                                                    $alert['severity']
                                                    ?? 'WARNING'
                                                )
                                            )
                                        );

                                    $alertOccurredAt =
                                        trim(
                                            (string) (
                                                $alert['occurredAt']
                                                ?? ''
                                            )
                                        );

                                    ?>

                                    <tr>

                                        <td>

                                            <span
                                                class="badge
                                        <?= $alertSeverity === 'CRITICAL'
                                            ? 'bg-danger-subtle'
                                            : 'bg-warning-subtle' ?>
                                        text-body
                                        p-2">

                                                <?= esc(
                                                    $alertSeverity
                                                ) ?>

                                            </span>

                                        </td>

                                        <td>

                                            <span class="fw-medium">

                                                <?= esc(
                                                    (string) (
                                                        $alert['title']
                                                        ?? 'Operational alert'
                                                    )
                                                ) ?>

                                            </span>

                                        </td>

                                        <td>

                                            <?= esc(
                                                (string) (
                                                    $alert['message']
                                                    ?? '—'
                                                )
                                            ) ?>

                                        </td>

                                        <td>

                                            <?= $alertOccurredAt !== ''
                                                ? esc(
                                                    DateDisplay
                                                        ::formatUtcDateTime(
                                                            $alertOccurredAt
                                                        )
                                                )
                                                : '—' ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        <?php endif; ?>

        <?php if ($otpAlerts !== []): ?>

            <div
                class="card
        border
        border-danger
        border-opacity-25">

                <div class="card-header">

                    <div
                        class="d-flex
                align-items-center
                justify-content-between">

                        <div>

                            <h5 class="card-title mb-1">
                                OTP Request Alerts
                            </h5>

                            <p class="text-muted mb-0">
                                Mobile contacts which reached the existing
                                five-request rolling 24-hour OTP threshold.
                            </p>

                        </div>

                        <span
                            class="badge
                    bg-danger-subtle
                    text-body
                    p-2">

                            Attention Required

                        </span>

                    </div>

                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table
                            class="table
                    table-hover
                    align-middle
                    mb-0">

                            <thead class="table-light">

                                <tr>
                                    <th>Severity</th>
                                    <th>Mobile</th>
                                    <th>Purpose</th>
                                    <th>Requests</th>
                                    <th>Last Request</th>
                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($otpAlerts as $alert): ?>
                                    <?php

                                    $otpSeverity =
                                        mb_strtoupper(
                                            trim(
                                                (string) (
                                                    $alert['severity']
                                                    ?? 'WARNING'
                                                )
                                            )
                                        );

                                    ?>
                                    <tr>
                                        <td>

                                            <span
                                                class="badge
        <?= $otpSeverity === 'CRITICAL'
                                        ? 'bg-danger-subtle'
                                        : 'bg-warning-subtle' ?>
        text-body
        p-2">

                                                <?= esc(
                                                    $otpSeverity
                                                ) ?>

                                            </span>

                                        </td>

                                        <td>
                                            <?= esc(
                                                (string) (
                                                    $alert['mobile']
                                                    ?? '—'
                                                )
                                            ) ?>
                                        </td>

                                        <td>

                                            <span
                                                class="badge
                                        bg-warning-subtle
                                        text-body
                                        p-2">

                                                <?= esc(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        (string) (
                                                            $alert['purpose']
                                                            ?? ''
                                                        )
                                                    )
                                                ) ?>

                                            </span>

                                        </td>

                                        <td>

                                            <span
                                                class="fw-semibold text-danger">

                                                <?= number_format(
                                                    (int) (
                                                        $alert['requestCount']
                                                        ?? 0
                                                    )
                                                ) ?>

                                            </span>

                                        </td>

                                        <td>

                                            <?php
                                            $lastRequestedAt =
                                                trim(
                                                    (string) (
                                                        $alert['lastRequestedAt']
                                                        ?? ''
                                                    )
                                                );
                                            ?>

                                            <?= $lastRequestedAt !== ''
                                                ? esc(
                                                    DateDisplay
                                                        ::formatUtcDateTime(
                                                            $lastRequestedAt
                                                        )
                                                )
                                                : '—' ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        <?php endif; ?>


        <div
            class="card
    border
    border-danger
    border-opacity-25">

            <div class="card-header">

                <h5 class="card-title mb-1">
                    SMS Delivery History
                </h5>

                <p class="text-muted mb-0">
                    SMS requests accepted or rejected by the configured
                    provider. Handset delivery confirmation is not available.
                </p>

            </div>

            <div class="card-body">

                <form
                    method="get"
                    action="<?= route_to(
                                'admin.communication-operations.index'
                            ) ?>">

                    <input
                        type="hidden"
                        name="channel"
                        value="sms">

                    <div class="row g-3 align-items-end">

                        <div class="col-lg-3">

                            <label
                                for="smsStatus"
                                class="form-label">

                                Status

                            </label>

                            <select
                                id="smsStatus"
                                name="status"
                                class="form-select"
                                data-choice
                                data-choice-search="false">

                                <option value="">
                                    All Statuses
                                </option>

                                <?php foreach ($smsStatusOptions as $status): ?>

                                    <option
                                        value="<?= esc(
                                                    $status,
                                                    'attr'
                                                ) ?>"
                                        <?= (
                                            $smsFilters['status']
                                            ?? ''
                                        ) === $status
                                            ? 'selected'
                                            : '' ?>>

                                        <?= esc(
                                            $status === 'SENT'
                                                ? 'Accepted by Provider'
                                                : ucfirst(
                                                    strtolower(
                                                        $status
                                                    )
                                                )
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-lg-3">

                            <label
                                for="smsMessageType"
                                class="form-label">

                                Message Type

                            </label>

                            <select
                                id="smsMessageType"
                                name="message_type"
                                class="form-select"
                                data-choice
                                data-choice-search="false">

                                <option value="">
                                    All Types
                                </option>

                                <?php foreach ($messageTypeOptions as $type): ?>

                                    <option
                                        value="<?= esc(
                                                    $type,
                                                    'attr'
                                                ) ?>"
                                        <?= (
                                            $smsFilters['messageType']
                                            ?? ''
                                        ) === $type
                                            ? 'selected'
                                            : '' ?>>

                                        <?= esc(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $type
                                            )
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-lg-4">

                            <label
                                for="smsSearch"
                                class="form-label">

                                Search

                            </label>

                            <input
                                type="text"
                                id="smsSearch"
                                name="search"
                                class="form-control"
                                maxlength="100"
                                value="<?= esc(
                                            (string) (
                                                $smsFilters['search']
                                                ?? ''
                                            ),
                                            'attr'
                                        ) ?>"
                                placeholder="Mobile or provider reference">

                        </div>

                        <div class="col-lg-2">

                            <div class="d-flex gap-2">

                                <button
                                    type="submit"
                                    class="btn btn-primary">

                                    <i class="ri-search-line me-1"></i>
                                    Search

                                </button>

                                <a
                                    href="<?= esc(
                                                $smsUrl,
                                                'attr'
                                            ) ?>"
                                    class="btn btn-light">

                                    Reset

                                </a>

                            </div>

                        </div>

                    </div>

                </form>

            </div>

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table
                        class="table
                table-hover
                align-middle
                mb-0">

                        <thead class="table-light">

                            <tr>
                                <th>Time</th>
                                <th>Type</th>
                                <th>Mobile</th>
                                <th>Provider</th>
                                <th>Status</th>
                                <th>Provider Ref.</th>
                                <th>Error</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if ($smsRows === []): ?>

                                <tr>

                                    <td
                                        colspan="7"
                                        class="text-center
                                text-muted
                                py-4">

                                        No SMS delivery records found.

                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($smsRows as $row): ?>

                                    <?php
                                    $smsStatus =
                                        mb_strtoupper(
                                            trim(
                                                (string) (
                                                    $row['status']
                                                    ?? ''
                                                )
                                            )
                                        );

                                    $createdAt =
                                        trim(
                                            (string) (
                                                $row['createdAt']
                                                ?? ''
                                            )
                                        );
                                    ?>

                                    <tr>

                                        <td>

                                            <?= $createdAt !== ''
                                                ? esc(
                                                    DateDisplay
                                                        ::formatUtcDateTime(
                                                            $createdAt
                                                        )
                                                )
                                                : '—' ?>

                                        </td>

                                        <td>

                                            <span
                                                class="badge
                                        bg-info-subtle
                                        text-body
                                        p-2">

                                                <?= esc(
                                                    (string) (
                                                        $row['messageType']
                                                        ?? ''
                                                    )
                                                ) ?>

                                            </span>

                                        </td>

                                        <td>
                                            <?= esc(
                                                (string) (
                                                    $row['recipient']
                                                    ?? '—'
                                                )
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= esc(
                                                (string) (
                                                    $row['provider']
                                                    ?? '—'
                                                )
                                            ) ?>
                                        </td>

                                        <td>

                                            <span
                                                class="badge
                                        <?= $smsStatus === 'SENT'
                                            ? 'bg-success-subtle'
                                            : 'bg-danger-subtle' ?>
                                        text-body
                                        p-2">

                                                <?= esc(
                                                    $smsStatus === 'SENT'
                                                        ? 'Accepted by Provider'
                                                        : $smsStatus
                                                ) ?>

                                            </span>

                                        </td>

                                        <td>

                                            <?= esc(
                                                (string) (
                                                    $row['providerMessageId']
                                                    ?: '—'
                                                )
                                            ) ?>

                                        </td>

                                        <td>

                                            <span
                                                class="<?= $smsStatus === 'FAILED'
                                                            ? 'text-danger'
                                                            : 'text-muted' ?>">

                                                <?= esc(
                                                    (string) (
                                                        $row['error']
                                                        ?: '—'
                                                    )
                                                ) ?>

                                            </span>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    <?php endif; ?>


</div>

<?php $this->endSection(); ?>