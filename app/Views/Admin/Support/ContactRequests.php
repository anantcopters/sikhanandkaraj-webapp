<?php

declare(strict_types=1);

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

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>

<div class="container-fluid px-3 px-lg-4">

    <?= view(
        'Components/Alerts/FormAlert',
        ['alert' => $formAlert ?? null]
    ) ?>

    <div class="mb-4">
        <h1 class="fs-22 fw-semibold mb-1">
            Contact Requests
        </h1>

        <p class="text-muted mb-0">
            Review messages submitted by authenticated members.
        </p>
    </div>

    <div
        class="card border border-danger
            border-opacity-25 shadow-sm">

        <div class="card-body">

            <form
                method="get"
                action="<?= route_to(
                            'admin.support.contacts'
                        ) ?>"
                class="row g-2 mb-4">

                <div class="col-12 col-md-5">
                    <input
                        type="search"
                        name="search"
                        class="form-control"
                        value="<?= esc(
                                    $searchTerm ?? '',
                                    'attr'
                                ) ?>"
                        maxlength="100"
                        placeholder="Search member name or Profile ID">
                </div>

                <div class="col-12 col-md-4">
                    <select
                        name="status"
                        class="form-select">

                        <?php foreach (
                            [
                                'OPEN' => 'Open',
                                'IN_PROGRESS' => 'In Progress',
                                'RESOLVED' => 'Resolved',
                                'CLOSED' => 'Closed',
                                'ALL' => 'All',
                            ] as $value => $label
                        ): ?>
                            <option
                                value="<?= esc(
                                            $value,
                                            'attr'
                                        ) ?>"
                                <?= ($selectedStatus ?? 'OPEN')
                                    === $value
                                    ? 'selected'
                                    : '' ?>>

                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <button
                        type="submit"
                        class="btn btn-primary w-100">

                        Filter
                    </button>
                </div>
            </form>

            <?php if ($requests === []): ?>
                <div class="text-center py-5">
                    <i
                        class="ri-customer-service-2-line
                            fs-36 text-muted">
                    </i>

                    <p class="text-muted mt-2 mb-0">
                        No Contact Us requests found.
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table
                        class="table table-hover
                            align-middle mb-0">

                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach (
                                $requests as $request
                            ): ?>
                                <?php
                                $requestId = (int) (
                                    $request['id']
                                    ?? 0
                                );

                                $status = strtoupper(
                                    trim(
                                        (string) (
                                            $request['status']
                                            ?? 'OPEN'
                                        )
                                    )
                                );

                                $isReviewable = in_array(
                                    $status,
                                    [
                                        'OPEN',
                                        'IN_PROGRESS',
                                    ],
                                    true
                                );
                                ?>

                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= esc(
                                                $request['member_name'] ?? 'Member'
                                            ) ?>
                                        </div>

                                        <div class="text-muted fs-12">
                                            <?= esc(
                                                $request['profile_ref_number'] ?? ''
                                            ) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div
                                            class="text-break">

                                            <?= esc(
                                                $request['message'] ?? ''
                                            ) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <span
                                            class="badge
                                                <?= $isReviewable
                                                    ? 'bg-warning-subtle text-warning'
                                                    : 'bg-success-subtle text-success' ?>
                                                p-2">

                                            <?= esc(
                                                ucwords(
                                                    strtolower(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $status
                                                        )
                                                    )
                                                )
                                            ) ?>
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <?= esc(
                                            (string) (
                                                $request['created_at'] ?? ''
                                            )
                                        ) ?>
                                    </td>

                                    <td class="text-end">
                                        <?php if ($isReviewable): ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm
                                                    btn-outline-primary"
                                                data-support-review-open
                                                data-review-type="contact"
                                                data-review-id="<?= esc(
                                                                    (string) $requestId,
                                                                    'attr'
                                                                ) ?>"
                                                data-review-label="<?= esc(
                                                                        $request['profile_ref_number'] ?? '',
                                                                        'attr'
                                                                    ) ?>">

                                                Review
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted fs-12">
                                                Reviewed
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <?= $pager->links(
                        'memberContactRequests'
                    ) ?>
                </div>
            <?php endif; ?>
        </div>
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