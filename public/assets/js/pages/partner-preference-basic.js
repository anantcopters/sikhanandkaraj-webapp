'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById(
        'partnerPreferenceLifestyleForm'
    );

    const submitButton = document.getElementById(
        'savePartnerPreferenceButton'
    );

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    /**
     * Activate the existing project save loader.
     *
     * @returns {void}
     */
    function showSavingState() {
        if (
            !(
                submitButton
                instanceof HTMLButtonElement
            )
        ) {
            return;
        }

        submitButton.disabled = true;

        submitButton.setAttribute(
            'aria-busy',
            'true'
        );

        submitButton
            .querySelector(
                '.registration-submit__label'
            )
            ?.classList.add('d-none');

        submitButton
            .querySelector(
                '.registration-submit__loading'
            )
            ?.classList.remove('d-none');
    }

    form.addEventListener('submit', (event) => {
        if (
            event.defaultPrevented
            || !form.checkValidity()
        ) {
            return;
        }

        /*
         * Delay the loader until the shared client-validation
         * handler has had an opportunity to prevent submission.
         *
         * This is the same flow used by Basic Partner Preference.
         */
        window.setTimeout(() => {
            if (
                event.defaultPrevented
                || !form.checkValidity()
            ) {
                return;
            }

            showSavingState();
        }, 0);
    });
});