<?php

declare(strict_types=1);

$pageTitle = trim(
    (string) (
        $pageTitle
        ?? 'Profiles Submitted'
    )
);

$profiles =
    isset($profiles)
    && is_array($profiles)
    ? $profiles
    : [];

$allowedStatuses = [
    'ALL',
    'DRAFT',
    'APPROVED',
];

$selectedStatus = strtoupper(
    trim(
        (string) (
            $selectedStatus
            ?? 'ALL'
        )
    )
);

if (
    !in_array(
        $selectedStatus,
        $allowedStatuses,
        true
    )
) {
    $selectedStatus =
        'ALL';
}

$searchTerm = trim(
    (string) (
        $searchTerm
        ?? ''
    )
);

$formAlert =
    isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$resolvedPager =
    $pager
    ?? null;

$profilesUrl =
    route_to(
        'field-officer.profiles.index'
    );

$resolvedProfiles = [];

foreach ($profiles as $profile) {
    if (!is_array($profile)) {
        continue;
    }

    $status = strtoupper(
        trim(
            (string) (
                $profile['display_status']
                ?? ''
            )
        )
    );

    $cityName = trim(
        (string) (
            $profile['city_name']
            ?? ''
        )
    );

    $stateName = trim(
        (string) (
            $profile['state_name']
            ?? ''
        )
    );

    $location = implode(
        ', ',
        array_filter(
            [
                $cityName,
                $stateName,
            ],
            static fn(
                string $value
            ): bool => $value !== ''
        )
    );

    if ($location === '') {
        $location = '—';
    }

    $reference = trim(
        (string) (
            $profile['profile_reference']
            ?? ''
        )
    );

    $profileId = trim(
        (string) (
            $profile['profile_id']
            ?? ''
        )
    );

    $resolvedProfiles[] = [
        'reference' =>
        $reference !== ''
            ? $reference
            : '—',

        'profileId' =>
        $profileId !== ''
            ? $profileId
            : '—',

        'fullName' =>
        trim(
            (string) (
                $profile['full_name']
                ?? ''
            )
        ),

        'mobileNumber' =>
        trim(
            (string) (
                $profile['mobile_number']
                ?? ''
            )
        ),

        'location' =>
        $location,

        'status' =>
        $status === 'APPROVED'
            ? 'APPROVED'
            : 'DRAFT',
    ];
}

$this->extend(
    'FieldOfficer/Layouts/Main'
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
                        Profiles Submitted
                    </h4>

                    <p
                        class="text-muted
                        mb-0">

                        Profiles associated with your
                        Field Officer ID.
                    </p>

                </div>

            </div>

        </div>
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $formAlert,
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
                action="<?= esc(
                            $profilesUrl,
                            'attr'
                        ) ?>"
                class="row
                g-2
                align-items-end">

                <div
                    class="col-12
                    col-md-5">

                    <label
                        for="fo-profile-search"
                        class="form-label">

                        Search profiles
                    </label>

                    <div class="input-group">

                        <span
                            class="input-group-text">

                            <i
                                class="ri-search-line"
                                aria-hidden="true">
                            </i>

                        </span>

                        <input
                            type="search"
                            id="fo-profile-search"
                            name="search"
                            class="form-control"
                            value="<?= esc(
                                        $searchTerm,
                                        'attr'
                                    ) ?>"
                            maxlength="100"
                            placeholder="Reference, name, mobile or location">

                    </div>
                </div>

                <div
                    class="col-12
                    col-md-3">

                    <label
                        for="fo-profile-status"
                        class="form-label">

                        Status
                    </label>

                    <select
                        id="fo-profile-status"
                        name="status"
                        class="form-select"
                        data-choice
                        data-choice-search="false">

                        <option
                            value="ALL"
                            <?= $selectedStatus === 'ALL'
                                ? 'selected'
                                : '' ?>>

                            All
                        </option>

                        <option
                            value="DRAFT"
                            <?= $selectedStatus === 'DRAFT'
                                ? 'selected'
                                : '' ?>>

                            Draft
                        </option>

                        <option
                            value="APPROVED"
                            <?= $selectedStatus === 'APPROVED'
                                ? 'selected'
                                : '' ?>>

                            Approved
                        </option>

                    </select>

                </div>

                <div
                    class="col-12
    col-md-auto">

                    <div
                        class="d-flex
        align-items-center
        gap-2">

                        <button
                            type="submit"
                            class="btn
            btn-primary">

                            <i
                                class="ri-search-line
                me-1"
                                aria-hidden="true">
                            </i>

                            Search

                        </button>

                        <a
                            href="<?= esc(
                                        $profilesUrl,
                                        'attr'
                                    ) ?>"
                            class="btn
            btn-light">

                            <i
                                class="ri-refresh-line
                me-1"
                                aria-hidden="true">
                            </i>

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

                            <th>
                                Reference
                            </th>

                            <th>
                                Profile ID
                            </th>

                            <th>
                                Member
                            </th>

                            <th>
                                Contact
                            </th>

                            <th>
                                Location
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (
                            $resolvedProfiles === []
                        ): ?>

                            <tr>

                                <td
                                    colspan="6"
                                    class="text-center
                                    text-muted
                                    py-4">

                                    <i
                                        class="ri-profile-line
                                        fs-24
                                        d-block
                                        mb-2"
                                        aria-hidden="true">
                                    </i>

                                    No profiles were found.

                                </td>

                            </tr>

                        <?php endif; ?>

                        <?php foreach (
                            $resolvedProfiles
                            as $profile
                        ): ?>

                            <tr>

                                <td>

                                    <span class="fw-semibold">

                                        <?= esc(
                                            $profile['reference']
                                        ) ?>

                                    </span>

                                </td>

                                <td>

                                    <?php if (
                                        $profile['profileId']
                                        !== '—'
                                    ): ?>

                                        <span
                                            class="fw-semibold
                text-primary">

                                            <?= esc(
                                                $profile['profileId']
                                            ) ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?= esc(
                                        $profile['fullName']
                                    ) ?>

                                </td>

                                <td>

                                    <?= esc(
                                        $profile['mobileNumber']
                                    ) ?>

                                </td>

                                <td>

                                    <?= esc(
                                        $profile['location']
                                    ) ?>

                                </td>

                                <td>

                                    <?php if (
                                        $profile['status']
                                        === 'APPROVED'
                                    ): ?>

                                        <span
                                            class="badge
                bg-success-subtle
                text-success p-2">

                                            Approved
                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="badge
                bg-warning-subtle
                text-dark p-2">

                                            Draft
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
            $resolvedPager
            instanceof \CodeIgniter\Pager\Pager
        ): ?>

            <div class="card-footer">

                <?= $resolvedPager->links(
                    'fieldOfficerProfiles'
                ) ?>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php $this->endSection(); ?>