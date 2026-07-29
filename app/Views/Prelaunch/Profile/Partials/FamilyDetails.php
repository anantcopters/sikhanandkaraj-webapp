<?php

declare(strict_types=1);

/**
 * @var array<string, string>|null            $validationErrors
 * @var array<int, array<string, mixed>>|null $familyValues
 * @var array<int, array<string, mixed>>|null $familyTypes
 * @var array<int, array<string, mixed>>|null $familyStatuses
 * @var array<int, array<string, mixed>>|null $communities
 * @var array<int, array<string, mixed>>|null $subcommunities
 */

$errorBag = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

$familyValueOptions = is_array(
    $familyValues ?? null
)
    ? $familyValues
    : [];

$familyTypeOptions = is_array(
    $familyTypes ?? null
)
    ? $familyTypes
    : [];

$familyStatusOptions = is_array(
    $familyStatuses ?? null
)
    ? $familyStatuses
    : [];

$communityOptions = is_array(
    $communities ?? null
)
    ? $communities
    : [];

$subcommunityOptions = is_array(
    $subcommunities ?? null
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

$familyValueId = (string) old(
    'family_value_id',
    ''
);

$familyTypeId = (string) old(
    'family_type_id',
    ''
);

$familyStatusId = (string) old(
    'family_status_id',
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

$familyValueError = trim(
    (string) (
        $errorBag['family_value_id']
        ?? ''
    )
);

$familyTypeError = trim(
    (string) (
        $errorBag['family_type_id']
        ?? ''
    )
);

$familyStatusError = trim(
    (string) (
        $errorBag['family_status_id']
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

$gotraClass = $gotraError !== ''
    ? 'is-invalid'
    : '';

$familyValueClass =
    $familyValueError !== ''
    ? 'is-invalid'
    : '';

$familyTypeClass =
    $familyTypeError !== ''
    ? 'is-invalid'
    : '';

$familyStatusClass =
    $familyStatusError !== ''
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
                    Add parent names, family background,
                    community and gotra information.
                </p>
            </div>
        </div>

        <hr class="my-2 mb-2">
        </hr>
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
                    minlength="2"
                    maxlength="100"
                    required>

                <?php if (
                    $fatherNameError !== ''
                ): ?>
                    <div class="invalid-feedback">
                        <?= esc($fatherNameError) ?>
                    </div>
                <?php endif ?>
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
                    minlength="2"
                    maxlength="100"
                    required>

                <?php if (
                    $motherNameError !== ''
                ): ?>
                    <div class="invalid-feedback">
                        <?= esc($motherNameError) ?>
                    </div>
                <?php endif ?>
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
                    maxlength="100">

                <?php if ($gotraError !== ''): ?>
                    <div class="invalid-feedback">
                        <?= esc($gotraError) ?>
                    </div>
                <?php endif ?>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="family_value_id"
                    class="form-label">
                    Family values
                </label>

                <select
                    id="family_value_id"
                    name="family_value_id"
                    class="form-select <?= esc(
                                            $familyValueClass,
                                            'attr'
                                        ) ?>"
                    required>
                    <option value="">
                        Select family values
                    </option>

                    <?php foreach (
                        $familyValueOptions as
                        $familyValueOption
                    ): ?>
                        <?php
                        if (!is_array(
                            $familyValueOption
                        )) {
                            continue;
                        }

                        $optionId = (string) (
                            $familyValueOption['id']
                            ?? ''
                        );

                        $optionName = trim(
                            (string) (
                                $familyValueOption['name']
                                ?? $familyValueOption['label']
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
                            $familyValueId
                            === $optionId;
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

                <?php if (
                    $familyValueError !== ''
                ): ?>
                    <div class="invalid-feedback">
                        <?= esc($familyValueError) ?>
                    </div>
                <?php endif ?>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="family_type_id"
                    class="form-label">
                    Family type
                </label>

                <select
                    id="family_type_id"
                    name="family_type_id"
                    class="form-select <?= esc(
                                            $familyTypeClass,
                                            'attr'
                                        ) ?>"
                    required>
                    <option value="">
                        Select family type
                    </option>

                    <?php foreach (
                        $familyTypeOptions as
                        $familyTypeOption
                    ): ?>
                        <?php
                        if (!is_array(
                            $familyTypeOption
                        )) {
                            continue;
                        }

                        $optionId = (string) (
                            $familyTypeOption['id']
                            ?? ''
                        );

                        $optionName = trim(
                            (string) (
                                $familyTypeOption['name']
                                ?? $familyTypeOption['label']
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
                            $familyTypeId
                            === $optionId;
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

                <?php if (
                    $familyTypeError !== ''
                ): ?>
                    <div class="invalid-feedback">
                        <?= esc($familyTypeError) ?>
                    </div>
                <?php endif ?>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="family_status_id"
                    class="form-label">
                    Family status
                </label>

                <select
                    id="family_status_id"
                    name="family_status_id"
                    class="form-select <?= esc(
                                            $familyStatusClass,
                                            'attr'
                                        ) ?>"
                    required>
                    <option value="">
                        Select family status
                    </option>

                    <?php foreach (
                        $familyStatusOptions as
                        $familyStatusOption
                    ): ?>
                        <?php
                        if (!is_array(
                            $familyStatusOption
                        )) {
                            continue;
                        }

                        $optionId = (string) (
                            $familyStatusOption['id']
                            ?? ''
                        );

                        $optionName = trim(
                            (string) (
                                $familyStatusOption['name']
                                ?? $familyStatusOption['label']
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
                            $familyStatusId
                            === $optionId;
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

                <?php if (
                    $familyStatusError !== ''
                ): ?>
                    <div class="invalid-feedback">
                        <?= esc($familyStatusError) ?>
                    </div>
                <?php endif ?>
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
                    data-choice-search="true" data-choice-position="bottom"
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
                        if (!is_array($communityOption)) {
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
                            $subcommunityId
                            === $optionId;
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