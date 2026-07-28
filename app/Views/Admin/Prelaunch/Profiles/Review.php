<?php

declare(strict_types=1);

/**
 * @var array<string, string>      $validationErrors
 * @var array<string, string>|null $formAlert
 * @var array<string, string>|null $profile
 * @var array<string, string>|null  $photo
  * @var array<string, string>|null  $photos
 */

$errors = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

$isDraft = ($profile['status'] ?? '') === 'DRAFT';

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <?= esc($profile['full_name']) ?>
            </h1>

            <p class="text-muted mb-0">
                <?= esc($profile['profile_reference']) ?>
                ·
                <?= esc($profile['status']) ?>
            </p>
        </div>

        <a
            href="<?= route_to(
                        'admin.prelaunch.profiles.index'
                    ) ?>"
            class="btn btn-outline-secondary">
            Back
        </a>
    </div>

    <!--
        Admin may edit only mobile number and email.
        All other profile fields are rendered read-only.
    -->
    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">
                        Profile details
                    </h2>
                </div>

                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Full name</dt>
                        <dd class="col-sm-8">
                            <?= esc($profile['full_name']) ?>
                        </dd>

                        <dt class="col-sm-4">Date of birth</dt>
                        <dd class="col-sm-8">
                            <?= esc($profile['date_of_birth']) ?>
                        </dd>

                        <dt class="col-sm-4">Gender</dt>
                        <dd class="col-sm-8">
                            <?= esc($profile['gender']) ?>
                        </dd>

                        <dt class="col-sm-4">Marital status</dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $profile['marital_status_name']
                                    ?? $profile['marital_status_id']
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">Education</dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $profile['education_name']
                                    ?? $profile['highest_education_id']
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">Occupation</dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $profile['occupation_name']
                                    ?? $profile['occupation_id']
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">Father name</dt>
                        <dd class="col-sm-8">
                            <?= esc($profile['father_name']) ?>
                        </dd>

                        <dt class="col-sm-4">Mother name</dt>
                        <dd class="col-sm-8">
                            <?= esc($profile['mother_name']) ?>
                        </dd>

                        <dt class="col-sm-4">Field Officer</dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $profile['field_officer_name']
                                    ?? $profile['field_officer_id']
                            ) ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="h5 mb-0">
                        Photographs
                    </h2>
                </div>

                <div class="card-body">
                    <div class="row g-4">
                        <?php foreach ($photos as $photo): ?>
                            <div class="col-12 col-md-4">
                                <img
                                    src="<?= route_to(
                                                'admin.prelaunch.photos.view',
                                                $photo['id'],
                                                'medium'
                                            ) ?>"
                                    class="img-fluid rounded mb-3"
                                    alt="Member photograph <?= esc(
                                                                $photo['sequence_no']
                                                            ) ?>">

                                <div class="mb-3">
                                    <span class="badge text-bg-secondary">
                                        <?= esc(
                                            $photo['approval_status']
                                        ) ?>
                                    </span>
                                </div>

                                <?php if ($isDraft): ?>
                                    <form
                                        action="<?= route_to(
                                                    'admin.prelaunch.photos.approve',
                                                    $photo['id']
                                                ) ?>"
                                        method="post"
                                        class="mb-2"
                                        data-submit-loader>
                                        <?= csrf_field() ?>

                                        <button
                                            type="submit"
                                            class="btn btn-success w-100">
                                            Approve Photo
                                        </button>
                                    </form>

                                    <form
                                        action="<?= route_to(
                                                    'admin.prelaunch.photos.reject',
                                                    $photo['id']
                                                ) ?>"
                                        method="post"
                                        data-submit-loader>
                                        <?= csrf_field() ?>

                                        <label
                                            for="photo_reason_<?= esc(
                                                                    $photo['id']
                                                                ) ?>"
                                            class="form-label">
                                            Rejection reason
                                        </label>

                                        <textarea
                                            class="form-control mb-2"
                                            id="photo_reason_<?= esc(
                                                                    $photo['id']
                                                                ) ?>"
                                            name="rejection_reason"
                                            minlength="5"
                                            maxlength="500"
                                            required></textarea>

                                        <button
                                            type="submit"
                                            class="btn btn-outline-danger w-100">
                                            Reject Photo
                                        </button>
                                    </form>
                                <?php endif ?>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">
                        Contact correction
                    </h2>
                </div>

                <div class="card-body">
                    <form
                        action="<?= route_to(
                                    'admin.prelaunch.profiles.contact',
                                    $profile['id']
                                ) ?>"
                        method="post"
                        data-submit-loader
                        novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label
                                for="email"
                                class="form-label">
                                Email
                            </label>

                            <input
                                type="email"
                                class="form-control <?= isset(
                                                        $errors['email']
                                                    ) ? 'is-invalid' : '' ?>"
                                id="email"
                                name="email"
                                value="<?= esc(
                                            old(
                                                'email',
                                                $profile['email']
                                            )
                                        ) ?>"
                                required>

                            <div class="invalid-feedback">
                                <?= esc(
                                    $errors['email']
                                        ?? 'Please enter a valid email.'
                                ) ?>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label
                                    for="country_code"
                                    class="form-label">
                                    Code
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="country_code"
                                    name="country_code"
                                    value="<?= esc(
                                                old(
                                                    'country_code',
                                                    $profile['country_code']
                                                )
                                            ) ?>"
                                    required>
                            </div>

                            <div class="col-8">
                                <label
                                    for="mobile_number"
                                    class="form-label">
                                    Mobile
                                </label>

                                <input
                                    type="tel"
                                    inputmode="numeric"
                                    class="form-control <?= isset(
                                                            $errors['mobile_number']
                                                        ) ? 'is-invalid' : '' ?>"
                                    id="mobile_number"
                                    name="mobile_number"
                                    value="<?= esc(
                                                old(
                                                    'mobile_number',
                                                    $profile['mobile_number']
                                                )
                                            ) ?>"
                                    pattern="[0-9]{10,15}"
                                    required>

                                <div class="invalid-feedback">
                                    <?= esc(
                                        $errors['mobile_number']
                                            ?? 'Please enter a valid mobile number.'
                                    ) ?>
                                </div>
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100">
                            Save Contact Changes
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($isDraft): ?>
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h2 class="h5 mb-0">
                            Profile decision
                        </h2>
                    </div>

                    <div class="card-body">
                        <form
                            action="<?= route_to(
                                        'admin.prelaunch.profiles.approve',
                                        $profile['id']
                                    ) ?>"
                            method="post"
                            class="mb-3"
                            data-submit-loader>
                            <?= csrf_field() ?>

                            <button
                                type="submit"
                                class="btn btn-success w-100">
                                Approve Profile
                            </button>
                        </form>

                        <form
                            action="<?= route_to(
                                        'admin.prelaunch.profiles.reject',
                                        $profile['id']
                                    ) ?>"
                            method="post"
                            data-submit-loader>
                            <?= csrf_field() ?>

                            <label
                                for="rejection_reason"
                                class="form-label">
                                Rejection reason
                            </label>

                            <textarea
                                class="form-control mb-3"
                                id="rejection_reason"
                                name="rejection_reason"
                                minlength="5"
                                maxlength="1000"
                                required></textarea>

                            <button
                                type="submit"
                                class="btn btn-outline-danger w-100">
                                Reject Profile
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>