<?php

declare(strict_types=1);

/**
 * @var string $aboutMe
 * @var array<string, string> $validationErrors
 */

$isJourney = ($isProfileJourney ?? false) === true;

$formAction = url_to(
    'web.profile.about-me.update'
);

if ($isJourney) {
    $formAction .= '?journey=1';
}

$aboutMe = isset($aboutMe)
    ? (string) $aboutMe
    : '';

$validationErrors = isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$aboutMeValue = (string) old(
    'about_me',
    $aboutMe,
    false
);
?>

<form
    action="<?= esc($formAction, 'attr') ?>"
    method="post"
    id="aboutMeForm"
    data-validate
    novalidate>

    <?= csrf_field() ?>

    <div class="mb-3">
        <label
            for="aboutMe"
            class="form-label">

            Describe yourself
            <span class="text-danger">*</span>
        </label>

        <textarea
            id="aboutMe"
            name="about_me"
            class="form-control <?= isset(
                                    $validationErrors['about_me']
                                ) ? 'is-invalid' : '' ?>"
            rows="10"
            maxlength="5000"
            data-about-me-input
            data-error-required="Please write a short introduction about yourself."
            aria-describedby="aboutMeHelp aboutMeError"
            placeholder="You may write about your personality, values, family outlook, interests, career goals and what matters to you in life."
            required><?= esc($aboutMeValue) ?></textarea>

        <div
            id="aboutMeHelp"
            class="d-flex flex-column flex-sm-row
                align-items-sm-center
                justify-content-between gap-1 mt-2">

            <span class="color-pink fs-12">
                Plain text only. Links and website addresses
                are not allowed.
            </span>

            <span
                class="fs-12 fw-medium"
                data-about-me-count
                aria-live="polite">

                0 of 500 words
            </span>
        </div>

        <?= view(
            'Components/Forms/FieldError',
            [
                'field' => 'about_me',
                'errorId' => 'aboutMeError',
                'errors' => $validationErrors,
            ]
        ) ?>
    </div>

    <div
        class="alert alert-info
            border-0 bg-info-subtle mb-0"
        role="note">

        <div class="d-flex align-items-start gap-2">
            <i
                class="ri-lightbulb-line
                    text-info fs-18"
                aria-hidden="true">
            </i>

            <div>
                <span class="fw-semibold d-block mb-1">
                    Make your introduction meaningful
                </span>

                <span class="fs-13">
                    Keep it honest and positive. Avoid sharing
                    phone numbers, email addresses or external links.
                </span>
            </div>
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
                id="saveAboutMeButton"
                class="btn registration-form__submit
                    fs-14 fw-semibold text-uppercase">

                <span class="registration-submit__label">
                    Save
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