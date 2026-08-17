<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * @var list<array<string, mixed>> $requests
 * @var mixed                      $pager
 * @var string                     $selectedStatus
 * @var string                     $searchTerm
 * @var array<string, string>      $validationErrors
 * @var array<string, mixed>       $reviewRecord
 * @var array<string, mixed>|null  $formAlert
 */

$requests =
    isset($requests)
    && is_array($requests)
    ? $requests
    : [];

$resolvedRequests =
    isset($requests)
    && is_array($requests)
    ? $requests
    : [];

$resolvedStatus = in_array(
    $selectedStatus ?? '',
    [
        'ALL',
        'OPEN',
        'RESOLVED',
    ],
    true
)
    ? (string) $selectedStatus
    : 'OPEN';

$resolvedSearch = trim(
    (string) (
        $searchTerm
        ?? ''
    )
);

$resolvedAlert =
    isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>

<div class="container-fluid">

    <div class="row">
        <div class="col-12">
            <div
                class="page-title-box
                    d-sm-flex
                    align-items-sm-center
                    justify-content-between
                    gap-3">

                <div>
                    <h4 class="mb-sm-0">
                        Contact Requests
                    </h4>

                    <p class="text-muted mb-0">
                        Review and resolve messages raised
                        by registered members.
                    </p>
                </div>

                <div class="page-title-right mt-3 mt-sm-0">
                    <form
                        method="get"
                        action="<?= route_to(
                                    'admin.support.contacts'
                                ) ?>"
                        class="d-flex gap-2">

                        <?php if (
                            $resolvedSearch !== ''
                        ): ?>
                            <input
                                type="hidden"
                                name="search"
                                value="<?= esc(
                                            $resolvedSearch,
                                            'attr'
                                        ) ?>">
                        <?php endif; ?>

                        <label
                            for="contact-status-filter"
                            class="visually-hidden">

                            Filter contact requests
                        </label>

                        <select
                            id="contact-status-filter"
                            name="status"
                            class="form-select"
                            data-choice
                            data-choice-search="false"
                            data-choice-position="bottom">

                            <?php foreach (
                                [
                                    'ALL' =>
                                    'All Requests',

                                    'OPEN' =>
                                    'Open',

                                    'RESOLVED' =>
                                    'Resolved',
                                ] as $value => $label
                            ): ?>
                                <option
                                    value="<?= esc(
                                                $value,
                                                'attr'
                                            ) ?>"
                                    <?= $resolvedStatus
                                        === $value
                                        ? 'selected'
                                        : '' ?>>

                                    <?= esc($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button
                            type="submit"
                            class="btn btn-primary">

                            Filter
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' => $resolvedAlert,
        ]
    ) ?>

    <div
        class="card
            border
            border-danger
            border-opacity-25">

        <div class="card-header">
            <form
                method="get"
                action="<?= route_to(
                            'admin.support.contacts'
                        ) ?>"
                class="row g-2 align-items-end">

                <input
                    type="hidden"
                    name="status"
                    value="<?= esc(
                                $resolvedStatus,
                                'attr'
                            ) ?>">

                <div class="col-12 col-md-8 col-lg-6">
                    <label
                        for="contact-request-search"
                        class="form-label">

                        Search requests
                    </label>

                    <div class="input-group">
                        <span class="input-group-text">
                            <i
                                class="ri-search-line"
                                aria-hidden="true">
                            </i>
                        </span>

                        <input
                            type="search"
                            id="contact-request-search"
                            name="search"
                            class="form-control"
                            value="<?= esc(
                                        $resolvedSearch,
                                        'attr'
                                    ) ?>"
                            maxlength="100"
                            placeholder="Request ID, Profile ID or member name">
                    </div>
                </div>

                <div class="col-12 col-md-auto">
                    <button
                        type="submit"
                        class="btn btn-primary
                            d-inline-flex
                            align-items-center
                            gap-1">

                        <i
                            class="ri-filter-3-line"
                            aria-hidden="true">
                        </i>

                        Filter
                    </button>

                    <a
                        href="<?= route_to(
                                    'admin.support.contacts'
                                ) ?>"
                        class="btn btn-light">

                        <i
                            class="ri-refresh-line me-1"
                            aria-hidden="true">
                        </i>

                        Reset
                    </a>
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
                            <th scope="col">
                                Request ID
                            </th>

                            <th scope="col">
                                Member
                            </th>

                            <th scope="col">
                                Member Message
                            </th>

                            <th scope="col">
                                Status
                            </th>

                            <th scope="col">
                                Admin Message
                            </th>

                            <th scope="col">
                                Submitted
                            </th>

                            <th
                                scope="col"
                                class="text-end">

                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($resolvedRequests === []): ?>
                            <tr>
                                <td
                                    colspan="7"
                                    class="text-center
                                        text-muted
                                        py-4">

                                    <i
                                        class="ri-customer-service-2-line
                                            fs-24
                                            d-block
                                            mb-2"
                                        aria-hidden="true">
                                    </i>

                                    No contact requests were found.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach (
                            $resolvedRequests as $request
                        ): ?>
                            <?php
                            $requestId = (int) (
                                $request['id']
                                ?? 0
                            );

                            $status = mb_strtoupper(
                                trim(
                                    (string) (
                                        $request['status']
                                        ?? 'OPEN'
                                    )
                                )
                            );

                            $isOpen = $status === 'OPEN';

                            $submittedDateTime =
                                DateDisplay::formatUtcDateTime(
                                    $request['created_at']
                                        ?? null
                                );

                            $submittedIso =
                                DateDisplay::utcToDisplayIso(
                                    $request['created_at']
                                        ?? null
                                );

                            $resolvedDateTime =
                                DateDisplay::formatUtcDateTime(
                                    $request['reviewed_at']
                                        ?? null,
                                    ''
                                );

                            $resolvedIso =
                                DateDisplay::utcToDisplayIso(
                                    $request['reviewed_at']
                                        ?? null
                                );
                            ?>

                            <tr>
                                <td>
                                    <span
                                        class="badge
                                            bg-primary-subtle
                                            text-primary
                                            p-2">

                                        <?= esc(
                                            $request['request_reference'] ?? '—'
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="fw-semibold">
                                        <?= esc(
                                            $request['member_name']
                                                ?? 'Member'
                                        ) ?>
                                    </span>

                                    <div class="small text-muted">
                                        <?= esc(
                                            $request['profile_ref_number'] ?? '—'
                                        ) ?>
                                    </div>
                                </td>

                                <td class="text-break">
                                    <?= esc(
                                        $request['message']
                                            ?? '—'
                                    ) ?>
                                </td>

                                <td>
                                    <span
                                        class="badge
                                            <?= $isOpen
                                                ? 'bg-warning-subtle text-dark'
                                                : 'bg-success-subtle text-success' ?>
                                            p-2">

                                        <?= $isOpen
                                            ? 'Open'
                                            : 'Resolved' ?>
                                    </span>
                                </td>

                                <td class="text-break">
                                    <?php if ($isOpen): ?>
                                        <span class="text-muted">
                                            Awaiting response
                                        </span>
                                    <?php else: ?>
                                        <div>
                                            <?= esc(
                                                $request['response_note']
                                                    ?? '—'
                                            ) ?>
                                        </div>

                                        <?php if (
                                            !empty($request['reviewer_name'])
                                        ): ?>
                                            <div class="small text-muted mt-1">
                                                By
                                                <?= esc(
                                                    $request['reviewer_name']
                                                ) ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (
                                            $resolvedDateTime !== ''
                                        ): ?>
                                            <div class="small text-muted">
                                                <time
                                                    datetime="<?= esc(
                                                                    $resolvedIso,
                                                                    'attr'
                                                                ) ?>">

                                                    <?= esc($resolvedDateTime) ?>
                                                </time>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>

                                <td class="text-nowrap">
                                    <time
                                        datetime="<?= esc(
                                                        $submittedIso,
                                                        'attr'
                                                    ) ?>">

                                        <?= esc($submittedDateTime) ?>
                                    </time>
                                </td>

                                <td class="text-end">
                                    <?php if ($isOpen): ?>
                                        <button
                                            type="button"
                                            class="btn
                                                btn-sm
                                                btn-soft-primary"
                                            data-support-review-open
                                            data-support-review-type="contact"
                                            data-support-review-id="<?= esc(
                                                                        (string) $requestId,
                                                                        'attr'
                                                                    ) ?>"
                                            data-support-review-label="<?= esc(
                                                                            $request['request_reference'] ?? '',
                                                                            'attr'
                                                                        ) ?>">

                                            <i
                                                class="ri-reply-line me-1"
                                                aria-hidden="true">
                                            </i>

                                            Resolve
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            —
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (
            isset($pager)
            && $pager !== null
        ): ?>
            <div class="card-footer">
                <?= $pager->links(
                    'memberContactRequests',
                    'default_full'
                ) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= view(
    'Admin/Support/_ReviewModal',
    [
        'reviewType' => 'contact',
        'validationErrors' =>
        $validationErrors ?? [],
        'reviewRecord' =>
        $reviewRecord ?? [],
    ]
) ?>

<?php $this->endSection(); ?>