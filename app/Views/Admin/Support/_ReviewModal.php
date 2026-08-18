<?php

declare(strict_types=1);

/**
 * @var string                     $reviewType
 * @var array<string, string>      $validationErrors
 * @var array<string, mixed>       $reviewRecord
 */

$isReport =
    ($reviewType ?? '') === 'report';

$errors =
    isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$reviewRecord =
    isset($reviewRecord)
    && is_array($reviewRecord)
    ? $reviewRecord
    : [];

$reopen =
    ($reviewRecord['type'] ?? '')
    === ($isReport ? 'report' : 'contact');

$recordId = max(
    0,
    (int) (
        $reviewRecord['id']
        ?? 0
    )
);

$noteName = $isReport
    ? 'resolution_note'
    : 'response_note';

$noteLabel = $isReport
    ? 'Resolution note'
    : 'Message to member';

$maximumLength = $isReport
    ? 1000
    : 255;

$maximumLengthMessage = $isReport
    ? 'Resolution note cannot exceed 1000 characters.'
    : 'Message cannot exceed 255 characters.';
?>

<div
    class="modal fade"
    id="memberSupportReviewModal"
    tabindex="-1"
    aria-labelledby="memberSupportReviewTitle"
    aria-hidden="true"
    data-review-type="<?= $isReport
                            ? 'report'
                            : 'contact' ?>"
    data-reopen-review="<?= $reopen
                            ? '1'
                            : '0' ?>"
    data-reopen-id="<?= esc(
                        (string) $recordId,
                        'attr'
                    ) ?>">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <form
                method="post"
                action=""
                data-validate
                data-support-review-form
                data-report-action-template="<?= esc(
                                                    site_url(
                                                        'admin/support/profile-reports/__ID__'
                                                    ),
                                                    'attr'
                                                ) ?>"
                data-contact-action-template="<?= esc(
                                                    site_url(
                                                        'admin/support/contact-requests/__ID__'
                                                    ),
                                                    'attr'
                                                ) ?>"
                data-submit-loader
                novalidate>

                <?= csrf_field() ?>

                <div class="modal-header bg-info-subtle py-2">
                    <div>
                        <h2
                            class="modal-title fs-16 fw-semibold"
                            id="memberSupportReviewTitle">

                            <?= $isReport
                                ? 'Review Profile Report'
                                : 'Resolve Contact Request' ?>
                        </h2>

                        <p
                            class="text-muted fs-12 mb-0"
                            data-support-review-label>
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

                    <div class="mb-3">
                        <label
                            for="supportReviewStatus"
                            class="form-label">

                            Status
                        </label>

                        <?php if ($isReport): ?>
                            <select
                                id="supportReviewStatus"
                                name="status"
                                class="form-select
                                    <?= isset($errors['status'])
                                        ? 'is-invalid'
                                        : '' ?>"
                                data-error-required="Please select a status."
                                required>

                                <option value="">
                                    Select status
                                </option>

                                <option
                                    value="REVIEWED"
                                    <?= old('status')
                                        === 'REVIEWED'
                                        ? 'selected'
                                        : '' ?>>

                                    Reviewed
                                </option>

                                <option
                                    value="DISMISSED"
                                    <?= old('status')
                                        === 'DISMISSED'
                                        ? 'selected'
                                        : '' ?>>

                                    Dismissed
                                </option>

                                <option
                                    value="ACTION_TAKEN"
                                    <?= old('status')
                                        === 'ACTION_TAKEN'
                                        ? 'selected'
                                        : '' ?>>

                                    Action Taken
                                </option>
                            </select>

                            <div
                                class="invalid-feedback
                                    <?= isset($errors['status'])
                                        ? 'd-block'
                                        : '' ?>"
                                data-validation-error="status">

                                <?= esc(
                                    $errors['status']
                                        ?? ''
                                ) ?>
                            </div>
                        <?php else: ?>
                            <input
                                type="hidden"
                                name="status"
                                value="RESOLVED">

                            <div
                                id="supportReviewStatus"
                                class="form-control bg-light">

                                Resolved
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label
                            for="supportReviewNote"
                            class="form-label">

                            <?= esc($noteLabel) ?>
                        </label>

                        <textarea
                            id="supportReviewNote"
                            name="<?= esc(
                                        $noteName,
                                        'attr'
                                    ) ?>"
                            rows="5"
                            minlength="5"
                            maxlength="<?= esc(
                                            (string) $maximumLength,
                                            'attr'
                                        ) ?>"
                            data-error-required="<?= esc(
                                                        'Please enter '
                                                            . mb_strtolower(
                                                                $noteLabel
                                                            )
                                                            . '.',
                                                        'attr'
                                                    ) ?>"
                            data-error-minlength="Please enter at least 5 characters."
                            data-error-maxlength="<?= esc(
                                                        $maximumLengthMessage,
                                                        'attr'
                                                    ) ?>"
                            class="form-control
                                <?= isset($errors[$noteName])
                                    ? 'is-invalid'
                                    : '' ?>"
                            required><?= esc(
                                            old($noteName)
                                        ) ?></textarea>

                        <div
                            class="invalid-feedback
                                <?= isset($errors[$noteName])
                                    ? 'd-block'
                                    : '' ?>"
                            data-validation-error="<?= esc(
                                                        $noteName,
                                                        'attr'
                                                    ) ?>">

                            <?= esc(
                                $errors[$noteName]
                                    ?? ''
                            ) ?>
                        </div>

                        <div class="form-text text-end">
                            Maximum
                            <?= esc(
                                (string) $maximumLength
                            ) ?>
                            characters
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
                        class="btn btn-primary"
                        data-submit-button>

                        <span data-submit-idle>
                            <i
                                class="<?= $isReport
                                            ? 'ri-save-line'
                                            : 'ri-check-line' ?> me-1"
                                aria-hidden="true">
                            </i>

                            <?= $isReport
                                ? 'Save Review'
                                : 'Resolve Request' ?>
                        </span>

                        <span
                            class="d-none"
                            data-submit-loading>

                            <span
                                class="spinner-border
                                    spinner-border-sm"
                                aria-hidden="true">
                            </span>

                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>