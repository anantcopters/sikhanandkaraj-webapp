<?php

declare(strict_types=1);

/**
 * @var int                  $memberId
 * @var string               $memberName
 * @var string               $memberReference
 * @var array<string, mixed> $comparison
 * @var array<string, mixed> $diagnosticErrors
 * @var array<string, mixed> $diagnosticInput
 */

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

$comparisonReference = trim(
    (string) (
        $diagnosticInput['profile_reference']
        ?? ''
    )
);

$profileMember =
    isset($comparison['profileMember'])
    && is_array(
        $comparison['profileMember']
    )
    ? $comparison['profileMember']
    : [];

$comparisonMember =
    isset($comparison['comparisonMember'])
    && is_array(
        $comparison['comparisonMember']
    )
    ? $comparison['comparisonMember']
    : [];

$forward =
    isset($comparison['forward'])
    && is_array(
        $comparison['forward']
    )
    ? $comparison['forward']
    : [];

$reverse =
    isset($comparison['reverse'])
    && is_array(
        $comparison['reverse']
    )
    ? $comparison['reverse']
    : [];

$hasResult =
    $comparison !== [];

$hasError =
    $diagnosticErrors !== [];

$renderDirection =
    static function (
        array $direction,
        array $viewer,
        array $candidate
    ): void {
        $eligible =
            ($direction['eligible'] ?? false)
            === true;

        $viewerName = trim(
            (string) (
                $viewer['name']
                ?? ''
            )
        );

        $viewerReference = trim(
            (string) (
                $viewer['profileReference']
                ?? ''
            )
        );

        $candidateName = trim(
            (string) (
                $candidate['name']
                ?? ''
            )
        );

        $candidateReference = trim(
            (string) (
                $candidate['profileReference']
                ?? ''
            )
        );

?>

    <div
        class="border
                rounded
                p-3
                h-100">

        <div class="fw-semibold mb-1">

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
                class="ri-arrow-right-line
                        mx-1"
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

        <div
            class="text-muted
                    fs-12
                    mb-3">

            Viewer → Candidate

        </div>

        <?php if (!$eligible): ?>

            <div
                class="alert
                        alert-warning
                        mb-0">

                <?= esc(
                    (string) (
                        $direction['reason']
                        ?? 'This member is not currently an eligible candidate.'
                    )
                ) ?>

            </div>

        <?php else: ?>

            <div class="row g-2 mb-3">

                <div class="col-6">

                    <div
                        class="border
                                rounded
                                p-3
                                text-center">

                        <div
                            class="text-muted
                                    fs-12">

                            Match Score

                        </div>

                        <div
                            class="fs-4
                                    fw-semibold">

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

                <div class="col-6">

                    <div
                        class="border
                                rounded
                                p-3
                                text-center">

                        <div
                            class="text-muted
                                    fs-12">

                            Partner Preference

                        </div>

                        <div
                            class="fs-4
                                    fw-semibold">

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

            <?php
            $weights =
                isset($direction['weights'])
                && is_array(
                    $direction['weights']
                )
                ? $direction['weights']
                : [];

            $contributions =
                isset(
                    $direction['weightedContributions']
                )
                && is_array(
                    $direction['weightedContributions']
                )
                ? $direction['weightedContributions']
                : [];

            $components = [
                [
                    'label' =>
                    'Partner Preference',

                    'score' =>
                    $direction['preferenceScore'] ?? 0,

                    'weight' =>
                    $weights['preference'] ?? 0,

                    'contribution' =>
                    $contributions['preference'] ?? 0,
                ],
                [
                    'label' =>
                    'Profile Completion',

                    'score' =>
                    $direction['profileCompletionScore'] ?? 0,

                    'weight' =>
                    $weights['profileCompletion'] ?? 0,

                    'contribution' =>
                    $contributions['profileCompletion'] ?? 0,
                ],
                [
                    'label' =>
                    'Approved Photos',

                    'score' =>
                    $direction['approvedPhotoScore'] ?? 0,

                    'weight' =>
                    $weights['approvedPhotos'] ?? 0,

                    'contribution' =>
                    $contributions['approvedPhotos'] ?? 0,
                ],
                [
                    'label' =>
                    'Trust',

                    'score' =>
                    $direction['trustScore'] ?? 0,

                    'weight' =>
                    $weights['trust'] ?? 0,

                    'contribution' =>
                    $contributions['trust'] ?? 0,
                ],
                [
                    'label' =>
                    'Membership',

                    'score' =>
                    $direction['commercialScore'] ?? 0,

                    'weight' =>
                    $weights['commercial'] ?? 0,

                    'contribution' =>
                    $contributions['commercial'] ?? 0,
                ],
            ];
            ?>

            <div class="table-responsive">

                <table
                    class="table
                            table-sm
                            align-middle
                            mb-0">

                    <thead>

                        <tr>

                            <th>
                                Component
                            </th>

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
                                            (float) $component['score'],
                                            2
                                        )
                                    ) ?>%

                                </td>

                                <td class="text-end">

                                    <?= esc(
                                        (string) $component['weight']
                                    ) ?>%

                                </td>

                                <td
                                    class="text-end
                                            fw-medium">

                                    <?= esc(
                                        number_format(
                                            (float) $component['contribution'],
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

<div
    class="modal fade"
    id="admin-match-diagnostic-modal"
    tabindex="-1"
    aria-labelledby="admin-match-diagnostic-title"
    aria-hidden="true"
    data-match-result="<?= $hasResult
                            ? '1'
                            : '0' ?>"
    data-match-error="<?= $hasError
                            ? '1'
                            : '0' ?>">

    <div
        class="modal-dialog
            modal-xl
            modal-dialog-centered
            modal-dialog-scrollable">

        <div class="modal-content">

            <div
                class="modal-header
                    bg-info-subtle
                    py-2">

                <div>

                    <h5
                        class="modal-title"
                        id="admin-match-diagnostic-title">

                        Test Match Score Against Member

                    </h5>

                    <div
                        class="text-muted
                            fs-13">

                        <?= esc(
                            $memberName
                        ) ?>

                        <?php if (
                            $memberReference !== ''
                        ): ?>

                            · <?= esc(
                                    $memberReference
                                ) ?>

                        <?php endif; ?>

                    </div>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>

            </div>

            <div class="modal-body">

                <form
                    method="post"
                    action="<?= esc(
                                route_to(
                                    'admin.members.match-score-diagnostic',
                                    $memberId
                                ),
                                'attr'
                            ) ?>"
                    data-validate
                    data-submit-loader
                    novalidate>

                    <?= csrf_field() ?>

                    <input
                        type="hidden"
                        name="return_context"
                        value="matches">

                    <div class="row g-2 align-items-start">

                        <div class="col-md-9">

                            <label
                                for="admin-match-profile-reference"
                                class="form-label">

                                Profile ID

                            </label>

                            <input
                                type="text"
                                id="admin-match-profile-reference"
                                name="profile_reference"
                                class="form-control<?= isset(
                                                        $diagnosticErrors['profile_reference']
                                                    )
                                                        ? ' is-invalid'
                                                        : '' ?>"
                                maxlength="50"
                                autocomplete="off"
                                value="<?= esc(
                                            $comparisonReference,
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
                                class="btn
                                    btn-primary
                                    fw-medium"
                                data-submit-button>

                                <span
                                    data-submit-idle>

                                    <i
                                        class="ri-calculator-line
                                            me-1"
                                        aria-hidden="true"></i>

                                    Calculate

                                </span>

                                <span
                                    class="d-none"
                                    data-submit-loading>

                                    <span
                                        class="spinner-border
                                            spinner-border-sm
                                            me-1"
                                        aria-hidden="true">
                                    </span>

                                    Calculating...

                                </span>

                            </button>

                        </div>

                    </div>

                </form>

                <?php if ($hasResult): ?>

                    <hr>

                    <div
                        class="alert
                            alert-info
                            border-0">

                        <i
                            class="ri-information-line
                                me-1"
                            aria-hidden="true"></i>

                        Match Score is directional. Both directions are
                        calculated independently using each member's
                        Partner Preferences.

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

    </div>

</div>