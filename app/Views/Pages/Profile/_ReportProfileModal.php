<?php

declare(strict_types=1);

/**
 * Shared Report Profile modal.
 *
 * Used by:
 *
 * - Full Profile View
 * - ProfileCard
 *
 * @var string                $viewedProfileReference
 * @var string                $reportCaptcha
 * @var bool                  $reopenReportModal
 * @var array<string, string> $reportValidationErrors
 * @var string                $modalId
 * @var string                $actionUrl
 * @var string                $actionSource
 */

$viewedProfileReference =
    isset($viewedProfileReference)
    ? trim(
        (string) $viewedProfileReference
    )
    : '';

$reportCaptcha =
    isset($reportCaptcha)
    ? trim(
        (string) $reportCaptcha
    )
    : '';

$reopenReportModal =
    ($reopenReportModal ?? false)
    === true;

$errors =
    isset($reportValidationErrors)
    && is_array(
        $reportValidationErrors
    )
    ? $reportValidationErrors
    : [];

$modalId = trim(
    (string) (
        $modalId
        ?? 'memberReportModal'
    )
);

if ($modalId === '') {
    $modalId =
        'memberReportModal';
}

$actionUrl = trim(
    (string) (
        $actionUrl
        ?? ''
    )
);

if (
    $actionUrl === ''
    && $viewedProfileReference !== ''
) {
    $actionUrl = route_to(
        'web.members.report',
        $viewedProfileReference
    );
}

$actionSource = trim(
    (string) (
        $actionSource
        ?? 'profile'
    )
);

$titleId =
    $modalId
    . 'Title';

$descriptionId =
    $modalId
    . 'Description';

$captchaId =
    $modalId
    . 'CaptchaAnswer';
?>

<div
    class="modal fade"
    id="<?= esc(
            $modalId,
            'attr'
        ) ?>"
    tabindex="-1"
    aria-labelledby="<?= esc(
                            $titleId,
                            'attr'
                        ) ?>"
    aria-hidden="true"
    data-reopen-report="<?= $reopenReportModal
                            ? '1'
                            : '0' ?>">

    <div
        class="
            modal-dialog
            modal-dialog-centered
        ">

        <div
            class="
                modal-content
                border-0
                shadow
            ">

            <form
                method="post"
                action="<?= esc(
                            $actionUrl,
                            'attr'
                        ) ?>"
                data-validate
                data-member-report-form
                data-submit-loader
                novalidate>

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="action_source"
                    value="<?= esc(
                                $actionSource,
                                'attr'
                            ) ?>">

                <div
                    class="
                        modal-header
                        bg-info-subtle
                        py-2
                    ">

                    <div>

                        <h2
                            class="
                                modal-title
                                fs-16
                                fw-semibold
                            "
                            id="<?= esc(
                                    $titleId,
                                    'attr'
                                ) ?>">

                            Report Profile

                        </h2>

                        <p
                            class="
                                text-muted
                                fs-12
                                mb-0
                            ">

                            Profile ID:
                            <?= esc(
                                $viewedProfileReference
                            ) ?>

                        </p>

                    </div>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                    </button>

                </div>

                <div class="modal-body">

                    <div class="alert alert-warning">

                        <strong>
                            Before reporting
                        </strong>

                        <p
                            class="
                                fs-13
                                mb-0
                                mt-1
                            ">

                            Report only fake identity, fraud,
                            impersonation, abusive behaviour,
                            inappropriate content or a genuine
                            safety concern. Misuse of reporting
                            may result in account review.

                        </p>

                    </div>

                    <div class="mb-3">

                        <label
                            for="<?= esc(
                                        $descriptionId,
                                        'attr'
                                    ) ?>"
                            class="form-label">

                            Why are you reporting this profile?

                        </label>

                        <textarea
                            id="<?= esc(
                                    $descriptionId,
                                    'attr'
                                ) ?>"
                            name="description"
                            rows="5"
                            minlength="10"
                            maxlength="1000"
                            class="form-control<?= isset(
                                                    $errors['description']
                                                )
                                                    ? ' is-invalid'
                                                    : '' ?>"
                            data-error-required="Please explain why you are reporting this profile."
                            data-error-minlength="Please enter at least 10 characters."
                            data-error-maxlength="Report description cannot exceed 1000 characters."
                            required><?= esc(
                                            old(
                                                'description'
                                            )
                                        ) ?></textarea>

                        <div
                            class="
                                invalid-feedback
                                <?= isset(
                                    $errors['description']
                                )
                                    ? 'd-block'
                                    : '' ?>
                            "
                            data-validation-error="description">

                            <?= esc(
                                $errors['description']
                                    ?? ''
                            ) ?>

                        </div>

                    </div>

                    <div class="mb-3">

                        <label
                            class="form-label"
                            for="<?= esc(
                                        $captchaId,
                                        'attr'
                                    ) ?>">

                            Security Verification

                        </label>

                        <div
                            class="
                                border
                                rounded
                                p-2
                                mb-2
                                bg-light
                                border-primary-subtle
                            ">

                            <div
                                class="
                                    d-flex
                                    align-items-center
                                    justify-content-between
                                ">

                                <span class="text-muted">
                                    Solve this question
                                </span>

                                <span class="fw-bold fs-18">

                                    <?= esc(
                                        $reportCaptcha
                                    ) ?> = ?

                                </span>

                            </div>

                        </div>

                        <input
                            type="text"
                            id="<?= esc(
                                    $captchaId,
                                    'attr'
                                ) ?>"
                            name="captcha_answer"
                            class="form-control<?= isset(
                                                    $errors['captcha_answer']
                                                )
                                                    ? ' is-invalid'
                                                    : '' ?>"
                            placeholder="Enter answer"
                            inputmode="numeric"
                            autocomplete="off"
                            maxlength="2"
                            pattern="[0-9]{1,2}"
                            data-error-required="Please enter the security answer."
                            data-error-pattern="Please enter a valid security answer."
                            required>

                        <div
                            class="
                                invalid-feedback
                                <?= isset(
                                    $errors['captcha_answer']
                                )
                                    ? 'd-block'
                                    : '' ?>
                            "
                            data-validation-error="captcha_answer">

                            <?= esc(
                                $errors['captcha_answer']
                                    ?? ''
                            ) ?>

                        </div>

                        <div class="form-text color-pink">

                            The security question expires after
                            5 minutes and can be used only once.

                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button
                        type="submit"
                        class="btn btn-danger"
                        data-submit-button>

                        <span data-submit-idle>

                            <i
                                class="ri-flag-line me-1"
                                aria-hidden="true">
                            </i>

                            Submit Report

                        </span>

                        <span
                            class="d-none"
                            data-submit-loading>

                            <span
                                class="
                                    spinner-border
                                    spinner-border-sm
                                "
                                aria-hidden="true">
                            </span>

                            Submitting...

                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>