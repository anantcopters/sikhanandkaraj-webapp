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
                        Monitor queued and delivered
                        application email communication.
                    </p>
                </div>

                <div class="mt-3 mt-sm-0">

                    <a
                        href="<?= route_to(
                                    'admin.email-preview.index'
                                ) ?>"
                        class="btn btn-soft-primary">

                        <i
                            class="ri-mail-settings-line
                            align-middle me-1">
                        </i>

                        Email Preview Centre
                    </a>

                </div>

            </div>

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

    <!--
        Overall queue-health cards.

        These are intentionally independent of the active table filters.
    -->
    <div class="row">

        <div class="col-xl col-md-4 col-sm-6">
            <div class="card">
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
            <div class="card">
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
            <div class="card">
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
            <div class="card">
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
            <div class="card">
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

    <!-- Existing Admin form/card/table patterns are reused. -->
    <div class="card">

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

</div>

<?php $this->endSection(); ?>