(function () {
    'use strict';

    function escapeHtml(value) {
        const element = document.createElement('div');

        element.textContent = String(value ?? '');

        return element.innerHTML;
    }

    function formatDateTime(value) {
        const rawValue = String(value ?? '').trim();

        if (rawValue === '') {
            return '—';
        }

        const date = new Date(rawValue);

        return Number.isNaN(date.getTime())
            ? rawValue
            : date.toLocaleString();
    }

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

        const modal = bootstrap.Modal
            .getOrCreateInstance(
                modalElement
            );

        document.querySelectorAll(
            '[data-member-status]'
        ).forEach(function (button) {
            button.addEventListener(
                'click',
                function () {
                    const action = String(
                        button.dataset.action ?? ''
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

                    document.getElementById(
                        'member-status-modal-title'
                    ).textContent = action === 'BLOCK'
                            ? 'Block Member'
                            : 'Unblock Member';

                    document.getElementById(
                        'member-status-identity'
                    ).textContent = code !== ''
                            ? name + ' · ' + code
                            : name;

                    document.getElementById(
                        'member-status-member-name'
                    ).textContent = name;

                    document.getElementById(
                        'member-status-member-code'
                    ).textContent = code !== ''
                            ? 'Member Code: ' + code
                            : 'Member Code: —';

                    document.getElementById(
                        'member-status-message'
                    ).textContent = action === 'BLOCK'
                            ? 'Enter the reason for blocking this member.'
                            : 'Enter the reason for unblocking this member.';

                    const submit = document.getElementById(
                        'member-status-submit'
                    );

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

    function renderHistory(history) {
        if (
            !Array.isArray(history)
            || history.length === 0
        ) {
            return [
                '<div class="text-center text-muted py-4">',
                '<i class="ri-history-line fs-24 ',
                'd-block mb-2" aria-hidden="true"></i>',
                'No block or unblock history is available.',
                '</div>'
            ].join('');
        }

        const rows = history.map(function (item) {
            const action = String(
                item.action ?? ''
            ).toUpperCase();

            const badge = action === 'BLOCK'
                ? 'bg-danger-subtle text-danger'
                : 'bg-success-subtle text-success';

            return [
                '<tr>',
                '<td><span class="badge ',
                badge,
                '">',
                escapeHtml(action),
                '</span></td>',
                '<td>',
                escapeHtml(item.previousStatus ?? '—'),
                ' <i class="ri-arrow-right-line mx-1"',
                ' aria-hidden="true"></i> ',
                escapeHtml(item.newStatus ?? '—'),
                '</td>',
                '<td>',
                escapeHtml(item.reason ?? '—'),
                '</td>',
                '<td>',
                escapeHtml(
                    item.adminName ?? 'Administrator'
                ),
                '</td>',
                '<td>',
                escapeHtml(
                    formatDateTime(item.changedAt)
                ),
                '</td>',
                '</tr>'
            ].join('');
        }).join('');

        return [
            '<div class="table-responsive">',
            '<table class="table table-hover ',
            'table-nowrap align-middle mb-0">',
            '<thead class="bg-info-subtle">',
            '<tr>',
            '<th>Action</th>',
            '<th>Transition</th>',
            '<th>Reason</th>',
            '<th>Administrator</th>',
            '<th>Date</th>',
            '</tr>',
            '</thead>',
            '<tbody>',
            rows,
            '</tbody>',
            '</table>',
            '</div>'
        ].join('');
    }

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

        const modal = bootstrap.Modal
            .getOrCreateInstance(
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
                        '<div class="text-center text-muted py-4">',
                        '<span class="spinner-border ',
                        'spinner-border-sm me-2"></span>',
                        'Loading history...',
                        '</div>'
                    ].join('');

                    modal.show();

                    try {
                        const response = await fetch(
                            url,
                            {
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
                            || payload.successful !== true
                        ) {
                            throw new Error(
                                payload.message
                                ?? 'History could not be loaded.'
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

                        title.textContent = reference !== ''
                            ? memberName
                            + ' ('
                            + reference
                            + ')'
                            : memberName;

                        content.innerHTML = renderHistory(
                            payload.history
                        );
                    } catch (error) {
                        content.innerHTML = [
                            '<div class="alert alert-danger mb-0">',
                            escapeHtml(
                                error instanceof Error
                                    ? error.message
                                    : 'History could not be loaded.'
                            ),
                            '</div>'
                        ].join('');
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