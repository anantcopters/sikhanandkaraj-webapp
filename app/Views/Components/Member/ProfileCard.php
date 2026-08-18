<?php

declare(strict_types=1);

/**
 * Search profile-card UI variables.
 *
 * Eligibility, blocking, reporting, privacy, Interest state, account type,
 * verification state and photo authorization are resolved before reaching
 * this view.
 *
 * @var array<string, mixed> $profile
 */

$profile =
    isset($profile)
    && is_array($profile)
    ? $profile
    : [];

$name = trim(
    (string) (
        $profile['name']
        ?? 'Member'
    )
);

if ($name === '') {
    $name = 'Member';
}

$reference = trim(
    (string) (
        $profile['referenceId']
        ?? ''
    )
);

$image = trim(
    (string) (
        $profile['image']
        ?? ''
    )
);

$profileUrl = trim(
    (string) (
        $profile['profileUrl']
        ?? ''
    )
);

if ($profileUrl === '') {
    $profileUrl = '#';
}

$interestUrl = trim(
    (string) (
        $profile['interestUrl']
        ?? ''
    )
);

$age =
    is_numeric(
        $profile['age']
            ?? null
    )
    ? max(
        0,
        (int) $profile['age']
    )
    : null;

$height = trim(
    (string) (
        $profile['height']
        ?? ''
    )
);

$city = trim(
    (string) (
        $profile['city']
        ?? ''
    )
);

$state = trim(
    (string) (
        $profile['state']
        ?? ''
    )
);

$location = trim(
    (string) (
        $profile['location']
        ?? (
            $city !== ''
            ? $city
            : $state
        )
    )
);

$maritalStatus = trim(
    (string) (
        $profile['maritalStatus']
        ?? ''
    )
);

$activity = trim(
    (string) (
        $profile['activity']
        ?? ''
    )
);

/*
 * Account type comes from MemberProfilePresentationService.
 *
 * Do not hardcode a fallback in the view. Missing backend data should not
 * silently display an incorrect account type.
 */
$accountType = trim(
    (string) (
        $profile['accountType']
        ?? ''
    )
);

/*
 * The shared presentation service decides whether to use the sentence or
 * compact professional-summary format.
 */
$professionalSummary = trim(
    (string) (
        $profile['professionalSummary']
        ?? ''
    )
);

/*
 * Verification values are normalized to booleans by the backend
 * presentation service.
 */
$verification =
    isset($profile['verification'])
    && is_array(
        $profile['verification']
    )
    ? $profile['verification']
    : [];

$interestRelationship =
    isset($profile['interestRelationship'])
    && is_array(
        $profile['interestRelationship']
    )
    ? $profile['interestRelationship']
    : [];

$interestState = strtoupper(
    trim(
        (string) (
            $interestRelationship['state']
            ?? 'NONE'
        )
    )
);

$canShowInterest =
    (
        $interestRelationship['canShowInterest']
        ?? false
    ) === true;

/*
 * Relationship status is intentionally coarse. Internal member IDs and
 * relationship implementation details are never exposed by the card.
 */
$relationshipLabel =
    match ($interestState) {
        'PENDING_SENT' =>
        'Interest Sent',

        'PENDING_RECEIVED' =>
        'Interest Received',

        'ACCEPTED_SENT',
        'ACCEPTED_RECEIVED' =>
        'Interest Accepted',

        default =>
        '',
    };
?>

<article
    class="card h-95 border border-danger
        border-opacity-25 shadow-sm
        overflow-hidden">

    <div class="card-body p-3 p-md-4">

        <div
            class="d-flex flex-column
                flex-sm-row gap-3">

            <!-- Profile photo, account type and professional summary -->
            <div
                class="d-flex flex-column
        align-items-center
        flex-shrink-0"
                style="width: 160px;">

                <a
                    href="<?= esc(
                                $profileUrl,
                                'attr'
                            ) ?>"
                    class="text-decoration-none">

                    <div class="member-profile-thumbnail">

                        <img
                            src="<?= esc(
                                        $image,
                                        'attr'
                                    ) ?>"
                            alt="<?= esc(
                                        $name
                                            . ' profile photo',
                                        'attr'
                                    ) ?>"
                            loading="lazy">

                    </div>

                </a>

                <?php if (
                    $accountType !== ''
                ): ?>

                    <span
                        class="badge rounded
                bg-primary-subtle
                text-primary
                border border-primary
                border-opacity-25
                mt-3 px-2 py-2 fs-12">

                        <i
                            class="ri-vip-crown-line
                    me-1 fs-14"
                            aria-hidden="true">
                        </i>

                        <?= esc(
                            $accountType
                        ) ?>

                    </span>

                <?php endif; ?>

            </div>

            <!-- Profile summary -->
            <div class="flex-grow-1 min-w-0">

                <!-- Name, reference and account type -->
                <div
                    class="d-flex
                        align-items-start
                        justify-content-between
                        gap-2 mb-2">

                    <div class="min-w-0">

                        <h3
                            class="fs-18
                                fw-semibold mb-1
                                text-truncate">

                            <a
                                href="<?= esc(
                                            $profileUrl,
                                            'attr'
                                        ) ?>"
                                class="text-body
                                    text-decoration-none">

                                <?= esc(
                                    $name
                                ) ?>

                            </a>

                        </h3>

                        <?php if (
                            $reference !== ''
                        ): ?>

                            <div
                                class="text-muted
                                    fs-13">

                                <?= esc(
                                    $reference
                                ) ?>

                            </div>

                        <?php endif; ?>

                    </div>

                    <?php if (
                        $relationshipLabel !== ''
                    ): ?>

                        <span
                            class="badge bg-light
            text-body border
            px-2 py-2
            flex-shrink-0">

                            <?= esc(
                                $relationshipLabel
                            ) ?>

                        </span>

                    <?php endif; ?>

                </div>

                <!-- Privacy-safe member activity -->
                <?php if (
                    $activity !== ''
                ): ?>

                    <div
                        class="d-flex
                            align-items-center
                            gap-1 fs-12
                            text-success mb-2">

                        <i
                            class="ri-checkbox-blank-circle-fill"
                            aria-hidden="true">
                        </i>

                        <span>
                            <?= esc(
                                $activity
                            ) ?>
                        </span>

                    </div>

                <?php endif; ?>

                <!-- Basic member summary -->
                <div
                    class="d-flex flex-wrap
                        align-items-center
                        gap-2 fs-13
                        text-muted mb-2">

                    <?php if (
                        $age !== null
                        && $age > 0
                    ): ?>

                        <span>
                            <?= esc(
                                (string) $age
                            ) ?>
                            yrs
                        </span>

                    <?php endif; ?>

                    <?php if (
                        $height !== ''
                    ): ?>

                        <?php if (
                            $age !== null
                            && $age > 0
                        ): ?>

                            <span aria-hidden="true">
                                ·
                            </span>

                        <?php endif; ?>

                        <span>
                            <?= esc(
                                $height
                            ) ?>
                        </span>

                    <?php endif; ?>

                </div>

                <?php if (
                    $location !== ''
                ): ?>

                    <p
                        class="d-flex
                            align-items-center
                            gap-1 text-muted
                            fs-13 mb-2">

                        <i
                            class="ri-map-pin-line
                                text-primary
                                flex-shrink-0"
                            aria-hidden="true">
                        </i>

                        <span>
                            <?= esc(
                                $location
                            ) ?>
                        </span>

                    </p>

                <?php endif; ?>

                <?php if (
                    $maritalStatus !== ''
                ): ?>

                    <p class="fs-13 mb-3">

                        <?= esc(
                            $maritalStatus
                        ) ?>

                    </p>

                <?php endif; ?>

                <!-- Profile actions -->
                <div
                    class="d-flex flex-wrap
                        align-items-center gap-2">

                    <a
                        href="<?= esc(
                                    $profileUrl,
                                    'attr'
                                ) ?>"
                        class="btn
                            btn-outline-primary
                            btn-sm
                            d-inline-flex
                            align-items-center
                            justify-content-center
                            gap-1">

                        <i
                            class="ri-eye-line"
                            aria-hidden="true">
                        </i>

                        View Profile

                    </a>

                    <?php if (
                        $canShowInterest
                        && $interestUrl !== ''
                    ): ?>

                        <form
                            method="post"
                            action="<?= esc(
                                        $interestUrl,
                                        'attr'
                                    ) ?>"
                            data-member-interest-form>

                            <?= csrf_field() ?>

                            <button
                                type="submit"
                                class="btn btn-danger
                                    btn-sm
                                    d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    gap-2">

                                <span
                                    class="d-inline-flex
                                        align-items-center
                                        gap-1"
                                    data-member-interest-label>

                                    <i
                                        class="ri-heart-add-line"
                                        aria-hidden="true">
                                    </i>

                                    Show Interest

                                </span>

                                <span
                                    class="d-none
                                        align-items-center
                                        gap-2"
                                    data-member-interest-loading>

                                    <span
                                        class="spinner-border
                                            spinner-border-sm"
                                        role="status"
                                        aria-hidden="true">
                                    </span>

                                    Sending...

                                </span>

                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            </div>


        </div>
        <?php if (
            $professionalSummary !== ''
        ): ?>

            <p
                class="text-body
            fs-13
            mt-3 mb-0">

                <?= esc(
                    $professionalSummary
                ) ?>

            </p>

        <?php endif; ?>
    </div>

    <?= view(
        'Components/Member/VerificationBadges',
        [
            'verification' =>
            $verification,
        ]
    ) ?>

</article>