'use strict';

document.addEventListener(
    'DOMContentLoaded',
    () => {
        const form =
            document.querySelector(
                '[data-admin-match-form]'
            );

        const sortSelect =
            document.querySelector(
                '[data-admin-match-sort]'
            );

        const loader =
            document.querySelector(
                '[data-admin-match-results-loader]'
            );

        const pagination =
            document.querySelector(
                '[data-admin-match-pagination]'
            );

        const navigationLinks =
            document.querySelectorAll(
                '[data-admin-match-navigation]'
            );

        const showLoader = () => {
            if (
                !(loader instanceof HTMLElement)
            ) {
                return;
            }

            loader.classList.remove(
                'd-none'
            );

            loader.classList.add(
                'd-flex'
            );

            loader.setAttribute(
                'aria-hidden',
                'false'
            );
        };

        const hideLoader = () => {
            if (
                !(loader instanceof HTMLElement)
            ) {
                return;
            }

            loader.classList.add(
                'd-none'
            );

            loader.classList.remove(
                'd-flex'
            );

            loader.setAttribute(
                'aria-hidden',
                'true'
            );
        };

        if (
            form instanceof HTMLFormElement
        ) {
            form.addEventListener(
                'submit',
                showLoader
            );
        }

        if (
            sortSelect
            instanceof HTMLSelectElement
            && sortSelect.form
            instanceof HTMLFormElement
        ) {
            sortSelect.addEventListener(
                'change',
                () => {
                    showLoader();

                    sortSelect.form.submit();
                }
            );
        }

        navigationLinks.forEach(
            (link) => {
                if (
                    !(link instanceof HTMLAnchorElement)
                ) {
                    return;
                }

                link.addEventListener(
                    'click',
                    showLoader
                );
            }
        );

        if (
            pagination instanceof HTMLElement
        ) {
            pagination.addEventListener(
                'click',
                (event) => {
                    const target =
                        event.target;

                    if (
                        !(target instanceof Element)
                    ) {
                        return;
                    }

                    const link =
                        target.closest(
                            'a'
                        );

                    if (
                        !(link instanceof HTMLAnchorElement)
                    ) {
                        return;
                    }

                    showLoader();
                }
            );
        }

        window.addEventListener(
            'pageshow',
            hideLoader
        );
    }
);