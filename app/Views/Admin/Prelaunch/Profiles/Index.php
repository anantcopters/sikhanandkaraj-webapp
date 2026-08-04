<?php

declare(strict_types=1);

use CodeIgniter\Pager\Pager;

/**
 * @var list<array<string, mixed>> $profiles
 * @var Pager                      $pager
 * @var string                     $selectedStatus
 * @var string                     $searchTerm
 * @var int                        $perPage
 * @var array<string, string>|null $formAlert
 */

$resolvedProfiles = is_array(
    $profiles ?? null
)
    ? $profiles
    : [];

$resolvedFormAlert = is_array(
    $formAlert ?? null
)
    ? $formAlert
    : null;

$resolvedStatus = in_array(
    $selectedStatus ?? '',
    [
        'DRAFT',
        'APPROVED',
        'REJECTED',
    ],
    true
)
    ? $selectedStatus
    : 'DRAFT';

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

$this->extend(
    'Admin/Layouts/Main'
);

$this->section(
    'content'
);
?>

<div class="container-fluid">
    <!-- Page heading and Choices.js status filter. -->
    <div class="row">
        <div class="col-12">
            <div
                class="page-title-box
                    d-sm-flex
                    align-items-sm-center
                    justify-content-between
                    gap-3">

                <div>
                    <h1 class="mb-1 fs-18">
                        Pre-launch Profiles
                    </h1>

                    <p class="text-muted mb-0">
                        Review submitted profile details
                        and photographs.
                    </p>
                </div>

                <div
                    class="page-title-right
                        mt-3 mt-sm-0">

                    <form
                        id="prelaunch-status-form"
                        method="get"
                        action="<?= route_to(
                                    'admin.prelaunch.profiles.index'
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
                        <?php endif ?>

                        <label
                            for="prelaunch-status-filter"
                            class="visually-hidden">
                            Filter profiles by status
                        </label>

                        <select
                            id="prelaunch-status-filter"
                            name="status"
                            class="form-select"
                            data-choice
                            data-choice-search="false"
                            data-choice-position="bottom"
                            data-choice-placeholder="Select status">

                            <?php foreach (
                                [
                                    'DRAFT' =>
                                    'Draft',

                                    'APPROVED' =>
                                    'Approved',

                                    'REJECTED' =>
                                    'Rejected',
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
                            <?php endforeach ?>
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
            $resolvedFormAlert,
        ]
    ) ?>

    <div
        class="card
            border
            border-danger
            border-opacity-25">

        <!--
            Match the working Pending Approval search UI:
            left-aligned label, input group, Search and Reset buttons.
        -->
        <div class="card-header">
            <form
                method="get"
                action="<?= route_to(
                            'admin.prelaunch.profiles.index'
                        ) ?>">

                <input
                    type="hidden"
                    name="status"
                    value="<?= esc(
                                $resolvedStatus,
                                'attr'
                            ) ?>">

                <div class="row g-2 align-items-end">
                    <div
                        class="col-12
                            col-md-6
                            col-xl-4">

                        <label
                            for="prelaunchProfileSearch"
                            class="form-label">
                            Search profiles
                        </label>

                        <div class="input-group">
                            <span
                                class="input-group-text"
                                aria-hidden="true">

                                <i class="ri-search-line"></i>
                            </span>

                            <input
                                type="search"
                                id="prelaunchProfileSearch"
                                name="search"
                                class="form-control"
                                value="<?= esc(
                                            $resolvedSearch,
                                            'attr'
                                        ) ?>"
                                maxlength="100"
                                placeholder="Name, reference, contact or location">
                        </div>
                    </div>

                    <div class="col-6 col-md-auto">
                        <button
                            type="submit"
                            class="btn
                                btn-primary
                                w-100">

                            <i
                                class="ri-search-line me-1"
                                aria-hidden="true"></i>

                            Search
                        </button>
                    </div>

                    <div class="col-6 col-md-auto">
                        <a
                            href="<?= esc(
                                        route_to(
                                            'admin.prelaunch.profiles.index'
                                        )
                                            . '?status='
                                            . rawurlencode(
                                                $resolvedStatus
                                            ),
                                        'attr'
                                    ) ?>"
                            class="btn
                                btn-soft-secondary
                                w-100">

                            Reset
                        </a>
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
                                Field Officer
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
                            $resolvedProfiles === []
                        ): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="text-center py-5">
                                        <div
                                            class="avatar-md
                                                mx-auto mb-3">

                                            <span
                                                class="avatar-title
                                                    rounded-circle
                                                    bg-primary-subtle
                                                    text-primary
                                                    fs-24">

                                                <i
                                                    class="ri-user-search-line"
                                                    aria-hidden="true"></i>
                                            </span>
                                        </div>

                                        <h2 class="h5 mb-1">
                                            No profiles found
                                        </h2>

                                        <p class="text-muted mb-0">
                                            Change the search term or
                                            selected status.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif ?>

                        <?php foreach (
                            $resolvedProfiles
                            as $profile
                        ): ?>
                            <?php
                            $profileId = (int) (
                                $profile['id']
                                ?? 0
                            );

                            $reference = trim(
                                (string) (
                                    $profile['profile_reference']
                                    ?? ''
                                )
                            );

                            $fullName = trim(
                                (string) (
                                    $profile['full_name']
                                    ?? ''
                                )
                            );

                            $gender = trim(
                                (string) (
                                    $profile['gender']
                                    ?? ''
                                )
                            );

                            $email = trim(
                                (string) (
                                    $profile['email']
                                    ?? ''
                                )
                            );

                            $countryCode = trim(
                                (string) (
                                    $profile['country_code']
                                    ?? ''
                                )
                            );

                            $mobileNumber = trim(
                                (string) (
                                    $profile['mobile_number']
                                    ?? ''
                                )
                            );

                            $location = implode(
                                ', ',
                                array_filter(
                                    [
                                        trim(
                                            (string) (
                                                $profile['city_name']
                                                ?? ''
                                            )
                                        ),

                                        trim(
                                            (string) (
                                                $profile['state_name']
                                                ?? ''
                                            )
                                        ),

                                        trim(
                                            (string) (
                                                $profile['country_name']
                                                ?? ''
                                            )
                                        ),
                                    ],
                                    static fn(
                                        string $value
                                    ): bool =>
                                    $value !== ''
                                )
                            );

                            $fieldOfficerName = trim(
                                (string) (
                                    $profile['field_officer_name']
                                    ?? ''
                                )
                            );

                            $officerCode = trim(
                                (string) (
                                    $profile['officer_code']
                                    ?? ''
                                )
                            );

                            $status = mb_strtoupper(
                                trim(
                                    (string) (
                                        $profile['status']
                                        ?? 'DRAFT'
                                    )
                                )
                            );

                            $statusClass = match ($status) {
                                'APPROVED' =>
                                'bg-success-subtle text-black',

                                'REJECTED' =>
                                'bg-danger-subtle text-danger',

                                default =>
                                'bg-warning-subtle text-black',
                            };
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

                                    <?php if (
                                        $gender !== ''
                                    ): ?>
                                        <div class="small text-muted">
                                            <?= esc(
                                                ucfirst(
                                                    mb_strtolower(
                                                        $gender
                                                    )
                                                )
                                            ) ?>
                                        </div>
                                    <?php endif ?>
                                </td>

                                <td>
                                    <?php if (
                                        $email !== ''
                                    ): ?>
                                        <div>
                                            <?= esc($email) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted">
                                            Not provided
                                        </div>
                                    <?php endif ?>

                                    <?php if (
                                        $mobileNumber !== ''
                                    ): ?>
                                        <div class="small text-muted">
                                            <?= esc(
                                                trim(
                                                    $countryCode
                                                        . ' '
                                                        . $mobileNumber
                                                )
                                            ) ?>
                                        </div>
                                    <?php endif ?>
                                </td>

                                <td>
                                    <?= esc(
                                        $location !== ''
                                            ? $location
                                            : '—'
                                    ) ?>
                                </td>

                                <td>
                                    <?= esc(
                                        $fieldOfficerName !== ''
                                            ? $fieldOfficerName
                                            : '—'
                                    ) ?>

                                    <?php if (
                                        $officerCode !== ''
                                    ): ?>
                                        <div class="small text-muted">
                                            <?= esc(
                                                $officerCode
                                            ) ?>
                                        </div>
                                    <?php endif ?>
                                </td>

                                <td>
                                    <span
                                        class="badge
                                            <?= esc(
                                                $statusClass,
                                                'attr'
                                            ) ?>
                                            p-2">

                                        <?= esc(
                                            ucfirst(
                                                mb_strtolower(
                                                    $status
                                                )
                                            )
                                        ) ?>
                                    </span>
                                </td>

                                <td class="text-end">
                                    <a
                                        href="<?= route_to(
                                                    'admin.prelaunch.profiles.review',
                                                    $profileId
                                                ) ?>"
                                        class="btn
                                            btn-soft-primary
                                            btn-sm"
                                        title="Review profile"
                                        aria-label="<?= esc(
                                                        'Review profile '
                                                            . $fullName,
                                                        'attr'
                                                    ) ?>">

                                        <i
                                            class="ri-eye-line"
                                            aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (
            $resolvedProfiles !== []
        ): ?>
            <div
                class="card-footer
                    d-flex
                    flex-column
                    flex-sm-row
                    align-items-sm-center
                    justify-content-between
                    gap-3">

                <span class="text-muted fs-13">
                    <?= esc(
                        (string) $resolvedPerPage
                    ) ?>
                    profiles per page
                </span>

                <div>
                    <?php
                    /*
                     * Preserve filters while paging. The active page value is
                     * managed separately by CI4 as page_prelaunchProfiles.
                     */
                    $pager->only([
                        'status',
                        'search',
                    ]);
                    ?>

                    <?= $pager->links(
                        'prelaunchProfiles',
                        'default_full'
                    ) ?>
                </div>
            </div>
        <?php endif ?>
    </div>
</div>

<?php $this->endSection(); ?>