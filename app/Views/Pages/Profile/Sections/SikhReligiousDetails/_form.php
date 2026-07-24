<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $sikhReligiousDetails */
/** @var array<string, mixed>|null $masterData */
/** @var array<string, string>|null $validationErrors */

$sikhReligiousDetails = isset($sikhReligiousDetails)
    && is_array($sikhReligiousDetails)
    ? $sikhReligiousDetails
    : [];

$masterData = isset($masterData)
    && is_array($masterData)
    ? $masterData
    : [];

$validationErrors = isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$details = $sikhReligiousDetails;
$errors = $validationErrors;
$options = $masterData;

$communities = $options['communities'] ?? [];
$subcommunities = $options['subcommunities'] ?? [];
$moonSigns = $options['moonSigns'] ?? [];
$birthStars = $options['birthStars'] ?? [];
$birthHours = $options['birthHours'] ?? range(1, 12);
$birthMinutes = $options['birthMinutes'] ?? range(0, 59);
$doshOptions = $options['doshOptions'] ?? [];
$country = $options['country'] ?? [];
$states = $options['states'] ?? [];
$cities = $options['cities'] ?? [];

$value = static function (
    string $field,
    mixed $stored = ''
): string {
    $oldValue = old($field, null, false);

    return $oldValue !== null
        ? (string) $oldValue
        : (string) $stored;
};

$selected = static function (
    string $field,
    string $option,
    mixed $stored = ''
) use ($value): string {
    return $value($field, $stored) === $option
        ? 'selected'
        : '';
};

$checked = static function (
    string $field,
    string $option,
    mixed $stored = ''
) use ($value): string {
    return strtoupper(
        $value($field, $stored)
    ) === strtoupper($option)
        ? 'checked'
        : '';
};

$selectedCommunity = $value(
    'community_id',
    $details['community_id'] ?? ''
);

$selectedSubcommunity = $value(
    'subcommunity_id',
    $details['subcommunity_id'] ?? ''
);

$selectedBirthState = $value(
    'birth_state_id',
    $details['birth_state_id'] ?? ''
);

$selectedBirthCity = $value(
    'birth_city_id',
    $details['birth_city_id'] ?? ''
);
?>

<form
    method="post"
    action="<?= url_to(
                'web.profile.sikh-religious-details.update'
            ) ?>"
    id="sikhReligiousDetailsForm"
    data-validate
    novalidate>

    <?= csrf_field() ?>

    <div class="row g-3">

        <div class="col-12 col-md-6">
            <label
                for="sikhCommunityId"
                class="form-label fw-medium">
                Community / Caste
                <span class="text-danger">*</span>
            </label>

            <select
                id="sikhCommunityId"
                name="community_id"
                class="form-select"
                data-choice
                data-choice-search="true" data-choice-position="bottom"
                data-error-required="Please select your community."
                required>

                <option value="">
                    Select community
                </option>

                <?php foreach ($communities as $community): ?>
                    <?php
                    $communityId = (string) (
                        $community['id'] ?? ''
                    );
                    ?>

                    <option
                        value="<?= esc(
                                    $communityId,
                                    'attr'
                                ) ?>"
                        <?= $selected(
                            'community_id',
                            $communityId,
                            $details['community_id'] ?? ''
                        ) ?>>
                        <?= esc(
                            (string) (
                                $community['name'] ?? ''
                            )
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' => 'community_id',
                    'errorId' => 'communityIdError',
                    'errors' => $errors,
                ]
            ) ?>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="sikhSubcommunityId"
                class="form-label fw-medium">
                Sub-community / Sub-caste
                <span class="text-danger">*</span>
            </label>

            <select
                id="sikhSubcommunityId"
                name="subcommunity_id"
                class="form-select"
                data-choice
                data-choice-search="true"
                data-choice-position="bottom"
                data-subcommunities-url="<?= esc(
                                                site_url(
                                                    'profile/master/sikh-subcommunities'
                                                ),
                                                'attr'
                                            ) ?>"
                data-selected-subcommunity="<?= esc(
                                                $selectedSubcommunity,
                                                'attr'
                                            ) ?>"
                data-error-required="Please select your sub-community."
                <?= $selectedCommunity === ''
                    ? 'disabled'
                    : '' ?>
                required>

                <option value="">
                    <?= $selectedCommunity === ''
                        ? 'Select community first'
                        : 'Select sub-community' ?>
                </option>

                <?php foreach (
                    $subcommunities as $subcommunity
                ): ?>
                    <?php
                    $subcommunityId = (string) (
                        $subcommunity['id'] ?? ''
                    );
                    ?>

                    <option
                        value="<?= esc(
                                    $subcommunityId,
                                    'attr'
                                ) ?>"
                        <?= $selected(
                            'subcommunity_id',
                            $subcommunityId,
                            $details['subcommunity_id'] ?? ''
                        ) ?>>
                        <?= esc(
                            (string) (
                                $subcommunity['name'] ?? ''
                            )
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' => 'subcommunity_id',
                    'errorId' =>
                    'subcommunityIdError',
                    'errors' => $errors,
                ]
            ) ?>
        </div>

        <div class="col-12">
            <hr class="my-2 mb-3">

            <h2 class="fs-16 fw-semibold mb-1">
                Birth Details
            </h2>

            <p class="text-muted fs-13 mb-0">
                Birth time and place of birth are required.
            </p>
        </div>

        <div class="col-4 col-md-2">
            <label
                for="birthHour"
                class="form-label fw-medium">
                Hour
                <span class="text-danger">*</span>
            </label>

            <select
                id="birthHour"
                name="birth_hour"
                class="form-select"
                data-choice
                data-choice-search="false"
                data-error-required="Please select the birth hour."
                required>

                <option value="">HH</option>

                <?php foreach ($birthHours as $hour): ?>
                    <?php $hourValue = (string) $hour; ?>

                    <option
                        value="<?= esc(
                                    $hourValue,
                                    'attr'
                                ) ?>"
                        <?= $selected(
                            'birth_hour',
                            $hourValue,
                            $details['birth_hour'] ?? ''
                        ) ?>>
                        <?= esc(
                            str_pad(
                                $hourValue,
                                2,
                                '0',
                                STR_PAD_LEFT
                            )
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-4 col-md-2">
            <label
                for="birthMinute"
                class="form-label fw-medium">
                Minute
                <span class="text-danger">*</span>
            </label>

            <select
                id="birthMinute"
                name="birth_minute"
                class="form-select"
                data-choice
                data-choice-search="false"
                data-error-required="Please select the birth minute."
                required>

                <option value="">MM</option>

                <?php foreach ($birthMinutes as $minute): ?>
                    <?php $minuteValue = (string) $minute; ?>

                    <option
                        value="<?= esc(
                                    $minuteValue,
                                    'attr'
                                ) ?>"
                        <?= $selected(
                            'birth_minute',
                            $minuteValue,
                            $details['birth_minute'] ?? ''
                        ) ?>>
                        <?= esc(
                            str_pad(
                                $minuteValue,
                                2,
                                '0',
                                STR_PAD_LEFT
                            )
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-4 col-md-2">
            <label
                for="birthMeridiem"
                class="form-label fw-medium">
                AM / PM
                <span class="text-danger">*</span>
            </label>

            <select
                id="birthMeridiem"
                name="birth_meridiem"
                class="form-select"
                data-choice
                data-choice-search="false"
                data-error-required="Please select AM or PM."
                required>

                <option value="">--</option>

                <option
                    value="AM"
                    <?= $selected(
                        'birth_meridiem',
                        'AM',
                        $details['birth_meridiem'] ?? ''
                    ) ?>>
                    AM
                </option>

                <option
                    value="PM"
                    <?= $selected(
                        'birth_meridiem',
                        'PM',
                        $details['birth_meridiem'] ?? ''
                    ) ?>>
                    PM
                </option>
            </select>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="gotra"
                class="form-label fw-medium">
                Gotra
            </label>

            <input
                type="text"
                id="gotra"
                name="gotra"
                class="form-control"
                maxlength="100"
                value="<?= esc(
                            $value(
                                'gotra',
                                $details['gotra'] ?? ''
                            ),
                            'attr'
                        ) ?>"
                placeholder="Enter gotra, if applicable">

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' => 'gotra',
                    'errorId' => 'gotraError',
                    'errors' => $errors,
                ]
            ) ?>
        </div>

        <div class="col-12 col-md-4">
            <label
                for="birthStateId"
                class="form-label fw-medium">
                State of birth
                <span class="text-danger">*</span>
            </label>

            <select
                id="birthStateId"
                name="birth_state_id"
                class="form-select"
                data-choice
                data-choice-search="true"
                data-choice-position="bottom"
                data-error-required="Please select the state of birth."
                required>

                <option value="">Select state</option>

                <?php foreach ($states as $state): ?>
                    <?php
                    $stateId = (string) (
                        $state['id'] ?? ''
                    );
                    ?>

                    <option
                        value="<?= esc(
                                    $stateId,
                                    'attr'
                                ) ?>"
                        <?= $selected(
                            'birth_state_id',
                            $stateId,
                            $details['birth_state_id'] ?? ''
                        ) ?>>
                        <?= esc(
                            (string) ($state['name'] ?? '')
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' => 'birth_state_id',
                    'errorId' => 'birthStateIdError',
                    'errors' => $errors,
                ]
            ) ?>
        </div>

        <div class="col-12 col-md-4">
            <label
                for="birthCityId"
                class="form-label fw-medium">
                City of birth
                <span class="text-danger">*</span>
            </label>

            <select
                id="birthCityId"
                name="birth_city_id"
                class="form-select"
                data-choice
                data-choice-search="true"
                data-choice-position="bottom"
                data-cities-url="<?= esc(
                                        site_url('profile/master/cities'),
                                        'attr'
                                    ) ?>"
                data-selected-city="<?= esc(
                                        $selectedBirthCity,
                                        'attr'
                                    ) ?>"
                data-error-required="Please select the city of birth."
                <?= $selectedBirthState === ''
                    ? 'disabled'
                    : '' ?>
                required>

                <option value="">
                    <?= $selectedBirthState === ''
                        ? 'Select state first'
                        : 'Select city' ?>
                </option>

                <?php foreach ($cities as $city): ?>
                    <?php
                    $cityId = (string) (
                        $city['id'] ?? ''
                    );
                    ?>

                    <option
                        value="<?= esc(
                                    $cityId,
                                    'attr'
                                ) ?>"
                        <?= $selected(
                            'birth_city_id',
                            $cityId,
                            $details['birth_city_id'] ?? ''
                        ) ?>>
                        <?= esc(
                            (string) ($city['name'] ?? '')
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' => 'birth_city_id',
                    'errorId' => 'birthCityIdError',
                    'errors' => $errors,
                ]
            ) ?>
        </div>

        <div class="col-12 col-md-4">
            <label
                for="birthCountry"
                class="form-label fw-medium">
                Country of birth
            </label>

            <input
                type="text"
                id="birthCountry"
                class="form-control bg-light"
                value="<?= esc(
                            (string) (
                                $country['name'] ?? 'India'
                            ),
                            'attr'
                        ) ?>"
                readonly>

            <input
                type="hidden"
                name="birth_country_id"
                value="<?= esc(
                            (string) ($country['id'] ?? ''),
                            'attr'
                        ) ?>">
        </div>

        <div class="col-12">
            <hr class="my-2 mb-3">

            <h2 class="fs-16 fw-semibold mb-1">
                Optional Astrological Details
            </h2>

            <p class="text-muted fs-13 mb-0">
                These fields are optional and shown only when
                you choose to provide them.
            </p>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="moonSignId"
                class="form-label fw-medium">
                Raashi / Moon sign
            </label>

            <select
                id="moonSignId"
                name="moon_sign_id"
                class="form-select"
                data-choice
                data-choice-search="true">

                <option value="">
                    Select moon sign
                </option>

                <?php foreach ($moonSigns as $moonSign): ?>
                    <?php
                    $moonSignId = (string) (
                        $moonSign['id'] ?? ''
                    );
                    ?>

                    <option
                        value="<?= esc(
                                    $moonSignId,
                                    'attr'
                                ) ?>"
                        <?= $selected(
                            'moon_sign_id',
                            $moonSignId,
                            $details['moon_sign_id'] ?? ''
                        ) ?>>
                        <?= esc(
                            sprintf(
                                '%s (%s)',
                                (string) (
                                    $moonSign['name'] ?? ''
                                ),
                                (string) (
                                    $moonSign['english_name'] ?? ''
                                )
                            )
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="birthStarId"
                class="form-label fw-medium">
                Birth star / Nakshatra
            </label>

            <select
                id="birthStarId"
                name="birth_star_id"
                class="form-select"
                data-choice
                data-choice-search="true">

                <option value="">
                    Select birth star
                </option>

                <?php foreach ($birthStars as $birthStar): ?>
                    <?php
                    $birthStarId = (string) (
                        $birthStar['id'] ?? ''
                    );
                    ?>

                    <option
                        value="<?= esc(
                                    $birthStarId,
                                    'attr'
                                ) ?>"
                        <?= $selected(
                            'birth_star_id',
                            $birthStarId,
                            $details['birth_star_id'] ?? ''
                        ) ?>>
                        <?= esc(
                            (string) (
                                $birthStar['name'] ?? ''
                            )
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <fieldset>
                <hr class="my-2 mb-3">
                <h2 class="fs-16 fw-semibold mb-3">
                    Have dosh?
                </h2>

                <div class="d-flex flex-wrap gap-3">
                    <?php foreach (
                        $doshOptions as $doshOption
                    ): ?>
                        <?php
                        $doshValue = (string) (
                            $doshOption['value'] ?? ''
                        );

                        $doshId = 'hasDosh'
                            . str_replace(
                                '_',
                                '',
                                ucwords(
                                    strtolower($doshValue),
                                    '_'
                                )
                            );
                        ?>

                        <div class="form-check">
                            <input
                                type="radio"
                                class="form-check-input"
                                id="<?= esc(
                                        $doshId,
                                        'attr'
                                    ) ?>"
                                name="has_dosh"
                                value="<?= esc(
                                            $doshValue,
                                            'attr'
                                        ) ?>"
                                <?= $checked(
                                    'has_dosh',
                                    $doshValue,
                                    $details['has_dosh'] ?? ''
                                ) ?>>

                            <label
                                class="form-check-label"
                                for="<?= esc(
                                            $doshId,
                                            'attr'
                                        ) ?>">
                                <?= esc(
                                    (string) (
                                        $doshOption['label']
                                        ?? ''
                                    )
                                ) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </fieldset>
        </div>
    </div>

    <div class="row g-2 mt-4">
        <div
            class="col-12 col-sm-6 col-md-3
                ms-md-auto order-2 order-sm-1">

            <a
                href="<?= url_to('web.profile.edit') ?>"
                class="btn btn-outline-danger
                    fs-14 fw-medium w-100">
                Cancel
            </a>
        </div>


        <div
            class="col-12 col-sm-6 col-md-3
                order-1 order-sm-2">

            <button
                type="submit"
                id="saveSikhReligiousDetailsButton"
                class="btn registration-form__submit
                    fs-14 fw-semibold text-uppercase">

                <span class="registration-submit__label">
                    Save Details
                </span>

                <span
                    class="registration-submit__loading d-none"
                    aria-hidden="true">

                    <span
                        class="spinner-border spinner-border-sm"
                        role="status"
                        aria-hidden="true">
                    </span>

                    <span>Saving...</span>
                </span>
            </button>
        </div>
    </div>
</form>