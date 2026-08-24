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
         * Delay the loader until shared client validation
         * has had an opportunity to prevent submission.
         *
         * This follows the existing Basic Partner
         * Preference save flow.
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