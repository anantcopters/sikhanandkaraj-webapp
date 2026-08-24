<?php

declare(strict_types=1);

use App\Support\BooleanValue;

/**
 * Lifestyle Partner Preference edit page.
 *
 * @var array<string, mixed>       $category
 * @var list<array<string, mixed>> $options
 * @var list<int>                  $selectedOptionIds
 * @var bool                       $isCompulsory
 * @var array<string, string>      $validationErrors
 * @var array<string, string>|null $formAlert
 */

$this->extend('Layouts/Main');

$this->section('content');

$categoryId = max(
    0,
    (int) (
        $category['id']
        ?? 0
    )
);

$categoryName = trim(
    (string) (
        $category['name']
        ?? 'Lifestyle'
    )
);

$categoryIcon = trim(
    (string) (
        $category['icon_class']
        ?? 'ri-palette-line'
    )
);

$resolvedOptions = is_array(
    $options ?? null
)
    ? array_values($options)
    : [];

$storedSelectedIds = is_array(
    $selectedOptionIds ?? null
)
    ? array_values(
        array_map(
            'intval',
            $selectedOptionIds
        )
    )
    : [];

$oldSelectedIds = old(
    'lifestyle_option_ids',
    null,
    false
);

$resolvedSelectedIds = is_array(
    $oldSelectedIds
)
    ? array_values(
        array_unique(
            array_map(
                'intval',
                $oldSelectedIds
            )
        )
    )
    : $storedSelectedIds;

$errors = is_array(
    $validationErrors ?? null
)
    ? $validationErrors
    : [];

$storedCompulsory =
    BooleanValue::fromDatabase(
        $isCompulsory ?? false
    );

$strictMatch = old(
    'is_compulsory',
    $storedCompulsory
        ? '1'
        : '0',
    false
) === '1';

$formAction = url_to(
    'web.partner-preference.lifestyle.update',
    $categoryId
);
?>

<section class="py-3 py-lg-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">

                <?= view(
                    'Pages/Profile/Partials/_feedback_alert',
                    [
                        'formAlert' =>
                        $formAlert ?? null,
                    ]
                ) ?>

                <div class="mb-3">
                    <a
                        href="<?= url_to(
                                    'web.partner-preference'
                                ) ?>#lifestyle"
                        class="d-inline-flex
                            align-items-center
                            gap-1 text-primary
                            fw-medium mb-2">

                        <i
                            class="ri-arrow-left-line"
                            aria-hidden="true"></i>

                        Back to Partner Preference
                    </a>

                    <div
                        class="d-flex
                            align-items-center
                            gap-2 mt-2">

                        <div
                            class="avatar-sm
                                flex-shrink-0"
                            aria-hidden="true">

                            <span
                                class="avatar-title
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary">

                                <i
                                    class="<?= esc(
                                                $categoryIcon,
                                                'attr'
                                            ) ?> fs-20"></i>
                            </span>
                        </div>

                        <div>
                            <h2
                                class="fs-16
                                    fw-semibold mb-1">

                                <?= esc(
                                    $categoryName
                                ) ?>
                            </h2>

                            <p
                                class="text-muted
                                    fs-13 mb-0">

                                Set your preferred partner criteria.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="card border
                        border-danger
                        border-opacity-25
                        shadow-none mb-0">

                    <div class="card-body p-3 p-md-4">

                        <form
                            method="post"
                            action="<?= esc(
                                        $formAction,
                                        'attr'
                                    ) ?>"
                            id="partnerPreferenceLifestyleForm"
                            data-validate
                            novalidate>

                            <?= csrf_field() ?>

                            <div class="row g-3">

                                <!-- Lifestyle options -->
                                <div class="col-12">

                                    <label class="form-labelm">
                                        <?= esc(
                                            $categoryName
                                        ) ?>

                                        <span class="text-danger">
                                            *
                                        </span>
                                    </label>

                                    <div
                                        class="d-flex
                                            flex-wrap
                                            gap-2">

                                        <?php foreach (
                                            $resolvedOptions
                                            as $option
                                        ): ?>

                                            <?php
                                            if (!is_array($option)) {
                                                continue;
                                            }

                                            $optionId = max(
                                                0,
                                                (int) (
                                                    $option['id']
                                                    ?? 0
                                                )
                                            );

                                            $optionName = trim(
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

                                            $controlId =
                                                'lifestylePreferenceOption'
                                                . $optionId;
                                            ?>

                                            <input
                                                type="checkbox"
                                                class="btn-check"
                                                id="<?= esc(
                                                        $controlId,
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
                                                    $resolvedSelectedIds,
                                                    true
                                                )
                                                    ? 'checked'
                                                    : '' ?>>

                                            <label
                                                class="btn
                                                    btn-outline-primary"
                                                for="<?= esc(
                                                            $controlId,
                                                            'attr'
                                                        ) ?>">

                                                <?= esc(
                                                    $optionName
                                                ) ?>
                                            </label>

                                        <?php endforeach; ?>

                                    </div>

                                    <?= view(
                                        'Components/Forms/FieldError',
                                        [
                                            'field' =>
                                            'lifestyle_option_ids',

                                            'errorId' =>
                                            'lifestyleOptionIdsError',

                                            'errors' =>
                                            $errors,
                                        ]
                                    ) ?>

                                </div>


                                <!-- Matching Preference -->
                                <div class="col-12">

                                    <div
                                        class="border rounded
                                            p-3 bg-light mt-2">

                                        <div
                                            class="fw-semibold
                                                text-dark mb-3">

                                            Matching Preference
                                        </div>

                                        <div
                                            class="form-check mb-2">

                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                name="is_compulsory"
                                                id="preferredMatch"
                                                value="0"
                                                <?= !$strictMatch
                                                    ? 'checked'
                                                    : '' ?>>

                                            <label
                                                class="form-check-label"
                                                for="preferredMatch">

                                                Prefer profiles matching
                                                this preference

                                                <span
                                                    class="badge
                                                        bg-success-subtle
                                                        text-success
                                                        ms-2">

                                                    Recommended

                                                </span>

                                            </label>

                                        </div>

                                        <div class="form-check">

                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                name="is_compulsory"
                                                id="strictMatch"
                                                value="1"
                                                <?= $strictMatch
                                                    ? 'checked'
                                                    : '' ?>>

                                            <label
                                                class="form-check-label"
                                                for="strictMatch">

                                                Show only profiles matching
                                                this preference

                                                <span
                                                    class="badge
                                                        bg-danger-subtle
                                                        text-danger
                                                        ms-2">

                                                    Strict Match

                                                </span>

                                            </label>

                                        </div>

                                        <div
                                            class="form-text
                                                color-pink mt-3">

                                            Recommended provides more
                                            matching profiles, while
                                            Strict Match only shows
                                            profiles that exactly satisfy
                                            this preference.

                                        </div>

                                    </div>

                                </div>


                                <!-- Client-side validation area -->
                                <div class="col-12">
                                    <div
                                        id="lifestylePreferenceError"
                                        class="invalid-feedback d-block"
                                        aria-live="polite"></div>
                                </div>


                                <!-- Existing Partner Preference actions -->
                                <div class="col-12">

                                    <div class="row g-2 mt-4">

                                        <div
                                            class="col-12
                                                col-sm-6
                                                col-md-3
                                                ms-md-auto
                                                order-2
                                                order-sm-1">

                                            <a
                                                href="<?= url_to(
                                                            'web.partner-preference'
                                                        ) ?>#lifestyle"
                                                class="btn
                                                    btn-outline-danger
                                                    fs-14
                                                    fw-medium
                                                    w-100">

                                                Cancel
                                            </a>

                                        </div>

                                        <div
                                            class="col-12
                                                col-sm-6
                                                col-md-3
                                                order-1
                                                order-sm-2">

                                            <button
                                                type="submit"
                                                class="btn
                                                    registration-form__submit
                                                    fs-14
                                                    fw-semibold
                                                    text-uppercase"
                                                id="savePartnerPreferenceButton">

                                                <span
                                                    class="registration-submit__label">

                                                    Save

                                                </span>

                                                <span
                                                    class="registration-submit__loading
                                                        d-none"
                                                    aria-hidden="true">

                                                    <span
                                                        class="spinner-border
                                                            spinner-border-sm"
                                                        role="status"
                                                        aria-hidden="true"></span>

                                                    <span>
                                                        Saving...
                                                    </span>

                                                </span>

                                            </button>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>