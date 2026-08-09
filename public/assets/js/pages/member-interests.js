'use strict';

document.addEventListener(
    'DOMContentLoaded',
    () => {
        const forms =
            document.querySelectorAll(
                '[data-interest-action-form]'
            );

        forms.forEach(
            (form) => {
                form.addEventListener(
                    'submit',
                    () => {
                        /*
                         * Disable all Interest action buttons
                         * on this profile while its response
                         * is being saved.
                         */
                        const card =
                            form.closest(
                                '.card'
                            );

                        const buttons =
                            card
                                ? card.querySelectorAll(
                                    '[data-interest-submit]'
                                )
                                : [];

                        buttons.forEach(
                            (button) => {
                                button.disabled =
                                    true;
                            }
                        );

                        const button =
                            form.querySelector(
                                '[data-interest-submit]'
                            );

                        if (!button) {
                            return;
                        }

                        const idle =
                            button.querySelector(
                                '.registration-submit__idle'
                            );

                        const loading =
                            button.querySelector(
                                '.registration-submit__loading'
                            );

                        if (idle) {
                            idle.classList.add(
                                'd-none'
                            );
                        }

                        if (loading) {
                            loading.classList.remove(
                                'd-none'
                            );
                        }
                    }
                );
            }
        );

        /**
 * Display the existing application confirmation modal
 * after a successful Accept or Decline action.
 */
        function showInterestActionConfirmation() {
            const source =
                document.querySelector(
                    '[data-interest-action-notice]'
                );

            if (
                !(source instanceof HTMLElement)
                || typeof bootstrap === 'undefined'
            ) {
                return;
            }

            const modalElement =
                document.getElementById(
                    'appConfirmationModal'
                );

            if (!modalElement) {
                return;
            }

            const title =
                document.getElementById(
                    'appConfirmationModalTitle'
                );

            const message =
                document.getElementById(
                    'appConfirmationModalMessage'
                );

            const cancel =
                document.getElementById(
                    'appConfirmationModalCancel'
                );

            const confirm =
                document.getElementById(
                    'appConfirmationModalConfirm'
                );

            const confirmLabel =
                confirm?.querySelector(
                    '[data-confirm-modal-label]'
                );

            const confirmLoading =
                confirm?.querySelector(
                    '[data-confirm-modal-loading]'
                );

            if (
                !title
                || !message
                || !confirm
            ) {
                return;
            }

            title.textContent =
                source.dataset.noticeTitle
                || 'Completed';

            message.textContent =
                source.dataset.noticeMessage
                || 'The action was completed.';

            /*
             * This is acknowledgement only,
             * so hide Cancel and show one OK button.
             */
            cancel?.classList.add(
                'd-none'
            );

            confirm.classList.remove(
                'btn-danger',
                'btn-warning',
                'btn-success'
            );

            confirm.classList.add(
                'btn-primary'
            );

            if (confirmLabel) {
                confirmLabel.textContent =
                    'OK';

                confirmLabel.classList.remove(
                    'd-none'
                );
            }

            confirmLoading?.classList.add(
                'd-none'
            );

            const modal =
                bootstrap.Modal
                    .getOrCreateInstance(
                        modalElement
                    );

            const closeNotice =
                function () {
                    modal.hide();

                    confirm.removeEventListener(
                        'click',
                        closeNotice
                    );
                };

            confirm.addEventListener(
                'click',
                closeNotice
            );

            modalElement.addEventListener(
                'hidden.bs.modal',
                function restoreCancel() {
                    cancel?.classList.remove(
                        'd-none'
                    );

                    modalElement.removeEventListener(
                        'hidden.bs.modal',
                        restoreCancel
                    );
                }
            );

            modal.show();
        }
        showInterestActionConfirmation();
    }
    
);