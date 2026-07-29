<?php

declare(strict_types=1);

/**
 * @var string                     $pageTitle
 * @var array<string, mixed>       $profile
 * @var list<array<string, mixed>> $photos
 * @var array<string, mixed>       $photoSummary
 * @var array<string, string>      $validationErrors
 * @var array<string, string>|null $formAlert
 * @var list<string>               $pageScripts
 */

$profile = is_array(
    $profile ?? null
)
    ? $profile
    : [];

$photos = is_array(
    $photos ?? null
)
    ? $photos
    : [];

$photoSummary = is_array(
    $photoSummary ?? null
)
    ? $photoSummary
    : [
        'total' => count($photos),
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'allApproved' => false,
    ];

$errors = is_array(
    $validationErrors ?? null
)
    ? $validationErrors
    : [];

$alert = is_array(
    $formAlert ?? null
)
    ? $formAlert
    : null;

$isDraft = mb_strtoupper(
    trim(
        (string) (
            $profile['status']
            ?? ''
        )
    )
) === 'DRAFT';

$allPhotosApproved = (
    $photoSummary['allApproved']
    ?? false
) === true;

/**
 * Convert a nullable profile value into safe display text.
 */
$displayValue = static function (
    mixed $value
): string {
    $text = trim(
        (string) $value
    );

    return $text !== ''
        ? $text
        : '—';
};

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>

<div class="container-fluid py-3 pt-0">
    <div
        class="d-flex flex-column flex-md-row
            justify-content-between align-items-md-center
            gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">
                <?= esc(
                    $displayValue(
                        $profile['full_name']
                            ?? ''
                    )
                ) ?>
            </h1>

            <p class="text-muted mb-0">
                <?= esc(
                    $displayValue(
                        $profile['profile_reference']
                            ?? ''
                    )
                ) ?>

                <span aria-hidden="true">·</span>

                <?= esc(
                    $displayValue(
                        $profile['status']
                            ?? ''
                    )
                ) ?>
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

    <?php if ($alert !== null): ?>
        <div
            class="alert alert-<?= esc(
                                    $alert['type']
                                        ?? 'danger'
                                ) ?>"
            role="alert">
            <?php if (
                trim(
                    (string) (
                        $alert['title']
                        ?? ''
                    )
                ) !== ''
            ): ?>
                <strong>
                    <?= esc(
                        $alert['title']
                    ) ?>
                </strong>
            <?php endif ?>

            <div>
                <?= esc(
                    $alert['message']
                        ?? ''
                ) ?>
            </div>
        </div>
    <?php endif ?>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <!-- Profile information is read-only for administrators. -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">
                        Member details
                    </h2>
                </div>

                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">
                            Profile created for
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['profile_created_for'] ?? ''
                                )
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Full name
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['full_name']
                                        ?? ''
                                )
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Date of birth
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['date_of_birth']
                                        ?? ''
                                )
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Gender
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['gender']
                                        ?? ''
                                )
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Marital status
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['marital_status_name'] ?? ''
                                )
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Height
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['height_name']
                                        ?? ''
                                )
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Mother tongue
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['mother_tongue_name'] ?? ''
                                )
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Location
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                implode(
                                    ', ',
                                    array_filter([
                                        trim((string) (
                                            $profile['city_name']
                                            ?? ''
                                        )),
                                        trim((string) (
                                            $profile['state_name']
                                            ?? ''
                                        )),
                                        trim((string) (
                                            $profile['country_name']
                                            ?? ''
                                        )),
                                    ])
                                ) ?: '—'
                            ) ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">
                        Education and profession
                    </h2>
                </div>

                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">
                            Highest education
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['education_name']
                                        ?? ''
                                )
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Employed in
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['employed_in']
                                        ?? ''
                                )
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Occupation
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['occupation_name']
                                        ?? ''
                                )
                            ) ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">
                        Family details
                    </h2>
                </div>

                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">
                            Father name
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['father_name']
                                        ?? ''
                                )
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Mother name
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['mother_name']
                                        ?? ''
                                )
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Family value
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['family_value_name']
                                        ?? ''
                                )
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Family type
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['family_type_name']
                                        ?? ''
                                )
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Family status
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['family_status_name']
                                        ?? ''
                                )
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Community
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['community_name']
                                        ?? ''
                                )
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Sub-community
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['subcommunity_name']
                                        ?? ''
                                )
                            ) ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">
                        Field Officer
                    </h2>
                </div>

                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">
                            Name
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['field_officer_name']
                                        ?? ''
                                )
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Officer code
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['officer_code']
                                        ?? ''
                                )
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Current status
                        </dt>
                        <dd class="col-sm-8">
                            <?= esc(
                                $displayValue(
                                    $profile['field_officer_status'] ?? ''
                                )
                            ) ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div
                    class="card-header d-flex
                        justify-content-between align-items-center">
                    <h2 class="h5 mb-0">
                        Photographs
                    </h2>

                    <span class="badge text-bg-secondary">
                        <?= esc(
                            $photoSummary['approved']
                                ?? 0
                        ) ?>
                        / 3 approved
                    </span>
                </div>

                <div class="card-body">
                    <div class="row g-4">
                        <?php if ($photos === []): ?>
                            <div class="col-12">
                                <div
                                    class="alert alert-warning mb-0"
                                    role="alert">
                                    No photographs were found for this profile.
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($photos as $photo): ?>
                                <?php
                                /*
         * Protect the template from malformed service data.
         */
                                if (!is_array($photo)) {
                                    continue;
                                }

                                $photoId = filter_var(
                                    $photo['id'] ?? null,
                                    FILTER_VALIDATE_INT
                                );

                                if (
                                    $photoId === false
                                    || $photoId <= 0
                                ) {
                                    continue;
                                }

                                $photoIdString = (string) $photoId;

                                $sequenceNumber = filter_var(
                                    $photo['sequence_no'] ?? null,
                                    FILTER_VALIDATE_INT
                                );

                                $sequenceNumber = $sequenceNumber !== false
                                    ? $sequenceNumber
                                    : 0;

                                $photoStatus = mb_strtoupper(
                                    trim(
                                        (string) (
                                            $photo['approval_status']
                                            ?? 'PENDING'
                                        )
                                    )
                                );

                                $photoBadgeClass = match ($photoStatus) {
                                    'APPROVED' =>
                                    'text-bg-success',

                                    'REJECTED' =>
                                    'text-bg-danger',

                                    default =>
                                    'text-bg-secondary',
                                };

                                $rejectionFieldId =
                                    'photo_reason_' . $photoIdString;
                                ?>

                                <div class="col-12 col-md-4">
                                    <img
                                        src="<?= esc(
                                                    route_to(
                                                        'admin.prelaunch.photos.view',
                                                        $photoId,
                                                        'medium'
                                                    ),
                                                    'attr'
                                                ) ?>"
                                        class="img-fluid rounded mb-3"
                                        alt="<?= esc(
                                                    'Member photograph '
                                                        . $sequenceNumber,
                                                    'attr'
                                                ) ?>">

                                    <div class="mb-3">
                                        <span
                                            class="badge <?= esc(
                                                                $photoBadgeClass,
                                                                'attr'
                                                            ) ?>">
                                            <?= esc($photoStatus) ?>
                                        </span>
                                    </div>

                                    <?php if ($isDraft): ?>
                                        <form
                                            action="<?= esc(
                                                        route_to(
                                                            'admin.prelaunch.photos.approve',
                                                            $photoId
                                                        ),
                                                        'attr'
                                                    ) ?>"
                                            method="post"
                                            class="mb-3"
                                            data-submit-loader>
                                            <?= csrf_field() ?>

                                            <button
                                                type="submit"
                                                class="btn btn-success w-100">
                                                Approve Photo
                                            </button>
                                        </form>

                                        <form
                                            action="<?= esc(
                                                        route_to(
                                                            'admin.prelaunch.photos.reject',
                                                            $photoId
                                                        ),
                                                        'attr'
                                                    ) ?>"
                                            method="post"
                                            data-submit-loader>
                                            <?= csrf_field() ?>

                                            <label
                                                for="<?= esc(
                                                            $rejectionFieldId,
                                                            'attr'
                                                        ) ?>"
                                                class="form-label">
                                                Rejection reason
                                            </label>

                                            <textarea
                                                class="form-control mb-2"
                                                id="<?= esc(
                                                        $rejectionFieldId,
                                                        'attr'
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
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <!-- Only email and mobile may be edited by administrators. -->
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
                                                    )
                                                        ? 'is-invalid'
                                                        : '' ?>"
                                id="email"
                                name="email"
                                value="<?= esc(
                                            old(
                                                'email',
                                                $profile['email']
                                                    ?? ''
                                            )
                                        ) ?>"
                                maxlength="190"
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
                                    class="form-control <?= isset(
                                                            $errors['country_code']
                                                        )
                                                            ? 'is-invalid'
                                                            : '' ?>"
                                    id="country_code"
                                    name="country_code"
                                    value="<?= esc(
                                                old(
                                                    'country_code',
                                                    $profile['country_code']
                                                        ?? '+91'
                                                )
                                            ) ?>"
                                    maxlength="5"
                                    required>

                                <div class="invalid-feedback">
                                    <?= esc(
                                        $errors['country_code']
                                            ?? 'Enter a valid code.'
                                    ) ?>
                                </div>
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
                                                        )
                                                            ? 'is-invalid'
                                                            : '' ?>"
                                    id="mobile_number"
                                    name="mobile_number"
                                    value="<?= esc(
                                                old(
                                                    'mobile_number',
                                                    $profile['mobile_number']
                                                        ?? ''
                                                )
                                            ) ?>"
                                    pattern="[0-9]{10,15}"
                                    maxlength="15"
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
                        <?php if (
                            !$allPhotosApproved
                        ): ?>
                            <div
                                class="alert alert-warning"
                                role="alert">
                                Approve all three photographs
                                before approving this profile.
                            </div>
                        <?php endif ?>

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
                                class="btn btn-success w-100"
                                <?= !$allPhotosApproved
                                    ? 'disabled'
                                    : '' ?>>
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