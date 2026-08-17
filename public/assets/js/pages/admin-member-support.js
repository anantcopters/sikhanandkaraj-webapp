(function () {
    'use strict';

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            const modalElement =
                document.getElementById(
                    'memberSupportReviewModal'
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

            const form =
                modalElement.querySelector(
                    '[data-support-review-form]'
                );

            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            const title =
                document.getElementById(
                    'memberSupportReviewTitle'
                );

            const label =
                modalElement.querySelector(
                    '[data-support-review-label]'
                );

            function configure(
                type,
                id,
                profileLabel
            ) {
                const template =
                    type === 'report'
                        ? form.dataset
                            .reportActionTemplate
                        : form.dataset
                            .contactActionTemplate;

                form.action = template.replace(
                    '__ID__',
                    id
                );

                if (title) {
                    title.textContent =
                        type === 'report'
                            ? 'Review Profile Report'
                            : 'Review Contact Request';
                }

                if (label) {
                    label.textContent =
                        profileLabel
                            ? 'Profile ID: '
                            + profileLabel
                            : '';
                }
            }

            document.querySelectorAll(
                '[data-support-review-open]'
            ).forEach(function (button) {
                button.addEventListener(
                    'click',
                    function () {
                        configure(
                            button.dataset.reviewType
                            || '',
                            button.dataset.reviewId
                            || '',
                            button.dataset.reviewLabel
                            || ''
                        );

                        modal.show();
                    }
                );
            });

            if (
                modalElement.dataset.reopenReview
                === '1'
                && modalElement.dataset.reopenId
            ) {
                configure(
                    modalElement.dataset.reviewType
                    || '',
                    modalElement.dataset.reopenId,
                    ''
                );

                modal.show();
            }

            form.addEventListener(
                'submit',
                function (event) {
                    const note =
                        form.querySelector(
                            'textarea'
                        );

                    if (
                        note
                        instanceof HTMLTextAreaElement
                    ) {
                        note.value = note.value
                            .replace(/\s+/g, ' ')
                            .trim();
                    }

                    if (!form.checkValidity()) {
                        event.preventDefault();

                        const invalid =
                            form.querySelector(
                                ':invalid'
                            );

                        invalid?.classList.add(
                            'is-invalid'
                        );

                        invalid?.focus();
                    }
                }
            );
        }
    );
})();