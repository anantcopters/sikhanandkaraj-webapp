<?php

declare(strict_types=1);

/**
 * Reusable application feedback modal.
 *
 * Usage:
 *
 * window.AppFeedbackModal.show({
 *     type: 'success',
 *     title: 'Verification email sent',
 *     message: 'Please check your inbox.',
 *     buttonText: 'Okay'
 * });
 */
?>

<div
    class="modal fade app-feedback-modal app-feedback-modal--info"
    id="appFeedbackModal"
    tabindex="-1"
    aria-labelledby="appFeedbackModalTitle"
    aria-describedby="appFeedbackModalMessage"
    aria-hidden="true">

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
                    Information
                </h2>

                <p
                    class="app-feedback-modal__message"
                    id="appFeedbackModalMessage">
                </p>

                <button
                    type="button"
                    class="btn app-feedback-modal__button"
                    id="appFeedbackModalButton"
                    data-bs-dismiss="modal">
                    Okay
                </button>

            </div>

        </div>
    </div>
</div>