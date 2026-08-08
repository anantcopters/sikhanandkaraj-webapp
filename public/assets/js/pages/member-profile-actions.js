(function () {
    'use strict';

    function initializeBlockModal() {
        const modalElement =
            document.getElementById(
                'memberBlockModal'
            );

        if (
            !modalElement
            || typeof bootstrap ===
            'undefined'
        ) {
            return;
        }

        const modal =
            bootstrap.Modal
                .getOrCreateInstance(
                    modalElement
                );

        const form =
            modalElement.querySelector(
                '[data-member-block-form]'
            );

        const comment =
            modalElement.querySelector(
                '#member-block-comment'
            );

        const submit =
            modalElement.querySelector(
                '[data-member-block-submit]'
            );

        const label =
            modalElement.querySelector(
                '[data-member-block-label]'
            );

        const loading =
            modalElement.querySelector(
                '[data-member-block-loading]'
            );

        document.querySelectorAll(
            '[data-member-block-open]'
        ).forEach(function (button) {
            button.addEventListener(
                'click',
                function () {
                    modal.show();
                }
            );
        });

        if (
            modalElement.dataset
                .reopenMemberBlock === '1'
        ) {
            modal.show();
        }

        if (
            !(form instanceof HTMLFormElement)
            || !(
                comment
                instanceof HTMLTextAreaElement
            )
        ) {
            return;
        }

        form.addEventListener(
            'submit',
            function (event) {
                const value =
                    comment.value
                        .replace(/\s+/g, ' ')
                        .trim();

                comment.value = value;

                if (
                    value === ''
                    || value.length > 250
                ) {
                    event.preventDefault();

                    comment.classList.add(
                        'is-invalid'
                    );

                    comment.focus();

                    return;
                }

                comment.classList.remove(
                    'is-invalid'
                );

                if (
                    submit
                    instanceof HTMLButtonElement
                ) {
                    submit.disabled = true;

                    submit.setAttribute(
                        'aria-busy',
                        'true'
                    );
                }

                label?.classList.add(
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

    /**
 * Initialize a standard submit-button loading state.
 *
 * Any form using this helper must provide:
 *
 * - a submit button;
 * - a normal label element;
 * - a loading element.
 *
 * @param {string} formSelector
 * @param {string} labelSelector
 * @param {string} loadingSelector
 *
 * @returns {void}
 */
    function initializeActionLoader(
        formSelector,
        labelSelector,
        loadingSelector
    ) {
        document.querySelectorAll(
            formSelector
        ).forEach(function (form) {
            if (
                !(
                    form
                    instanceof HTMLFormElement
                )
            ) {
                return;
            }

            form.addEventListener(
                'submit',
                function () {
                    const button =
                        form.querySelector(
                            '[type="submit"]'
                        );

                    const label =
                        form.querySelector(
                            labelSelector
                        );

                    const loading =
                        form.querySelector(
                            loadingSelector
                        );

                    if (
                        button
                        instanceof HTMLButtonElement
                    ) {
                        button.disabled = true;

                        button.setAttribute(
                            'aria-busy',
                            'true'
                        );
                    }

                    label?.classList.add(
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
        });
    }

    /**
     * Use the already-present global confirmation modal
     * as a post-success acknowledgement popup.
     */
    function showMemberActionConfirmation() {
        const source =
            document.querySelector(
                '[data-member-action-notice]'
            );

        if (
            !(source instanceof HTMLElement)
            || typeof bootstrap ===
            'undefined'
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

                modalElement
                    .removeEventListener(
                        'hidden.bs.modal',
                        restoreCancel
                    );
            }
        );

        modal.show();
    }

    initializeBlockModal();

    initializeActionLoader(
        '[data-member-interest-form]',
        '[data-member-interest-label]',
        '[data-member-interest-loading]'
    );

    initializeActionLoader(
        '[data-member-shortlist-form]',
        '[data-member-shortlist-label]',
        '[data-member-shortlist-loading]'
    );

    showMemberActionConfirmation();
})();