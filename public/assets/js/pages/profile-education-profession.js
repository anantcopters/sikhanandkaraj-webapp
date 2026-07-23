'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById(
        'educationProfessionForm'
    );

    const submitButton = document.getElementById(
        'saveEducationProfessionButton'
    );

    const employedIn = document.getElementById(
        'employedIn'
    );

    const occupation = document.getElementById(
        'occupation'
    );

    /**
     * Set the occupation to Not Applicable when the member
     * selects Not Working.
     *
     * Choices.js keeps the native select as the source of truth.
     * After changing it, also update the Choices instance when
     * the global helper is available.
     */
    function synchronizeNotWorkingOccupation() {
        if (!employedIn || !occupation) {
            return;
        }

        const notApplicableOption = Array.from(
            occupation.options
        ).find((option) => {
            return (
                option.dataset.code
                === 'NOT_APPLICABLE'
            );
        });

        if (
            employedIn.value !== 'NOT_WORKING'
            || !notApplicableOption
        ) {
            return;
        }

        const notApplicableValue =
            notApplicableOption.value;

        occupation.value = notApplicableValue;

        /*
         * Rebuild the Choices instance so its visible label
         * matches the updated native select value.
         */
        if (window.SelectChoice) {
            window.SelectChoice.destroy(occupation);
            window.SelectChoice.create(occupation);
        }

        occupation.dispatchEvent(
            new Event(
                'change',
                {
                    bubbles: true
                }
            )
        );
    }

    employedIn?.addEventListener(
        'change',
        synchronizeNotWorkingOccupation
    );

    synchronizeNotWorkingOccupation();

    /**
     * Display the loading state only after the form is valid.
     * This follows the same behavior as Basic Details.
     */
    form?.addEventListener('submit', (event) => {
        if (
            !submitButton
            || event.defaultPrevented
            || !form.checkValidity()
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