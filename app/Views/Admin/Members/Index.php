<?php

declare(strict_types=1);

use App\Models\UserModel;
use CodeIgniter\Pager\Pager;

/**
 * @var list<array<string, mixed>> $members
 * @var Pager                      $pager
 * @var string                     $selectedStatus
 * @var string                     $searchTerm
 * @var int                        $perPage
 * @var array<string, string>|null $formAlert
 * @var array<string, string>      $validationErrors
 * @var array<string, mixed>|null  $statusModal
 */

$resolvedStatus = in_array(
    $selectedStatus ?? '',
    [
        'ALL',
        UserModel::STATUS_PENDING,
        UserModel::STATUS_ACTIVE,
        UserModel::STATUS_SUSPENDED,
        UserModel::STATUS_DELETED,
    ],
    true
)
    ? (string) $selectedStatus
    : 'ALL';

$resolvedMembers = is_array(
    $members ?? null
)
    ? $members
    : [];

$resolvedSearch = trim(
    (string) (
        $searchTerm
        ?? ''
    )
);

$resolvedPerPage = max(
    1,
    (int) (
        $perPage
        ?? 10
    )
);

$resolvedAlert = is_array(
    $formAlert ?? null
)
    ? $formAlert
    : null;

$statusModal = session(
    'statusModal'
);

$resolvedStatusModal = is_array(
    $statusModal
)
    ? $statusModal
    : null;

$validationErrors = session(
    'validationErrors'
);

$resolvedValidationErrors = is_array(
    $validationErrors
)
    ? $validationErrors
    : [];

$this->extend(
    'Admin/Layouts/Main'
);

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

                        Members
                    </h4>

                    <p class="text-muted mb-0">
                        Search, review and manage registered members.
                    </p>
                </div>

                <div class="page-title-right mt-3 mt-sm-0">
                    <form
                        id="member-status-filter-form"
                        method="get"
                        action="<?= route_to(
                                    'admin.members.index'
                                ) ?>">

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
                            for="member-status-filter"
                            class="visually-hidden">
                            Filter members by account status
                        </label>

                        <select
                            id="member-status-filter"
                            name="status"
                            class="form-select"
                            data-choice
                            data-choice-search="false"
                            data-choice-position="bottom"
                            data-choice-placeholder="Select status">

                            <?php foreach (
                                [
                                    'ALL' =>
                                    'All Members',

                                    'ACTIVE' =>
                                    'Active',

                                    'PENDING' =>
                                    'Pending',

                                    'SUSPENDED' =>
                                    'Blocked',

                                    'DELETED' =>
                                    'Deleted',
                                ]
                                as $value => $label
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
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $resolvedAlert,
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
                            'admin.members.index'
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
                        for="member-search"
                        class="form-label">
                        Search members
                    </label>

                    <div class="input-group">
                        <span class="input-group-text">
                            <i
                                class="ri-search-line"
                                aria-hidden="true"></i>
                        </span>

                        <input
                            type="search"
                            id="member-search"
                            name="search"
                            class="form-control"
                            value="<?= esc(
                                        $resolvedSearch,
                                        'attr'
                                    ) ?>"
                            maxlength="100"
                            placeholder="Reference, name, mobile, email or location">
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
                            class="ri-search-line"
                            aria-hidden="true"></i>

                        Search
                    </button>

                    <?php if (
                        $resolvedSearch !== ''
                    ): ?>
                        <a
                            href="<?= route_to(
                                        'admin.members.index'
                                    ) ?>"
                            class="btn btn-light">
                            Reset
                        </a>
                    <?php endif; ?>
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
                                Reference
                            </th>

                            <th scope="col">
                                Member
                            </th>

                            <th scope="col">
                                Contact
                            </th>

                            <th scope="col">
                                Location
                            </th>

                            <th scope="col">
                                Status
                            </th>

                            <th
                                scope="col"
                                class="text-end">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (
                            $resolvedMembers === []
                        ): ?>
                            <tr>
                                <td
                                    colspan="6"
                                    class="text-center
                                        text-muted
                                        py-4">

                                    <i
                                        class="ri-user-search-line
                                            fs-24
                                            d-block
                                            mb-2"
                                        aria-hidden="true"></i>

                                    No members were found.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach (
                            $resolvedMembers
                            as $member
                        ): ?>
                            <?php
                            $userId = (int) (
                                $member['id']
                                ?? 0
                            );

                            $reference = trim(
                                (string) (
                                    $member['profile_ref_number']
                                    ?? ''
                                )
                            );

                            $fullName = trim(
                                (string) (
                                    $member['full_name']
                                    ?? ''
                                )
                            );

                            $mobile = trim(
                                (string) (
                                    $member['mobile_number']
                                    ?? ''
                                )
                            );

                            $email = trim(
                                (string) (
                                    $member['email_address']
                                    ?? ''
                                )
                            );

                            $location = implode(
                                ', ',
                                array_filter([
                                    trim(
                                        (string) (
                                            $member['city_name']
                                            ?? ''
                                        )
                                    ),
                                    trim(
                                        (string) (
                                            $member['state_name']
                                            ?? ''
                                        )
                                    ),
                                    trim(
                                        (string) (
                                            $member['country_name']
                                            ?? ''
                                        )
                                    ),
                                ])
                            );

                            $status = mb_strtoupper(
                                trim(
                                    (string) (
                                        $member['account_status']
                                        ?? ''
                                    )
                                )
                            );

                            $statusClass = match ($status) {
                                UserModel::STATUS_ACTIVE =>
                                'bg-success-subtle text-success',

                                UserModel::STATUS_SUSPENDED =>
                                'bg-danger-subtle text-danger',

                                UserModel::STATUS_PENDING =>
                                'bg-warning-subtle text-dark',

                                default =>
                                'bg-secondary-subtle text-secondary',
                            };

                            $canBlock =
                                $status
                                === UserModel::STATUS_ACTIVE;

                            $canUnblock =
                                $status
                                === UserModel::STATUS_SUSPENDED;
                            ?>

                            <tr>
                                <td>
                                    <span
                                        class="badge
                                            bg-primary-subtle
                                            text-primary
                                            p-2">
                                        <?= esc(
                                            $reference !== ''
                                                ? $reference
                                                : '—'
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="fw-semibold">
                                        <?= esc(
                                            $fullName !== ''
                                                ? $fullName
                                                : '—'
                                        ) ?>
                                    </span>

                                    <div class="small text-muted">
                                        <?= esc(
                                            ucfirst(
                                                mb_strtolower(
                                                    (string) (
                                                        $member['gender']
                                                        ?? ''
                                                    )
                                                )
                                            )
                                        ) ?>
                                    </div>
                                </td>

                                <td>
                                    <div>
                                        <?= esc(
                                            $mobile !== ''
                                                ? $mobile
                                                : '—'
                                        ) ?>
                                    </div>

                                    <?php if (
                                        $email !== ''
                                    ): ?>
                                        <div class="small text-muted">
                                            <?= esc($email) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= esc(
                                        $location !== ''
                                            ? $location
                                            : '—'
                                    ) ?>
                                </td>

                                <td>
                                    <span
                                        class="badge
                                            <?= esc(
                                                $statusClass,
                                                'attr'
                                            ) ?>">
                                        <?= esc(
                                            $status !== ''
                                                ? $status
                                                : 'UNKNOWN'
                                        ) ?>
                                    </span>
                                </td>

                                <td class="text-end">
                                    <div
                                        class="d-inline-flex
                                            align-items-center
                                            gap-1">

                                        <a
                                            href="<?= route_to(
                                                        'admin.members.view',
                                                        $userId
                                                    ) ?>"
                                            class="btn
                                                btn-sm
                                                btn-soft-primary"
                                            title="View member"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            aria-label="View <?= esc(
                                                                    $fullName,
                                                                    'attr'
                                                                ) ?>">

                                            <i
                                                class="ri-eye-line"
                                                aria-hidden="true"></i>
                                        </a>

                                        <button
                                            type="button"
                                            class="btn
                                                btn-sm
                                                btn-soft-info"
                                            data-member-history
                                            data-history-url="<?= esc(
                                                                    route_to(
                                                                        'admin.members.history',
                                                                        $userId
                                                                    ),
                                                                    'attr'
                                                                ) ?>"

                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            title="View status history"
                                            aria-label="View status history for <?= esc(
                                                                                    $fullName,
                                                                                    'attr'
                                                                                ) ?>">

                                            <i
                                                class="ri-history-line"
                                                aria-hidden="true"></i>
                                        </button>

                                        <?php if (
                                            $canBlock
                                        ): ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-soft-danger"
                                                data-member-status
                                                data-action="BLOCK"
                                                data-member-name="<?= esc(
                                                                        $fullName,
                                                                        'attr'
                                                                    ) ?>"
                                                data-member-code="<?= esc(
                                                                        $reference,
                                                                        'attr'
                                                                    ) ?>"
                                                data-form-action="<?= esc(
                                                                        route_to(
                                                                            'admin.members.block',
                                                                            $userId
                                                                        ),
                                                                        'attr'
                                                                    ) ?>"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                title="Block member"
                                                aria-label="Block member">

                                                <i
                                                    class="ri-forbid-line"
                                                    aria-hidden="true"></i>
                                            </button>
                                        <?php elseif ($canUnblock): ?>
                                            <button
                                                type="button"
                                                class="btn
            btn-sm
            btn-soft-success"
                                                data-member-status
                                                data-action="UNBLOCK"
                                                data-member-name="<?= esc(
                                                                        $fullName,
                                                                        'attr'
                                                                    ) ?>"
                                                data-member-code="<?= esc(
                                                                        $reference,
                                                                        'attr'
                                                                    ) ?>"
                                                data-form-action="<?= esc(
                                                                        route_to(
                                                                            'admin.members.unblock',
                                                                            $userId
                                                                        ),
                                                                        'attr'
                                                                    ) ?>"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                title="Unblock member"
                                                aria-label="Unblock <?= esc(
                                                                        $fullName,
                                                                        'attr'
                                                                    ) ?>">

                                                <i
                                                    class="ri-checkbox-circle-line"
                                                    aria-hidden="true"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (
            $resolvedMembers !== []
        ): ?>
            <?php
            $pager->only([
                'status',
                'search',
            ]);
            ?>

            <div class="card-footer py-3">
                <?= view(
                    'Components/Pagination',
                    [
                        'pager' =>
                        $pager,

                        'group' =>
                        'adminMembers',

                        'perPage' =>
                        $resolvedPerPage,

                        'itemLabel' =>
                        'members',

                        'surroundCount' =>
                        2,
                    ]
                ) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Block/unblock modal -->
<div
    class="modal fade"
    id="member-status-modal"
    tabindex="-1"
    aria-labelledby="member-status-modal-title"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form
                method="post"
                id="member-status-form"
                novalidate>

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="return_url"
                    value="<?= esc(
                                current_url()
                                    . (
                                        $_SERVER['QUERY_STRING'] ?? ''
                                        ? '?'
                                        . $_SERVER['QUERY_STRING']
                                        : ''
                                    ),
                                'attr'
                            ) ?>">

                <div class="modal-header bg-info-subtle py-2">
                    <div>
                        <h5
                            class="modal-title mb-1"
                            id="member-status-modal-title">
                            Change Member Status
                        </h5>

                        <div
                            id="member-status-identity"
                            class="fs-13">
                        </div>
                    </div>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                    </button>
                </div>

                <div class="modal-body">
                    <div
                        class="alert
                            alert-info
                            border-0"
                        role="status">

                        <div class="fw-semibold">
                            Member
                        </div>

                        <div id="member-status-member-name">
                            —
                        </div>

                        <div
                            id="member-status-member-code"
                            class="fs-13">
                            Member Code: —
                        </div>
                    </div>

                    <p
                        id="member-status-message"
                        class="text-muted">
                    </p>

                    <label
                        for="member-status-reason"
                        class="form-label">
                        Reason
                    </label>

                    <input
                        type="text"
                        id="member-status-reason"
                        name="reason"
                        class="form-control"
                        maxlength="64"
                        required
                        aria-describedby="
                            member-status-reason-help
                            member-status-reason-error">

                    <div
                        id="member-status-reason-help"
                        class="form-text color-pink">
                        Maximum 64 characters.
                    </div>

                    <div
                        id="member-status-reason-error"
                        class="invalid-feedback">
                        Please enter a reason of no more than
                        64 characters.
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        id="member-status-submit"
                        class="btn btn-primary">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- History modal -->
<div
    class="modal fade"
    id="member-history-modal"
    tabindex="-1"
    aria-labelledby="member-history-modal-title"
    aria-hidden="true">

    <div
        class="modal-dialog
            modal-lg
            modal-dialog-centered
            modal-dialog-scrollable">

        <div class="modal-content">
            <div class="modal-header bg-info-subtle py-2">
                <h5
                    class="modal-title"
                    id="member-history-modal-title">
                    Member Status History
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>
            </div>

            <div
                class="modal-body"
                id="member-history-content">

                <div class="text-center text-muted py-4">
                    No history loaded.
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->endSection();
