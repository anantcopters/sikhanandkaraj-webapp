<?php

declare(strict_types=1);

/**
 * @var string               $modalId
 * @var array<string, mixed> $profile
 */

$modalId = trim(
    (string) (
        $modalId
        ?? ''
    )
);

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

$reference = trim(
    (string) (
        $profile['referenceId']
        ?? ''
    )
);

$matchScore = max(
    0.0,
    min(
        100.0,
        (float) (
            $profile['matchScore']
            ?? 0
        )
    )
);

$partnerPreference = max(
    0.0,
    min(
        100.0,
        (float) (
            $profile['partnerPreferencePercentage']
            ?? 0
        )
    )
);

$components =
    isset(
        $profile['matchScoreComponents']
    )
    && is_array(
        $profile['matchScoreComponents']
    )
    ? $profile['matchScoreComponents']
    : [];

$contributions =
    isset(
        $profile['matchScoreContributions']
    )
    && is_array(
        $profile['matchScoreContributions']
    )
    ? $profile['matchScoreContributions']
    : [];
?>

<div
    class="modal fade"
    id="<?= esc(
            $modalId,
            'attr'
        ) ?>"
    tabindex="-1"
    aria-hidden="true">

    <div
        class="modal-dialog
            modal-lg
            modal-dialog-centered
            modal-dialog-scrollable">

        <div class="modal-content">

            <div
                class="modal-header
                    bg-info-subtle
                    py-2">

                <div>

                    <h2
                        class="modal-title
                            fs-17">

                        Match Score

                    </h2>

                    <p
                        class="text-muted
                            fs-12 mb-0">

                        <?= esc($name) ?>

                        <?php if (
                            $reference !== ''
                        ): ?>

                            · <?= esc(
                                    $reference
                                ) ?>

                        <?php endif; ?>

                    </p>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>

            </div>

            <div class="modal-body">

                <div class="row g-3 mb-3">

                    <div class="col-6">

                        <div
                            class="border
                                rounded-3
                                p-3
                                text-center">

                            <div
                                class="text-muted
                                    fs-12">

                                Match Score

                            </div>

                            <div
                                class="fs-4
                                    fw-semibold
                                    text-primary">

                                <?= esc(
                                    number_format(
                                        $matchScore,
                                        2
                                    )
                                ) ?>%

                            </div>

                        </div>

                    </div>

                    <div class="col-6">

                        <div
                            class="border
                                rounded-3
                                p-3
                                text-center">

                            <div
                                class="text-muted
                                    fs-12">

                                Partner Preference

                            </div>

                            <div
                                class="fs-4
                                    fw-semibold
                                    text-success">

                                <?= esc(
                                    number_format(
                                        $partnerPreference,
                                        2
                                    )
                                ) ?>%

                            </div>

                        </div>

                    </div>

                </div>

                <?php
                $rows = [
                    'Partner Preference' => [
                        'score' =>
                        $components['preference'] ?? 0,

                        'contribution' =>
                        $contributions['preference'] ?? 0,
                    ],

                    'Profile Completion' => [
                        'score' =>
                        $components['profileCompletion'] ?? 0,

                        'contribution' =>
                        $contributions['profileCompletion'] ?? 0,
                    ],

                    'Approved Photos' => [
                        'score' =>
                        $components['approvedPhotos'] ?? 0,

                        'contribution' =>
                        $contributions['approvedPhotos'] ?? 0,
                    ],

                    'Trust' => [
                        'score' =>
                        $components['trust'] ?? 0,

                        'contribution' =>
                        $contributions['trust'] ?? 0,
                    ],

                    'Membership Priority' => [
                        'score' =>
                        $components['commercial'] ?? 0,

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
                                    Contribution
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $rows
                                as $label => $row
                            ): ?>

                                <tr>

                                    <td>
                                        <?= esc(
                                            $label
                                        ) ?>
                                    </td>

                                    <td class="text-end">

                                        <?= esc(
                                            number_format(
                                                (float) (
                                                    $row['score']
                                                    ?? 0
                                                ),
                                                2
                                            )
                                        ) ?>%

                                    </td>

                                    <td
                                        class="text-end
                                            fw-medium">

                                        <?= esc(
                                            number_format(
                                                (float) (
                                                    $row['contribution']
                                                    ?? 0
                                                ),
                                                2
                                            )
                                        ) ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-light"
                    data-bs-dismiss="modal">

                    Close

                </button>

            </div>

        </div>

    </div>

</div>