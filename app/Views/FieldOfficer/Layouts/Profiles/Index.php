<?php

declare(strict_types=1);

$profiles =
    is_array(
        $profiles
            ?? null
    )
    ? $profiles
    : [];

$selectedStatus =
    in_array(
        $selectedStatus
            ?? '',
        [
            'ALL',
            'DRAFT',
            'APPROVED',
        ],
        true
    )
    ? $selectedStatus
    : 'ALL';

$searchTerm = trim(
    (string) (
        $searchTerm
        ?? ''
    )
);

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
                        class="text-muted mb-0">

                        Profiles associated with your
                        Field Officer ID.
                    </p>

                </div>
            </div>
        </div>
    </div>

    <div
        class="card
        border
        border-danger
        border-opacity-25">

        <div class="card-header">

            <form
                method="get"
                action="<?= route_to(
                            'field-officer.profiles.index'
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
                                class="ri-search-line">
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

                <div class="col-12 col-md-auto">

                    <button
                        type="submit"
                        class="btn btn-primary">

                        <i
                            class="ri-search-line me-1">
                        </i>

                        Search
                    </button>

                    <?php if (
                        $searchTerm !== ''
                        || $selectedStatus !== 'ALL'
                    ): ?>

                        <a
                            href="<?= route_to(
                                        'field-officer.profiles.index'
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

                            <th>
                                Reference
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

                            <th
                                class="text-end">

                                Action
                            </th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if ($profiles === []): ?>

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
                                    mb-2">
                                    </i>

                                    No profiles were found.
                                </td>
                            </tr>

                        <?php endif; ?>

                        <?php foreach (
                            $profiles
                            as $profile
                        ): ?>

                            <?php
                            $sourceType = strtoupper(
                                trim(
                                    (string) (
                                        $profile['source_type'] ?? ''
                                    )
                                )
                            );

                            $sourceId = (int) (
                                $profile['source_id'] ?? 0
                            );

                            $memberId = (int) (
                                $profile['member_user_id'] ?? 0
                            );

                            $status = strtoupper(
                                trim(
                                    (string) (
                                        $profile['display_status'] ?? ''
                                    )
                                )
                            );

                            $viewUrl =
                                $sourceType
                                === 'PRELAUNCH'
                                ? route_to(
                                    'field-officer.profiles.prelaunch.view',
                                    $sourceId
                                )
                                : route_to(
                                    'field-officer.profiles.member.view',
                                    $memberId
                                );

                            $location = implode(
                                ', ',
                                array_filter([
                                    trim(
                                        (string) (
                                            $profile['city_name'] ?? ''
                                        )
                                    ),

                                    trim(
                                        (string) (
                                            $profile['state_name'] ?? ''
                                        )
                                    ),
                                ])
                            );
                            ?>

                            <tr>

                                <td>
                                    <span
                                        class="fw-semibold">

                                        <?= esc(
                                            (string) (
                                                $profile['profile_reference'] ?? ''
                                            )
                                        ) ?>
                                    </span>
                                </td>

                                <td>

                                    <?= esc(
                                        (string) (
                                            $profile['full_name'] ?? ''
                                        )
                                    ) ?>

                                </td>

                                <td>

                                    <?= esc(
                                        (string) (
                                            $profile['mobile_number'] ?? ''
                                        )
                                    ) ?>

                                </td>

                                <td>

                                    <?= esc(
                                        $location !== ''
                                            ? $location
                                            : '—'
                                    ) ?>

                                </td>

                                <td>

                                    <?php if (
                                        $status
                                        === 'APPROVED'
                                    ): ?>

                                        <span
                                            class="badge
                                        bg-success-subtle
                                        text-success">

                                            Approved
                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="badge
                                        bg-warning-subtle
                                        text-dark">

                                            Draft
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td class="text-end">

                                    <a
                                        href="<?= esc(
                                                    $viewUrl,
                                                    'attr'
                                                ) ?>"
                                        class="btn
                                    btn-soft-primary
                                    btn-sm"
                                        title="View profile"
                                        aria-label="View profile">

                                        <i
                                            class="ri-eye-line">
                                        </i>
                                    </a>

                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>

            </div>
        </div>

        <?php if (
            isset($pager)
        ): ?>

            <div class="card-footer">

                <?= $pager->links(
                    'fieldOfficerProfiles'
                ) ?>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php $this->endSection(); ?>