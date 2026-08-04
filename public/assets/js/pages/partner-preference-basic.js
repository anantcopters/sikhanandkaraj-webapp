'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById(
        'partnerPreferenceBasicForm'
    );

    const submitButton = document.getElementById(
        'savePartnerPreferenceButton'
    );

    const rangeError = document.getElementById(
        'preferenceRangeError'
    );

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    /**
     * Display or clear a cross-field validation error.
     *
     * @param {string} message
     * @returns {void}
     */
    function setRangeError(message) {
        if (!(rangeError instanceof HTMLElement)) {
            return;
        }

        rangeError.textContent = message;
    }

    /**
     * Validate Age From/To and Height From/To.
     *
     * @returns {boolean}
     */
    function validateRange() {
        const item = String(
            form.dataset.preferenceItem || ''
        );

        setRangeError('');

        if (item === 'age') {
            const ageFrom = form.querySelector(
                '[name="age_from"]'
            );

            const ageTo = form.querySelector(
                '[name="age_to"]'
            );

            if (
                ageFrom instanceof HTMLSelectElement
                && ageTo instanceof HTMLSelectElement
                && ageFrom.value !== ''
                && ageTo.value !== ''
                && Number(ageFrom.value)
                > Number(ageTo.value)
            ) {
                setRangeError(
                    'Minimum age cannot be greater '
                    + 'than maximum age.'
                );

                ageTo.focus();

                return false;
            }
        }

        if (item === 'height') {
            const heightFrom = form.querySelector(
                '[name="height_from_id"]'
            );

            const heightTo = form.querySelector(
                '[name="height_to_id"]'
            );

            if (
                heightFrom instanceof HTMLSelectElement
                && heightTo instanceof HTMLSelectElement
                && heightFrom.value !== ''
                && heightTo.value !== ''
                && Number(heightFrom.value)
                > Number(heightTo.value)
            ) {
                setRangeError(
                    'Minimum height cannot be greater '
                    + 'than maximum height.'
                );

                heightTo.focus();

                return false;
            }
        }

        return true;
    }

    form.addEventListener('submit', (event) => {
        if (!validateRange()) {
            event.preventDefault();
            return;
        }

        if (
            event.defaultPrevented
            || !form.checkValidity()
            || !(submitButton instanceof HTMLButtonElement)
        ) {
            return;
        }

        window.setTimeout(() => {
            if (
                event.defaultPrevented
                || !form.checkValidity()
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
        }, 0);
    });
});