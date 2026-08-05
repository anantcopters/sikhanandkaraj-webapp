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
     * Configure the Block/Unblock modal.
     */
    function initializeStatusModal() {
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
                        || ![
                            'BLOCK',
                            'UNBLOCK'
                        ].includes(action)
                    ) {
                        return;
                    }

                    form.action = formAction;

                    if (title) {
                        title.textContent =
                            action === 'BLOCK'
                                ? 'Block Member'
                                : 'Unblock Member';
                    }

                    if (identity) {
                        identity.textContent =
                            memberCode !== ''
                                ? memberName
                                + ' · '
                                + memberCode
                                : memberName;
                    }

                    if (nameElement) {
                        nameElement.textContent =
                            memberName;
                    }

                    if (codeElement) {
                        codeElement.textContent =
                            memberCode !== ''
                                ? 'Member Code: '
                                + memberCode
                                : 'Member Code: —';
                    }

                    if (message) {
                        message.textContent =
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

                    if (
                        reason
                        instanceof HTMLInputElement
                    ) {
                        reason.value = '';

                        reason.classList.remove(
                            'is-invalid'
                        );
                    }

                    modal.show();
                }
            );
        });

        form.addEventListener(
            'submit',
            function (event) {
                if (
                    !(
                        reason
                        instanceof HTMLInputElement
                    )
                ) {
                    return;
                }

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
     * Render one local date-time value supplied by PHP.
     *
     * JavaScript must not parse or timezone-convert this value. PHP has
     * already converted it from UTC to the configured display timezone.
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
     * Render member status history.
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
                        + 'text-success';

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
     * Initialize the History button.
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

    /**
     * Initialize retained-photo modal previews.
     */
    function initializePhotoModal() {
        const modalElement = document.getElementById(
            'admin-photo-modal'
        );

        const title = document.getElementById(
            'admin-photo-modal-title'
        );

        const loading = document.getElementById(
            'admin-photo-loading'
        );

        const errorElement = document.getElementById(
            'admin-photo-error'
        );

        const image = document.getElementById(
            'admin-photo-image'
        );

        if (
            !modalElement
            || !title
            || !loading
            || !errorElement
            || !(image instanceof HTMLImageElement)
            || typeof bootstrap === 'undefined'
        ) {
            return;
        }

        const modal =
            bootstrap.Modal.getOrCreateInstance(
                modalElement
            );

        document.querySelectorAll(
            '[data-admin-photo]'
        ).forEach(function (button) {
            button.addEventListener(
                'click',
                async function () {
                    const endpoint = String(
                        button.dataset
                            .modalUrlEndpoint
                        ?? ''
                    ).trim();

                    const photoTitle = String(
                        button.dataset.photoTitle
                        ?? 'Member Photograph'
                    ).trim();

                    if (endpoint === '') {
                        return;
                    }

                    title.textContent = photoTitle;

                    loading.classList.remove(
                        'd-none'
                    );

                    errorElement.classList.add(
                        'd-none'
                    );

                    image.classList.add(
                        'd-none'
                    );

                    image.removeAttribute(
                        'src'
                    );

                    image.alt = photoTitle;

                    modal.show();
                    button.disabled = true;

                    try {
                        const response = await fetch(
                            endpoint,
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
                                ?? 'The photograph '
                                + 'could not be loaded.'
                            );
                        }

                        const imageUrl = String(
                            payload.originalUrl
                            || payload.mediumUrl
                            || ''
                        ).trim();

                        if (imageUrl === '') {
                            throw new Error(
                                'The photograph is unavailable.'
                            );
                        }

                        image.src = imageUrl;

                        image.classList.remove(
                            'd-none'
                        );
                    } catch (error) {
                        errorElement.textContent =
                            error instanceof Error
                                ? error.message
                                : 'The photograph could '
                                + 'not be loaded.';

                        errorElement.classList.remove(
                            'd-none'
                        );
                    } finally {
                        loading.classList.add(
                            'd-none'
                        );

                        button.disabled = false;
                    }
                }
            );
        });

        modalElement.addEventListener(
            'hidden.bs.modal',
            function () {
                image.removeAttribute(
                    'src'
                );

                image.classList.add(
                    'd-none'
                );

                errorElement.classList.add(
                    'd-none'
                );

                loading.classList.remove(
                    'd-none'
                );
            }
        );
    }

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            initializeStatusModal();
            initializeHistoryModal();
            initializePhotoModal();
        }
    );
}());