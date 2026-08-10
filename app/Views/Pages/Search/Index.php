<?php

declare(strict_types=1);

/**
 * Member Search-form UI variables.
 *
 * @var string                     $pageTitle
 * @var string                     $mode
 * @var array<string, mixed>       $filters
 * @var array<string, mixed>       $masterData
 * @var array<string, string>|null $formAlert
 */

/*
 * --------------------------------------------------------------------------
 * Normalize view-local variables
 * --------------------------------------------------------------------------
 */

$pageTitle =
    isset($pageTitle)
    && is_string($pageTitle)
    && trim($pageTitle) !== ''
    ? trim($pageTitle)
    : 'Search Profiles';

$mode =
    isset($mode)
    && $mode === 'advanced'
    ? 'advanced'
    : 'basic';

$filters =
    isset($filters)
    && is_array($filters)
    ? $filters
    : [];

$masterData =
    isset($masterData)
    && is_array($masterData)
    ? $masterData
    : [];

$formAlert =
    isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

/*
 * Normalize one multi-value Search filter.
 */
$selected =
    static function (
        string $key
    ) use (
        $filters
    ): array {
        $value =
            $filters[$key]
            ?? [];

        return is_array($value)
            ? array_values(
                $value
            )
            : [];
    };

/*
 * Resolve master collections once before HTML rendering.
 */
$heights =
    isset(
        $masterData['heights']
    )
    && is_array(
        $masterData['heights']
    )
    ? $masterData['heights']
    : [];

$maritalStatuses =
    isset(
        $masterData['maritalStatuses']
    )
    && is_array(
        $masterData['maritalStatuses']
    )
    ? $masterData['maritalStatuses']
    : [];

$states =
    isset(
        $masterData['states']
    )
    && is_array(
        $masterData['states']
    )
    ? $masterData['states']
    : [];

$cities =
    isset(
        $masterData['cities']
    )
    && is_array(
        $masterData['cities']
    )
    ? $masterData['cities']
    : [];

$communities =
    isset(
        $masterData['communities']
    )
    && is_array(
        $masterData['communities']
    )
    ? $masterData['communities']
    : [];

$educationGroups =
    isset(
        $masterData['educationGroups']
    )
    && is_array(
        $masterData['educationGroups']
    )
    ? $masterData['educationGroups']
    : [];

$employmentTypes =
    isset(
        $masterData['employmentTypes']
    )
    && is_array(
        $masterData['employmentTypes']
    )
    ? $masterData['employmentTypes']
    : [];

$occupationGroups =
    isset(
        $masterData['occupationGroups']
    )
    && is_array(
        $masterData['occupationGroups']
    )
    ? $masterData['occupationGroups']
    : [];

$annualIncomes =
    isset(
        $masterData['annualIncomes']
    )
    && is_array(
        $masterData['annualIncomes']
    )
    ? $masterData['annualIncomes']
    : [];

$profileManagedBy =
    isset(
        $masterData['profileManagedBy']
    )
    && is_array(
        $masterData['profileManagedBy']
    )
    ? $masterData['profileManagedBy']
    : [];

$lifestyleCategories =
    isset(
        $masterData['lifestyleCategories']
    )
    && is_array(
        $masterData['lifestyleCategories']
    )
    ? $masterData['lifestyleCategories']
    : [];

$lifestyleOptionsByCategory =
    isset(
        $masterData['lifestyleOptionsByCategory']
    )
    && is_array(
        $masterData['lifestyleOptionsByCategory']
    )
    ? $masterData['lifestyleOptionsByCategory']
    : [];

$this->extend(
    'Layouts/Main'
);

$this->section(
    'content'
);
?>

<section class="py-3 py-lg-4">
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-12 col-xl-9">

                <!-- =====================================================
                     Feedback
                     ===================================================== -->

                <?= view(
                    'Pages/Profile/Partials/_feedback_alert',
                    [
                        'formAlert' =>
                        $formAlert,
                    ]
                ) ?>

                <!-- =====================================================
                     Universal Profile-ID Search
                     ===================================================== -->

                <div
                    class="card border border-danger
                        border-opacity-25 shadow-sm mb-4">

                    <div class="card-body p-3 p-md-4">

                        <form
                            action="<?= url_to(
                                        'web.search.profile'
                                    ) ?>"
                            method="get">

                            <div
                                class="d-flex flex-column
                                    flex-md-row
                                    align-items-md-end gap-2">

                                <div class="flex-grow-1">

                                    <label
                                        for="profileId"
                                        class="form-labelm">

                                        Search by Profile ID
                                    </label>

                                    <input
                                        type="text"
                                        id="profileId"
                                        name="profile_id"
                                        maxlength="50"
                                        class="form-control"
                                        placeholder="Enter Profile ID"
                                        autocomplete="off">

                                </div>

                                <div>
                                    <button
                                        type="submit"
                                        class="btn btn-primary">

                                        <i
                                            class="ri-search-line me-1"
                                            aria-hidden="true">
                                        </i>

                                        Search
                                    </button>
                                </div>

                            </div>

                        </form>

                    </div>
                </div>

                <!-- =====================================================
                     Search card
                     ===================================================== -->

                <div
                    class="card border border-danger
                        border-opacity-25 shadow-sm">

                    <div class="card-body p-3 p-md-4">

                        <!-- =================================================
                             Search mode
                             ================================================= -->

                        <ul
                            class="nav nav-tabs mb-4"
                            role="tablist">

                            <li class="nav-item">
                                <a
                                    href="<?= esc(
                                                route_to(
                                                    'web.search'
                                                )
                                                    . '?mode=basic',
                                                'attr'
                                            ) ?>"
                                    class="nav-link
                                        <?= $mode === 'basic'
                                            ? 'active bg-success-subtle'
                                            : '' ?>">

                                    Basic Search
                                </a>
                            </li>

                            <li class="nav-item">
                                <a
                                    href="<?= esc(
                                                route_to(
                                                    'web.search'
                                                )
                                                    . '?mode=advanced',
                                                'attr'
                                            ) ?>"
                                    class="nav-link
                                        <?= $mode === 'advanced'
                                            ? 'active bg-success-subtle'
                                            : '' ?>">

                                    Advanced Search
                                </a>
                            </li>

                        </ul>

                        <form
                            method="get"
                            action="<?= url_to(
                                        'web.search.results'
                                    ) ?>"
                            id="memberSearchForm">

                            <input
                                type="hidden"
                                name="mode"
                                value="<?= esc(
                                            $mode,
                                            'attr'
                                        ) ?>">

                            <!-- =============================================
                                 Age
                                 Exact Partner Preference pattern.
                                 ============================================= -->

                            <div class="row g-3">

                                <div class="col-12 col-sm-6">

                                    <label
                                        for="ageFrom"
                                        class="form-labelm">

                                        Age From
                                    </label>

                                    <select
                                        id="ageFrom"
                                        name="age_min"
                                        class="form-select"
                                        data-choice
                                        data-choice-search="false">

                                        <option value="">
                                            Any
                                        </option>

                                        <?php for (
                                            $age = 18;
                                            $age <= 80;
                                            ++$age
                                        ): ?>

                                            <option
                                                value="<?= esc(
                                                            (string) $age,
                                                            'attr'
                                                        ) ?>"
                                                <?= (string) (
                                                    $filters['age_min']
                                                    ?? ''
                                                )
                                                    === (string) $age
                                                    ? 'selected'
                                                    : '' ?>>

                                                <?= esc(
                                                    (string) $age
                                                ) ?>

                                            </option>

                                        <?php endfor; ?>

                                    </select>

                                </div>

                                <div class="col-12 col-sm-6">

                                    <label
                                        for="ageTo"
                                        class="form-labelm">

                                        Age To
                                    </label>

                                    <select
                                        id="ageTo"
                                        name="age_max"
                                        class="form-select"
                                        data-choice
                                        data-choice-search="false">

                                        <option value="">
                                            Any
                                        </option>

                                        <?php for (
                                            $age = 18;
                                            $age <= 80;
                                            ++$age
                                        ): ?>

                                            <option
                                                value="<?= esc(
                                                            (string) $age,
                                                            'attr'
                                                        ) ?>"
                                                <?= (string) (
                                                    $filters['age_max']
                                                    ?? ''
                                                )
                                                    === (string) $age
                                                    ? 'selected'
                                                    : '' ?>>

                                                <?= esc(
                                                    (string) $age
                                                ) ?>

                                            </option>

                                        <?php endfor; ?>

                                    </select>

                                </div>

                                <!-- =========================================
                                     Height
                                     Exact Partner Preference pattern.
                                     ========================================= -->

                                <?php
                                $heightFields = [
                                    'height_min_id' => [
                                        'id' =>
                                        'heightFrom',

                                        'label' =>
                                        'Height From',

                                        'placeholder' =>
                                        'Any height',
                                    ],

                                    'height_max_id' => [
                                        'id' =>
                                        'heightTo',

                                        'label' =>
                                        'Height To',

                                        'placeholder' =>
                                        'Any height',
                                    ],
                                ];
                                ?>

                                <?php foreach (
                                    $heightFields
                                    as $field => $configuration
                                ): ?>

                                    <div class="col-12 col-sm-6">

                                        <label
                                            for="<?= esc(
                                                        $configuration['id'],
                                                        'attr'
                                                    ) ?>"
                                            class="form-labelm">

                                            <?= esc(
                                                $configuration['label']
                                            ) ?>

                                        </label>

                                        <select
                                            id="<?= esc(
                                                    $configuration['id'],
                                                    'attr'
                                                ) ?>"
                                            name="<?= esc(
                                                        $field,
                                                        'attr'
                                                    ) ?>"
                                            class="form-select"
                                            data-choice
                                            data-choice-search="true"
                                            data-choice-search-placeholder="Search height"
                                            data-choice-position="bottom">

                                            <option value="">
                                                <?= esc(
                                                    $configuration['placeholder']
                                                ) ?>
                                            </option>

                                            <?php foreach (
                                                $heights
                                                as $height
                                            ): ?>

                                                <?php
                                                $heightId =
                                                    (int) (
                                                        $height['id']
                                                        ?? 0
                                                    );

                                                $heightName =
                                                    trim(
                                                        (string) (
                                                            $height['display_name']
                                                            ?? ''
                                                        )
                                                    );

                                                if (
                                                    $heightId <= 0
                                                    || $heightName === ''
                                                ) {
                                                    continue;
                                                }
                                                ?>

                                                <option
                                                    value="<?= esc(
                                                                (string)
                                                                $heightId,
                                                                'attr'
                                                            ) ?>"
                                                    <?= (int) (
                                                        $filters[$field]
                                                        ?? 0
                                                    ) === $heightId
                                                        ? 'selected'
                                                        : '' ?>>

                                                    <?= esc(
                                                        $heightName
                                                    ) ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>

                                <?php endforeach; ?>

                                <!-- =========================================
                                     Marital Status
                                     ========================================= -->

                                <div class="col-12 col-md-6">

                                    <label
                                        for="searchMaritalStatus"
                                        class="form-labelm">

                                        Marital Status
                                    </label>

                                    <select
                                        id="searchMaritalStatus"
                                        name="marital_status_ids[]"
                                        class="form-select"
                                        data-choice
                                        data-choice-search="true"
                                        data-choice-position="bottom"
                                        multiple>

                                        <?php foreach (
                                            $maritalStatuses
                                            as $status
                                        ): ?>

                                            <?php
                                            $statusId =
                                                (int) (
                                                    $status['id']
                                                    ?? 0
                                                );
                                            ?>

                                            <option
                                                value="<?= esc(
                                                            (string)
                                                            $statusId,
                                                            'attr'
                                                        ) ?>"
                                                <?= in_array(
                                                    $statusId,
                                                    array_map(
                                                        'intval',
                                                        $selected(
                                                            'marital_status_ids'
                                                        )
                                                    ),
                                                    true
                                                )
                                                    ? 'selected'
                                                    : '' ?>>

                                                <?= esc(
                                                    (string) (
                                                        $status['name']
                                                        ?? ''
                                                    )
                                                ) ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>

                                <!-- =========================================
                                     State Living In
                                     Reuse Partner Preference "Any".
                                     ========================================= -->

                                <?= view(
                                    'Pages/PartnerPreference/Additional/_multi_select',
                                    [
                                        'field' =>
                                        'state_ids',

                                        'label' =>
                                        'State Living In',

                                        'placeholder' =>
                                        '',

                                        'options' =>
                                        $states,

                                        'optionValueKey' =>
                                        'id',

                                        'optionLabelKey' =>
                                        'name',

                                        'selectedValues' =>
                                        $selected(
                                            'state_ids'
                                        ),

                                        'showSelectAll' =>
                                        true,

                                        'required' =>
                                        false,

                                        'columnClass' =>
                                        'col-12 col-md-6',

                                        'errors' =>
                                        [],
                                    ]
                                ) ?>

                                <!-- =========================================
                                     Photo Settings
                                     Single-row presentation.
                                     ========================================= -->

                                <div class="col-12">

                                    <div
                                        class="d-flex flex-wrap
                                            align-items-center gap-3">

                                        <label
                                            class="form-labelm mb-0">

                                            Photo Settings
                                        </label>

                                        <div class="form-check mb-0">

                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                id="photoPublic"
                                                name="photo_visibility[]"
                                                value="PUBLIC"
                                                <?= in_array(
                                                    'PUBLIC',
                                                    $selected(
                                                        'photo_visibility'
                                                    ),
                                                    true
                                                )
                                                    ? 'checked'
                                                    : '' ?>>

                                            <label
                                                class="form-check-label"
                                                for="photoPublic">

                                                Public
                                            </label>

                                        </div>

                                        <div class="form-check mb-0">

                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                id="photoInterested"
                                                name="photo_visibility[]"
                                                value="INTERESTED_MEMBERS"
                                                <?= in_array(
                                                    'INTERESTED_MEMBERS',
                                                    $selected(
                                                        'photo_visibility'
                                                    ),
                                                    true
                                                )
                                                    ? 'checked'
                                                    : '' ?>>

                                            <label
                                                class="form-check-label"
                                                for="photoInterested">

                                                Visible to Interested Members
                                            </label>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <?php if (
                                $mode === 'advanced'
                            ): ?>

                                <!-- =========================================
                                     Advanced Search
                                     ========================================= -->

                                <hr class="my-4">

                                <div class="row g-3">

                                    <!-- Community -->

                                    <?= view(
                                        'Pages/PartnerPreference/Additional/_multi_select',
                                        [
                                            'field' =>
                                            'community_ids',

                                            'label' =>
                                            'Community',

                                            'placeholder' =>
                                            '',

                                            'options' =>
                                            $communities,

                                            'optionValueKey' =>
                                            'id',

                                            'optionLabelKey' =>
                                            'name',

                                            'selectedValues' =>
                                            $selected(
                                                'community_ids'
                                            ),

                                            'showSelectAll' =>
                                            true,

                                            'required' =>
                                            false,

                                            'columnClass' =>
                                            'col-12 col-md-6',

                                            'errors' =>
                                            [],
                                        ]
                                    ) ?>

                                    <!-- City -->

                                    <?= view(
                                        'Pages/PartnerPreference/Additional/_multi_select',
                                        [
                                            'field' =>
                                            'city_ids',

                                            'label' =>
                                            'City Living In',

                                            'placeholder' =>
                                            '',

                                            'options' =>
                                            $cities,

                                            'optionValueKey' =>
                                            'id',

                                            'optionLabelKey' =>
                                            'name',

                                            'selectedValues' =>
                                            $selected(
                                                'city_ids'
                                            ),

                                            'showSelectAll' =>
                                            true,

                                            'disabled' =>
                                            $selected(
                                                'state_ids'
                                            ) === [],

                                            'required' =>
                                            false,

                                            'columnClass' =>
                                            'col-12 col-md-6',

                                            'errors' =>
                                            [],
                                        ]
                                    ) ?>

                                    <!--
                                        Search JS uses this route for dependent
                                        State → City master loading.
                                    -->
                                    <input
                                        type="hidden"
                                        id="searchCitiesUrl"
                                        value="<?= esc(
                                                    url_to(
                                                        'web.search.cities'
                                                    ),
                                                    'attr'
                                                ) ?>">

                                    <!-- =========================================================
     Highest Education
     ========================================================= -->

                                    <?= view(
                                        'Pages/PartnerPreference/Additional/_multi_select',
                                        [
                                            'field' =>
                                            'education_ids',

                                            'label' =>
                                            'Highest Education',

                                            'placeholder' =>
                                            '',

                                            /*
         * Education masters are grouped by education category.
         */
                                            'groups' =>
                                            $educationGroups,

                                            'groupLabelKey' =>
                                            'name',

                                            'groupItemsKey' =>
                                            'educations',

                                            'optionValueKey' =>
                                            'id',

                                            'optionLabelKey' =>
                                            'name',

                                            'selectedValues' =>
                                            $selected(
                                                'education_ids'
                                            ),

                                            /*
         * "Any" follows the existing Partner Preference behaviour.
         */
                                            'showSelectAll' =>
                                            true,

                                            /*
         * Search criteria are optional and Education must never depend
         * on State/City selection.
         *
         * Explicit false is important because this shared partial is
         * rendered immediately after the conditionally disabled City field.
         */
                                            'disabled' =>
                                            false,

                                            'required' =>
                                            false,

                                            'columnClass' =>
                                            'col-12 col-md-6',

                                            'errors' =>
                                            [],
                                        ]
                                    ) ?>

                                    <!-- =========================================================
     Employed In
     ========================================================= -->

                                    <?= view(
                                        'Pages/PartnerPreference/Additional/_multi_select',
                                        [
                                            'field' =>
                                            'employed_in',

                                            'label' =>
                                            'Employed In',

                                            'placeholder' =>
                                            '',

                                            'options' =>
                                            $employmentTypes,

                                            'optionValueKey' =>
                                            'value',

                                            'optionLabelKey' =>
                                            'label',

                                            'selectedValues' =>
                                            $selected(
                                                'employed_in'
                                            ),

                                            'showSelectAll' =>
                                            true,

                                            /*
         * Employment selection is independent of State/City.
         */
                                            'disabled' =>
                                            false,

                                            'required' =>
                                            false,

                                            'columnClass' =>
                                            'col-12 col-md-6',

                                            'errors' =>
                                            [],
                                        ]
                                    ) ?>

                                    <!-- =========================================================
     Occupation
     ========================================================= -->

                                    <?= view(
                                        'Pages/PartnerPreference/Additional/_multi_select',
                                        [
                                            'field' =>
                                            'occupation_ids',

                                            'label' =>
                                            'Occupation',

                                            'placeholder' =>
                                            '',

                                            /*
         * Occupations are grouped by occupation category.
         */
                                            'groups' =>
                                            $occupationGroups,

                                            'groupLabelKey' =>
                                            'name',

                                            'groupItemsKey' =>
                                            'occupations',

                                            'optionValueKey' =>
                                            'id',

                                            'optionLabelKey' =>
                                            'name',

                                            'selectedValues' =>
                                            $selected(
                                                'occupation_ids'
                                            ),

                                            'showSelectAll' =>
                                            true,

                                            /*
         * Occupation selection is independent of State/City.
         */
                                            'disabled' =>
                                            false,

                                            'required' =>
                                            false,

                                            'columnClass' =>
                                            'col-12 col-md-6',

                                            'errors' =>
                                            [],
                                        ]
                                    ) ?>

                                    <!-- =========================================================
     Annual Income
     Same multi-bracket UI as Partner Preference.
     ========================================================= -->

                                    <?= view(
                                        'Pages/PartnerPreference/Additional/_multi_select',
                                        [
                                            'field' =>
                                            'annual_income_ids',

                                            'label' =>
                                            'Annual Income',

                                            'placeholder' =>
                                            '',

                                            'options' =>
                                            $annualIncomes,

                                            'optionValueKey' =>
                                            'id',

                                            'optionLabelKey' =>
                                            'display_name',

                                            'selectedValues' =>
                                            $selected(
                                                'annual_income_ids'
                                            ),

                                            'showSelectAll' =>
                                            true,

                                            /*
         * Annual Income selection is independent of State/City.
         */
                                            'disabled' =>
                                            false,

                                            'required' =>
                                            false,

                                            'columnClass' =>
                                            'col-12 col-md-6',

                                            'errors' =>
                                            [],
                                        ]
                                    ) ?>

                                    <!-- =====================================
                                         Profile Managed By
                                         ===================================== -->

                                    <!-- =====================================
     Profile Managed By
     Full-width single-row option group.
     ===================================== -->

                                    <div class="col-12">

                                        <div
                                            class="d-flex flex-column
            flex-md-row
            align-items-md-center
            gap-2 gap-md-4">

                                            <label
                                                class="form-labelm mb-md-0
                flex-shrink-0">

                                                Profile Managed By
                                            </label>

                                            <div
                                                class="d-flex flex-wrap
                align-items-center gap-3">

                                                <?php foreach (
                                                    $profileManagedBy
                                                    as $option
                                                ): ?>

                                                    <?php
                                                    $value =
                                                        trim(
                                                            (string) (
                                                                $option['value']
                                                                ?? ''
                                                            )
                                                        );

                                                    $label =
                                                        trim(
                                                            (string) (
                                                                $option['label']
                                                                ?? ''
                                                            )
                                                        );

                                                    if (
                                                        $value === ''
                                                        || $label === ''
                                                    ) {
                                                        continue;
                                                    }

                                                    $controlId =
                                                        'managed'
                                                        . ucfirst(
                                                            $value
                                                        );
                                                    ?>

                                                    <div class="form-check mb-0">

                                                        <input
                                                            type="checkbox"
                                                            class="form-check-input"
                                                            name="managed_by[]"
                                                            value="<?= esc(
                                                                        $value,
                                                                        'attr'
                                                                    ) ?>"
                                                            id="<?= esc(
                                                                    $controlId,
                                                                    'attr'
                                                                ) ?>"
                                                            <?= in_array(
                                                                $value,
                                                                $selected(
                                                                    'managed_by'
                                                                ),
                                                                true
                                                            )
                                                                ? 'checked'
                                                                : '' ?>>

                                                        <label
                                                            class="form-check-label"
                                                            for="<?= esc(
                                                                        $controlId,
                                                                        'attr'
                                                                    ) ?>">

                                                            <?= esc(
                                                                $label
                                                            ) ?>

                                                        </label>

                                                    </div>

                                                <?php endforeach; ?>

                                            </div>

                                        </div>

                                    </div>

                                    <!-- =====================================
                                         Lifestyle
                                         Keep same UI as Profile Edit.
                                         ===================================== -->

                                    <div class="col-12">

                                        <label class="form-labelm">
                                            Lifestyle
                                        </label>

                                        <ul
                                            class="nav nav-tabs
                                                flex-nowrap overflow-auto mb-3"
                                            role="tablist">

                                            <?php foreach (
                                                $lifestyleCategories
                                                as $index => $category
                                            ): ?>

                                                <?php
                                                $categoryId =
                                                    (int) (
                                                        $category['id']
                                                        ?? 0
                                                    );
                                                ?>

                                                <li
                                                    class="nav-item
                                                        flex-shrink-0">

                                                    <button
                                                        type="button"
                                                        class="nav-link
                                                            <?= $index === 0
                                                                ? 'active'
                                                                : '' ?>"
                                                        data-bs-toggle="tab"
                                                        data-bs-target="#searchLifestyle<?= esc(
                                                                                            (string)
                                                                                            $categoryId,
                                                                                            'attr'
                                                                                        ) ?>">

                                                        <?= esc(
                                                            (string) (
                                                                $category['name']
                                                                ?? ''
                                                            )
                                                        ) ?>

                                                    </button>

                                                </li>

                                            <?php endforeach; ?>

                                        </ul>

                                        <div class="tab-content">

                                            <?php foreach (
                                                $lifestyleCategories
                                                as $index => $category
                                            ): ?>

                                                <?php
                                                $categoryId =
                                                    (int) (
                                                        $category['id']
                                                        ?? 0
                                                    );

                                                $categoryOptions =
                                                    $lifestyleOptionsByCategory[$categoryId]
                                                    ?? [];
                                                ?>

                                                <div
                                                    class="tab-pane fade
                                                        <?= $index === 0
                                                            ? 'show active'
                                                            : '' ?>"
                                                    id="searchLifestyle<?= esc(
                                                                            (string)
                                                                            $categoryId,
                                                                            'attr'
                                                                        ) ?>">

                                                    <div
                                                        class="d-flex flex-wrap
                                                            gap-2 py-2">

                                                        <?php foreach (
                                                            $categoryOptions
                                                            as $option
                                                        ): ?>

                                                            <?php
                                                            $optionId =
                                                                (int) (
                                                                    $option['id']
                                                                    ?? 0
                                                                );
                                                            ?>

                                                            <input
                                                                type="checkbox"
                                                                class="btn-check"
                                                                name="lifestyle_option_ids[]"
                                                                value="<?= esc(
                                                                            (string)
                                                                            $optionId,
                                                                            'attr'
                                                                        ) ?>"
                                                                id="searchLifestyleOption<?= esc(
                                                                                                (string)
                                                                                                $optionId,
                                                                                                'attr'
                                                                                            ) ?>"
                                                                <?= in_array(
                                                                    $optionId,
                                                                    array_map(
                                                                        'intval',
                                                                        $selected(
                                                                            'lifestyle_option_ids'
                                                                        )
                                                                    ),
                                                                    true
                                                                )
                                                                    ? 'checked'
                                                                    : '' ?>>

                                                            <label
                                                                class="btn
                                                                    btn-outline-primary"
                                                                for="searchLifestyleOption<?= esc(
                                                                                                (string)
                                                                                                $optionId,
                                                                                                'attr'
                                                                                            ) ?>">

                                                                <?= esc(
                                                                    (string) (
                                                                        $option['name']
                                                                        ?? ''
                                                                    )
                                                                ) ?>

                                                            </label>

                                                        <?php endforeach; ?>

                                                    </div>

                                                </div>

                                            <?php endforeach; ?>

                                        </div>

                                    </div>

                                </div>

                            <?php endif; ?>

                            <!-- =============================================
                                 Actions
                                 ============================================= -->

                            <div
                                class="d-flex flex-column
                                    flex-sm-row justify-content-end
                                    gap-2 mt-4">

                                <a
                                    href="<?= esc(
                                                route_to(
                                                    'web.search'
                                                )
                                                    . '?mode='
                                                    . rawurlencode(
                                                        $mode
                                                    ),
                                                'attr'
                                            ) ?>"
                                    class="btn btn-outline-secondary">

                                    Reset
                                </a>

                                <button
                                    type="submit"
                                    class="btn btn-primary">

                                    <i
                                        class="ri-search-line me-1"
                                        aria-hidden="true">
                                    </i>

                                    Search Profiles
                                </button>

                            </div>

                        </form>

                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

<?php $this->endSection(); ?>