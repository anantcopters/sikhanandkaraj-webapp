(function (window, document) {
    'use strict';

    function initializeStatusFilters() {
        document.querySelectorAll(
            '[data-support-status-filter]'
        ).forEach(function (select) {
            if (
                !(select instanceof HTMLSelectElement)
                || select.dataset.statusFilterInitialized
                === 'true'
            ) {
                return;
            }

            const form = select.form;

            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            select.dataset.statusFilterInitialized =
                'true';

            select.addEventListener(
                'change',
                function () {
                    form.submit();
                }
            );
        });
    }

    function initializeSupportReviewModal() {
        const modalElement =
            document.getElementById(
                'memberSupportReviewModal'
            );

        if (
            !(modalElement instanceof HTMLElement)
            || typeof bootstrap === 'undefined'
        ) {
            return;
        }

        if (
            modalElement.dataset.supportInitialized
            === 'true'
        ) {
            return;
        }

        const form = modalElement.querySelector(
            '[data-support-review-form]'
        );

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        modalElement.dataset.supportInitialized =
            'true';

        const modal =
            bootstrap.Modal.getOrCreateInstance(
                modalElement
            );

        const title =
            document.getElementById(
                'memberSupportReviewTitle'
            );

        const label =
            modalElement.querySelector(
                '[data-support-review-label]'
            );

        const note =
            modalElement.querySelector(
                '#supportReviewNote'
            );

        function configure(
            type,
            recordId,
            recordLabel
        ) {
            const normalizedType =
                type === 'report'
                    ? 'report'
                    : 'contact';

            const normalizedId =
                String(recordId || '').replace(
                    /[^0-9]/g,
                    ''
                );

            if (normalizedId === '') {
                return false;
            }

            const template =
                normalizedType === 'report'
                    ? form.dataset
                        .reportActionTemplate
                    : form.dataset
                        .contactActionTemplate;

            if (!template) {
                return false;
            }

            form.action = template.replace(
                '__ID__',
                normalizedId
            );

            if (title) {
                title.textContent =
                    normalizedType === 'report'
                        ? 'Review Profile Report'
                        : 'Resolve Contact Request';
            }

            if (label) {
                label.textContent =
                    recordLabel !== ''
                        ? (
                            normalizedType === 'report'
                                ? 'Profile ID: '
                                : 'Request ID: '
                        ) + recordLabel
                        : '';
            }

            return true;
        }

        document.querySelectorAll(
            '[data-support-review-open]'
        ).forEach(function (button) {
            button.addEventListener(
                'click',
                function () {
                    if (
                        note instanceof
                        HTMLTextAreaElement
                    ) {
                        note.value = '';
                        note.setCustomValidity('');
                        note.classList.remove(
                            'is-invalid'
                        );
                    }

                    const configured = configure(
                        button.dataset
                            .supportReviewType
                        || '',
                        button.dataset
                            .supportReviewId
                        || '',
                        button.dataset
                            .supportReviewLabel
                        || ''
                    );

                    if (configured) {
                        modal.show();
                    }
                }
            );
        });

        if (
            modalElement.dataset.reopenReview
            === '1'
            && modalElement.dataset.reopenId
        ) {
            const configured = configure(
                modalElement.dataset.reviewType
                || '',
                modalElement.dataset.reopenId,
                ''
            );

            if (configured) {
                modal.show();
            }
        }

        form.addEventListener(
            'submit',
            function () {
                if (
                    note instanceof
                    HTMLTextAreaElement
                ) {
                    note.value = note.value
                        .replace(/\s+/g, ' ')
                        .trim();
                }
            },
            true
        );
    }

    function initialize() {
        initializeStatusFilters();
        initializeSupportReviewModal();
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initialize
        );
    } else {
        initialize();
    }
})(window, document);