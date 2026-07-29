<?php

declare(strict_types=1);

/**
 * Prelaunch family details.
 *
 * @var array<string, string>|null            $validationErrors
 * @var array<int, array<string, mixed>>|null $communities
 * @var array<int, array<string, mixed>>|null $subcommunities
 */

$errorBag = is_array(
    $validationErrors
        ?? null
)
    ? $validationErrors
    : [];

$communityOptions = is_array(
    $communities
        ?? null
)
    ? $communities
    : [];

$subcommunityOptions = is_array(
    $subcommunities
        ?? null
)
    ? $subcommunities
    : [];

$fatherName = (string) old(
    'father_name',
    ''
);

$motherName = (string) old(
    'mother_name',
    ''
);

$gotra = (string) old(
    'gotra',
    ''
);

$communityId = (string) old(
    'sikh_community_id',
    ''
);

$subcommunityId = (string) old(
    'sikh_subcommunity_id',
    ''
);

$fatherNameError = trim(
    (string) (
        $errorBag['father_name']
        ?? ''
    )
);

$motherNameError = trim(
    (string) (
        $errorBag['mother_name']
        ?? ''
    )
);

$gotraError = trim(
    (string) (
        $errorBag['gotra']
        ?? ''
    )
);

$communityError = trim(
    (string) (
        $errorBag['sikh_community_id']
        ?? ''
    )
);

$subcommunityError = trim(
    (string) (
        $errorBag['sikh_subcommunity_id']
        ?? ''
    )
);

$fatherNameClass =
    $fatherNameError !== ''
    ? 'is-invalid'
    : '';

$motherNameClass =
    $motherNameError !== ''
    ? 'is-invalid'
    : '';

$gotraClass =
    $gotraError !== ''
    ? 'is-invalid'
    : '';

$communityClass =
    $communityError !== ''
    ? 'is-invalid'
    : '';

$subcommunityClass =
    $subcommunityError !== ''
    ? 'is-invalid'
    : '';

$subcommunityRouteTemplate = route_to(
    'prelaunch.master.subcommunities',
    0
);
?>

<div class="card border border-danger border-opacity-25 shadow-sm mb-3">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-start gap-3 mb-3">
            <div class="fs-3 text-primary">
                <i
                    class="ri-home-heart-line"
                    aria-hidden="true"></i>
            </div>

            <div>
                <h5 class="mb-1 fs-14 fw-semibold">
                    Family details
                </h5>

                <p class="text-muted mb-0 fs-12">
                    Add parent names, community and gotra
                    information.
                </p>
            </div>
        </div>

        <hr class="my-2 mb-2">

        <div class="row g-3 pt-2">
            <div class="col-12 col-md-6">
                <label
                    for="father_name"
                    class="form-label">
                    Father’s name
                </label>

                <input
                    type="text"
                    id="father_name"
                    name="father_name"
                    class="form-control <?= esc(
                                            $fatherNameClass,
                                            'attr'
                                        ) ?>"
                    value="<?= esc(
                                $fatherName,
                                'attr'
                            ) ?>"
                    aria-describedby="father_nameError"
                    placeholder="Enter father’s name"
                    minlength="2"
                    maxlength="100"
                    autocomplete="off"
                    data-error-required="Please enter father’s name."
                    data-error-minlength="Father’s name must contain at least 2 characters."
                    data-error-maxlength="Father’s name cannot exceed 100 characters."
                    required>

                <div
                    id="father_nameError"
                    class="invalid-feedback"
                    data-validation-error="father_name">
                    <?= esc($fatherNameError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="mother_name"
                    class="form-label">
                    Mother’s name
                </label>

                <input
                    type="text"
                    id="mother_name"
                    name="mother_name"
                    class="form-control <?= esc(
                                            $motherNameClass,
                                            'attr'
                                        ) ?>"
                    value="<?= esc(
                                $motherName,
                                'attr'
                            ) ?>"
                    aria-describedby="mother_nameError"
                    placeholder="Enter mother’s name"
                    minlength="2"
                    maxlength="100"
                    autocomplete="off"
                    data-error-required="Please enter mother’s name."
                    data-error-minlength="Mother’s name must contain at least 2 characters."
                    data-error-maxlength="Mother’s name cannot exceed 100 characters."
                    required>

                <div
                    id="mother_nameError"
                    class="invalid-feedback"
                    data-validation-error="mother_name">
                    <?= esc($motherNameError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="gotra"
                    class="form-label">
                    Gotra
                </label>

                <input
                    type="text"
                    id="gotra"
                    name="gotra"
                    class="form-control <?= esc(
                                            $gotraClass,
                                            'attr'
                                        ) ?>"
                    value="<?= esc(
                                $gotra,
                                'attr'
                            ) ?>"
                    aria-describedby="gotraError"
                    placeholder="Enter gotra"
                    minlength="2"
                    maxlength="100"
                    autocomplete="off"
                    data-error-required="Please enter gotra."
                    data-error-minlength="Gotra must contain at least 2 characters."
                    data-error-maxlength="Gotra cannot exceed 100 characters."
                    data-error-pattern="Gotra may contain letters, spaces, apostrophes, full stops and hyphens only."
                    required>

                <div
                    id="gotraError"
                    class="invalid-feedback"
                    data-validation-error="gotra">
                    <?= esc($gotraError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="sikh_community_id"
                    class="form-label">
                    Community
                </label>

                <select
                    id="sikh_community_id"
                    name="sikh_community_id"
                    class="form-select <?= esc(
                                            $communityClass,
                                            'attr'
                                        ) ?>"
                    data-subcommunity-url-template="<?= esc(
                                                        $subcommunityRouteTemplate,
                                                        'attr'
                                                    ) ?>"
                    data-choice
                    data-choices
                    data-choice-search="true"
                    data-choice-position="bottom"
                    data-error-required="Please select your community."
                    required>
                    <option value="">
                        Select community
                    </option>

                    <?php foreach (
                        $communityOptions as
                        $communityOption
                    ): ?>
                        <?php
                        if (!is_array(
                            $communityOption
                        )) {
                            continue;
                        }

                        $optionId = (string) (
                            $communityOption['id']
                            ?? ''
                        );

                        $optionName = trim(
                            (string) (
                                $communityOption['name']
                                ?? $communityOption['label']
                                ?? ''
                            )
                        );

                        if (
                            $optionId === ''
                            || $optionName === ''
                        ) {
                            continue;
                        }

                        $optionSelected =
                            $communityId === $optionId;
                        ?>

                        <option
                            value="<?= esc(
                                        $optionId,
                                        'attr'
                                    ) ?>"
                            <?= $optionSelected
                                ? 'selected'
                                : '' ?>>
                            <?= esc($optionName) ?>
                        </option>
                    <?php endforeach ?>
                </select>

                <div
                    id="sikh_community_idError"
                    class="invalid-feedback"
                    data-validation-error="sikh_community_id">
                    <?= esc($communityError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="sikh_subcommunity_id"
                    class="form-label">
                    Sub-community
                </label>

                <select
                    id="sikh_subcommunity_id"
                    name="sikh_subcommunity_id"
                    class="form-select <?= esc(
                                            $subcommunityClass,
                                            'attr'
                                        ) ?>"
                    data-selected-value="<?= esc(
                                                $subcommunityId,
                                                'attr'
                                            ) ?>"
                    data-choice
                    data-choices
                    data-choice-search="true"
                    data-choice-position="bottom"
                    data-error-required="Please select your sub-community."
                    required>
                    <option value="">
                        Select sub-community
                    </option>

                    <?php foreach (
                        $subcommunityOptions as
                        $subcommunityOption
                    ): ?>
                        <?php
                        if (!is_array(
                            $subcommunityOption
                        )) {
                            continue;
                        }

                        $optionId = (string) (
                            $subcommunityOption['id']
                            ?? ''
                        );

                        $optionName = trim(
                            (string) (
                                $subcommunityOption['name']
                                ?? $subcommunityOption['label']
                                ?? ''
                            )
                        );

                        if (
                            $optionId === ''
                            || $optionName === ''
                        ) {
                            continue;
                        }

                        $optionSelected =
                            $subcommunityId === $optionId;
                        ?>

                        <option
                            value="<?= esc(
                                        $optionId,
                                        'attr'
                                    ) ?>"
                            <?= $optionSelected
                                ? 'selected'
                                : '' ?>>
                            <?= esc($optionName) ?>
                        </option>
                    <?php endforeach ?>
                </select>

                <div
                    id="sikh_subcommunity_idError"
                    class="invalid-feedback"
                    data-validation-error="sikh_subcommunity_id">
                    <?= esc($subcommunityError) ?>
                </div>
            </div>
        </div>
    </div>
</div>