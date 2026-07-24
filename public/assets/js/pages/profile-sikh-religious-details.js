'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById(
        'sikhReligiousDetailsForm'
    );

    const submitButton = document.getElementById(
        'saveSikhReligiousDetailsButton'
    );

    if (
        !(form instanceof HTMLFormElement)
        || !(submitButton instanceof HTMLButtonElement)
    ) {
        return;
    }

    const submitLabel = submitButton.querySelector(
        '.registration-submit__label'
    );

    const submitLoader = submitButton.querySelector(
        '.registration-submit__loading'
    );

    let isSubmitting = false;

    form.addEventListener('submit', (event) => {
        /*
         * The common validation script may prevent submission when
         * required fields are invalid.
         */
        if (
            event.defaultPrevented
            || !form.checkValidity()
        ) {
            return;
        }

        if (isSubmitting) {
            event.preventDefault();
            return;
        }

        event.preventDefault();
        isSubmitting = true;

        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');

        submitLabel?.classList.add('d-none');
        submitLoader?.classList.remove('d-none');

        /*
         * Allow the browser to render the loader before navigation.
         */
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => {
                form.submit();
            });
        });
    });
});