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
 * Normalize view-local values
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
 * Normalize one multi-select filter.
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

$this->extend(
    'Layouts/Main'
);

$this->section(
    'content'
);
?>

<section class="py-3 py-lg-4">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <aside class="col-md-12 col-lg-6 col-xl-9">
                <!-- =============================================================
             Feedback
             ============================================================= -->

                <?= view(
                    'Pages/Profile/Partials/_feedback_alert',
                    [
                        'formAlert' =>
                        $formAlert,
                    ]
                ) ?>

                <!-- =============================================================
             Universal Profile-ID Search
             ============================================================= -->

                <div
                    class="card border border-danger
                border-opacity-25 shadow-sm mb-4">

                    <div class="card-body p-3 p-md-4">

                        <form
                            action="<?= url_to(
                                        'web.search.profile'
                                    ) ?>"
                            method="get">

                            <label
                                for="profileId"
                                class="form-label fw-semibold">

                                Search by Profile ID
                            </label>

                            <div class="input-group">

                                <input
                                    type="text"
                                    id="profileId"
                                    name="profile_id"
                                    maxlength="50"
                                    class="form-control"
                                    placeholder="Enter Profile ID"
                                    autocomplete="off">

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

                        </form>

                    </div>
                </div>

                <!-- =============================================================
             Search criteria
             ============================================================= -->

                <div
                    class="card border border-danger
                border-opacity-25 shadow-sm">

                    <div class="card-body p-3 p-md-4">

                        <!-- =====================================================
                     Basic / Advanced tabs
                     ===================================================== -->

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

                            <!-- =================================================
                         Basic criteria
                         ================================================= -->

                            <div class="row g-3">

                                <div class="col-6 col-lg-3">
                                    <label class="form-label">
                                        Age From
                                    </label>

                                    <input
                                        type="number"
                                        name="age_min"
                                        min="18"
                                        max="100"
                                        class="form-control"
                                        value="<?= esc(
                                                    (string) (
                                                        $filters['age_min']
                                                        ?? ''
                                                    ),
                                                    'attr'
                                                ) ?>">
                                </div>

                                <div class="col-6 col-lg-3">
                                    <label class="form-label">
                                        Age To
                                    </label>

                                    <input
                                        type="number"
                                        name="age_max"
                                        min="18"
                                        max="100"
                                        class="form-control"
                                        value="<?= esc(
                                                    (string) (
                                                        $filters['age_max']
                                                        ?? ''
                                                    ),
                                                    'attr'
                                                ) ?>">
                                </div>

                                <!-- Height uses Choices but remains range-based. -->

                                <div class="col-6 col-lg-3">
                                    <label
                                        for="heightFrom"
                                        class="form-label">

                                        Height From
                                    </label>

                                    <select
                                        id="heightFrom"
                                        name="height_min_id"
                                        class="form-select"
                                        data-choice
                                        data-choice-search="true"
                                        data-choice-placeholder="Any height">

                                        <option value="">
                                            Any
                                        </option>

                                        <?php foreach (
                                            $masterData['heights']
                                                ?? []
                                            as $height
                                        ): ?>

                                            <?php
                                            $heightId =
                                                (int) (
                                                    $height['id']
                                                    ?? 0
                                                );
                                            ?>

                                            <option
                                                value="<?= esc(
                                                            (string)
                                                            $heightId,
                                                            'attr'
                                                        ) ?>"
                                                <?= (int) (
                                                    $filters['height_min_id']
                                                    ?? 0
                                                ) === $heightId
                                                    ? 'selected'
                                                    : '' ?>>

                                                <?= esc(
                                                    (string) (
                                                        $height['display_name']
                                                        ?? ''
                                                    )
                                                ) ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>
                                </div>

                                <div class="col-6 col-lg-3">
                                    <label
                                        for="heightTo"
                                        class="form-label">

                                        Height To
                                    </label>

                                    <select
                                        id="heightTo"
                                        name="height_max_id"
                                        class="form-select"
                                        data-choice
                                        data-choice-search="true"
                                        data-choice-placeholder="Any height">

                                        <option value="">
                                            Any
                                        </option>

                                        <?php foreach (
                                            $masterData['heights']
                                                ?? []
                                            as $height
                                        ): ?>

                                            <?php
                                            $heightId =
                                                (int) (
                                                    $height['id']
                                                    ?? 0
                                                );
                                            ?>

                                            <option
                                                value="<?= esc(
                                                            (string)
                                                            $heightId,
                                                            'attr'
                                                        ) ?>"
                                                <?= (int) (
                                                    $filters['height_max_id']
                                                    ?? 0
                                                ) === $heightId
                                                    ? 'selected'
                                                    : '' ?>>

                                                <?= esc(
                                                    (string) (
                                                        $height['display_name']
                                                        ?? ''
                                                    )
                                                ) ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>
                                </div>

                                <!-- Marital status: searchable removable multi-select. -->

                                <div class="col-12 col-md-6">
                                    <label
                                        for="searchMaritalStatus"
                                        class="form-label">

                                        Marital Status
                                    </label>

                                    <select
                                        id="searchMaritalStatus"
                                        name="marital_status_ids[]"
                                        class="form-select"
                                        multiple
                                        data-choice
                                        data-choice-search="true"
                                        data-choice-remove="true">

                                        <?php foreach (
                                            $masterData['maritalStatuses']
                                                ?? []
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

                                <!-- State: multi-select and City dependency source. -->

                                <div class="col-12 col-md-6">
                                    <label
                                        for="searchStates"
                                        class="form-label">

                                        State Living In
                                    </label>

                                    <select
                                        id="searchStates"
                                        name="state_ids[]"
                                        class="form-select"
                                        multiple
                                        data-choice
                                        data-choice-search="true"
                                        data-choice-remove="true">

                                        <?php foreach (
                                            $masterData['states']
                                                ?? []
                                            as $state
                                        ): ?>

                                            <?php
                                            $stateId =
                                                (int) (
                                                    $state['id']
                                                    ?? 0
                                                );
                                            ?>

                                            <option
                                                value="<?= esc(
                                                            (string)
                                                            $stateId,
                                                            'attr'
                                                        ) ?>"
                                                <?= in_array(
                                                    $stateId,
                                                    array_map(
                                                        'intval',
                                                        $selected(
                                                            'state_ids'
                                                        )
                                                    ),
                                                    true
                                                )
                                                    ? 'selected'
                                                    : '' ?>>

                                                <?= esc(
                                                    (string) (
                                                        $state['name']
                                                        ?? ''
                                                    )
                                                ) ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>
                                </div>

                                <!-- Photo visibility requirement. -->

                                <div class="col-12">
                                    <label
                                        class="form-label fw-semibold">

                                        Photo Settings
                                    </label>

                                    <div
                                        class="d-flex flex-wrap gap-3">

                                        <div class="form-check">
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

                                        <div class="form-check">
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

                                <!-- =============================================
                             Advanced criteria
                             ============================================= -->

                                <hr class="my-4">

                                <div class="row g-3">

                                    <div class="col-12 col-md-6">
                                        <label
                                            for="searchCommunity"
                                            class="form-label">

                                            Community
                                        </label>

                                        <select
                                            id="searchCommunity"
                                            name="community_ids[]"
                                            class="form-select"
                                            multiple
                                            data-choice
                                            data-choice-search="true"
                                            data-choice-remove="true">

                                            <?php foreach (
                                                $masterData['communities']
                                                    ?? []
                                                as $community
                                            ): ?>

                                                <?php
                                                $communityId =
                                                    (int) (
                                                        $community['id']
                                                        ?? 0
                                                    );
                                                ?>

                                                <option
                                                    value="<?= esc(
                                                                (string)
                                                                $communityId,
                                                                'attr'
                                                            ) ?>"
                                                    <?= in_array(
                                                        $communityId,
                                                        array_map(
                                                            'intval',
                                                            $selected(
                                                                'community_ids'
                                                            )
                                                        ),
                                                        true
                                                    )
                                                        ? 'selected'
                                                        : '' ?>>

                                                    <?= esc(
                                                        (string) (
                                                            $community['name']
                                                            ?? ''
                                                        )
                                                    ) ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label
                                            for="searchCities"
                                            class="form-label">

                                            City Living In
                                        </label>

                                        <select
                                            id="searchCities"
                                            name="city_ids[]"
                                            class="form-select"
                                            multiple
                                            data-choice
                                            data-choice-search="true"
                                            data-choice-remove="true">

                                            <?php foreach (
                                                $masterData['cities']
                                                    ?? []
                                                as $city
                                            ): ?>

                                                <?php
                                                $cityId =
                                                    (int) (
                                                        $city['id']
                                                        ?? 0
                                                    );
                                                ?>

                                                <option
                                                    value="<?= esc(
                                                                (string)
                                                                $cityId,
                                                                'attr'
                                                            ) ?>"
                                                    <?= in_array(
                                                        $cityId,
                                                        array_map(
                                                            'intval',
                                                            $selected(
                                                                'city_ids'
                                                            )
                                                        ),
                                                        true
                                                    )
                                                        ? 'selected'
                                                        : '' ?>>

                                                    <?= esc(
                                                        (string) (
                                                            $city['name']
                                                            ?? ''
                                                        )
                                                    ) ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>
                                    </div>

                                    <!-- Highest Education -->

                                    <div class="col-12 col-lg-6">
                                        <label
                                            for="searchEducation"
                                            class="form-label">

                                            Highest Education
                                        </label>

                                        <select
                                            id="searchEducation"
                                            name="education_ids[]"
                                            class="form-select"
                                            multiple
                                            data-choice
                                            data-choice-search="true"
                                            data-choice-remove="true">

                                            <?php foreach (
                                                $masterData['educationGroups']
                                                    ?? []
                                                as $group
                                            ): ?>

                                                <optgroup
                                                    label="<?= esc(
                                                                (string) (
                                                                    $group['category_name']
                                                                    ?? ''
                                                                ),
                                                                'attr'
                                                            ) ?>">

                                                    <?php foreach (
                                                        $group['options']
                                                            ?? []
                                                        as $option
                                                    ): ?>

                                                        <?php
                                                        $optionId =
                                                            (int) (
                                                                $option['id']
                                                                ?? 0
                                                            );
                                                        ?>

                                                        <option
                                                            value="<?= esc(
                                                                        (string)
                                                                        $optionId,
                                                                        'attr'
                                                                    ) ?>"
                                                            <?= in_array(
                                                                $optionId,
                                                                array_map(
                                                                    'intval',
                                                                    $selected(
                                                                        'education_ids'
                                                                    )
                                                                ),
                                                                true
                                                            )
                                                                ? 'selected'
                                                                : '' ?>>

                                                            <?= esc(
                                                                (string) (
                                                                    $option['name']
                                                                    ?? ''
                                                                )
                                                            ) ?>

                                                        </option>

                                                    <?php endforeach; ?>

                                                </optgroup>

                                            <?php endforeach; ?>

                                        </select>
                                    </div>

                                    <!-- Occupation -->

                                    <div class="col-12 col-lg-6">
                                        <label
                                            for="searchOccupation"
                                            class="form-label">

                                            Occupation
                                        </label>

                                        <select
                                            id="searchOccupation"
                                            name="occupation_ids[]"
                                            class="form-select"
                                            multiple
                                            data-choice
                                            data-choice-search="true"
                                            data-choice-remove="true">

                                            <?php foreach (
                                                $masterData['occupationGroups']
                                                    ?? []
                                                as $group
                                            ): ?>

                                                <optgroup
                                                    label="<?= esc(
                                                                (string) (
                                                                    $group['category_name']
                                                                    ?? ''
                                                                ),
                                                                'attr'
                                                            ) ?>">

                                                    <?php foreach (
                                                        $group['options']
                                                            ?? []
                                                        as $option
                                                    ): ?>

                                                        <?php
                                                        $optionId =
                                                            (int) (
                                                                $option['id']
                                                                ?? 0
                                                            );
                                                        ?>

                                                        <option
                                                            value="<?= esc(
                                                                        (string)
                                                                        $optionId,
                                                                        'attr'
                                                                    ) ?>"
                                                            <?= in_array(
                                                                $optionId,
                                                                array_map(
                                                                    'intval',
                                                                    $selected(
                                                                        'occupation_ids'
                                                                    )
                                                                ),
                                                                true
                                                            )
                                                                ? 'selected'
                                                                : '' ?>>

                                                            <?= esc(
                                                                (string) (
                                                                    $option['name']
                                                                    ?? ''
                                                                )
                                                            ) ?>

                                                        </option>

                                                    <?php endforeach; ?>

                                                </optgroup>

                                            <?php endforeach; ?>

                                        </select>
                                    </div>

                                    <!-- Employment type -->

                                    <div class="col-12 col-lg-6">
                                        <label
                                            for="searchEmployedIn"
                                            class="form-label">

                                            Employed In
                                        </label>

                                        <select
                                            id="searchEmployedIn"
                                            name="employed_in[]"
                                            class="form-select"
                                            multiple
                                            data-choice
                                            data-choice-search="true"
                                            data-choice-remove="true">

                                            <?php foreach (
                                                $masterData['employmentTypes']
                                                    ?? []
                                                as $employment
                                            ): ?>

                                                <?php
                                                $value =
                                                    trim(
                                                        (string) (
                                                            $employment['value']
                                                            ?? ''
                                                        )
                                                    );
                                                ?>

                                                <option
                                                    value="<?= esc(
                                                                $value,
                                                                'attr'
                                                            ) ?>"
                                                    <?= in_array(
                                                        $value,
                                                        $selected(
                                                            'employed_in'
                                                        ),
                                                        true
                                                    )
                                                        ? 'selected'
                                                        : '' ?>>

                                                    <?= esc(
                                                        (string) (
                                                            $employment['label']
                                                            ?? ''
                                                        )
                                                    ) ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>
                                    </div>

                                    <!-- Annual-income range -->

                                    <div class="col-6 col-lg-3">
                                        <label
                                            for="incomeFrom"
                                            class="form-label">

                                            Income From
                                        </label>

                                        <select
                                            id="incomeFrom"
                                            name="annual_income_from_id"
                                            class="form-select"
                                            data-choice
                                            data-choice-search="true">

                                            <option value="">
                                                Any
                                            </option>

                                            <?php foreach (
                                                $masterData['annualIncomes']
                                                    ?? []
                                                as $income
                                            ): ?>

                                                <?php
                                                $incomeId =
                                                    (int) (
                                                        $income['id']
                                                        ?? 0
                                                    );
                                                ?>

                                                <option
                                                    value="<?= esc(
                                                                (string)
                                                                $incomeId,
                                                                'attr'
                                                            ) ?>"
                                                    <?= (int) (
                                                        $filters['annual_income_from_id']
                                                        ?? 0
                                                    ) === $incomeId
                                                        ? 'selected'
                                                        : '' ?>>

                                                    <?= esc(
                                                        (string) (
                                                            $income['display_name']
                                                            ?? ''
                                                        )
                                                    ) ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>
                                    </div>

                                    <div class="col-6 col-lg-3">
                                        <label
                                            for="incomeTo"
                                            class="form-label">

                                            Income To
                                        </label>

                                        <select
                                            id="incomeTo"
                                            name="annual_income_to_id"
                                            class="form-select"
                                            data-choice
                                            data-choice-search="true">

                                            <option value="">
                                                Any
                                            </option>

                                            <?php foreach (
                                                $masterData['annualIncomes']
                                                    ?? []
                                                as $income
                                            ): ?>

                                                <?php
                                                $incomeId =
                                                    (int) (
                                                        $income['id']
                                                        ?? 0
                                                    );
                                                ?>

                                                <option
                                                    value="<?= esc(
                                                                (string)
                                                                $incomeId,
                                                                'attr'
                                                            ) ?>"
                                                    <?= (int) (
                                                        $filters['annual_income_to_id']
                                                        ?? 0
                                                    ) === $incomeId
                                                        ? 'selected'
                                                        : '' ?>>

                                                    <?= esc(
                                                        (string) (
                                                            $income['display_name']
                                                            ?? ''
                                                        )
                                                    ) ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>
                                    </div>

                                </div>

                                <!--
                            Keep the existing Profile Managed By and Lifestyle
                            markup from the current Search branch here.

                            They already use the correct existing profile values
                            and Lifestyle tag UI; they do not need conversion to
                            Choices because those requirements were specifically
                            requested as checkbox/tag interfaces.
                        -->

                            <?php endif; ?>

                            <!-- =================================================
                         Actions
                         ================================================= -->

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
                                    class="btn btn-outline-danger">

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
            </aside>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>