'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById(
        'partnerPreferenceLifestyleForm'
    );

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const optionInputs = Array.from(
        form.querySelectorAll(
            '[data-lifestyle-option]'
        )
    );

    /**
 * Update the visual state for one Lifestyle option.
 *
 * @param {HTMLInputElement} input
 */
    const updateOptionIcon = (input) => {
        const label = form.querySelector(
            `label[for="${CSS.escape(input.id)}"]`
        );

        if (!(label instanceof HTMLLabelElement)) {
            return;
        }

        const icon = label.querySelector('i');

        if (icon instanceof HTMLElement) {
            icon.classList.toggle(
                'ri-check-line',
                input.checked
            );

            icon.classList.toggle(
                'ri-add-line',
                !input.checked
            );
        }

        /*
         * Keep the selected button state explicit.
         * This also fixes the first selected option
         * not receiving its selected colour.
         */
        label.classList.toggle(
            'active',
            input.checked
        );

        label.setAttribute(
            'aria-pressed',
            input.checked ? 'true' : 'false'
        );
    };

    /*
     * Initialise all Lifestyle options.
     */
    optionInputs.forEach((input) => {
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        updateOptionIcon(input);

        input.addEventListener('change', () => {
            updateOptionIcon(input);
        });
    });

    /*
     * Existing Partner Preference save-loader behaviour.
     */
    form.addEventListener('submit', (event) => {
        const submitButton =
            document.getElementById(
                'savePartnerPreferenceButton'
            );

        if (
            !(
                submitButton
                instanceof HTMLButtonElement
            )
        ) {
            return;
        }

        if (
            form.dataset.submitting
            === 'true'
        ) {
            event.preventDefault();

            return;
        }

        event.preventDefault();

        form.dataset.submitting = 'true';

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

        /*
         * Allow the browser to paint the loader
         * before native form submission.
         */
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                form.submit();
            });
        });
    });
});