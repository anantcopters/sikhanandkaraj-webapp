'use strict';

document.addEventListener(
    'DOMContentLoaded',
    () => {
        const sortSelect =
            document.querySelector(
                '[data-admin-match-sort]'
            );

        if (
            sortSelect
            && sortSelect.form
        ) {
            sortSelect.addEventListener(
                'change',
                () => {
                    sortSelect.form.submit();
                }
            );
        }

        const modalElement =
            document.getElementById(
                'admin-match-diagnostic-modal'
            );

        if (!modalElement) {
            return;
        }

        const profileReferenceInput =
            modalElement.querySelector(
                '[name="profile_reference"]'
            );

        const diagnosticButtons =
            document.querySelectorAll(
                '[data-match-diagnostic]'
            );

        diagnosticButtons.forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        if (
                            profileReferenceInput
                        ) {
                            profileReferenceInput.value =
                                button.dataset
                                    .profileReference
                                || '';

                            profileReferenceInput
                                .classList
                                .remove(
                                    'is-invalid'
                                );
                        }

                        const modal =
                            bootstrap.Modal
                                .getOrCreateInstance(
                                    modalElement
                                );

                        modal.show();
                    }
                );
            }
        );

        /*
         * Diagnostic POST follows the existing Post/Redirect/Get flow.
         *
         * When the controller returns validation errors or a completed
         * comparison through flashdata, reopen the modal automatically.
         */
        if (
            modalElement.dataset
                .matchResult === '1'
            || modalElement.dataset
                .matchError === '1'
        ) {
            const modal =
                bootstrap.Modal
                    .getOrCreateInstance(
                        modalElement
                    );

            modal.show();
        }
    }
);