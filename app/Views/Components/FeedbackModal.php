<?php

declare(strict_types=1);

/**
 * Reusable application feedback modal.
 *
 * The modal can be opened:
 *
 * 1. Automatically through CI4 flashdata.
 * 2. Manually through window.AppFeedbackModal.show().
 *
 * Expected flashdata format:
 *
 * [
 *     'type' => 'info',
 *     'title' => 'Verification email sent',
 *     'message' => 'Please check your inbox.',
 *     'button_text' => 'Okay',
 * ]
 */

$modalData = session()->getFlashdata('feedback_modal');

$allowedTypes = [
    'info',
    'success',
    'warning',
    'error',
];

$type = is_array($modalData)
    ? (string) ($modalData['type'] ?? 'info')
    : 'info';

if (!in_array($type, $allowedTypes, true)) {
    $type = 'info';
}

$title = is_array($modalData)
    ? trim((string) ($modalData['title'] ?? 'Information'))
    : '';

$message = is_array($modalData)
    ? trim((string) ($modalData['message'] ?? ''))
    : '';

$buttonText = is_array($modalData)
    ? trim((string) ($modalData['button_text'] ?? 'Okay'))
    : 'Okay';

$shouldOpen = is_array($modalData)
    && $title !== ''
    && $message !== '';
?>

<div
    class="modal fade app-feedback-modal"
    id="appFeedbackModal"
    tabindex="-1"
    aria-labelledby="appFeedbackModalTitle"
    aria-describedby="appFeedbackModalMessage"
    aria-hidden="true"
    data-auto-open="<?= $shouldOpen ? 'true' : 'false' ?>"
    data-modal-type="<?= esc($type, 'attr') ?>">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content app-feedback-modal__content">

            <div class="modal-body app-feedback-modal__body">

                <div
                    class="app-feedback-modal__icon"
                    id="appFeedbackModalIcon"
                    aria-hidden="true">

                    <i class="mdi mdi-information-outline"></i>
                </div>

                <h2
                    class="app-feedback-modal__title"
                    id="appFeedbackModalTitle">
                    <?= esc($title !== '' ? $title : 'Information') ?>
                </h2>

                <p
                    class="app-feedback-modal__message"
                    id="appFeedbackModalMessage">
                    <?= esc($message) ?>
                </p>

                <button
                    type="button"
                    class="btn app-feedback-modal__button"
                    id="appFeedbackModalButton"
                    data-bs-dismiss="modal">
                    <?= esc($buttonText !== '' ? $buttonText : 'Okay') ?>
                </button>

            </div>

        </div>
    </div>
</div>