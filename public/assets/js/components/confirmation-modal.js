(function (window, document) {
    'use strict';

    const modalElement = document.getElementById(
        'appConfirmationModal'
    );

    if (
        !modalElement
        || typeof bootstrap === 'undefined'
    ) {
        return;
    }

    const titleElement = document.getElementById(
        'appConfirmationModalTitle'
    );

    const messageElement = document.getElementById(
        'appConfirmationModalMessage'
    );

    const iconElement = document.getElementById(
        'appConfirmationModalIcon'
    );

    const cancelButton = document.getElementById(
        'appConfirmationModalCancel'
    );

    const confirmButton = document.getElementById(
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

    const confirmLabel = confirmButton.querySelector(
        '[data-confirm-modal-label]'
    );

    const confirmLoading = confirmButton.querySelector(
        '[data-confirm-modal-loading]'
    );

    /*
    * Keep the reusable confirmation modal above any already-open
    * application modal, such as the member photo review modal.
    *
    * Bootstrap normally gives all modals the same z-index, so the
    * confirmation backdrop may otherwise appear behind the first modal.
    */
    const confirmationModalZIndex = 1080;
    const confirmationBackdropZIndex = 1075;

    modalElement.style.zIndex = String(
        confirmationModalZIndex
    );

    modalElement.addEventListener(
        'shown.bs.modal',
        function () {
            const backdrops = document.querySelectorAll(
                '.modal-backdrop'
            );

            const confirmationBackdrop =
                backdrops.length > 0
                    ? backdrops[backdrops.length - 1]
                    : null;

            if (confirmationBackdrop instanceof HTMLElement) {
                confirmationBackdrop.style.zIndex = String(
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
    let confirmationInProgress = false;

    /**
     * Return a safe Bootstrap button class.
     *
     * @param {string} requestedClass
     * @returns {string}
     */
    function resolveButtonClass(requestedClass) {
        return permittedButtonClasses.includes(
            requestedClass
        )
            ? requestedClass
            : 'btn-danger';
    }

    /**
     * Reset the modal confirmation button.
     */
    function resetConfirmButton() {
        confirmationInProgress = false;

        confirmButton.disabled = false;

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
     * Apply the requested Bootstrap button class.
     *
     * @param {string} requestedClass
     */
    function setConfirmButtonClass(
        requestedClass
    ) {
        permittedButtonClasses.forEach(
            function (buttonClass) {
                confirmButton.classList.remove(
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
     * Only existing project icon classes are accepted.
     *
     * @param {string} requestedIcon
     */
    function setIcon(requestedIcon) {
        const icon = iconElement.querySelector(
            'i'
        );

        if (!icon) {
            return;
        }

        icon.className = '';

        icon.classList.add(
            requestedIcon || 'ri-alert-line',
            'fs-24'
        );
    }

    /**
     * Open confirmation for a form.
     *
     * @param {HTMLFormElement} form
     * @param {HTMLElement|null} submitButton
     */
    function openForForm(
        form,
        submitButton
    ) {
        pendingForm = form;
        pendingSubmitButton = submitButton;

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
            confirmLoading.lastChild.textContent =
                form.dataset.confirmLoadingText
                    ? ' '
                    + form.dataset
                        .confirmLoadingText
                    : ' Processing...';
        }

        setConfirmButtonClass(
            form.dataset.confirmButtonClass
            || 'btn-danger'
        );

        setIcon(
            form.dataset.confirmIcon
            || 'ri-alert-line'
        );

        resetConfirmButton();

        modal.show();
    }

    /**
     * Intercept every reusable confirmation form.
     *
     * Capturing mode ensures this executes before page-specific submit
     * handlers and prevents destructive actions until confirmed.
     */
    document.addEventListener(
        'submit',
        function (event) {
            const form = event.target;

            if (
                !(form instanceof HTMLFormElement)
                || !form.matches(
                    '[data-confirm-form]'
                )
            ) {
                return;
            }

            /*
             * The second submission occurs after confirmation.
             * Allow it to continue to page-specific handlers and server.
             */
            if (
                form.dataset.confirmed === 'true'
            ) {
                delete form.dataset.confirmed;

                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            const submitButton =
                event.submitter instanceof HTMLElement
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

    confirmButton.addEventListener(
        'click',
        function () {
            if (
                confirmationInProgress
                || !pendingForm
            ) {
                return;
            }

            confirmationInProgress = true;

            confirmButton.disabled = true;

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

            /*
             * Mark the form so the next submit event is allowed.
             */
            pendingForm.dataset.confirmed =
                'true';

            modal.hide();

            /*
             * requestSubmit preserves native validation, the original
             * submit button and page-specific submit handlers.
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
             * Do not clear the form while its confirmed submission
             * is being dispatched.
             */
            if (confirmationInProgress) {
                window.setTimeout(
                    function () {
                        pendingForm = null;
                        pendingSubmitButton = null;

                        resetConfirmButton();
                    },
                    0
                );

                return;
            }

            pendingForm = null;
            pendingSubmitButton = null;

            resetConfirmButton();
        }
    );

    /**
     * Optional programmatic API for non-form actions.
     *
     * Usage:
     *
     * window.AppConfirmationModal.show({
     *     title: 'Delete record?',
     *     message: 'This cannot be undone.',
     *     confirmText: 'Delete',
     *     onConfirm: function () {}
     * });
     */
    window.AppConfirmationModal = {
        show: function (options) {
            const settings = options || {};

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

            resetConfirmButton();

            const handler = function () {
                confirmButton.removeEventListener(
                    'click',
                    handler
                );

                if (
                    typeof settings.onConfirm
                    === 'function'
                ) {
                    settings.onConfirm();
                }
            };

            confirmButton.addEventListener(
                'click',
                handler,
                {
                    once: true
                }
            );

            modal.show();
        },

        hide: function () {
            modal.hide();
        }
    };
})(window, document);