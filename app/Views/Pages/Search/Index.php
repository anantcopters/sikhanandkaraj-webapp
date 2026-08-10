<?php

declare(strict_types=1);

/**
 * Search page UI variables.
 *
 * @var string                     $pageTitle
 * @var string                     $mode
 * @var string                     $sort
 * @var int                        $page
 * @var int                        $perPage
 * @var int                        $total
 * @var int                        $totalPages
 * @var list<array<string, mixed>> $profiles
 * @var array<string, mixed>       $filters
 * @var array<string, mixed>       $masterData
 * @var array<string, string>|null $formAlert
 */

/*
 * --------------------------------------------------------------------------
 * Normalize view-local values
 * --------------------------------------------------------------------------
 *
 * Controller/service supplied values are normalized once before rendering.
 * Business rules remain in the service layer.
 */

$this->extend(
    'Layouts/Main'
);

$this->section(
    'content'
);

$mode =
    ($mode ?? 'basic')
    === 'advanced'
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

$profiles =
    isset($profiles)
    && is_array($profiles)
    ? $profiles
    : [];

$total =
    max(
        0,
        (int) (
            $total
            ?? 0
        )
    );

$page =
    max(
        1,
        (int) (
            $page
            ?? 1
        )
    );

$totalPages =
    max(
        1,
        (int) (
            $totalPages
            ?? 1
        )
    );

$sort =
    (string) (
        $sort
        ?? 'default'
    );

$selected =
    static function (
        string $key
    ) use (
        $filters
    ): array {
        return isset(
            $filters[$key]
        )
            && is_array(
                $filters[$key]
            )
            ? $filters[$key]
            : [];
    };
?>

<section class="py-3 py-lg-4">
    <div class="container">

        <?= view(
            'Pages/Profile/Partials/_feedback_alert',
            [
                'formAlert' =>
                $formAlert ?? null,
            ]
        ) ?>

        <div class="mb-4">
            <h1 class="fs-24 fw-semibold mb-1">
                Search Profiles
            </h1>

            <p class="text-muted mb-0">
                Find profiles using the preferences
                most important to you.
            </p>
        </div>

        <!-- Profile ID search -->
        <div
            class="card border border-danger
                border-opacity-25 shadow-sm mb-4">

            <div class="card-body p-3 p-md-4">

                <form
                    method="get"
                    action="<?= url_to(
                                'web.search.profile'
                            ) ?>">

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
                            class="form-control"
                            maxlength="50"
                            placeholder="Enter Profile ID"
                            autocomplete="off">

                        <button
                            type="submit"
                            class="btn btn-primary">

                            <i
                                class="ri-search-line me-1"
                                aria-hidden="true">
                            </i>

                            Search Profile
                        </button>

                    </div>

                    <div class="form-text">
                        Enter the exact Profile ID to
                        directly open an available profile.
                    </div>

                </form>

            </div>
        </div>

        <div
            class="card border border-danger
                border-opacity-25 shadow-sm mb-4">

            <div class="card-body p-3 p-md-4">

                <!-- Main tabs -->
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
                            class="nav-link <?= $mode === 'basic'
                                                ? 'active'
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
                            class="nav-link <?= $mode === 'advanced'
                                                ? 'active'
                                                : '' ?>">

                            Advanced Search
                        </a>
                    </li>

                </ul>

                <form
                    method="get"
                    action="<?= url_to(
                                'web.search'
                            ) ?>"
                    id="memberSearchForm">

                    <input
                        type="hidden"
                        name="mode"
                        value="<?= esc(
                                    $mode,
                                    'attr'
                                ) ?>">

                    <!-- Basic fields -->
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

                        <div class="col-6 col-lg-3">

                            <label class="form-label">
                                Height From
                            </label>

                            <select
                                name="height_min_id"
                                class="form-select">

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

                            <label class="form-label">
                                Height To
                            </label>

                            <select
                                name="height_max_id"
                                class="form-select">

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

                        <div class="col-12 col-md-6">

                            <label class="form-label">
                                Marital Status
                            </label>

                            <select
                                name="marital_status_ids[]"
                                class="form-select"
                                multiple>

                                <?php foreach (
                                    $masterData['maritalStatuses']
                                        ?? []
                                    as $row
                                ): ?>

                                    <?php
                                    $id =
                                        (int) (
                                            $row['id']
                                            ?? 0
                                        );
                                    ?>

                                    <option
                                        value="<?= esc(
                                                    (string)
                                                    $id,
                                                    'attr'
                                                ) ?>"
                                        <?= in_array(
                                            $id,
                                            $selected(
                                                'marital_status_ids'
                                            ),
                                            true
                                        )
                                            ? 'selected'
                                            : '' ?>>

                                        <?= esc(
                                            (string) (
                                                $row['name']
                                                ?? ''
                                            )
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-12 col-md-6">

                            <label class="form-label">
                                State Living In
                            </label>

                            <select
                                name="state_ids[]"
                                id="searchStates"
                                class="form-select"
                                multiple>

                                <?php foreach (
                                    $masterData['states']
                                        ?? []
                                    as $row
                                ): ?>

                                    <?php
                                    $id =
                                        (int) (
                                            $row['id']
                                            ?? 0
                                        );
                                    ?>

                                    <option
                                        value="<?= esc(
                                                    (string)
                                                    $id,
                                                    'attr'
                                                ) ?>"
                                        <?= in_array(
                                            $id,
                                            $selected(
                                                'state_ids'
                                            ),
                                            true
                                        )
                                            ? 'selected'
                                            : '' ?>>

                                        <?= esc(
                                            (string) (
                                                $row['name']
                                                ?? ''
                                            )
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-12">

                            <label class="form-label fw-semibold">
                                Photo Settings
                            </label>

                            <div
                                class="d-flex flex-wrap gap-3">

                                <div class="form-check">

                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="photo_visibility[]"
                                        value="PUBLIC"
                                        id="photoPublic"
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
                                        name="photo_visibility[]"
                                        value="INTERESTED_MEMBERS"
                                        id="photoInterested"
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

                        <hr class="my-4">

                        <div class="row g-3">

                            <div class="col-12 col-md-6">

                                <label class="form-label">
                                    Community
                                </label>

                                <select
                                    name="community_ids[]"
                                    class="form-select"
                                    multiple>

                                    <?php foreach (
                                        $masterData['communities']
                                            ?? []
                                        as $row
                                    ): ?>

                                        <?php
                                        $id =
                                            (int) (
                                                $row['id']
                                                ?? 0
                                            );
                                        ?>

                                        <option
                                            value="<?= esc(
                                                        (string)
                                                        $id,
                                                        'attr'
                                                    ) ?>"
                                            <?= in_array(
                                                $id,
                                                $selected(
                                                    'community_ids'
                                                ),
                                                true
                                            )
                                                ? 'selected'
                                                : '' ?>>

                                            <?= esc(
                                                (string) (
                                                    $row['name']
                                                    ?? ''
                                                )
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <div class="col-12 col-md-6">

                                <label class="form-label">
                                    City Living In
                                </label>

                                <select
                                    name="city_ids[]"
                                    id="searchCities"
                                    class="form-select"
                                    multiple>

                                    <?php foreach (
                                        $masterData['cities']
                                            ?? []
                                        as $row
                                    ): ?>

                                        <option
                                            value="<?= esc(
                                                        (string) (
                                                            $row['id']
                                                            ?? ''
                                                        ),
                                                        'attr'
                                                    ) ?>">

                                            <?= esc(
                                                (string) (
                                                    $row['name']
                                                    ?? ''
                                                )
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <div class="col-12">

                                <label class="form-label fw-semibold">
                                    Profile Managed By
                                </label>

                                <div class="d-flex flex-wrap gap-3">

                                    <?php foreach (
                                        $masterData['profileManagedBy']
                                            ?? []
                                        as $option
                                    ): ?>

                                        <?php
                                        $value =
                                            (string) (
                                                $option['value']
                                                ?? ''
                                            );
                                        ?>

                                        <div class="form-check">

                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                name="managed_by[]"
                                                value="<?= esc(
                                                            $value,
                                                            'attr'
                                                        ) ?>"
                                                id="managed<?= esc(
                                                                ucfirst(
                                                                    $value
                                                                ),
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
                                                for="managed<?= esc(
                                                                ucfirst(
                                                                    $value
                                                                ),
                                                                'attr'
                                                            ) ?>">

                                                <?= esc(
                                                    (string) (
                                                        $option['label']
                                                        ?? ''
                                                    )
                                                ) ?>
                                            </label>

                                        </div>

                                    <?php endforeach; ?>

                                </div>

                            </div>

                            <!-- Education -->
                            <div class="col-12 col-lg-6">

                                <label class="form-label">
                                    Highest Education
                                </label>

                                <select
                                    name="education_ids[]"
                                    class="form-select"
                                    multiple>

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
                                                $id =
                                                    (int) (
                                                        $option['id']
                                                        ?? 0
                                                    );
                                                ?>

                                                <option
                                                    value="<?= esc(
                                                                (string)
                                                                $id,
                                                                'attr'
                                                            ) ?>"
                                                    <?= in_array(
                                                        $id,
                                                        $selected(
                                                            'education_ids'
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

                                <label class="form-label">
                                    Occupation
                                </label>

                                <select
                                    name="occupation_ids[]"
                                    class="form-select"
                                    multiple>

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
                                                $id =
                                                    (int) (
                                                        $option['id']
                                                        ?? 0
                                                    );
                                                ?>

                                                <option
                                                    value="<?= esc(
                                                                (string)
                                                                $id,
                                                                'attr'
                                                            ) ?>"
                                                    <?= in_array(
                                                        $id,
                                                        $selected(
                                                            'occupation_ids'
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

                            <div class="col-12 col-lg-6">

                                <label class="form-label">
                                    Employed In
                                </label>

                                <select
                                    name="employed_in[]"
                                    class="form-select"
                                    multiple>

                                    <?php foreach (
                                        $masterData['employmentTypes']
                                            ?? []
                                        as $option
                                    ): ?>

                                        <?php
                                        $value =
                                            (string) (
                                                $option['value']
                                                ?? ''
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
                                                    $option['label']
                                                    ?? ''
                                                )
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <div class="col-6 col-lg-3">

                                <label class="form-label">
                                    Income From
                                </label>

                                <select
                                    name="annual_income_from_id"
                                    class="form-select">

                                    <option value="">
                                        Any
                                    </option>

                                    <?php foreach (
                                        $masterData['annualIncomes']
                                            ?? []
                                        as $income
                                    ): ?>

                                        <option
                                            value="<?= esc(
                                                        (string) (
                                                            $income['id']
                                                            ?? ''
                                                        ),
                                                        'attr'
                                                    ) ?>">

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

                                <label class="form-label">
                                    Income To
                                </label>

                                <select
                                    name="annual_income_to_id"
                                    class="form-select">

                                    <option value="">
                                        Any
                                    </option>

                                    <?php foreach (
                                        $masterData['annualIncomes']
                                            ?? []
                                        as $income
                                    ): ?>

                                        <option
                                            value="<?= esc(
                                                        (string) (
                                                            $income['id']
                                                            ?? ''
                                                        ),
                                                        'attr'
                                                    ) ?>">

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

                        <!-- Lifestyle -->
                        <hr class="my-4">

                        <h2 class="fs-16 fw-semibold mb-1">
                            Lifestyle
                        </h2>

                        <p class="text-muted fs-13 mb-3">
                            Select lifestyle choices that
                            should match the profile.
                        </p>

                        <ul
                            class="nav nav-tabs flex-nowrap
                                overflow-auto mb-3"
                            role="tablist">

                            <?php foreach (
                                $masterData['lifestyleCategories']
                                    ?? []
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
                                    class="nav-item flex-shrink-0">

                                    <button
                                        type="button"
                                        class="nav-link <?= $index === 0
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
                                $masterData['lifestyleCategories']
                                    ?? []
                                as $index => $category
                            ): ?>

                                <?php
                                $categoryId =
                                    (int) (
                                        $category['id']
                                        ?? 0
                                    );

                                $options =
                                    $masterData['lifestyleOptionsByCategory'][$categoryId]
                                    ?? [];
                                ?>

                                <div
                                    class="tab-pane fade <?= $index === 0
                                                                ? 'show active'
                                                                : '' ?>"
                                    id="searchLifestyle<?= esc(
                                                            (string)
                                                            $categoryId,
                                                            'attr'
                                                        ) ?>">

                                    <div
                                        class="d-flex flex-wrap gap-2 py-2">

                                        <?php foreach (
                                            $options
                                            as $option
                                        ): ?>

                                            <?php
                                            $optionId =
                                                (int) (
                                                    $option['id']
                                                    ?? 0
                                                );

                                            $checked =
                                                in_array(
                                                    $optionId,
                                                    $selected(
                                                        'lifestyle_option_ids'
                                                    ),
                                                    true
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
                                                <?= $checked
                                                    ? 'checked'
                                                    : '' ?>>

                                            <label
                                                class="btn btn-outline-primary"
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

                    <?php endif; ?>

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

        <!-- Results -->
        <div class="row g-4">

            <!-- Quick Search -->
            <aside class="col-12 col-lg-3">

                <div
                    class="card border border-danger
                        border-opacity-25 shadow-sm">

                    <div class="card-body p-4">

                        <div
                            class="avatar-sm mb-3"
                            aria-hidden="true">

                            <span
                                class="avatar-title
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary">

                                <i
                                    class="ri-flashlight-line
                                        fs-20">
                                </i>

                            </span>

                        </div>

                        <h2 class="fs-16 fw-semibold mb-2">
                            Quick Search
                        </h2>

                        <p class="text-muted fs-13 mb-0">
                            Quick search options will be
                            available here soon.
                        </p>

                    </div>
                </div>

            </aside>

            <div class="col-12 col-lg-9">

                <div
                    class="d-flex flex-column
                        flex-sm-row
                        align-items-sm-center
                        justify-content-between
                        gap-3 mb-3">

                    <div>
                        <h2
                            class="fs-20 fw-semibold mb-1">

                            Search Results
                        </h2>

                        <p class="text-muted mb-0">
                            <?= esc(
                                (string)
                                $total
                            ) ?>
                            matching profiles
                        </p>
                    </div>

                    <form
                        method="get"
                        action="<?= url_to(
                                    'web.search'
                                ) ?>"
                        class="d-flex align-items-center gap-2">

                        <?php foreach (
                            $_GET as $key => $value
                        ): ?>

                            <?php if (
                                $key === 'sort'
                                || $key === 'page'
                            ) {
                                continue;
                            } ?>

                            <?php if (
                                is_array($value)
                            ): ?>

                                <?php foreach (
                                    $value as $item
                                ): ?>

                                    <input
                                        type="hidden"
                                        name="<?= esc(
                                                    $key
                                                        . '[]',
                                                    'attr'
                                                ) ?>"
                                        value="<?= esc(
                                                    (string)
                                                    $item,
                                                    'attr'
                                                ) ?>">

                                <?php endforeach; ?>

                            <?php else: ?>

                                <input
                                    type="hidden"
                                    name="<?= esc(
                                                $key,
                                                'attr'
                                            ) ?>"
                                    value="<?= esc(
                                                (string)
                                                $value,
                                                'attr'
                                            ) ?>">

                            <?php endif; ?>

                        <?php endforeach; ?>

                        <label
                            for="searchSort"
                            class="text-muted
                                fs-13 text-nowrap">
                            Sort:
                        </label>

                        <select
                            id="searchSort"
                            name="sort"
                            class="form-select form-select-sm"
                            onchange="this.form.submit()">

                            <option
                                value="default"
                                <?= $sort === 'default'
                                    ? 'selected'
                                    : '' ?>>
                                Default Order
                            </option>

                            <option
                                value="latest"
                                <?= $sort === 'latest'
                                    ? 'selected'
                                    : '' ?>>
                                Latest First
                            </option>

                            <option
                                value="oldest"
                                <?= $sort === 'oldest'
                                    ? 'selected'
                                    : '' ?>>
                                Oldest First
                            </option>

                            <option
                                value="last_login"
                                <?= $sort === 'last_login'
                                    ? 'selected'
                                    : '' ?>>
                                Last Logged In
                            </option>

                        </select>

                    </form>

                </div>

                <?php if ($profiles === []): ?>

                    <div
                        class="card border border-danger
                            border-opacity-25 shadow-sm">

                        <div
                            class="card-body p-5 text-center">

                            <i
                                class="ri-search-eye-line
                                    fs-36 text-muted"
                                aria-hidden="true">
                            </i>

                            <h3
                                class="fs-18 fw-semibold
                                    mt-3 mb-2">

                                No profiles found
                            </h3>

                            <p class="text-muted mb-0">
                                Try widening one or more
                                search preferences.
                            </p>

                        </div>

                    </div>

                <?php else: ?>

                    <div class="row g-3">

                        <?php foreach (
                            $profiles
                            as $profile
                        ): ?>

                            <div class="col-12 col-xl-6">

                                <?= view(
                                    'Pages/Search/_profile_card',
                                    [
                                        'profile' =>
                                        $profile,
                                    ]
                                ) ?>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <?php if (
                        $totalPages > 1
                    ): ?>

                        <nav
                            class="mt-4"
                            aria-label="Search results pages">

                            <ul
                                class="pagination
                                    justify-content-center
                                    mb-0">

                                <?php for (
                                    $pageNumber = 1;
                                    $pageNumber <= $totalPages;
                                    ++$pageNumber
                                ): ?>

                                    <?php
                                    $query =
                                        $_GET;

                                    $query['page'] =
                                        $pageNumber;

                                    $url =
                                        route_to(
                                            'web.search'
                                        )
                                        . '?'
                                        . http_build_query(
                                            $query
                                        );
                                    ?>

                                    <li
                                        class="page-item <?= $pageNumber === $page
                                                                ? 'active'
                                                                : '' ?>">

                                        <a
                                            class="page-link"
                                            href="<?= esc(
                                                        $url,
                                                        'attr'
                                                    ) ?>">

                                            <?= esc(
                                                (string)
                                                $pageNumber
                                            ) ?>

                                        </a>

                                    </li>

                                <?php endfor; ?>

                            </ul>

                        </nav>

                    <?php endif; ?>

                <?php endif; ?>

            </div>
        </div>

    </div>
</section>

<?php $this->endSection(); ?>