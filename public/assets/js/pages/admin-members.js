(function () {
    'use strict';

    /**
     * Escape dynamically generated HTML.
     *
     * @param {unknown} value
     * @returns {string}
     */
    function escapeHtml(value) {
        const element = document.createElement(
            'div'
        );

        element.textContent = String(
            value ?? ''
        );

        return element.innerHTML;
    }

    /**
     * Render one local date-time value supplied by PHP.
     *
     * JavaScript must not use new Date() or toLocaleString() because PHP has
     * already converted the database UTC timestamp to the configured display
     * timezone.
     *
     * @param {object} item
     * @returns {string}
     */
    function renderDateTime(item) {
        const changedAtDisplay = String(
            item.changedAtDisplay
            ?? '—'
        ).trim();

        const changedAtIso = String(
            item.changedAtIso
            ?? ''
        ).trim();

        if (changedAtIso === '') {
            return escapeHtml(
                changedAtDisplay !== ''
                    ? changedAtDisplay
                    : '—'
            );
        }

        return [
            '<time datetime="',
            escapeHtml(changedAtIso),
            '">',
            escapeHtml(
                changedAtDisplay !== ''
                    ? changedAtDisplay
                    : '—'
            ),
            '</time>'
        ].join('');
    }

    /**
     * Submit the member listing when the status filter changes.
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
     * Initialize list-page Block/Unblock modal actions.
     */
    function initializeStatusModal() {
        const modalElement = document.getElementById(
            'member-status-modal'
        );

        const form = document.getElementById(
            'member-status-form'
        );

        const reason = document.getElementById(
            'member-status-reason'
        );

        if (
            !modalElement
            || !(form instanceof HTMLFormElement)
            || !(reason instanceof HTMLInputElement)
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
                    const action = String(
                        button.dataset.action
                        ?? ''
                    ).toUpperCase();

                    const name = String(
                        button.dataset.memberName
                        ?? 'Member'
                    ).trim();

                    const code = String(
                        button.dataset.memberCode
                        ?? ''
                    ).trim();

                    const formAction = String(
                        button.dataset.formAction
                        ?? ''
                    ).trim();

                    if (
                        formAction === ''
                        || ![
                            'BLOCK',
                            'UNBLOCK'
                        ].includes(action)
                    ) {
                        return;
                    }

                    form.action = formAction;

                    const title =
                        document.getElementById(
                            'member-status-modal-title'
                        );

                    const identity =
                        document.getElementById(
                            'member-status-identity'
                        );

                    const memberName =
                        document.getElementById(
                            'member-status-member-name'
                        );

                    const memberCode =
                        document.getElementById(
                            'member-status-member-code'
                        );

                    const statusMessage =
                        document.getElementById(
                            'member-status-message'
                        );

                    const submit =
                        document.getElementById(
                            'member-status-submit'
                        );

                    if (title) {
                        title.textContent =
                            action === 'BLOCK'
                                ? 'Block Member'
                                : 'Unblock Member';
                    }

                    if (identity) {
                        identity.textContent =
                            code !== ''
                                ? name
                                + ' · '
                                + code
                                : name;
                    }

                    if (memberName) {
                        memberName.textContent =
                            name;
                    }

                    if (memberCode) {
                        memberCode.textContent =
                            code !== ''
                                ? 'Member Code: '
                                + code
                                : 'Member Code: —';
                    }

                    if (statusMessage) {
                        statusMessage.textContent =
                            action === 'BLOCK'
                                ? 'Enter the reason for '
                                + 'blocking this member.'
                                : 'Enter the reason for '
                                + 'unblocking this member.';
                    }

                    if (submit) {
                        submit.textContent =
                            action === 'BLOCK'
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

                    reason.value = '';

                    reason.classList.remove(
                        'is-invalid'
                    );

                    modal.show();
                }
            );
        });

        form.addEventListener(
            'submit',
            function (event) {
                const value = reason.value
                    .replace(/\s+/g, ' ')
                    .trim();

                reason.value = value;

                if (
                    value === ''
                    || value.length > 64
                ) {
                    event.preventDefault();

                    reason.classList.add(
                        'is-invalid'
                    );

                    reason.focus();
                }
            }
        );
    }

    /**
     * Render member account-status history.
     *
     * @param {Array<object>} history
     * @returns {string}
     */
    function renderHistory(history) {
        if (
            !Array.isArray(history)
            || history.length === 0
        ) {
            return [
                '<div class="text-center ',
                'text-muted py-4">',
                '<i class="ri-history-line ',
                'fs-24 d-block mb-2" ',
                'aria-hidden="true"></i>',
                'No block or unblock history ',
                'is available.',
                '</div>'
            ].join('');
        }

        const rows = history.map(
            function (item) {
                const action = String(
                    item.action
                    ?? ''
                ).toUpperCase();

                const badgeClass =
                    action === 'BLOCK'
                        ? 'bg-danger-subtle '
                        + 'text-danger'
                        : 'bg-success-subtle '
                        + 'text-body p-2';

                return [
                    '<tr>',
                    '<td><span class="badge ',
                    badgeClass,
                    '">',
                    escapeHtml(action),
                    '</span></td>',
                    '<td>',
                    escapeHtml(
                        item.previousStatus
                        ?? '—'
                    ),
                    ' <i class="ri-arrow-right-line ',
                    'mx-1" aria-hidden="true"></i> ',
                    escapeHtml(
                        item.newStatus
                        ?? '—'
                    ),
                    '</td>',
                    '<td>',
                    escapeHtml(
                        item.reason
                        ?? '—'
                    ),
                    '</td>',
                    '<td>',
                    escapeHtml(
                        item.adminName
                        ?? 'Administrator'
                    ),
                    '<div class="small text-muted">',
                    escapeHtml(
                        item.adminRole
                        ?? ''
                    ),
                    '</div>',
                    '</td>',
                    '<td>',
                    renderDateTime(item),
                    '</td>',
                    '</tr>'
                ].join('');
            }
        ).join('');

        return [
            '<div class="table-responsive">',
            '<table class="table table-hover ',
            'table-nowrap align-middle mb-0">',
            '<thead class="bg-info-subtle">',
            '<tr>',
            '<th scope="col">Action</th>',
            '<th scope="col">Transition</th>',
            '<th scope="col">Reason</th>',
            '<th scope="col">Administrator</th>',
            '<th scope="col">Date</th>',
            '</tr>',
            '</thead>',
            '<tbody>',
            rows,
            '</tbody>',
            '</table>',
            '</div>'
        ].join('');
    }

    /**
     * Initialize member status-history modal.
     */
    function initializeHistoryModal() {
        const modalElement = document.getElementById(
            'member-history-modal'
        );

        const title = document.getElementById(
            'member-history-modal-title'
        );

        const content = document.getElementById(
            'member-history-content'
        );

        if (
            !modalElement
            || !title
            || !content
            || typeof bootstrap === 'undefined'
        ) {
            return;
        }

        const modal =
            bootstrap.Modal.getOrCreateInstance(
                modalElement
            );

        document.querySelectorAll(
            '[data-member-history]'
        ).forEach(function (button) {
            button.addEventListener(
                'click',
                async function () {
                    const url = String(
                        button.dataset.historyUrl
                        ?? ''
                    ).trim();

                    if (url === '') {
                        return;
                    }

                    content.innerHTML = [
                        '<div class="text-center ',
                        'text-muted py-4">',
                        '<span class="spinner-border ',
                        'spinner-border-sm me-2" ',
                        'aria-hidden="true"></span>',
                        'Loading history...',
                        '</div>'
                    ].join('');

                    modal.show();
                    button.disabled = true;

                    try {
                        const response = await fetch(
                            url,
                            {
                                method: 'GET',

                                headers: {
                                    'Accept':
                                        'application/json',

                                    'X-Requested-With':
                                        'XMLHttpRequest'
                                },

                                credentials:
                                    'same-origin'
                            }
                        );

                        const payload =
                            await response.json();

                        if (
                            !response.ok
                            || payload.successful
                            !== true
                        ) {
                            throw new Error(
                                payload.message
                                ?? 'History could not '
                                + 'be loaded.'
                            );
                        }

                        const memberName = String(
                            payload.member?.name
                            ?? 'Member'
                        );

                        const reference = String(
                            payload.member?.reference
                            ?? ''
                        );

                        title.textContent =
                            reference !== ''
                                ? memberName
                                + ' ('
                                + reference
                                + ')'
                                : memberName;

                        content.innerHTML =
                            renderHistory(
                                payload.history
                            );
                    } catch (error) {
                        content.innerHTML = [
                            '<div class="alert ',
                            'alert-danger mb-0" ',
                            'role="alert">',
                            escapeHtml(
                                error instanceof Error
                                    ? error.message
                                    : 'History could not '
                                    + 'be loaded.'
                            ),
                            '</div>'
                        ].join('');
                    } finally {
                        button.disabled = false;
                    }
                }
            );
        });
    }

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            initializeStatusFilter();
            initializeStatusModal();
            initializeHistoryModal();
        }
    );
}());