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
     *
     * @returns {void}
     */
    function setRangeError(message) {
        if (!(rangeError instanceof HTMLElement)) {
            return;
        }

        rangeError.textContent = message;
    }

    /**
     * Resolve the numeric value of a selected field.
     *
     * @param {string} selector
     *
     * @returns {number|null}
     */
    function selectedNumber(selector) {
        const field = form.querySelector(selector);

        if (!(field instanceof HTMLSelectElement)) {
            return null;
        }

        if (field.value === '') {
            return null;
        }

        const value = Number(field.value);

        return Number.isFinite(value)
            ? value
            : null;
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
            const ageFrom = selectedNumber(
                '[name="age_from"]'
            );

            const ageTo = selectedNumber(
                '[name="age_to"]'
            );

            if (
                ageFrom !== null
                && ageTo !== null
                && ageFrom > ageTo
            ) {
                setRangeError(
                    'Minimum age cannot be greater '
                    + 'than maximum age.'
                );

                form
                    .querySelector(
                        '[name="age_to"]'
                    )
                    ?.focus();

                return false;
            }
        }

        if (item === 'height') {
            const heightFrom = selectedNumber(
                '[name="height_from_id"]'
            );

            const heightTo = selectedNumber(
                '[name="height_to_id"]'
            );

            if (
                heightFrom !== null
                && heightTo !== null
                && heightFrom > heightTo
            ) {
                setRangeError(
                    'Minimum height cannot be greater '
                    + 'than maximum height.'
                );

                form
                    .querySelector(
                        '[name="height_to_id"]'
                    )
                    ?.focus();

                return false;
            }
        }

        return true;
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
        if (!validateRange()) {
            event.preventDefault();

            return;
        }

        if (
            event.defaultPrevented
            || !form.checkValidity()
        ) {
            return;
        }

        /*
         * Delay the loader until the shared client-validation
         * handler has had an opportunity to prevent submission.
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