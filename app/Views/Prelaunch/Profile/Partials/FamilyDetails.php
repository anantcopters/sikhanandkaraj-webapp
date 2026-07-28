<?php

declare(strict_types=1);

/**
 * Family Details section for the standalone
 * pre-launch profile collection form.
 *
 * @var array<string, string>              $validationErrors
 * @var array<int, array<string, mixed>>   $familyValues
 * @var array<int, array<string, mixed>>   $familyTypes
 * @var array<int, array<string, mixed>>   $familyStatuses
 * @var array<int, array<string, mixed>>   $communities
 * @var array<int, array<string, mixed>>   $subcommunities
 */

$errors = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

$familyValueOptions = is_array($familyValues ?? null)
    ? $familyValues
    : [];

$familyTypeOptions = is_array($familyTypes ?? null)
    ? $familyTypes
    : [];

$familyStatusOptions = is_array(
    $familyStatuses ?? null
)
    ? $familyStatuses
    : [];

$communityOptions = is_array($communities ?? null)
    ? $communities
    : [];

$subcommunityOptions = is_array(
    $subcommunities ?? null
)
    ? $subcommunities
    : [];

$selectedFamilyValueId = (string) old(
    'family_value_id',
    ''
);

$selectedFamilyTypeId = (string) old(
    'family_type_id',
    ''
);

$selectedFamilyStatusId = (string) old(
    'family_status_id',
    ''
);

$selectedCommunityId = (string) old(
    'sikh_community_id',
    ''
);

$selectedSubcommunityId = (string) old(
    'sikh_subcommunity_id',
    ''
);

/*
 * This named route must be publicly accessible because the
 * pre-launch page does not require member authentication.
 */
$subcommunityLookupUrl = route_to(
    'prelaunch.master.subcommunities',
    0
);
?>

<fieldset class="mb-4">
    <legend class="h5 mb-3">
        Family details
    </legend>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label
                for="father_name"
                class="form-label">
                Father’s name

                <span
                    class="text-danger"
                    aria-hidden="true">
                    *
                </span>
            </label>

            <input
                type="text"
                id="father_name"
                name="father_name"
                class="form-control <?= isset(
                                        $errors['father_name']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                value="<?= esc(
                            old('father_name'),
                            'attr'
                        ) ?>"
                minlength="2"
                maxlength="100"
                autocomplete="off"
                required>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['father_name']
                        ?? 'Please enter the father’s name.'
                ) ?>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="mother_name"
                class="form-label">
                Mother’s name

                <span
                    class="text-danger"
                    aria-hidden="true">
                    *
                </span>
            </label>

            <input
                type="text"
                id="mother_name"
                name="mother_name"
                class="form-control <?= isset(
                                        $errors['mother_name']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                value="<?= esc(
                            old('mother_name'),
                            'attr'
                        ) ?>"
                minlength="2"
                maxlength="100"
                autocomplete="off"
                required>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['mother_name']
                        ?? 'Please enter the mother’s name.'
                ) ?>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <label
                for="family_value_id"
                class="form-label">
                Family values

                <span
                    class="text-danger"
                    aria-hidden="true">
                    *
                </span>
            </label>

            <select
                id="family_value_id"
                name="family_value_id"
                class="form-select <?= isset(
                                        $errors['family_value_id']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                required>
                <option value="">
                    Select family values
                </option>

                <?php foreach (
                    $familyValueOptions as $familyValue
                ): ?>
                    <?php
                    if (!is_array($familyValue)) {
                        continue;
                    }

                    $familyValueId = (string) (
                        $familyValue['id'] ?? ''
                    );

                    $familyValueName = (string) (
                        $familyValue['name']
                        ?? $familyValue['label']
                        ?? ''
                    );

                    if (
                        $familyValueId === ''
                        || $familyValueName === ''
                    ) {
                        continue;
                    }
                    ?>

                    <option
                        value="<?= esc(
                                    $familyValueId,
                                    'attr'
                                ) ?>"
                        <?= $selectedFamilyValueId
                            === $familyValueId
                            ? 'selected'
                            : '' ?>>
                        <?= esc($familyValueName) ?>
                    </option>
                <?php endforeach ?>
            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['family_value_id']
                        ?? 'Please select family values.'
                ) ?>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <label
                for="family_type_id"
                class="form-label">
                Family type

                <span
                    class="text-danger"
                    aria-hidden="true">
                    *
                </span>
            </label>

            <select
                id="family_type_id"
                name="family_type_id"
                class="form-select <?= isset(
                                        $errors['family_type_id']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                required>
                <option value="">
                    Select family type
                </option>

                <?php foreach (
                    $familyTypeOptions as $familyType
                ): ?>
                    <?php
                    if (!is_array($familyType)) {
                        continue;
                    }

                    $familyTypeId = (string) (
                        $familyType['id'] ?? ''
                    );

                    $familyTypeName = (string) (
                        $familyType['name']
                        ?? $familyType['label']
                        ?? ''
                    );

                    if (
                        $familyTypeId === ''
                        || $familyTypeName === ''
                    ) {
                        continue;
                    }
                    ?>

                    <option
                        value="<?= esc(
                                    $familyTypeId,
                                    'attr'
                                ) ?>"
                        <?= $selectedFamilyTypeId
                            === $familyTypeId
                            ? 'selected'
                            : '' ?>>
                        <?= esc($familyTypeName) ?>
                    </option>
                <?php endforeach ?>
            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['family_type_id']
                        ?? 'Please select family type.'
                ) ?>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <label
                for="family_status_id"
                class="form-label">
                Family status

                <span
                    class="text-danger"
                    aria-hidden="true">
                    *
                </span>
            </label>

            <select
                id="family_status_id"
                name="family_status_id"
                class="form-select <?= isset(
                                        $errors['family_status_id']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                required>
                <option value="">
                    Select family status
                </option>

                <?php foreach (
                    $familyStatusOptions as $familyStatus
                ): ?>
                    <?php
                    if (!is_array($familyStatus)) {
                        continue;
                    }

                    $familyStatusId = (string) (
                        $familyStatus['id'] ?? ''
                    );

                    $familyStatusName = (string) (
                        $familyStatus['name']
                        ?? $familyStatus['label']
                        ?? ''
                    );

                    if (
                        $familyStatusId === ''
                        || $familyStatusName === ''
                    ) {
                        continue;
                    }
                    ?>

                    <option
                        value="<?= esc(
                                    $familyStatusId,
                                    'attr'
                                ) ?>"
                        <?= $selectedFamilyStatusId
                            === $familyStatusId
                            ? 'selected'
                            : '' ?>>
                        <?= esc($familyStatusName) ?>
                    </option>
                <?php endforeach ?>
            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['family_status_id']
                        ?? 'Please select family status.'
                ) ?>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="sikh_community_id"
                class="form-label">
                Community

                <span
                    class="text-danger"
                    aria-hidden="true">
                    *
                </span>
            </label>

            <select
                id="sikh_community_id"
                name="sikh_community_id"
                class="form-select <?= isset(
                                        $errors['sikh_community_id']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                data-subcommunity-url-template="<?= esc(
                                                    $subcommunityLookupUrl,
                                                    'attr'
                                                ) ?>"
                required>
                <option value="">
                    Select community
                </option>

                <?php foreach (
                    $communityOptions as $community
                ): ?>
                    <?php
                    if (!is_array($community)) {
                        continue;
                    }

                    $communityId = (string) (
                        $community['id'] ?? ''
                    );

                    $communityName = (string) (
                        $community['name']
                        ?? $community['label']
                        ?? ''
                    );

                    if (
                        $communityId === ''
                        || $communityName === ''
                    ) {
                        continue;
                    }
                    ?>

                    <option
                        value="<?= esc(
                                    $communityId,
                                    'attr'
                                ) ?>"
                        <?= $selectedCommunityId
                            === $communityId
                            ? 'selected'
                            : '' ?>>
                        <?= esc($communityName) ?>
                    </option>
                <?php endforeach ?>
            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['sikh_community_id']
                        ?? 'Please select community.'
                ) ?>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="sikh_subcommunity_id"
                class="form-label">
                Sub-community

                <span
                    class="text-danger"
                    aria-hidden="true">
                    *
                </span>
            </label>

            <select
                id="sikh_subcommunity_id"
                name="sikh_subcommunity_id"
                class="form-select <?= isset(
                                        $errors['sikh_subcommunity_id']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                data-selected-value="<?= esc(
                                            $selectedSubcommunityId,
                                            'attr'
                                        ) ?>"
                <?= $selectedCommunityId === ''
                    ? 'disabled'
                    : '' ?>
                required>
                <option value="">
                    <?= $selectedCommunityId === ''
                        ? 'Select community first'
                        : 'Select sub-community' ?>
                </option>

                <?php foreach (
                    $subcommunityOptions as $subcommunity
                ): ?>
                    <?php
                    if (!is_array($subcommunity)) {
                        continue;
                    }

                    $subcommunityId = (string) (
                        $subcommunity['id'] ?? ''
                    );

                    $subcommunityName = (string) (
                        $subcommunity['name']
                        ?? $subcommunity['label']
                        ?? ''
                    );

                    if (
                        $subcommunityId === ''
                        || $subcommunityName === ''
                    ) {
                        continue;
                    }
                    ?>

                    <option
                        value="<?= esc(
                                    $subcommunityId,
                                    'attr'
                                ) ?>"
                        <?= $selectedSubcommunityId
                            === $subcommunityId
                            ? 'selected'
                            : '' ?>>
                        <?= esc($subcommunityName) ?>
                    </option>
                <?php endforeach ?>
            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['sikh_subcommunity_id']
                        ?? 'Please select sub-community.'
                ) ?>
            </div>
        </div>
    </div>
</fieldset>