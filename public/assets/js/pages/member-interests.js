'use strict';

document.addEventListener(
    'DOMContentLoaded',
    () => {

        /**
         * Handle loading state while Accept/Decline
         * is being processed.
         */
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
                         * Disable both Accept and Decline
                         * buttons on the current profile.
                         *
                         * This prevents the member clicking
                         * the opposite action while the first
                         * request is being submitted.
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

                                button.setAttribute(
                                    'aria-disabled',
                                    'true'
                                );
                            }
                        );

                        /*
                         * Only the button actually clicked
                         * displays the Saving loader.
                         */
                        const button =
                            form.querySelector(
                                '[data-interest-submit]'
                            );

                        if (!button) {
                            return;
                        }

                        button.setAttribute(
                            'aria-busy',
                            'true'
                        );

                        const idle =
                            button.querySelector(
                                '.registration-submit__idle'
                            );

                        const loading =
                            button.querySelector(
                                '.registration-submit__loading'
                            );

                        idle?.classList.add(
                            'd-none'
                        );

                        loading?.classList.remove(
                            'd-none'
                        );

                        loading?.classList.add(
                            'd-inline-flex'
                        );
                    }
                );
            }
        );

        /**
         * Display a post-success acknowledgement
         * after Accept/Decline redirects back to
         * the Interest page.
         *
         * Modal behaviour belongs entirely to the
         * reusable confirmation-modal.js component.
         */
        function showInterestActionConfirmation() {
            const source =
                document.querySelector(
                    '[data-interest-action-notice]'
                );

            if (
                !(
                    source
                    instanceof HTMLElement
                )
                || !window
                    .AppConfirmationModal
            ) {
                return;
            }

            window.AppConfirmationModal
                .show(
                    {
                        title:
                            source.dataset
                                .noticeTitle
                            || 'Completed',

                        message:
                            source.dataset
                                .noticeMessage
                            || 'The action was completed.',

                        /*
                         * This modal is an acknowledgement
                         * after the action has already
                         * succeeded.
                         */
                        confirmText:
                            'OK',

                        buttonClass:
                            'btn-primary',

                        icon:
                            'ri-checkbox-circle-line',

                        showCancel:
                            false,

                        closeOnConfirm:
                            true
                    }
                );
        }

        showInterestActionConfirmation();
    }
);