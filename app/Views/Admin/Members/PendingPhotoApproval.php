<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $members
 * @var \CodeIgniter\Pager\Pager $pager
 * @var string $search
 * @var array<string, string>|null $formAlert
 */

$members = isset($members) && is_array($members)
    ? $members
    : [];

$search = trim(
    (string) ($search ?? '')
);

$this->extend('Admin/Layouts/Main');

$this->section('content');
?>

<div class="container-fluid">

    <div class="row">
        <div class="col-12">

            <div
                class="page-title-box
                    d-sm-flex align-items-sm-center
                    justify-content-between gap-3">

                <div>
                    <h4 class="mb-1">
                        Pending Member Photo Approval
                    </h4>

                    <p class="text-muted mb-0">
                        Review and moderate member profile photos.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' => $formAlert ?? null,
        ]
    ) ?>

    <div class="card border border-danger border-opacity-25">

        <div class="card-header">

            <form
                method="get"
                action="<?= route_to(
                            'admin.members.photo-approvals'
                        ) ?>">

                <div class="row g-2 align-items-end">

                    <div class="col-12 col-md-6 col-xl-4">

                        <label
                            for="memberSearch"
                            class="form-label fw-medium">

                            Search members
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
                                id="memberSearch"
                                name="search"
                                class="form-control"
                                value="<?= esc(
                                            $search,
                                            'attr'
                                        ) ?>"
                                maxlength="100"
                                placeholder="Name or reference number">

                        </div>
                    </div>

                    <div class="col-6 col-md-auto">

                        <button
                            type="submit"
                            class="btn btn-primary w-100">

                            <i
                                class="ri-search-line me-1"
                                aria-hidden="true">
                            </i>

                            Search
                        </button>
                    </div>

                    <div class="col-6 col-md-auto">

                        <a
                            href="<?= route_to(
                                        'admin.members'
                                            . '.photo-approvals'
                                    ) ?>"
                            class="btn
                                btn-soft-secondary w-100">

                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table
                    class="table table-hover
                        table-nowrap align-middle mb-0">

                    <thead class="bg-info-subtle">
                        <tr>
                            <th>Reference</th>
                            <th>Member</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Location</th>
                            <th>Profile Created</th>
                            <th>Pending</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if ($members === []): ?>
                            <tr>
                                <td colspan="8">

                                    <div class="text-center py-5">

                                        <div
                                            class="avatar-md
                                                mx-auto mb-3">

                                            <span
                                                class="avatar-title
                                                    rounded-circle
                                                    bg-success-subtle
                                                    text-success fs-24">

                                                <i
                                                    class="ri-checkbox-circle-line"
                                                    aria-hidden="true">
                                                </i>
                                            </span>
                                        </div>

                                        <h5 class="mb-1">
                                            No photos pending approval
                                        </h5>

                                        <p class="text-muted mb-0">
                                            There are currently no member
                                            photos waiting for review.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($members as $member): ?>
                            <?php
                            $memberId = (int) (
                                $member['member_id'] ?? 0
                            );

                            $age = is_numeric(
                                $member['age'] ?? null
                            )
                                ? (int) $member['age']
                                : null;

                            $createdAt = trim(
                                (string) (
                                    $member['profile_created_at'] ?? ''
                                )
                            );

                            $pendingCount = (int) (
                                $member['pending_photo_count'] ?? 0
                            );
                            ?>

                            <tr
                                data-member-row="<?= esc(
                                                        (string) $memberId,
                                                        'attr'
                                                    ) ?>">

                                <td>
                                    <span class="fw-semibold">
                                        <?= esc(
                                            (string) (
                                                $member['profile_ref_number'] ?? ''
                                            )
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="fw-medium">
                                        <?= esc(
                                            (string) (
                                                $member['full_name']
                                                ?? ''
                                            )
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= $age !== null
                                        ? esc((string) $age)
                                        : '—' ?>
                                </td>

                                <td>
                                    <?php
                                    $gender = trim(
                                        (string) (
                                            $member['gender']
                                            ?? ''
                                        )
                                    );
                                    ?>

                                    <?= $gender !== ''
                                        ? esc(
                                            ucwords(
                                                strtolower($gender)
                                            )
                                        )
                                        : '—' ?>
                                </td>

                                <td>
                                    <?php
                                    $location = trim(
                                        (string) (
                                            $member['location']
                                            ?? ''
                                        )
                                    );
                                    ?>

                                    <?= $location !== ''
                                        ? esc($location)
                                        : '—' ?>
                                </td>

                                <td>
                                    <?= $createdAt !== ''
                                        ? esc(
                                            date(
                                                'd M Y',
                                                strtotime($createdAt)
                                            )
                                        )
                                        : '—' ?>
                                </td>

                                <td>
                                    <span
                                        class="badge
                                            bg-warning-subtle
                                            text-warning p-2"
                                        data-pending-count>

                                        Pending
                                        (<?= esc(
                                                (string) $pendingCount
                                            ) ?>)
                                    </span>
                                </td>

                                <td class="text-end">

                                    <div
                                        class="d-inline-flex
                                            align-items-center gap-2">

                                        <button
                                            type="button"
                                            class="btn
                                                btn-soft-secondary
                                                btn-sm"
                                            data-photo-review
                                            data-member-id="<?= esc(
                                                                (string) $memberId,
                                                                'attr'
                                                            ) ?>"
                                            data-member-name="<?= esc(
                                                                    (string) (
                                                                        $member['full_name'] ?? ''
                                                                    ),
                                                                    'attr'
                                                                ) ?>"
                                            data-photo-url="<?= esc(
                                                                route_to(
                                                                    'admin.members'
                                                                        . '.photo-approvals'
                                                                        . '.photos',
                                                                    $memberId
                                                                ),
                                                                'attr'
                                                            ) ?>"
                                            title="View photos"
                                            aria-label="View member photos">

                                            <i
                                                class="ri-eye-line"
                                                aria-hidden="true">
                                            </i>
                                        </button>

                                        <form
                                            method="post"
                                            action="<?= route_to(
                                                        'admin.members.photos.approve-all',
                                                        $memberId
                                                    ) ?>"
                                            class="mb-0"
                                            data-confirm-form
                                            data-moderation-form
                                            data-action-type="approve-all"
                                            data-member-id="<?= esc(
                                                                (string) $memberId,
                                                                'attr'
                                                            ) ?>"
                                            data-confirm-title="Approve all photos?"
                                            data-confirm-message="Approve every pending photo for this member?"
                                            data-confirm-button-text="Approve All"
                                            data-confirm-button-class="btn-success"
                                            data-confirm-icon="ri-checkbox-circle-line"
                                            data-confirm-loading-text="Approving...">

                                            <?= csrf_field() ?>

                                            <button
                                                type="submit"
                                                class="btn
        btn-soft-success
        btn-sm
        d-inline-flex
        align-items-center
        justify-content-center"
                                                title="Approve all pending photos"
                                                aria-label="Approve all pending photos">

                                                <i
                                                    class="ri-checkbox-circle-line"
                                                    aria-hidden="true">
                                                </i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($members !== []): ?>
            <div
                class="card-footer
                    d-flex flex-column
                    flex-sm-row align-items-sm-center
                    justify-content-between gap-3">

                <span class="text-muted fs-13">
                    20 members per page
                </span>

                <div>
                    <?php $pager->only(['search']); ?>

                    <?= $pager->links(
                        'pendingPhotoMembers',
                        'default_full'
                    ) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= view(
    'Admin/Members/_PhotoReviewModal'
) ?>
<script>
    window.csrfTokenName =
        <?= json_encode(
            csrf_token(),
            JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
        ) ?>;

    window.csrfTokenHash =
        <?= json_encode(
            csrf_hash(),
            JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
        ) ?>;
</script>
<?php $this->endSection(); ?>