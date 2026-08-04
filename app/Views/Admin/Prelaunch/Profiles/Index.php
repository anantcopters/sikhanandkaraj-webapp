<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $profiles
 * @var string                     $selectedStatus
 * @var string                     $searchTerm
 * @var int                        $currentPage
 * @var int                        $perPage
 * @var int                        $totalProfiles
 * @var int                        $firstResultNumber
 * @var int                        $lastResultNumber
 * @var string                     $pagerLinks
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

$resolvedTotal = max(
    0,
    (int) (
        $totalProfiles
        ?? 0
    )
);

$resolvedFirst = max(
    0,
    (int) (
        $firstResultNumber
        ?? 0
    )
);

$resolvedLast = max(
    0,
    (int) (
        $lastResultNumber
        ?? 0
    )
);

$resolvedPagerLinks = is_string(
    $pagerLinks ?? null
)
    ? $pagerLinks
    : '';

$this->extend(
    'Admin/Layouts/Main'
);

$this->section(
    'content'
);
?>

<div class="container-fluid">
    <!-- Page heading and status filter. -->
    <div class="row">
        <div class="col-12">
            <div
                class="page-title-box
                    d-sm-flex
                    align-items-center
                    justify-content-between
                    gap-3">

                <div>
                    <h1 class="mb-sm-0 fs-18">
                        Pre-launch Profiles
                    </h1>

                    <p class="text-muted mb-0 mt-1">
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
                            class="form-select"
                            name="status"
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

        <div class="card-body p-3 p-lg-4">
            <!-- Search and result summary. -->
            <div
                class="d-flex
                    flex-column
                    flex-lg-row
                    align-items-lg-center
                    justify-content-between
                    gap-3">

                <form
                    method="get"
                    action="<?= route_to(
                                'admin.prelaunch.profiles.index'
                            ) ?>"
                    class="flex-grow-1"
                    role="search">

                    <input
                        type="hidden"
                        name="status"
                        value="<?= esc(
                                    $resolvedStatus,
                                    'attr'
                                ) ?>">

                    <div
                        class="input-group
                            admin-list-search">

                        <span
                            class="input-group-text
                                bg-white"
                            aria-hidden="true">

                            <i class="ri-search-line"></i>
                        </span>

                        <input
                            type="search"
                            id="prelaunch-profile-search"
                            name="search"
                            class="form-control"
                            value="<?= esc(
                                        $resolvedSearch,
                                        'attr'
                                    ) ?>"
                            maxlength="100"
                            placeholder="Search reference, member, contact, location or Field Officer"
                            aria-label="Search prelaunch profiles">

                        <button
                            type="submit"
                            class="btn btn-primary">

                            Search
                        </button>

                        <?php if (
                            $resolvedSearch !== ''
                        ): ?>
                            <a
                                href="<?= esc(
                                            site_url(
                                                'admin/prelaunch/profiles'
                                            )
                                                . '?status='
                                                . rawurlencode(
                                                    $resolvedStatus
                                                ),
                                            'attr'
                                        ) ?>"
                                class="btn
                                    btn-soft-secondary">

                                <i
                                    class="ri-close-line"
                                    aria-hidden="true"></i>

                                Reset
                            </a>
                        <?php endif ?>
                    </div>
                </form>

                <div
                    class="text-muted
                        fs-13 flex-shrink-0">

                    <?php if (
                        $resolvedTotal > 0
                    ): ?>
                        Showing

                        <strong>
                            <?= esc(
                                (string) $resolvedFirst
                            ) ?>
                        </strong>

                        –

                        <strong>
                            <?= esc(
                                (string) $resolvedLast
                            ) ?>
                        </strong>

                        of

                        <strong>
                            <?= esc(
                                (string) $resolvedTotal
                            ) ?>
                        </strong>
                    <?php else: ?>
                        No profiles found
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="card-body p-0 border-top">
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
                                <td
                                    colspan="7"
                                    class="text-center
                                        text-muted
                                        py-5">

                                    <div
                                        class="avatar-lg
                                            rounded-circle
                                            bg-light
                                            d-inline-flex
                                            align-items-center
                                            justify-content-center
                                            mb-3">

                                        <i
                                            class="ri-user-search-line
                                                fs-24"
                                            aria-hidden="true"></i>
                                    </div>

                                    <div class="fw-semibold mb-1">
                                        No prelaunch profiles found
                                    </div>

                                    <div class="fs-13">
                                        Change the search term or
                                        selected status.
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
                                        <div
                                            class="small
                                                text-muted">

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
                                        <p class="mb-0">
                                            <?= esc($email) ?>
                                        </p>
                                    <?php else: ?>
                                        <p
                                            class="text-muted
                                                mb-0">
                                            Not provided
                                        </p>
                                    <?php endif ?>

                                    <?php if (
                                        $mobileNumber !== ''
                                    ): ?>
                                        <div
                                            class="small
                                                text-muted">

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
                                        <div
                                            class="small
                                                text-muted">

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
            $resolvedTotal
            > (int) ($perPage ?? 20)
            && $resolvedPagerLinks !== ''
        ): ?>
            <div
                class="card-body
                    border-top
                    d-flex
                    justify-content-end
                    py-3">

                <nav
                    aria-label="Prelaunch profile pages"
                    class="admin-list-pagination">

                    <?= $resolvedPagerLinks ?>
                </nav>
            </div>
        <?php endif ?>
    </div>
</div>

<?php $this->endSection(); ?>