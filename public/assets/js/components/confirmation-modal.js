(function (window, document) {
    'use strict';

    const modalElement =
        document.getElementById(
            'appConfirmationModal'
        );

    if (
        !modalElement
        || typeof bootstrap === 'undefined'
    ) {
        return;
    }

    const modal =
        bootstrap.Modal
            .getOrCreateInstance(
                modalElement
            );

    const titleElement =
        document.getElementById(
            'appConfirmationModalTitle'
        );

    const messageElement =
        document.getElementById(
            'appConfirmationModalMessage'
        );

    const iconElement =
        document.getElementById(
            'appConfirmationModalIcon'
        );

    const cancelButton =
        document.getElementById(
            'appConfirmationModalCancel'
        );

    const confirmButton =
        document.getElementById(
            'appConfirmationModalConfirm'
        );

    if (
        !titleElement
        || !messageElement
        || !iconElement
        || !cancelButton
        || !confirmButton
    ) {
        return;
    }

    const confirmLabel =
        confirmButton.querySelector(
            '[data-confirm-modal-label]'
        );

    const confirmLoading =
        confirmButton.querySelector(
            '[data-confirm-modal-loading]'
        );

    /*
     * Keep the reusable confirmation modal above another
     * application modal when one is already open.
     */
    const confirmationModalZIndex =
        1080;

    const confirmationBackdropZIndex =
        1075;

    modalElement.style.zIndex =
        String(
            confirmationModalZIndex
        );

    modalElement.addEventListener(
        'shown.bs.modal',
        function () {
            const backdrops =
                document.querySelectorAll(
                    '.modal-backdrop'
                );

            const confirmationBackdrop =
                backdrops.length > 0
                    ? backdrops[
                    backdrops.length - 1
                    ]
                    : null;

            if (
                confirmationBackdrop
                instanceof HTMLElement
            ) {
                confirmationBackdrop
                    .style.zIndex =
                    String(
                        confirmationBackdropZIndex
                    );
            }
        }
    );

    const permittedButtonClasses = [
        'btn-primary',
        'btn-danger',
        'btn-warning',
        'btn-success'
    ];

    let pendingForm = null;

    let pendingSubmitButton = null;

    let confirmationInProgress =
        false;

    let programmaticConfirmHandler =
        null;

    /**
     * Return a safe Bootstrap button class.
     *
     * @param {string} requestedClass
     *
     * @returns {string}
     */
    function resolveButtonClass(
        requestedClass
    ) {
        return permittedButtonClasses
            .includes(
                requestedClass
            )
            ? requestedClass
            : 'btn-danger';
    }

    /**
     * Reset the confirmation button into its
     * normal non-loading state.
     */
    function resetConfirmButton() {
        confirmationInProgress =
            false;

        confirmButton.disabled =
            false;

        confirmButton.removeAttribute(
            'aria-busy'
        );

        confirmLabel?.classList.remove(
            'd-none'
        );

        confirmLoading?.classList.add(
            'd-none'
        );

        confirmLoading?.classList.remove(
            'd-inline-flex'
        );
    }

    /**
     * Restore normal reusable modal controls.
     */
    function resetModalControls() {
        cancelButton.classList.remove(
            'd-none'
        );

        resetConfirmButton();
    }

    /**
     * Apply a permitted Bootstrap button class.
     *
     * @param {string} requestedClass
     */
    function setConfirmButtonClass(
        requestedClass
    ) {
        permittedButtonClasses.forEach(
            function (buttonClass) {
                confirmButton
                    .classList
                    .remove(
                        buttonClass
                    );
            }
        );

        confirmButton.classList.add(
            resolveButtonClass(
                requestedClass
            )
        );
    }

    /**
     * Update the confirmation modal icon.
     *
     * @param {string} requestedIcon
     */
    function setIcon(
        requestedIcon
    ) {
        const icon =
            iconElement.querySelector(
                'i'
            );

        if (!icon) {
            return;
        }

        icon.className =
            '';

        icon.classList.add(
            requestedIcon
            || 'ri-alert-line',

            'fs-24'
        );
    }

    /**
     * Clear a previous programmatic confirmation
     * listener when the same global modal is reused.
     */
    function removeProgrammaticHandler() {
        if (
            typeof programmaticConfirmHandler
            !== 'function'
        ) {
            return;
        }

        confirmButton.removeEventListener(
            'click',
            programmaticConfirmHandler
        );

        programmaticConfirmHandler =
            null;
    }

    /**
     * Open normal form confirmation.
     *
     * @param {HTMLFormElement} form
     * @param {HTMLElement|null} submitButton
     */
    function openForForm(
        form,
        submitButton
    ) {
        removeProgrammaticHandler();

        resetModalControls();

        pendingForm =
            form;

        pendingSubmitButton =
            submitButton;

        titleElement.textContent =
            form.dataset.confirmTitle
            || 'Confirm action';

        messageElement.textContent =
            form.dataset.confirmMessage
            || 'Are you sure you want to continue?';

        if (confirmLabel) {
            confirmLabel.textContent =
                form.dataset.confirmButtonText
                || 'Confirm';
        }

        if (confirmLoading) {
            confirmLoading.lastChild
                .textContent =
                form.dataset
                    .confirmLoadingText
                    ? ' '
                    + form.dataset
                        .confirmLoadingText
                    : ' Processing...';
        }

        setConfirmButtonClass(
            form.dataset
                .confirmButtonClass
            || 'btn-danger'
        );

        setIcon(
            form.dataset.confirmIcon
            || 'ri-alert-line'
        );

        modal.show();
    }

    /**
     * Intercept all forms configured to use
     * the reusable confirmation modal.
     */
    document.addEventListener(
        'submit',
        function (event) {
            const form =
                event.target;

            if (
                !(
                    form
                    instanceof HTMLFormElement
                )
                || !form.matches(
                    '[data-confirm-form]'
                )
            ) {
                return;
            }

            /*
             * This is the submission issued after
             * the member has confirmed.
             */
            if (
                form.dataset.confirmed
                === 'true'
            ) {
                delete form.dataset
                    .confirmed;

                return;
            }

            event.preventDefault();

            event.stopImmediatePropagation();

            const submitButton =
                event.submitter
                    instanceof HTMLElement
                    ? event.submitter
                    : form.querySelector(
                        '[type="submit"]'
                    );

            openForForm(
                form,
                submitButton
            );
        },
        true
    );

    /**
     * Handle form-confirmation requests.
     *
     * Programmatic confirmations are handled by
     * the temporary listener registered in show().
     */
    confirmButton.addEventListener(
        'click',
        function () {
            if (
                confirmationInProgress
                || !pendingForm
            ) {
                return;
            }

            confirmationInProgress =
                true;

            confirmButton.disabled =
                true;

            confirmButton.setAttribute(
                'aria-busy',
                'true'
            );

            confirmLabel?.classList.add(
                'd-none'
            );

            confirmLoading?.classList.remove(
                'd-none'
            );

            confirmLoading?.classList.add(
                'd-inline-flex'
            );

            pendingForm.dataset.confirmed =
                'true';

            modal.hide();

            /*
             * requestSubmit preserves:
             *
             * - native validation;
             * - original submit button;
             * - page-specific submit handlers.
             */
            if (
                pendingSubmitButton
                instanceof HTMLButtonElement
                || pendingSubmitButton
                instanceof HTMLInputElement
            ) {
                pendingForm.requestSubmit(
                    pendingSubmitButton
                );
            } else {
                pendingForm.requestSubmit();
            }
        }
    );

    modalElement.addEventListener(
        'hidden.bs.modal',
        function () {
            /*
             * A confirmed form submission is still
             * being dispatched.
             */
            if (
                confirmationInProgress
            ) {
                window.setTimeout(
                    function () {
                        pendingForm =
                            null;

                        pendingSubmitButton =
                            null;

                        resetModalControls();
                    },
                    0
                );

                return;
            }

            pendingForm =
                null;

            pendingSubmitButton =
                null;

            removeProgrammaticHandler();

            resetModalControls();
        }
    );

    /**
     * Public reusable API.
     *
     * Example confirmation:
     *
     * window.AppConfirmationModal.show({
     *     title: 'Delete record?',
     *     message: 'This cannot be undone.',
     *     confirmText: 'Delete',
     *     buttonClass: 'btn-danger',
     *     icon: 'ri-delete-bin-line',
     *     onConfirm: function () {}
     * });
     *
     * Example acknowledgement:
     *
     * window.AppConfirmationModal.show({
     *     title: 'Interest Accepted',
     *     message: 'Interest accepted successfully.',
     *     confirmText: 'OK',
     *     buttonClass: 'btn-primary',
     *     icon: 'ri-checkbox-circle-line',
     *     showCancel: false,
     *     closeOnConfirm: true
     * });
     */
    window.AppConfirmationModal = {

        show: function (options) {
            const settings =
                options || {};

            /*
             * Programmatic mode must never inherit
             * a previous form confirmation.
             */
            pendingForm =
                null;

            pendingSubmitButton =
                null;

            removeProgrammaticHandler();

            resetModalControls();

            titleElement.textContent =
                settings.title
                || 'Confirm action';

            messageElement.textContent =
                settings.message
                || 'Are you sure you want to continue?';

            if (confirmLabel) {
                confirmLabel.textContent =
                    settings.confirmText
                    || 'Confirm';
            }

            setConfirmButtonClass(
                settings.buttonClass
                || 'btn-danger'
            );

            setIcon(
                settings.icon
                || 'ri-alert-line'
            );

            /*
             * Confirmation dialogs show Cancel by default.
             *
             * Success acknowledgement dialogs can use:
             *
             * showCancel: false
             */
            if (
                settings.showCancel
                === false
            ) {
                cancelButton
                    .classList
                    .add(
                        'd-none'
                    );
            }

            programmaticConfirmHandler =
                function () {
                    if (
                        typeof settings
                            .onConfirm
                        === 'function'
                    ) {
                        settings.onConfirm();
                    }

                    /*
                     * Normal confirmation API keeps
                     * previous behaviour unless explicitly
                     * requested otherwise.
                     *
                     * Acknowledgement dialogs generally use
                     * closeOnConfirm: true.
                     */
                    if (
                        settings.closeOnConfirm
                        === true
                    ) {
                        modal.hide();
                    }
                };

            confirmButton.addEventListener(
                'click',
                programmaticConfirmHandler
            );

            modal.show();
        },

        hide: function () {
            modal.hide();
        }
    };

})(window, document);