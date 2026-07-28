<?php

declare(strict_types=1);

/**
 * @var array<string, string>|null $formAlert
 */

$alert = is_array($formAlert ?? null)
    ? $formAlert
    : null;

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>

<div class="container-fluid py-3">
    <div
        class="d-flex flex-column flex-md-row
        justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">
                Pre-launch Profiles
            </h1>

            <p class="text-muted mb-0">
                Review submitted profile details and photographs.
            </p>
        </div>

        <form
            method="get"
            action="<?= route_to(
                        'admin.prelaunch.profiles.index'
                    ) ?>">
            <select
                class="form-select"
                name="status"
                onchange="this.form.submit()">
                <?php foreach (
                    [
                        'DRAFT' => 'Draft',
                        'APPROVED' => 'Approved',
                        'REJECTED' => 'Rejected',
                    ] as $value => $label
                ): ?>
                    <option
                        value="<?= esc($value) ?>"
                        <?= ($selectedStatus ?? 'DRAFT') === $value
                            ? 'selected'
                            : '' ?>>
                        <?= esc($label) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </form>
    </div>

    <?php if ($alert !== null): ?>
        <div
            class="alert alert-<?= esc(
                                    $alert['type'] ?? 'success'
                                ) ?>"
            role="alert">
            <strong>
                <?= esc($alert['title'] ?? '') ?>
            </strong>

            <?= esc($alert['message'] ?? '') ?>
        </div>
    <?php endif ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Member</th>
                            <th>Contact</th>
                            <th>Location</th>
                            <th>Field Officer</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (($profiles ?? []) === []): ?>
                            <tr>
                                <td
                                    colspan="7"
                                    class="text-center text-muted py-4">
                                    No profiles found.
                                </td>
                            </tr>
                        <?php endif ?>

                        <?php foreach (
                            $profiles ?? [] as $profile
                        ): ?>
                            <tr>
                                <td>
                                    <?= esc(
                                        $profile['profile_reference']
                                    ) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= esc(
                                            $profile['full_name']
                                        ) ?>
                                    </strong>

                                    <div class="small text-muted">
                                        <?= esc(
                                            $profile['gender']
                                        ) ?>
                                    </div>
                                </td>

                                <td>
                                    <div>
                                        <?= esc($profile['email']) ?>
                                    </div>

                                    <div class="small text-muted">
                                        <?= esc(
                                            $profile['country_code']
                                                . ' '
                                                . $profile['mobile_number']
                                        ) ?>
                                    </div>
                                </td>

                                <td>
                                    <?= esc(
                                        $profile['city_name']
                                            . ', '
                                            . $profile['state_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= esc(
                                        $profile['field_officer_name']
                                    ) ?>

                                    <div class="small text-muted">
                                        <?= esc(
                                            $profile['officer_code']
                                        ) ?>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge text-bg-secondary">
                                        <?= esc(
                                            $profile['status']
                                        ) ?>
                                    </span>
                                </td>

                                <td class="text-end">
                                    <a
                                        href="<?= route_to(
                                                    'admin.prelaunch.profiles.review',
                                                    $profile['id']
                                                ) ?>"
                                        class="btn btn-sm btn-outline-primary">
                                        Review
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>