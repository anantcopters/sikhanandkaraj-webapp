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

        sort.addEventListener(
            'change',
            () => {
                const loader =
                    document.querySelector(
                        '.page-loader'
                    );

                if (
                    loader instanceof HTMLElement
                ) {
                    loader.classList.remove(
                        'd-none'
                    );
                }

                form.submit();
            }
        );
    }
);