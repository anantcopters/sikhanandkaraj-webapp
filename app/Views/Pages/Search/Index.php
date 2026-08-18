<?php

declare(strict_types=1);

/**
 * Member Search form UI variables.
 *
 * Search intentionally owns its multi-select UI instead of reusing the
 * Partner Preference multi-select partial because Search and Partner
 * Preference have different "Any" semantics.
 *
 * Search:
 *     Empty selection = Any / no restriction.
 *
 * Partner Preference:
 *     Any has persistence-specific behaviour.
 *
 * @var string                     $pageTitle
 * @var string                     $mode
 * @var array<string, mixed>       $filters
 * @var array<string, mixed>       $masterData
 * @var array<string, string>|null $formAlert
 */

/*
 * --------------------------------------------------------------------------
 * Normalize top-level view variables
 * --------------------------------------------------------------------------
 *
 * Controller/service supplied values are normalized once before rendering.
 * Search business rules remain in MemberSearchService.
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
 * --------------------------------------------------------------------------
 * Normalize Search filter selections
 * --------------------------------------------------------------------------
 */

/**
 * Return one normalized multi-value Search filter.
 *
 * @return array<int, mixed>
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

/**
 * Return one multi-value Search filter normalized as integer IDs.
 *
 * @return list<int>
 */
$selectedIds =
    static function (
        string $key
    ) use (
        $selected
    ): array {
        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $selected(
                            $key
                        )
                    ),
                    static fn(
                        int $value
                    ): bool =>
                    $value > 0
                )
            )
        );
    };

/*
 * --------------------------------------------------------------------------
 * Normalize scalar Search values
 * --------------------------------------------------------------------------
 */

$ageMin =
    trim(
        (string) (
            $filters['age_min']
            ?? ''
        )
    );

$ageMax =
    trim(
        (string) (
            $filters['age_max']
            ?? ''
        )
    );

$heightMinId =
    max(
        0,
        (int) (
            $filters['height_min_id']
            ?? 0
        )
    );

$heightMaxId =
    max(
        0,
        (int) (
            $filters['height_max_id']
            ?? 0
        )
    );

/*
 * --------------------------------------------------------------------------
 * Normalize Basic Search masters
 * --------------------------------------------------------------------------
 */

$heights =
    isset(
        $masterData['heights']
    )
    && is_array(
        $masterData['heights']
    )
    ? array_values(
        $masterData['heights']
    )
    : [];

$maritalStatuses =
    isset(
        $masterData['maritalStatuses']
    )
    && is_array(
        $masterData['maritalStatuses']
    )
    ? array_values(
        $masterData['maritalStatuses']
    )
    : [];

$countries = isset($masterData['countries'])
    && is_array($masterData['countries'])
    ? array_values($masterData['countries'])
    : [];

$states =
    isset(
        $masterData['states']
    )
    && is_array(
        $masterData['states']
    )
    ? array_values(
        $masterData['states']
    )
    : [];

/*
 * --------------------------------------------------------------------------
 * Normalize Advanced Search masters
 * --------------------------------------------------------------------------
 */

$cities =
    isset(
        $masterData['cities']
    )
    && is_array(
        $masterData['cities']
    )
    ? array_values(
        $masterData['cities']
    )
    : [];

$communities =
    isset(
        $masterData['communities']
    )
    && is_array(
        $masterData['communities']
    )
    ? array_values(
        $masterData['communities']
    )
    : [];

$educationGroups =
    isset(
        $masterData['educationGroups']
    )
    && is_array(
        $masterData['educationGroups']
    )
    ? array_values(
        $masterData['educationGroups']
    )
    : [];

$employmentTypes =
    isset(
        $masterData['employmentTypes']
    )
    && is_array(
        $masterData['employmentTypes']
    )
    ? array_values(
        $masterData['employmentTypes']
    )
    : [];

$occupationGroups =
    isset(
        $masterData['occupationGroups']
    )
    && is_array(
        $masterData['occupationGroups']
    )
    ? array_values(
        $masterData['occupationGroups']
    )
    : [];

$annualIncomes =
    isset(
        $masterData['annualIncomes']
    )
    && is_array(
        $masterData['annualIncomes']
    )
    ? array_values(
        $masterData['annualIncomes']
    )
    : [];

$profileManagedBy =
    isset(
        $masterData['profileManagedBy']
    )
    && is_array(
        $masterData['profileManagedBy']
    )
    ? array_values(
        $masterData['profileManagedBy']
    )
    : [];

$lifestyleCategories =
    isset(
        $masterData['lifestyleCategories']
    )
    && is_array(
        $masterData['lifestyleCategories']
    )
    ? array_values(
        $masterData['lifestyleCategories']
    )
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

/*
 * --------------------------------------------------------------------------
 * Normalize multi-select Search selections
 * --------------------------------------------------------------------------
 */

$selectedMaritalStatusIds =
    $selectedIds(
        'marital_status_ids'
    );

$selectedCountryIds =
    $selectedIds(
        'country_ids'
    );

$selectedStateIds =
    $selectedIds(
        'state_ids'
    );

$selectedCommunityIds =
    $selectedIds(
        'community_ids'
    );

$selectedCityIds =
    $selectedIds(
        'city_ids'
    );

$selectedEducationIds =
    $selectedIds(
        'education_ids'
    );

$selectedOccupationIds =
    $selectedIds(
        'occupation_ids'
    );

$selectedAnnualIncomeIds =
    $selectedIds(
        'annual_income_ids'
    );

$selectedLifestyleOptionIds =
    $selectedIds(
        'lifestyle_option_ids'
    );

$selectedEmploymentTypes =
    array_values(
        array_unique(
            array_filter(
                array_map(
                    static fn(
                        mixed $value
                    ): string =>
                    trim(
                        (string) $value
                    ),
                    $selected(
                        'employed_in'
                    )
                ),
                static fn(
                    string $value
                ): bool =>
                $value !== ''
            )
        )
    );

$selectedManagedBy =
    array_values(
        array_unique(
            array_filter(
                array_map(
                    static fn(
                        mixed $value
                    ): string =>
                    trim(
                        (string) $value
                    ),
                    $selected(
                        'managed_by'
                    )
                ),
                static fn(
                    string $value
                ): bool =>
                $value !== ''
            )
        )
    );

$selectedPhotoVisibility =
    array_values(
        array_unique(
            array_filter(
                array_map(
                    static fn(
                        mixed $value
                    ): string =>
                    trim(
                        (string) $value
                    ),
                    $selected(
                        'photo_visibility'
                    )
                ),
                static fn(
                    string $value
                ): bool =>
                $value !== ''
            )
        )
    );

/*
 * City remains dependent on at least one State selection.
 */
$cityDisabled =
    $selectedStateIds === [];

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
                     Page heading
                     ===================================================== -->

                <div class="mb-4">

                    <h1
                        class="fs-24 fw-semibold mb-1">

                        Search Profiles
                    </h1>

                    <p class="text-muted mb-0">

                        Find profiles using the preferences
                        that matter most to you.
                    </p>

                </div>

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
                     Main Search card
                     ===================================================== -->

                <div
                    class="card border border-danger
                        border-opacity-25 shadow-sm">

                    <div class="card-body p-3 p-md-4">

                        <!-- =================================================
                             Basic / Advanced Search navigation
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

                        <!-- =================================================
                             Search form
                             ================================================= -->

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
                                 Basic Search
                                 ============================================= -->

                            <div class="row g-3">

                                <div class="col-12 mt-2">
                                    <span class="form-text color-pink fw-medium fs-12 text-uppercase mt-0">
                                        <span class="text-danger me-1">*</span>
                                        Leave Blank for ANY
                                    </span>
                                </div>

                                <!-- =========================================
                                     Age From
                                     Same UI direction as Partner Preference.
                                     ========================================= -->

                                <div class="col-12 col-md-6">

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
                                        data-choice-search="false"
                                        data-choice-position="bottom">

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
                                                <?= $ageMin ===
                                                    (string) $age
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
                                     Age To
                                     ========================================= -->

                                <div class="col-12 col-md-6">

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
                                        data-choice-search="false"
                                        data-choice-position="bottom">

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
                                                <?= $ageMax ===
                                                    (string) $age
                                                    ? 'selected'
                                                    : '' ?>>

                                                <?= esc(
                                                    (string) $age
                                                ) ?>

                                            </option>

                                        <?php endfor; ?>

                                    </select>
                                    <!-- Client-side Age range validation feedback. -->
                                    <div
                                        id="ageRangeError"
                                        class="invalid-feedback d-block"
                                        hidden>
                                    </div>

                                </div>

                                <!-- =========================================
                                     Height From
                                     ========================================= -->

                                <div class="col-12 col-md-6">

                                    <label
                                        for="heightFrom"
                                        class="form-labelm">

                                        Height From
                                    </label>

                                    <select
                                        id="heightFrom"
                                        name="height_min_id"
                                        class="form-select"
                                        data-choice
                                        data-choice-search="true"
                                        data-choice-position="bottom"
                                        data-choice-search-placeholder="Search height">

                                        <option value="">
                                            Any
                                        </option>

                                        <?php foreach (
                                            $heights
                                            as $height
                                        ): ?>

                                            <?php
                                            $heightId =
                                                max(
                                                    0,
                                                    (int) (
                                                        $height['id']
                                                        ?? 0
                                                    )
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
                                                data-height-cm="<?= esc(
                                                                    (string) (
                                                                        $height['height_cm']
                                                                        ?? ''
                                                                    ),
                                                                    'attr'
                                                                ) ?>"
                                                <?= $heightMinId === $heightId
                                                    ? 'selected'
                                                    : '' ?>>

                                                <?= esc(
                                                    $heightName
                                                ) ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>

                                <!-- =========================================
                                     Height To
                                     ========================================= -->

                                <div class="col-12 col-md-6">

                                    <label
                                        for="heightTo"
                                        class="form-labelm">

                                        Height To
                                    </label>

                                    <select
                                        id="heightTo"
                                        name="height_max_id"
                                        class="form-select"
                                        data-choice
                                        data-choice-search="true"
                                        data-choice-position="bottom"
                                        data-choice-search-placeholder="Search height">

                                        <option value="">
                                            Any
                                        </option>

                                        <?php foreach (
                                            $heights
                                            as $height
                                        ): ?>

                                            <?php
                                            $heightId =
                                                max(
                                                    0,
                                                    (int) (
                                                        $height['id']
                                                        ?? 0
                                                    )
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
                                                data-height-cm="<?= esc(
                                                                    (string) (
                                                                        $height['height_cm']
                                                                        ?? ''
                                                                    ),
                                                                    'attr'
                                                                ) ?>"
                                                <?= $heightMaxId === $heightId
                                                    ? 'selected'
                                                    : '' ?>>

                                                <?= esc(
                                                    $heightName
                                                ) ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>
                                    <!-- Client-side Height range validation feedback. -->
                                    <div
                                        id="heightRangeError"
                                        class="invalid-feedback d-block"
                                        hidden>
                                    </div>

                                </div>

                                <!-- =========================================
                                     Marital Status
                                     Empty selection = Any.
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
                                        data-choice-remove="true"
                                        data-choice-position="bottom"
                                        data-choice-search-placeholder="Search marital status"
                                        multiple>

                                        <?php foreach (
                                            $maritalStatuses
                                            as $status
                                        ): ?>

                                            <?php
                                            $statusId =
                                                max(
                                                    0,
                                                    (int) (
                                                        $status['id']
                                                        ?? 0
                                                    )
                                                );

                                            $statusName =
                                                trim(
                                                    (string) (
                                                        $status['name']
                                                        ?? ''
                                                    )
                                                );

                                            if (
                                                $statusId <= 0
                                                || $statusName === ''
                                            ) {
                                                continue;
                                            }
                                            ?>

                                            <option
                                                value="<?= esc(
                                                            (string)
                                                            $statusId,
                                                            'attr'
                                                        ) ?>"
                                                <?= in_array(
                                                    $statusId,
                                                    $selectedMaritalStatusIds,
                                                    true
                                                )
                                                    ? 'selected'
                                                    : '' ?>>

                                                <?= esc(
                                                    $statusName
                                                ) ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>

                                <!-- =========================================
     Country Living In
     Empty selection = Any Country.
     ========================================= -->

                                <div class="col-12 col-md-6">

                                    <label
                                        for="countryIds"
                                        class="form-labelm">

                                        Country Living In
                                    </label>

                                    <select
                                        id="countryIds"
                                        name="country_ids[]"
                                        class="form-select"
                                        data-choice
                                        data-choice-search="true"
                                        data-choice-remove="true"
                                        data-choice-position="bottom"
                                        data-choice-search-placeholder="Search country"
                                        multiple>

                                        <?php foreach (
                                            $countries
                                            as $country
                                        ): ?>

                                            <?php
                                            $countryId =
                                                max(
                                                    0,
                                                    (int) (
                                                        $country['id']
                                                        ?? 0
                                                    )
                                                );

                                            $countryName =
                                                trim(
                                                    (string) (
                                                        $country['name']
                                                        ?? ''
                                                    )
                                                );

                                            if (
                                                $countryId <= 0
                                                || $countryName === ''
                                            ) {
                                                continue;
                                            }
                                            ?>

                                            <option
                                                value="<?= esc(
                                                            (string) $countryId,
                                                            'attr'
                                                        ) ?>"
                                                <?= in_array(
                                                    $countryId,
                                                    $selectedCountryIds,
                                                    true
                                                )
                                                    ? 'selected'
                                                    : '' ?>>

                                                <?= esc(
                                                    $countryName
                                                ) ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                    <input
                                        type="hidden"
                                        id="searchStatesUrl"
                                        value="<?= esc(
                                                    route_to(
                                                        'web.search.states'
                                                    ),
                                                    'attr'
                                                ) ?>">

                                    <div class="form-text text-secondary">
                                        Leave empty for any country.
                                    </div>

                                </div>

                                <div class="col-12 col-md-6">

                                    <label
                                        for="stateIds"
                                        class="form-labelm">

                                        State Living In
                                    </label>

                                    <select
                                        id="stateIds"
                                        name="state_ids[]"
                                        class="form-select"
                                        data-choice
                                        data-choice-search="true"
                                        data-choice-remove="true"
                                        data-choice-position="bottom"
                                        data-choice-search-placeholder="Search state"
                                        multiple>

                                        <?php foreach (
                                            $states
                                            as $state
                                        ): ?>

                                            <?php
                                            $stateId =
                                                max(
                                                    0,
                                                    (int) (
                                                        $state['id']
                                                        ?? 0
                                                    )
                                                );

                                            $stateName =
                                                trim(
                                                    (string) (
                                                        $state['name']
                                                        ?? ''
                                                    )
                                                );

                                            if (
                                                $stateId <= 0
                                                || $stateName === ''
                                            ) {
                                                continue;
                                            }
                                            ?>

                                            <option
                                                value="<?= esc(
                                                            (string)
                                                            $stateId,
                                                            'attr'
                                                        ) ?>"
                                                <?= in_array(
                                                    $stateId,
                                                    $selectedStateIds,
                                                    true
                                                )
                                                    ? 'selected'
                                                    : '' ?>>

                                                <?= esc(
                                                    $stateName
                                                ) ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>

                                <!-- =========================================
                                     Photo Settings
                                     Single-row presentation.
                                     ========================================= -->

                                <div class="col-12">

                                    <div
                                        class="d-flex flex-wrap
                                            align-items-center gap-3">

                                        <span
                                            class="form-labelm mb-0">

                                            Photo Settings
                                        </span>

                                        <div class="form-check mb-0">

                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                id="photoPublic"
                                                name="photo_visibility[]"
                                                value="PUBLIC"
                                                <?= in_array(
                                                    'PUBLIC',
                                                    $selectedPhotoVisibility,
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
                                                type="checkbox"
                                                class="form-check-input"
                                                id="photoInterested"
                                                name="photo_visibility[]"
                                                value="INTERESTED_MEMBERS"
                                                <?= in_array(
                                                    'INTERESTED_MEMBERS',
                                                    $selectedPhotoVisibility,
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
                                     Advanced Search separator
                                     ========================================= -->

                                <hr class="my-4">

                                <div class="row g-3">

                                    <!-- =====================================
                                         Community
                                         ===================================== -->

                                    <div class="col-12 col-md-6">

                                        <label
                                            for="searchCommunity"
                                            class="form-labelm">

                                            Community
                                        </label>

                                        <select
                                            id="searchCommunity"
                                            name="community_ids[]"
                                            class="form-select"
                                            data-choice
                                            data-choice-search="true"
                                            data-choice-remove="true"
                                            data-choice-position="bottom"
                                            data-choice-search-placeholder="Search community"
                                            multiple>

                                            <?php foreach (
                                                $communities
                                                as $community
                                            ): ?>

                                                <?php
                                                $communityId =
                                                    max(
                                                        0,
                                                        (int) (
                                                            $community['id']
                                                            ?? 0
                                                        )
                                                    );

                                                $communityName =
                                                    trim(
                                                        (string) (
                                                            $community['name']
                                                            ?? ''
                                                        )
                                                    );

                                                if (
                                                    $communityId <= 0
                                                    || $communityName === ''
                                                ) {
                                                    continue;
                                                }
                                                ?>

                                                <option
                                                    value="<?= esc(
                                                                (string)
                                                                $communityId,
                                                                'attr'
                                                            ) ?>"
                                                    <?= in_array(
                                                        $communityId,
                                                        $selectedCommunityIds,
                                                        true
                                                    )
                                                        ? 'selected'
                                                        : '' ?>>

                                                    <?= esc(
                                                        $communityName
                                                    ) ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>

                                    <!-- =====================================
                                         City Living In
                                         Dependent on selected State(s).
                                         ===================================== -->

                                    <div class="col-12 col-md-6">

                                        <label
                                            for="cityIds"
                                            class="form-labelm">

                                            City Living In
                                        </label>

                                        <select
                                            id="cityIds"
                                            name="city_ids[]"
                                            class="form-select"
                                            data-choice
                                            data-choice-search="true"
                                            data-choice-remove="true"
                                            data-choice-position="bottom"
                                            data-choice-search-placeholder="Search city"
                                            <?= $cityDisabled
                                                ? 'disabled'
                                                : '' ?>
                                            multiple>

                                            <?php foreach (
                                                $cities
                                                as $city
                                            ): ?>

                                                <?php
                                                $cityId =
                                                    max(
                                                        0,
                                                        (int) (
                                                            $city['id']
                                                            ?? 0
                                                        )
                                                    );

                                                $cityName =
                                                    trim(
                                                        (string) (
                                                            $city['name']
                                                            ?? ''
                                                        )
                                                    );

                                                if (
                                                    $cityId <= 0
                                                    || $cityName === ''
                                                ) {
                                                    continue;
                                                }
                                                ?>

                                                <option
                                                    value="<?= esc(
                                                                (string)
                                                                $cityId,
                                                                'attr'
                                                            ) ?>"
                                                    <?= in_array(
                                                        $cityId,
                                                        $selectedCityIds,
                                                        true
                                                    )
                                                        ? 'selected'
                                                        : '' ?>>

                                                    <?= esc(
                                                        $cityName
                                                    ) ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                        <div class="form-text color-pink text-uppercase fw-medium">
                                            Select State Living In first.
                                        </div>
                                        <!-- Client-side Height range validation feedback. -->
                                        <div
                                            id="heightRangeError"
                                            class="invalid-feedback d-block"
                                            hidden>
                                        </div>

                                    </div>

                                    <!--
                                        Search JavaScript uses this named route
                                        for dependent State → City loading.
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

                                    <!-- =====================================
                                         Highest Education
                                         Group structure:
                                         name → educations.
                                         ===================================== -->

                                    <div class="col-12 col-md-6">

                                        <label
                                            for="searchEducation"
                                            class="form-labelm">

                                            Highest Education
                                        </label>

                                        <select
                                            id="searchEducation"
                                            name="education_ids[]"
                                            class="form-select"
                                            data-choice
                                            data-choice-search="true"
                                            data-choice-remove="true"
                                            data-choice-position="bottom"
                                            data-choice-search-placeholder="Search education"
                                            multiple>

                                            <?php foreach (
                                                $educationGroups
                                                as $group
                                            ): ?>

                                                <?php
                                                if (
                                                    !is_array(
                                                        $group
                                                    )
                                                ) {
                                                    continue;
                                                }

                                                $groupName =
                                                    trim(
                                                        (string) (
                                                            $group['name']
                                                            ?? ''
                                                        )
                                                    );

                                                $groupEducations =
                                                    isset(
                                                        $group['educations']
                                                    )
                                                    && is_array(
                                                        $group['educations']
                                                    )
                                                    ? array_values(
                                                        $group['educations']
                                                    )
                                                    : [];

                                                if (
                                                    $groupName === ''
                                                    || $groupEducations === []
                                                ) {
                                                    continue;
                                                }
                                                ?>

                                                <optgroup
                                                    label="<?= esc(
                                                                $groupName,
                                                                'attr'
                                                            ) ?>">

                                                    <?php foreach (
                                                        $groupEducations
                                                        as $education
                                                    ): ?>

                                                        <?php
                                                        $educationId =
                                                            max(
                                                                0,
                                                                (int) (
                                                                    $education['id']
                                                                    ?? 0
                                                                )
                                                            );

                                                        $educationName =
                                                            trim(
                                                                (string) (
                                                                    $education['name']
                                                                    ?? ''
                                                                )
                                                            );

                                                        if (
                                                            $educationId <= 0
                                                            || $educationName === ''
                                                        ) {
                                                            continue;
                                                        }
                                                        ?>

                                                        <option
                                                            value="<?= esc(
                                                                        (string)
                                                                        $educationId,
                                                                        'attr'
                                                                    ) ?>"
                                                            <?= in_array(
                                                                $educationId,
                                                                $selectedEducationIds,
                                                                true
                                                            )
                                                                ? 'selected'
                                                                : '' ?>>

                                                            <?= esc(
                                                                $educationName
                                                            ) ?>

                                                        </option>

                                                    <?php endforeach; ?>

                                                </optgroup>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>

                                    <!-- =====================================
                                         Employed In
                                         ===================================== -->

                                    <div class="col-12 col-md-6">

                                        <label
                                            for="searchEmployedIn"
                                            class="form-labelm">

                                            Employed In
                                        </label>

                                        <select
                                            id="searchEmployedIn"
                                            name="employed_in[]"
                                            class="form-select"
                                            data-choice
                                            data-choice-search="true"
                                            data-choice-remove="true"
                                            data-choice-position="bottom"
                                            data-choice-search-placeholder="Search employment"
                                            multiple>

                                            <?php foreach (
                                                $employmentTypes
                                                as $employment
                                            ): ?>

                                                <?php
                                                if (
                                                    !is_array(
                                                        $employment
                                                    )
                                                ) {
                                                    continue;
                                                }

                                                $employmentValue =
                                                    trim(
                                                        (string) (
                                                            $employment['value']
                                                            ?? ''
                                                        )
                                                    );

                                                $employmentLabel =
                                                    trim(
                                                        (string) (
                                                            $employment['label']
                                                            ?? ''
                                                        )
                                                    );

                                                if (
                                                    $employmentValue === ''
                                                    || $employmentLabel === ''
                                                ) {
                                                    continue;
                                                }
                                                ?>

                                                <option
                                                    value="<?= esc(
                                                                $employmentValue,
                                                                'attr'
                                                            ) ?>"
                                                    <?= in_array(
                                                        $employmentValue,
                                                        $selectedEmploymentTypes,
                                                        true
                                                    )
                                                        ? 'selected'
                                                        : '' ?>>

                                                    <?= esc(
                                                        $employmentLabel
                                                    ) ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>

                                    <!-- =====================================
                                         Occupation
                                         Group structure:
                                         name → occupations.
                                         ===================================== -->

                                    <div class="col-12 col-md-6">

                                        <label
                                            for="searchOccupation"
                                            class="form-labelm">

                                            Occupation
                                        </label>

                                        <select
                                            id="searchOccupation"
                                            name="occupation_ids[]"
                                            class="form-select"
                                            data-choice
                                            data-choice-search="true"
                                            data-choice-remove="true"
                                            data-choice-position="bottom"
                                            data-choice-search-placeholder="Search occupation"
                                            multiple>

                                            <?php foreach (
                                                $occupationGroups
                                                as $group
                                            ): ?>

                                                <?php
                                                if (
                                                    !is_array(
                                                        $group
                                                    )
                                                ) {
                                                    continue;
                                                }

                                                $groupName =
                                                    trim(
                                                        (string) (
                                                            $group['name']
                                                            ?? ''
                                                        )
                                                    );

                                                $groupOccupations =
                                                    isset(
                                                        $group['occupations']
                                                    )
                                                    && is_array(
                                                        $group['occupations']
                                                    )
                                                    ? array_values(
                                                        $group['occupations']
                                                    )
                                                    : [];

                                                if (
                                                    $groupName === ''
                                                    || $groupOccupations === []
                                                ) {
                                                    continue;
                                                }
                                                ?>

                                                <optgroup
                                                    label="<?= esc(
                                                                $groupName,
                                                                'attr'
                                                            ) ?>">

                                                    <?php foreach (
                                                        $groupOccupations
                                                        as $occupation
                                                    ): ?>

                                                        <?php
                                                        $occupationId =
                                                            max(
                                                                0,
                                                                (int) (
                                                                    $occupation['id']
                                                                    ?? 0
                                                                )
                                                            );

                                                        $occupationName =
                                                            trim(
                                                                (string) (
                                                                    $occupation['name']
                                                                    ?? ''
                                                                )
                                                            );

                                                        if (
                                                            $occupationId <= 0
                                                            || $occupationName === ''
                                                        ) {
                                                            continue;
                                                        }
                                                        ?>

                                                        <option
                                                            value="<?= esc(
                                                                        (string)
                                                                        $occupationId,
                                                                        'attr'
                                                                    ) ?>"
                                                            <?= in_array(
                                                                $occupationId,
                                                                $selectedOccupationIds,
                                                                true
                                                            )
                                                                ? 'selected'
                                                                : '' ?>>

                                                            <?= esc(
                                                                $occupationName
                                                            ) ?>

                                                        </option>

                                                    <?php endforeach; ?>

                                                </optgroup>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>

                                    <!-- =====================================
                                         Annual Income
                                         Multi-select master brackets.
                                         ===================================== -->

                                    <div class="col-12 col-md-6">

                                        <label
                                            for="searchAnnualIncome"
                                            class="form-labelm">

                                            Annual Income
                                        </label>

                                        <select
                                            id="searchAnnualIncome"
                                            name="annual_income_ids[]"
                                            class="form-select"
                                            data-choice
                                            data-choice-search="true"
                                            data-choice-remove="true"
                                            data-choice-position="bottom"
                                            data-choice-search-placeholder="Search annual income"
                                            multiple>

                                            <?php foreach (
                                                $annualIncomes
                                                as $income
                                            ): ?>

                                                <?php
                                                if (
                                                    !is_array(
                                                        $income
                                                    )
                                                ) {
                                                    continue;
                                                }

                                                $incomeId =
                                                    max(
                                                        0,
                                                        (int) (
                                                            $income['id']
                                                            ?? 0
                                                        )
                                                    );

                                                $incomeName =
                                                    trim(
                                                        (string) (
                                                            $income['display_name']
                                                            ?? ''
                                                        )
                                                    );

                                                if (
                                                    $incomeId <= 0
                                                    || $incomeName === ''
                                                ) {
                                                    continue;
                                                }
                                                ?>

                                                <option
                                                    value="<?= esc(
                                                                (string)
                                                                $incomeId,
                                                                'attr'
                                                            ) ?>"
                                                    <?= in_array(
                                                        $incomeId,
                                                        $selectedAnnualIncomeIds,
                                                        true
                                                    )
                                                        ? 'selected'
                                                        : '' ?>>

                                                    <?= esc(
                                                        $incomeName
                                                    ) ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>

                                    <!-- =====================================
                                         Profile Managed By
                                         Full-width single-row group.
                                         ===================================== -->

                                    <div class="col-12 mt-4">

                                        <div
                                            class="d-flex flex-column
                                                flex-md-row
                                                align-items-md-center
                                                gap-2 gap-md-4">

                                            <label
                                                class="form-labelm
                                                    mb-md-0 flex-shrink-0">

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
                                                    if (
                                                        !is_array(
                                                            $option
                                                        )
                                                    ) {
                                                        continue;
                                                    }

                                                    $managedValue =
                                                        trim(
                                                            (string) (
                                                                $option['value']
                                                                ?? ''
                                                            )
                                                        );

                                                    $managedLabel =
                                                        trim(
                                                            (string) (
                                                                $option['label']
                                                                ?? ''
                                                            )
                                                        );

                                                    if (
                                                        $managedValue === ''
                                                        || $managedLabel === ''
                                                    ) {
                                                        continue;
                                                    }

                                                    $managedControlId =
                                                        'managed'
                                                        . preg_replace(
                                                            '/[^a-zA-Z0-9]/',
                                                            '',
                                                            ucfirst(
                                                                $managedValue
                                                            )
                                                        );
                                                    ?>

                                                    <div
                                                        class="form-check mb-0">

                                                        <input
                                                            type="checkbox"
                                                            class="form-check-input"
                                                            id="<?= esc(
                                                                    $managedControlId,
                                                                    'attr'
                                                                ) ?>"
                                                            name="managed_by[]"
                                                            value="<?= esc(
                                                                        $managedValue,
                                                                        'attr'
                                                                    ) ?>"
                                                            <?= in_array(
                                                                $managedValue,
                                                                $selectedManagedBy,
                                                                true
                                                            )
                                                                ? 'checked'
                                                                : '' ?>>

                                                        <label
                                                            class="form-check-label"
                                                            for="<?= esc(
                                                                        $managedControlId,
                                                                        'attr'
                                                                    ) ?>">

                                                            <?= esc(
                                                                $managedLabel
                                                            ) ?>

                                                        </label>

                                                    </div>

                                                <?php endforeach; ?>

                                            </div>

                                        </div>

                                    </div>

                                    <!-- =====================================
                                         Lifestyle
                                         Same button/tag direction as Profile
                                         Edit; no Search-specific CSS.
                                         ===================================== -->

                                    <?php if (
                                        $lifestyleCategories !== []
                                    ): ?>

                                        <div class="col-12 mt-4">

                                            <label class="form-labelm">

                                                Lifestyle
                                            </label>

                                            <!-- Lifestyle categories -->

                                            <ul
                                                class="nav nav-tabs
                                                    flex-nowrap
                                                    overflow-auto mb-3"
                                                role="tablist">

                                                <?php foreach (
                                                    $lifestyleCategories
                                                    as $index => $category
                                                ): ?>

                                                    <?php
                                                    if (
                                                        !is_array(
                                                            $category
                                                        )
                                                    ) {
                                                        continue;
                                                    }

                                                    $categoryId =
                                                        max(
                                                            0,
                                                            (int) (
                                                                $category['id']
                                                                ?? 0
                                                            )
                                                        );

                                                    $categoryName =
                                                        trim(
                                                            (string) (
                                                                $category['name']
                                                                ?? ''
                                                            )
                                                        );

                                                    if (
                                                        $categoryId <= 0
                                                        || $categoryName === ''
                                                    ) {
                                                        continue;
                                                    }

                                                    $tabId =
                                                        'searchLifestyle'
                                                        . $categoryId;
                                                    ?>

                                                    <li
                                                        class="nav-item
                                                            flex-shrink-0"
                                                        role="presentation">

                                                        <button
                                                            type="button"
                                                            class="nav-link
                                                                <?= $index === 0
                                                                    ? 'active'
                                                                    : '' ?>"
                                                            data-bs-toggle="tab"
                                                            data-bs-target="#<?= esc(
                                                                                    $tabId,
                                                                                    'attr'
                                                                                ) ?>"
                                                            aria-controls="<?= esc(
                                                                                $tabId,
                                                                                'attr'
                                                                            ) ?>"
                                                            aria-selected="<?= $index === 0
                                                                                ? 'true'
                                                                                : 'false' ?>">

                                                            <?= esc(
                                                                $categoryName
                                                            ) ?>

                                                        </button>

                                                    </li>

                                                <?php endforeach; ?>

                                            </ul>

                                            <!-- Lifestyle options -->

                                            <div class="tab-content">

                                                <?php foreach (
                                                    $lifestyleCategories
                                                    as $index => $category
                                                ): ?>

                                                    <?php
                                                    if (
                                                        !is_array(
                                                            $category
                                                        )
                                                    ) {
                                                        continue;
                                                    }

                                                    $categoryId =
                                                        max(
                                                            0,
                                                            (int) (
                                                                $category['id']
                                                                ?? 0
                                                            )
                                                        );

                                                    if (
                                                        $categoryId <= 0
                                                    ) {
                                                        continue;
                                                    }

                                                    $tabId =
                                                        'searchLifestyle'
                                                        . $categoryId;

                                                    $categoryOptions =
                                                        isset(
                                                            $lifestyleOptionsByCategory[$categoryId]
                                                        )
                                                        && is_array(
                                                            $lifestyleOptionsByCategory[$categoryId]
                                                        )
                                                        ? array_values(
                                                            $lifestyleOptionsByCategory[$categoryId]
                                                        )
                                                        : [];
                                                    ?>

                                                    <div
                                                        class="tab-pane fade
                                                            <?= $index === 0
                                                                ? 'show active'
                                                                : '' ?>"
                                                        id="<?= esc(
                                                                $tabId,
                                                                'attr'
                                                            ) ?>"
                                                        role="tabpanel">

                                                        <div
                                                            class="d-flex
                                                                flex-wrap
                                                                gap-2 py-2">

                                                            <?php foreach (
                                                                $categoryOptions
                                                                as $option
                                                            ): ?>

                                                                <?php
                                                                if (
                                                                    !is_array(
                                                                        $option
                                                                    )
                                                                ) {
                                                                    continue;
                                                                }

                                                                $optionId =
                                                                    max(
                                                                        0,
                                                                        (int) (
                                                                            $option['id']
                                                                            ?? 0
                                                                        )
                                                                    );

                                                                $optionName =
                                                                    trim(
                                                                        (string) (
                                                                            $option['name']
                                                                            ?? ''
                                                                        )
                                                                    );

                                                                if (
                                                                    $optionId <= 0
                                                                    || $optionName === ''
                                                                ) {
                                                                    continue;
                                                                }

                                                                $optionControlId =
                                                                    'searchLifestyleOption'
                                                                    . $optionId;
                                                                ?>

                                                                <input
                                                                    type="checkbox"
                                                                    class="btn-check"
                                                                    id="<?= esc(
                                                                            $optionControlId,
                                                                            'attr'
                                                                        ) ?>"
                                                                    name="lifestyle_option_ids[]"
                                                                    value="<?= esc(
                                                                                (string)
                                                                                $optionId,
                                                                                'attr'
                                                                            ) ?>"
                                                                    <?= in_array(
                                                                        $optionId,
                                                                        $selectedLifestyleOptionIds,
                                                                        true
                                                                    )
                                                                        ? 'checked'
                                                                        : '' ?>>

                                                                <label
                                                                    class="btn
                                                                        btn-outline-primary"
                                                                    for="<?= esc(
                                                                                $optionControlId,
                                                                                'attr'
                                                                            ) ?>">

                                                                    <?= esc(
                                                                        $optionName
                                                                    ) ?>

                                                                </label>

                                                            <?php endforeach; ?>

                                                        </div>

                                                    </div>

                                                <?php endforeach; ?>

                                            </div>

                                        </div>

                                    <?php endif; ?>

                                </div>

                            <?php endif; ?>

                            <!-- =============================================
                                 Search actions
                                 ============================================= -->

                            <div
                                class="d-flex flex-column
                                    flex-sm-row
                                    justify-content-end
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