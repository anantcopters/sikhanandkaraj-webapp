<?php

declare(strict_types=1);

/**
 * @var array<string,mixed>       $category
 * @var list<array<string,mixed>> $options
 * @var list<int>                 $selectedOptionIds
 * @var bool                      $isCompulsory
 * @var array<string,string>      $validationErrors
 * @var array<string,string>|null $formAlert
 */

$this->extend(
    'Layouts/Main'
);

$this->section(
    'content'
);

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

$resolvedSelectedIds = is_array(
    old(
        'lifestyle_option_ids',
        $selectedOptionIds ?? []
    )
)
    ? array_values(
        array_unique(
            array_map(
                'intval',
                old(
                    'lifestyle_option_ids',
                    $selectedOptionIds ?? []
                )
            )
        )
    )
    : [];

$errors = is_array(
    $validationErrors ?? null
)
    ? $validationErrors
    : [];

$resolvedCompulsory =
    old(
        'is_compulsory',
        ($isCompulsory ?? false)
            ? '1'
            : '0'
    ) === '1';
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
                            fw-medium">

                        <i
                            class="ri-arrow-left-line"
                            aria-hidden="true"></i>

                        Back to Partner Preference
                    </a>
                </div>

                <div
                    class="card border border-danger
                        border-opacity-25 shadow-none">

                    <div class="card-body p-3 p-md-4">

                        <div
                            class="d-flex
                                align-items-start
                                gap-2 mb-4">

                            <span
                                class="avatar-sm
                                    flex-shrink-0">

                                <span
                                    class="avatar-title
                                        rounded-circle
                                        bg-primary-subtle
                                        text-primary">

                                    <i
                                        class="<?= esc(
                                                    $categoryIcon,
                                                    'attr'
                                                ) ?> fs-18"
                                        aria-hidden="true"></i>
                                </span>
                            </span>

                            <div>
                                <h1
                                    class="fs-16
                                        fw-semibold mb-1">

                                    <?= esc(
                                        $categoryName
                                    ) ?>
                                </h1>

                                <p
                                    class="text-muted
                                        fs-13 mb-0">

                                    Select the preferences
                                    that matter to you.
                                </p>
                            </div>
                        </div>

                        <form
                            method="post"
                            action="<?= url_to(
                                        'web.partner-preference'
                                            . '.lifestyle.update',
                                        $categoryId
                                    ) ?>"
                            data-validate-form>

                            <?= csrf_field() ?>

                            <div class="row g-3">

                                <div class="col-12">

                                    <label class="form-labelm">
                                        <?= esc(
                                            $categoryName
                                        ) ?>

                                        <span class="text-danger">*</span>
                                    </label>

                                    <div
                                        class="d-flex
                                            flex-wrap gap-2">

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

                                <div class="col-12">
                                    <hr class="my-1">
                                </div>

                                <div class="col-12">

                                    <input
                                        type="hidden"
                                        name="is_compulsory"
                                        value="0">

                                    <div class="form-check">

                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            id="isCompulsory"
                                            name="is_compulsory"
                                            value="1"
                                            <?= $resolvedCompulsory
                                                ? 'checked'
                                                : '' ?>>

                                        <label
                                            class="form-check-label"
                                            for="isCompulsory">

                                            This preference is compulsory
                                        </label>

                                    </div>

                                    <div class="form-text">
                                        Show only matches having at least
                                        one of the selected
                                        <?= esc(
                                            strtolower(
                                                $categoryName
                                            )
                                        ) ?>
                                        preferences.
                                    </div>

                                </div>

                                <div
                                    class="col-12
                                        d-flex
                                        justify-content-end">

                                    <button
                                        type="submit"
                                        class="btn
                                            registration-form__submit
                                            fs-14 fw-medium
                                            text-uppercase"
                                        data-submit-button>

                                        <span
                                            class="registration-submit__idle"
                                            data-submit-idle>

                                            <i
                                                class="mdi
                                                    mdi-cloud-upload-outline
                                                    fs-20"
                                                aria-hidden="true"></i>

                                            Save
                                        </span>

                                        <span
                                            class="registration-submit__loading
                                                d-none"
                                            data-submit-loading>

                                            <span
                                                class="spinner-border
                                                    spinner-border-sm"
                                                role="status"
                                                aria-hidden="true"></span>

                                            Saving...
                                        </span>

                                    </button>

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