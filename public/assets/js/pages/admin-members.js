(function () {
    'use strict';

    /**
     * Open and configure a member status modal.
     *
     * @param {HTMLElement} button
     * @param {bootstrap.Modal} modal
     * @param {HTMLFormElement} form
     */
    function configureStatusModal(
        button,
        modal,
        form
    ) {
        const action = String(
            button.dataset.action ?? ''
        ).toUpperCase();

        const memberName = String(
            button.dataset.memberName
            ?? 'Member'
        ).trim();

        const memberCode = String(
            button.dataset.memberCode
            ?? ''
        ).trim();

        const formAction = String(
            button.dataset.formAction
            ?? ''
        ).trim();

        if (
            formAction === ''
            || !['BLOCK', 'UNBLOCK']
                .includes(action)
        ) {
            return;
        }

        const title = document.getElementById(
            'member-status-modal-title'
        );

        const identity = document.getElementById(
            'member-status-identity'
        );

        const nameElement = document.getElementById(
            'member-status-member-name'
        );

        const codeElement = document.getElementById(
            'member-status-member-code'
        );

        const message = document.getElementById(
            'member-status-message'
        );

        const reason = document.getElementById(
            'member-status-reason'
        );

        const submit = document.getElementById(
            'member-status-submit'
        );

        form.action = formAction;

        if (title) {
            title.textContent = action === 'BLOCK'
                ? 'Block Member'
                : 'Unblock Member';
        }

        if (identity) {
            identity.textContent = memberCode !== ''
                ? memberName + ' · ' + memberCode
                : memberName;
        }

        if (nameElement) {
            nameElement.textContent = memberName;
        }

        if (codeElement) {
            codeElement.textContent = memberCode !== ''
                ? 'Member Code: ' + memberCode
                : 'Member Code: —';
        }

        if (message) {
            message.textContent = action === 'BLOCK'
                ? 'Enter the reason for blocking this member.'
                : 'Enter the reason for unblocking this member.';
        }

        if (submit) {
            submit.textContent = action === 'BLOCK'
                ? 'Block Member'
                : 'Unblock Member';

            submit.classList.remove(
                'btn-primary',
                'btn-danger',
                'btn-success'
            );

            submit.classList.add(
                action === 'BLOCK'
                    ? 'btn-danger'
                    : 'btn-success'
            );
        }

        if (reason) {
            reason.value = '';
            reason.classList.remove(
                'is-invalid'
            );
        }

        modal.show();

        const focusReason = function () {
            if (reason) {
                reason.focus();
            }

            modal._element.removeEventListener(
                'shown.bs.modal',
                focusReason
            );
        };

        modal._element.addEventListener(
            'shown.bs.modal',
            focusReason
        );
    }

    /**
     * Initialize member list status filtering.
     */
    function initializeStatusFilter() {
        const select = document.getElementById(
            'member-status-filter'
        );

        const form = document.getElementById(
            'member-status-filter-form'
        );

        if (!select || !form) {
            return;
        }

        select.addEventListener(
            'change',
            function () {
                form.submit();
            }
        );
    }

    /**
     * Initialize list-page block/unblock buttons.
     */
    function initializeStatusButtons() {
        const modalElement = document.getElementById(
            'member-status-modal'
        );

        const form = document.getElementById(
            'member-status-form'
        );

        if (
            !modalElement
            || !(form instanceof HTMLFormElement)
            || typeof bootstrap === 'undefined'
        ) {
            return;
        }

        const modal =
            bootstrap.Modal.getOrCreateInstance(
                modalElement
            );

        document.querySelectorAll(
            '[data-member-status]'
        ).forEach(function (button) {
            button.addEventListener(
                'click',
                function () {
                    configureStatusModal(
                        button,
                        modal,
                        form
                    );
                }
            );
        });
    }

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            initializeStatusFilter();
            initializeStatusButtons();
        }
    );
}());