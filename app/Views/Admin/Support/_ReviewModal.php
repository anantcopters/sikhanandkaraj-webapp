<?php

declare(strict_types=1);

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
                novalidate>

                <?= csrf_field() ?>

                <div class="modal-header bg-info-subtle">
                    <div>
                        <h2
                            class="modal-title fs-16 fw-semibold"
                            id="memberSupportReviewTitle">

                            Review
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

                        <select
                            id="supportReviewStatus"
                            name="status"
                            class="form-select
                                <?= isset($errors['status'])
                                    ? 'is-invalid'
                                    : '' ?>"
                            required>

                            <option value="">
                                Select status
                            </option>

                            <?php if ($isReport): ?>
                                <option value="REVIEWED">
                                    Reviewed
                                </option>

                                <option value="DISMISSED">
                                    Dismissed
                                </option>

                                <option value="ACTION_TAKEN">
                                    Action Taken
                                </option>
                            <?php else: ?>
                                <option value="IN_PROGRESS">
                                    In Progress
                                </option>

                                <option value="RESOLVED">
                                    Resolved
                                </option>

                                <option value="CLOSED">
                                    Closed
                                </option>
                            <?php endif; ?>
                        </select>

                        <div class="invalid-feedback">
                            <?= esc(
                                $errors['status']
                                    ?? 'Please select a status.'
                            ) ?>
                        </div>
                    </div>

                    <?php
                    $noteName = $isReport
                        ? 'resolution_note'
                        : 'response_note';

                    $maximumLength = $isReport
                        ? 1000
                        : 2000;
                    ?>

                    <div class="mb-3">
                        <label
                            for="supportReviewNote"
                            class="form-label">

                            <?= $isReport
                                ? 'Resolution note'
                                : 'Response note' ?>
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
                            class="form-control
                                <?= isset($errors[$noteName])
                                    ? 'is-invalid'
                                    : '' ?>"
                            required><?= esc(
                                            old($noteName)
                                        ) ?></textarea>

                        <div class="invalid-feedback">
                            <?= esc(
                                $errors[$noteName]
                                    ?? 'Please enter at least 5 characters.'
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
                        class="btn btn-primary"
                        data-submit-button>

                        <span data-submit-idle>
                            Save Review
                        </span>

                        <span
                            data-submit-loading
                            class="d-none">

                            <span
                                class="spinner-border
                                    spinner-border-sm">
                            </span>

                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>