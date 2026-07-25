<?php

declare(strict_types=1);

/**
 * Reusable application confirmation modal.
 *
 * Any form can activate this modal through the following attributes:
 *
 * data-confirm-form
 * data-confirm-title="Delete photo?"
 * data-confirm-message="This action cannot be undone."
 * data-confirm-button-text="Delete Photo"
 * data-confirm-button-class="btn-danger"
 * data-confirm-icon="ri-delete-bin-line"
 *
 * The form is submitted only after the member confirms the action.
 */
?>

<div
    class="modal fade"
    id="appConfirmationModal"
    tabindex="-1"
    aria-labelledby="appConfirmationModalTitle"
    aria-describedby="appConfirmationModalMessage"
    aria-hidden="true">

    <div
        class="modal-dialog modal-dialog-centered
            modal-sm">

        <div class="modal-content border-0 shadow">

            <div class="modal-body p-4 text-center">

                <div
                    class="avatar-lg rounded-circle
                        bg-danger-subtle text-danger
                        d-inline-flex align-items-center
                        justify-content-center mb-3"
                    id="appConfirmationModalIcon"
                    aria-hidden="true">

                    <i class="ri-alert-line fs-24"></i>
                </div>

                <h2
                    class="fs-18 fw-semibold mb-2"
                    id="appConfirmationModalTitle">

                    Confirm action
                </h2>

                <p
                    class="text-muted fs-13 mb-4"
                    id="appConfirmationModalMessage">

                    Are you sure you want to continue?
                </p>

                <div
                    class="d-flex flex-column-reverse
                        flex-sm-row justify-content-center
                        gap-2">

                    <button
                        type="button"
                        class="btn btn-light flex-fill"
                        id="appConfirmationModalCancel"
                        data-bs-dismiss="modal">

                        Cancel
                    </button>

                    <button
                        type="button"
                        class="btn btn-danger flex-fill
                            d-inline-flex align-items-center
                            justify-content-center gap-2"
                        id="appConfirmationModalConfirm">

                        <span
                            data-confirm-modal-label>
                            Confirm
                        </span>

                        <span
                            class="d-none align-items-center"
                            data-confirm-modal-loading
                            aria-live="polite">

                            <span
                                class="spinner-border
                                    spinner-border-sm me-1"
                                aria-hidden="true">
                            </span>

                            Processing...
                        </span>
                    </button>

                </div>

            </div>

        </div>

    </div>

</div>