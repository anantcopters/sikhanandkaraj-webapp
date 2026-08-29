'use strict';

document.addEventListener(
    'DOMContentLoaded',
    () => {
        const form =
            document.querySelector(
                '[data-search-result-sort-form]'
            );

        if (
            !(form instanceof HTMLFormElement)
        ) {
            return;
        }

        const sort =
            form.querySelector(
                'select[name="sort"]'
            );

        if (
            !(sort instanceof HTMLSelectElement)
        ) {
            return;
        }

        const loader =
            document.querySelector(
                '[data-search-results-loader]'
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

        sort.addEventListener(
            'change',
            () => {
                showLoader();

                form.submit();
            }
        );

        window.addEventListener(
            'pageshow',
            () => {
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
            }
        );
    }
);