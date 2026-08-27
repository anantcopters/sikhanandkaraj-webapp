<?php

declare(strict_types=1);

/**
 * @var int                  $memberId
 * @var array<string, mixed> $diagnostics
 * @var array<string, mixed> $comparison
 * @var array<string, mixed> $diagnosticErrors
 * @var array<string, mixed> $diagnosticInput
 */

$memberId =
    max(
        0,
        (int) (
            $memberId
            ?? 0
        )
    );

$diagnostics =
    isset($diagnostics)
    && is_array($diagnostics)
    ? $diagnostics
    : [];

$comparison =
    isset($comparison)
    && is_array($comparison)
    ? $comparison
    : [];

$diagnosticErrors =
    isset($diagnosticErrors)
    && is_array($diagnosticErrors)
    ? $diagnosticErrors
    : [];

$diagnosticInput =
    isset($diagnosticInput)
    && is_array($diagnosticInput)
    ? $diagnosticInput
    : [];

if ($diagnostics === []) {
    return;
}

$weights =
    isset($diagnostics['weights'])
    && is_array(
        $diagnostics['weights']
    )
    ? $diagnostics['weights']
    : [];

$profileCompletion =
    max(
        0,
        min(
            100,
            (int) (
                $diagnostics['profileCompletion']
                ?? 0
            )
        )
    );

$approvedPhotoCount =
    max(
        0,
        (int) (
            $diagnostics['approvedPhotoCount']
            ?? 0
        )
    );

$approvedPhotoScore =
    (float) (
        $diagnostics['approvedPhotoScore']
        ?? 0
    );

$trustPoints =
    max(
        0,
        (int) (
            $diagnostics['trustPoints']
            ?? 0
        )
    );

$trustScore =
    (float) (
        $diagnostics['trustScore']
        ?? 0
    );

$commercialPriority =
    max(
        0,
        (int) (
            $diagnostics['commercialPriority']
            ?? 0
        )
    );

$commercialScore =
    (float) (
        $diagnostics['commercialScore']
        ?? 0
    );

$membershipPlan =
    trim(
        (string) (
            $diagnostics['membershipPlanName']
            ?? ''
        )
    );

if ($membershipPlan === '') {
    $membershipPlan =
        'Free';
}

$verificationSignals = [
    [
        'label' =>
        'Mobile',

        'verified' => ($diagnostics['mobileVerified'] ?? false)
            === true,

        'points' =>
        1,
    ],
    [
        'label' =>
        'Email',

        'verified' => ($diagnostics['emailVerified'] ?? false)
            === true,

        'points' =>
        1,
    ],
    [
        'label' =>
        'Aadhaar',

        'verified' => ($diagnostics['aadhaarVerified'] ?? false)
            === true,

        'points' =>
        3,
    ],
    [
        'label' =>
        'Live Introduction',

        'verified' => ($diagnostics['videoVerified'] ?? false)
            === true,

        'points' =>
        3,
    ],
];

$enteredProfileReference =
    trim(
        (string) (
            $diagnosticInput['profile_reference']
            ?? ''
        )
    );

/*
 * Render one directional comparison result.
 *
 * Keeping this presentation helper inside the view avoids creating CSS or a
 * second partial for a small Admin-only diagnostic block.
 */
$renderDirection =
    static function (
        array $direction,
        array $viewer,
        array $candidate
    ): void {
        $eligible =
            ($direction['eligible'] ?? false)
            === true;

        $viewerName =
            trim(
                (string) (
                    $viewer['name']
                    ?? ''
                )
            );

        $viewerReference =
            trim(
                (string) (
                    $viewer['profileReference']
                    ?? ''
                )
            );

        $candidateName =
            trim(
                (string) (
                    $candidate['name']
                    ?? ''
                )
            );

        $candidateReference =
            trim(
                (string) (
                    $candidate['profileReference']
                    ?? ''
                )
            );

?>
    <div class="border rounded p-3 h-100">

        <div
            class="d-flex
                    align-items-start
                    justify-content-between
                    gap-3
                    mb-3">

            <div>
                <div class="fw-semibold">
                    <?= esc(
                        $viewerName
                    ) ?>

                    <?php if (
                        $viewerReference !== ''
                    ): ?>
                        <span class="text-muted">
                            (<?= esc(
                                    $viewerReference
                                ) ?>)
                        </span>
                    <?php endif; ?>

                    <i
                        class="ri-arrow-right-line mx-1"
                        aria-hidden="true"></i>

                    <?= esc(
                        $candidateName
                    ) ?>

                    <?php if (
                        $candidateReference !== ''
                    ): ?>
                        <span class="text-muted">
                            (<?= esc(
                                    $candidateReference
                                ) ?>)
                        </span>
                    <?php endif; ?>
                </div>

                <div class="text-muted fs-12 mt-1">
                    Viewer → Candidate
                </div>
            </div>

            <?php if ($eligible): ?>
                <span
                    class="badge
                            bg-success-subtle
                            text-black p-2">

                    Eligible
                </span>
            <?php else: ?>
                <span
                    class="badge
                            bg-secondary-subtle
                            text-black p-2">

                    Not Eligible
                </span>
            <?php endif; ?>

        </div>

        <?php if (!$eligible): ?>

            <div
                class="alert
                        alert-warning
                        mb-0"
                role="alert">

                <?= esc(
                    (string) (
                        $direction['reason']
                        ?? 'This member is not currently an eligible candidate.'
                    )
                ) ?>
            </div>

        <?php else: ?>

            <?php
            $contributions =
                isset(
                    $direction['weightedContributions']
                )
                && is_array(
                    $direction['weightedContributions']
                )
                ? $direction['weightedContributions']
                : [];

            $directionWeights =
                isset(
                    $direction['weights']
                )
                && is_array(
                    $direction['weights']
                )
                ? $direction['weights']
                : [];

            $components = [
                [
                    'label' =>
                    'Partner Preference',

                    'score' =>
                    (float) (
                        $direction['preferenceScore']
                        ?? 0
                    ),

                    'weight' =>
                    (int) (
                        $directionWeights['preference']
                        ?? 0
                    ),

                    'contribution' =>
                    (float) (
                        $contributions['preference']
                        ?? 0
                    ),
                ],
                [
                    'label' =>
                    'Profile Completion',

                    'score' =>
                    (float) (
                        $direction['profileCompletionScore']
                        ?? 0
                    ),

                    'weight' =>
                    (int) (
                        $directionWeights['profileCompletion']
                        ?? 0
                    ),

                    'contribution' =>
                    (float) (
                        $contributions['profileCompletion']
                        ?? 0
                    ),
                ],
                [
                    'label' =>
                    'Approved Photos',

                    'score' =>
                    (float) (
                        $direction['approvedPhotoScore']
                        ?? 0
                    ),

                    'weight' =>
                    (int) (
                        $directionWeights['approvedPhotos']
                        ?? 0
                    ),

                    'contribution' =>
                    (float) (
                        $contributions['approvedPhotos']
                        ?? 0
                    ),
                ],
                [
                    'label' =>
                    'Trust',

                    'score' =>
                    (float) (
                        $direction['trustScore']
                        ?? 0
                    ),

                    'weight' =>
                    (int) (
                        $directionWeights['trust']
                        ?? 0
                    ),

                    'contribution' =>
                    (float) (
                        $contributions['trust']
                        ?? 0
                    ),
                ],
                [
                    'label' =>
                    'Membership',

                    'score' =>
                    (float) (
                        $direction['commercialScore']
                        ?? 0
                    ),

                    'weight' =>
                    (int) (
                        $directionWeights['commercial']
                        ?? 0
                    ),

                    'contribution' =>
                    (float) (
                        $contributions['commercial']
                        ?? 0
                    ),
                ],
            ];
            ?>

            <div class="row g-3 mb-3">

                <div class="col-sm-6">
                    <div
                        class="border
                                rounded
                                p-3
                                text-center
                                h-100">

                        <div class="text-muted fs-12 mb-1">
                            Final Match Score
                        </div>

                        <div class="fs-3 fw-semibold">
                            <?= esc(
                                number_format(
                                    (float) (
                                        $direction['matchScore']
                                        ?? 0
                                    ),
                                    2
                                )
                            ) ?>%
                        </div>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div
                        class="border
                                rounded
                                p-3
                                text-center
                                h-100">

                        <div class="text-muted fs-12 mb-1">
                            Partner Preference
                        </div>

                        <div class="fs-3 fw-semibold">
                            <?= esc(
                                number_format(
                                    (float) (
                                        $direction['matchPercentage']
                                        ?? 0
                                    ),
                                    2
                                )
                            ) ?>%
                        </div>
                    </div>
                </div>

            </div>

            <div class="mb-3">

                <?php if (
                    ($direction['passesCompulsory'] ?? true)
                    === true
                ): ?>

                    <span
                        class="badge
                                bg-success-subtle
                                text-black p-2">

                        <i
                            class="ri-checkbox-circle-line me-1"
                            aria-hidden="true"></i>

                        Compulsory Preferences Passed
                    </span>

                <?php else: ?>

                    <span
                        class="badge
                                bg-danger-subtle
                                text-black p-2">

                        <i
                            class="ri-close-circle-line me-1"
                            aria-hidden="true"></i>

                        Compulsory Preferences Failed
                    </span>

                <?php endif; ?>

            </div>

            <div class="table-responsive">

                <table
                    class="table
                            table-sm
                            table-borderless
                            align-middle
                            mb-0">

                    <thead>
                        <tr class="text-muted">
                            <th>Component</th>
                            <th class="text-end">
                                Score
                            </th>
                            <th class="text-end">
                                Weight
                            </th>
                            <th class="text-end">
                                Contribution
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach (
                            $components
                            as $component
                        ): ?>

                            <tr>
                                <td>
                                    <?= esc(
                                        $component['label']
                                    ) ?>
                                </td>

                                <td class="text-end">
                                    <?= esc(
                                        number_format(
                                            $component['score'],
                                            2
                                        )
                                    ) ?>%
                                </td>

                                <td class="text-end">
                                    <?= esc(
                                        (string)
                                        $component['weight']
                                    ) ?>%
                                </td>

                                <td
                                    class="text-end
                                            fw-medium">

                                    <?= esc(
                                        number_format(
                                            $component['contribution'],
                                            2
                                        )
                                    ) ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>
<?php
    };
?>

<div class="card border border-danger border-opacity-25">

    <div class="card-header">

        <div
            class="d-flex
                align-items-center
                justify-content-between
                gap-3">

            <div>
                <h5 class="card-title mb-1">
                    <i
                        class="ri-bar-chart-grouped-line me-1"
                        aria-hidden="true"></i>

                    Match Score Diagnostics
                </h5>

                <p class="text-muted mb-0">
                    Inspect the ranking signals and test the actual score
                    against another member.
                </p>
            </div>

            <span
                class="badge
                    bg-primary-subtle
                    text-black p-2">

                Admin Only
            </span>

        </div>

    </div>

    <div class="card-body">

        <!-- Candidate-intrinsic signals -->
        <div class="row g-3">

            <div class="col-md-6 col-xl-3">

                <div
                    class="border
                        rounded
                        p-3
                        h-100">

                    <div
                        class="d-flex
                            align-items-center
                            justify-content-between
                            mb-2">

                        <span class="text-muted">
                            Profile Completion
                        </span>

                        <i
                            class="ri-profile-line text-primary"
                            aria-hidden="true"></i>
                    </div>

                    <div class="fs-4 fw-semibold">
                        <?= esc(
                            (string)
                            $profileCompletion
                        ) ?>%
                    </div>

                    <div class="small text-muted mt-1">
                        Weight:
                        <?= esc(
                            (string) (
                                $weights['profileCompletion']
                                ?? 0
                            )
                        ) ?>%
                    </div>

                </div>
            </div>

            <div class="col-md-6 col-xl-3">

                <div
                    class="border
                        rounded
                        p-3
                        h-100">

                    <div
                        class="d-flex
                            align-items-center
                            justify-content-between
                            mb-2">

                        <span class="text-muted">
                            Approved Photos
                        </span>

                        <i
                            class="ri-image-line text-primary"
                            aria-hidden="true"></i>
                    </div>

                    <div class="fs-4 fw-semibold">
                        <?= esc(
                            number_format(
                                $approvedPhotoScore,
                                2
                            )
                        ) ?>%
                    </div>

                    <div class="small text-muted mt-1">
                        <?= esc(
                            (string)
                            $approvedPhotoCount
                        ) ?>
                        approved · Weight:
                        <?= esc(
                            (string) (
                                $weights['approvedPhotos']
                                ?? 0
                            )
                        ) ?>%
                    </div>

                </div>
            </div>

            <div class="col-md-6 col-xl-3">

                <div
                    class="border
                        rounded
                        p-3
                        h-100">

                    <div
                        class="d-flex
                            align-items-center
                            justify-content-between
                            mb-2">

                        <span class="text-muted">
                            Trust
                        </span>

                        <i
                            class="ri-shield-check-line text-success"
                            aria-hidden="true"></i>
                    </div>

                    <div class="fs-4 fw-semibold">
                        <?= esc(
                            number_format(
                                $trustScore,
                                2
                            )
                        ) ?>%
                    </div>

                    <div class="small text-muted mt-1">
                        <?= esc(
                            (string)
                            $trustPoints
                        ) ?>/8 points · Weight:
                        <?= esc(
                            (string) (
                                $weights['trust']
                                ?? 0
                            )
                        ) ?>%
                    </div>

                </div>
            </div>

            <div class="col-md-6 col-xl-3">

                <div
                    class="border
                        rounded
                        p-3
                        h-100">

                    <div
                        class="d-flex
                            align-items-center
                            justify-content-between
                            mb-2">

                        <span class="text-muted">
                            Membership Priority
                        </span>

                        <i
                            class="ri-vip-crown-line text-primary"
                            aria-hidden="true"></i>
                    </div>

                    <div class="fs-4 fw-semibold">
                        <?= esc(
                            number_format(
                                $commercialScore,
                                2
                            )
                        ) ?>%
                    </div>

                    <div class="small text-muted mt-1">
                        <?= esc(
                            $membershipPlan
                        ) ?>
                        · Priority
                        <?= esc(
                            (string)
                            $commercialPriority
                        ) ?>/3
                        · Weight:
                        <?= esc(
                            (string) (
                                $weights['commercial']
                                ?? 0
                            )
                        ) ?>%
                    </div>

                </div>
            </div>

        </div>

        <hr>

        <div class="row">

            <div class="col-lg-7">

                <h6 class="mb-3">
                    Trust Signals
                </h6>

                <div class="row g-2">

                    <?php foreach (
                        $verificationSignals
                        as $signal
                    ): ?>

                        <div class="col-sm-6">

                            <div
                                class="d-flex
                                    align-items-center
                                    justify-content-between
                                    border
                                    rounded
                                    px-3
                                    py-2">

                                <div
                                    class="d-flex
                                        align-items-center
                                        gap-2">

                                    <i
                                        class="<?= $signal['verified']
                                                    ? 'ri-checkbox-circle-fill text-success'
                                                    : 'ri-close-circle-line text-muted' ?>"
                                        aria-hidden="true"></i>

                                    <span>
                                        <?= esc(
                                            $signal['label']
                                        ) ?>
                                    </span>

                                </div>

                                <span class="small text-muted">
                                    <?= $signal['verified']
                                        ? '+'
                                        . esc(
                                            (string)
                                            $signal['points']
                                        )
                                        : '+0' ?>
                                </span>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

            <div class="col-lg-5 mt-4 mt-lg-0">

                <h6 class="mb-3">
                    Active Ranking Weights
                </h6>

                <?php
                $weightRows = [
                    'Partner Preference' =>
                    $weights['preference']
                        ?? 0,

                    'Profile Completion' =>
                    $weights['profileCompletion']
                        ?? 0,

                    'Approved Photos' =>
                    $weights['approvedPhotos']
                        ?? 0,

                    'Trust' =>
                    $weights['trust']
                        ?? 0,

                    'Membership Priority' =>
                    $weights['commercial']
                        ?? 0,
                ];
                ?>

                <?php foreach (
                    $weightRows
                    as $label => $weight
                ): ?>

                    <div
                        class="d-flex
                            justify-content-between
                            py-1">

                        <span class="text-muted">
                            <?= esc($label) ?>
                        </span>

                        <strong>
                            <?= esc(
                                (string)
                                $weight
                            ) ?>%
                        </strong>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

        <hr>

        <!-- Viewer-specific diagnostic -->
        <div class="row">

            <div class="col-xl-8">

                <h6 class="mb-1">
                    Test Match Score Against Member
                </h6>

                <p class="text-muted mb-3">
                    Enter another Profile ID to calculate the actual
                    directional Match Score using the production matching
                    and ranking services.
                </p>

                <form
                    method="post"
                    action="<?= esc(
                                route_to(
                                    'admin.members.match-score-diagnostic',
                                    $memberId
                                ),
                                'attr'
                            ) ?>">

                    <?= csrf_field() ?>

                    <div class="row g-2 align-items-start">

                        <div class="col-md-8">

                            <label
                                for="matchScoreProfileReference"
                                class="form-label">

                                Profile ID
                            </label>

                            <input
                                type="text"
                                id="matchScoreProfileReference"
                                name="profile_reference"
                                class="form-control<?= isset(
                                                        $diagnosticErrors['profile_reference']
                                                    )
                                                        ? ' is-invalid'
                                                        : '' ?>"
                                maxlength="50"
                                autocomplete="off"
                                value="<?= esc(
                                            $enteredProfileReference,
                                            'attr'
                                        ) ?>"
                                data-error-required="Please enter the Profile ID."
                                data-error-maxlength="Profile ID cannot exceed 50 characters."
                                required>

                            <?php if (
                                isset(
                                    $diagnosticErrors['profile_reference']
                                )
                            ): ?>

                                <div class="invalid-feedback">
                                    <?= esc(
                                        (string)
                                        $diagnosticErrors['profile_reference']
                                    ) ?>
                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="col-md-auto">

                            <label
                                class="form-label
                                    d-none
                                    d-md-block">

                                &nbsp;
                            </label>

                            <button
                                type="submit"
                                class="
        btn
        registration-form__submit
        fs-14
        fw-medium
        text-uppercase
    "
                                data-submit-button>

                                <span
                                    class="registration-submit__idle"
                                    data-submit-idle>

                                    <i
                                        class="ri-bar-chart-box-line me-1 fs-18"
                                        aria-hidden="true">
                                    </i>

                                    Calculate

                                </span>

                                <span
                                    class="
            registration-submit__loading
            d-none
        "
                                    data-submit-loading>

                                    <span
                                        class="
                spinner-border
                spinner-border-sm
            "
                                        role="status"
                                        aria-hidden="true">
                                    </span>

                                    Calculating...

                                </span>

                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>

        <?php if ($comparison !== []): ?>

            <?php
            $profileMember =
                isset(
                    $comparison['profileMember']
                )
                && is_array(
                    $comparison['profileMember']
                )
                ? $comparison['profileMember']
                : [];

            $comparisonMember =
                isset(
                    $comparison['comparisonMember']
                )
                && is_array(
                    $comparison['comparisonMember']
                )
                ? $comparison['comparisonMember']
                : [];

            $forward =
                isset(
                    $comparison['forward']
                )
                && is_array(
                    $comparison['forward']
                )
                ? $comparison['forward']
                : [];

            $reverse =
                isset(
                    $comparison['reverse']
                )
                && is_array(
                    $comparison['reverse']
                )
                ? $comparison['reverse']
                : [];
            ?>

            <hr>

            <div
                class="alert
                    alert-info
                    d-flex
                    align-items-center
                    gap-2"
                role="alert">

                <i
                    class="ri-information-line fs-20"
                    aria-hidden="true"></i>

                <div>
                    Match Score is directional. The two results below are
                    calculated separately because each member can have
                    different Partner Preferences.
                </div>

            </div>

            <div class="row g-3">

                <div class="col-xl-6">

                    <?php
                    $renderDirection(
                        $forward,
                        $profileMember,
                        $comparisonMember
                    );
                    ?>

                </div>

                <div class="col-xl-6">

                    <?php
                    $renderDirection(
                        $reverse,
                        $comparisonMember,
                        $profileMember
                    );
                    ?>

                </div>

            </div>

        <?php endif; ?>

    </div>

</div>