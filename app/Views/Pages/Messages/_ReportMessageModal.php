<?php

declare(strict_types=1);

/**
 * @var int $messageId
 */

$messageId =
    isset($messageId)
    && is_numeric($messageId)
    ? max(
        0,
        (int) $messageId
    )
    : 0;

if ($messageId <= 0) {
    return;
}

$modalId =
    'reportMessageModal'
    . $messageId;
?>

<div
    class="modal fade"
    id="<?= esc(
            $modalId,
            'attr'
        ) ?>"
    tabindex="-1"
    aria-labelledby="<?= esc(
                            $modalId,
                            'attr'
                        ) ?>Label"
    aria-hidden="true">

    <div
        class="modal-dialog
            modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="<?= esc(
                            $modalId,
                            'attr'
                        ) ?>Label">

                    Report Message

                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>

            </div>

            <form
                method="post"
                action="<?= route_to(
                            'web.messages.report',
                            $messageId
                        ) ?>"
                data-validation-form>

                <?= csrf_field() ?>

                <div class="modal-body">

                    <p class="text-muted fs-13">
                        Tell us why this message should be reviewed.
                        Reporting preserves the message for moderation.
                    </p>

                    <div class="mb-3">

                        <label
                            for="messageReportReason<?= $messageId ?>"
                            class="form-label">

                            Reason
                            <span class="text-danger">*</span>

                        </label>

                        <select
                            id="messageReportReason<?= $messageId ?>"
                            name="reason"
                            class="form-select"
                            required>

                            <option value="">
                                Select reason
                            </option>

                            <option value="HARASSMENT">
                                Harassment / abuse
                            </option>

                            <option value="ASKING_FOR_MONEY">
                                Asking for money
                            </option>

                            <option value="FAKE_IDENTITY">
                                Fake / suspicious identity
                            </option>

                            <option value="INAPPROPRIATE">
                                Inappropriate content
                            </option>

                            <option value="UNWANTED_CONTACT">
                                Repeated unwanted contact
                            </option>

                            <option value="SPAM">
                                Spam
                            </option>

                            <option value="OTHER">
                                Other
                            </option>

                        </select>

                    </div>

                    <div>

                        <label
                            for="messageReportComment<?= $messageId ?>"
                            class="form-label">

                            Additional details

                        </label>

                        <textarea
                            id="messageReportComment<?= $messageId ?>"
                            name="comment"
                            rows="3"
                            maxlength="500"
                            class="form-control"
                            placeholder="Optional additional details"></textarea>

                        <div class="form-text">
                            Maximum 500 characters.
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
                        class="btn
                            registration-form__submit
                            fs-14
                            fw-medium
                            text-uppercase"
                        data-submit-button>

                        <span
                            class="registration-submit__idle"
                            data-submit-idle>

                            <i
                                class="ri-flag-line fs-18 me-1"
                                aria-hidden="true">
                            </i>

                            Report Message

                        </span>

                        <span
                            class="registration-submit__loading
                                d-none"
                            data-submit-loading>

                            <span
                                class="spinner-border
                                    spinner-border-sm
                                    me-1"
                                aria-hidden="true">
                            </span>

                            Reporting...

                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>