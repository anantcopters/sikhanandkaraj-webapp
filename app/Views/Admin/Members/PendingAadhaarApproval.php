<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * @var list<array<string, mixed>> $members
 * @var \CodeIgniter\Pager\Pager  $pager
 * @var string                    $search
 * @var array<string, string>|null $formAlert
 */

$resolvedMembers = isset($members) && is_array($members) ? $members : [];
$resolvedSearch = trim((string) ($search ?? ''));

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>

<div class="container-fluid">
    <div class="page-title-box d-sm-flex align-items-sm-center justify-content-between gap-3">
        <div>
            <h1 class="fs-18 fw-semibold mb-1">Pending Aadhaar Authentication</h1>
            <p class="text-muted mb-0">Review Aadhaar documents submitted by members.</p>
        </div>
    </div>

    <?= view('Components/Alerts/FormAlert', ['alert' => $formAlert ?? null]) ?>

    <div class="card border border-danger border-opacity-25">
        <div class="card-header">
            <form method="get" action="<?= route_to('admin.members.aadhaar-approvals') ?>">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-6 col-xl-4">
                        <label for="aadhaarMemberSearch" class="form-label">Search members</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ri-search-line" aria-hidden="true"></i></span>
                            <input
                                type="search"
                                id="aadhaarMemberSearch"
                                name="search"
                                class="form-control"
                                value="<?= esc($resolvedSearch, 'attr') ?>"
                                maxlength="100"
                                placeholder="Name or member ID">
                        </div>
                    </div>
                    <div class="col-6 col-md-auto">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                    <div class="col-6 col-md-auto">
                        <a href="<?= route_to('admin.members.aadhaar-approvals') ?>" class="btn btn-soft-secondary w-100">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="bg-info-subtle">
                        <tr>
                            <th>Member ID</th>
                            <th>Member</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Location</th>
                            <th>Uploaded</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resolvedMembers === []): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="ri-checkbox-circle-line text-success fs-24" aria-hidden="true"></i>
                                    <h2 class="fs-16 mt-2 mb-1">No Aadhaar submissions pending</h2>
                                    <p class="text-muted mb-0">The review queue is currently empty.</p>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($resolvedMembers as $member): ?>
                            <?php
                            $reference = trim((string) ($member['profile_ref_number'] ?? ''));
                            $gender = strtoupper(trim((string) ($member['gender'] ?? '')));
                            ?>
                            <tr>
                                <td class="fw-semibold"><?= esc($reference) ?></td>
                                <td><?= esc((string) ($member['full_name'] ?? 'Member')) ?></td>
                                <td><?= is_numeric($member['age'] ?? null) ? esc((string) $member['age']) : '—' ?></td>
                                <td><?= $gender === 'M' ? 'Male' : ($gender === 'F' ? 'Female' : '—') ?></td>
                                <td><?= esc(trim((string) ($member['location'] ?? '')) ?: '—') ?></td>
                                <td><?= esc(DateDisplay::formatUtcDateTime($member['uploaded_at'] ?? null)) ?></td>
                                <td><span class="badge bg-warning-subtle text-warning">Under Review</span></td>
                                <td class="text-end">
                                    <a
                                        href="<?= route_to('admin.members.aadhaar-approvals.review', $reference) ?>"
                                        class="btn btn-sm btn-soft-primary"
                                        title="Review Aadhaar"
                                        aria-label="Review Aadhaar for <?= esc($reference, 'attr') ?>">
                                        <i class="ri-eye-line" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($resolvedMembers !== []): ?>
            <div class="card-footer">
                <?php $pager->only(['search']); ?>
                <?= $pager->links('pendingAadhaarMembers', 'default_full') ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
