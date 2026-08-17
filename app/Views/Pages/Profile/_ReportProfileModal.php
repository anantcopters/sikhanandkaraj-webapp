<?php

declare(strict_types=1);

/**
 * @var string                $viewedProfileReference
 * @var string                $reportCaptcha
 * @var bool                  $reopenReportModal
 * @var array<string, string> $reportValidationErrors
 */

$viewedProfileReference = isset($viewedProfileReference)
    ? trim((string) $viewedProfileReference)
    : '';

$reportCaptcha = isset($reportCaptcha)
    ? trim((string) $reportCaptcha)
    : '';

$reopenReportModal =
    ($reopenReportModal ?? false) === true;

$errors = isset($reportValidationErrors)
    && is_array($reportValidationErrors)
    ? $reportValidationErrors
    : [];
?>

<div
    class="modal fade"
    id="memberReportModal"
    tabindex="-1"
    aria-labelledby="memberReportModalTitle"
    aria-hidden="true"
    data-reopen-report="<?= (
                            $reopenReportModal
                            ?? false
                        ) === true
                            ? '1'
                            : '0' ?>">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <form
                method="post"
                action="<?= route_to(
                            'web.members.report',
                            $viewedProfileReference
                        ) ?>"
                data-member-report-form
                novalidate>

                <?= csrf_field() ?>

                <div class="modal-header bg-warning-subtle">
                    <div>
                        <h2
                            class="modal-title fs-16 fw-semibold"
                            id="memberReportModalTitle">

                            Report Profile
                        </h2>

                        <p class="text-muted fs-12 mb-0">
                            Profile ID:
                            <?= esc($viewedProfileReference) ?>
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
                        <strong>Before reporting</strong>

                        <p class="fs-13 mb-0 mt-1">
                            Report only fake identity, fraud,
                            impersonation, abusive behaviour,
                            inappropriate content or a genuine
                            safety concern. Misuse of reporting
                            may result in account review.
                        </p>
                    </div>

                    <div class="mb-3">
                        <label
                            for="member-report-description"
                            class="form-label">

                            Why are you reporting this profile?
                        </label>

                        <textarea
                            id="member-report-description"
                            name="description"
                            rows="5"
                            minlength="10"
                            maxlength="1000"
                            class="form-control
                                <?= isset($errors['description'])
                                    ? 'is-invalid'
                                    : '' ?>"
                            required><?= esc(
                                            old('description')
                                        ) ?></textarea>

                        <div class="invalid-feedback">
                            <?= esc(
                                $errors['description']
                                    ?? 'Please enter between 10 and 1000 characters.'
                            ) ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label
                            for="member-report-captcha"
                            class="form-label">

                            What is
                            <strong>
                                <?= esc(
                                    $reportCaptcha
                                        ?? ''
                                ) ?>
                            </strong>
                            ?
                        </label>

                        <input
                            type="text"
                            inputmode="numeric"
                            id="member-report-captcha"
                            name="captcha_answer"
                            maxlength="2"
                            class="form-control
                                <?= isset(
                                    $errors['captcha_answer']
                                )
                                    ? 'is-invalid'
                                    : '' ?>"
                            required>

                        <div class="invalid-feedback">
                            <?= esc(
                                $errors['captcha_answer']
                                    ?? 'Please enter the security answer.'
                            ) ?>
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
                        data-member-report-submit>

                        <span data-member-report-label>
                            Submit Report
                        </span>

                        <span
                            class="d-none"
                            data-member-report-loading>

                            <span
                                class="spinner-border
                                    spinner-border-sm">
                            </span>

                            Submitting...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>