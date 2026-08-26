<?php

declare(strict_types=1);

/**
 * Reusable Report Profile modal for member cards.
 *
 * Report is available to both Free and Paid members.
 *
 * @var string $modalId
 * @var string $profileReference
 * @var string $actionUrl
 * @var string $reportCaptcha
 */

$modalId = trim(
    (string) (
        $modalId
        ?? ''
    )
);

$profileReference = trim(
    (string) (
        $profileReference
        ?? ''
    )
);

$actionUrl = trim(
    (string) (
        $actionUrl
        ?? ''
    )
);

$reportCaptcha = trim(
    (string) (
        $reportCaptcha
        ?? ''
    )
);
?>

<div
    class="modal fade"
    id="<?= esc(
            $modalId,
            'attr'
        ) ?>"
    tabindex="-1"
    aria-hidden="true">

    <div
        class="modal-dialog
            modal-dialog-centered">

        <div
            class="modal-content
                border-0 shadow">

            <div class="modal-header bg-info-subtle py-2">

                <h2
                    class="modal-title
                        fs-18 fw-semibold">

                    Report Profile

                </h2>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>

            </div>

            <form
                method="post"
                action="<?= esc(
                            $actionUrl,
                            'attr'
                        ) ?>"
                data-validate
                data-submit-loader
                novalidate>

                <?= csrf_field() ?>

                <!--
                    Tells MemberProfileController that this request originated
                    from a card/listing rather than Full Profile.
                -->
                <input
                    type="hidden"
                    name="action_source"
                    value="card">

                <div class="modal-body">

                    <p class="text-muted fs-13">

                        Report
                        <strong>
                            <?= esc(
                                $profileReference
                            ) ?>
                        </strong>
                        if you believe the profile requires
                        administrator review.

                    </p>

                    <div class="mb-3">

                        <label
                            class="form-label"
                            for="<?= esc(
                                        $modalId . 'Description',
                                        'attr'
                                    ) ?>">

                            Reason for reporting

                        </label>

                        <textarea
                            id="<?= esc(
                                    $modalId . 'Description',
                                    'attr'
                                ) ?>"
                            name="description"
                            class="form-control"
                            rows="4"
                            maxlength="500"
                            required
                            data-error-required="Please enter why you are reporting this profile."
                            data-error-maxlength="The report cannot exceed 500 characters."></textarea>

                    </div>

                    <?php if ($reportCaptcha !== ''): ?>

                        <div class="mb-0">

                            <label
                                class="form-label"
                                for="<?= esc(
                                            $modalId . 'Captcha',
                                            'attr'
                                        ) ?>">

                                Security Check

                            </label>

                            <div class="input-group">

                                <span
                                    class="input-group-text">

                                    <?= esc(
                                        $reportCaptcha
                                    ) ?>

                                </span>

                                <input
                                    type="text"
                                    id="<?= esc(
                                            $modalId . 'Captcha',
                                            'attr'
                                        ) ?>"
                                    name="captcha_answer"
                                    class="form-control"
                                    maxlength="20"
                                    autocomplete="off"
                                    required
                                    data-error-required="Please enter the security answer.">

                            </div>

                        </div>

                    <?php endif; ?>

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
                        class="btn
                            registration-form__submit
                            fs-14
                            fw-medium
                            text-uppercase w-25"
                        data-submit-button>

                        <span data-submit-idle>

                            <i
                                class="ri-flag-line
                                    me-1 fs-20"
                                aria-hidden="true">
                            </i>

                            Report Profile

                        </span>

                        <span
                            class="registration-submit__loading
                                d-none"
                            data-submit-loading>

                            <span
                                class="spinner-border
                                    spinner-border-sm"
                                aria-hidden="true">
                            </span>

                            Sending...

                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>