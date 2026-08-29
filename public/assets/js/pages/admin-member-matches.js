'use strict';

document.addEventListener(
    'DOMContentLoaded',
    () => {
        const sortSelect =
            document.querySelector(
                '[data-admin-match-sort]'
            );

        const loader =
            document.querySelector(
                '[data-admin-match-results-loader]'
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
            sortSelect
            && sortSelect.form
        ) {
            sortSelect.addEventListener(
                'change',
                () => {
                    showLoader();

                    sortSelect.form.submit();
                }
            );
        }

        window.addEventListener(
            'pageshow',
            hideLoader
        );
    }
);