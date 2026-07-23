'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const employedIn = document.querySelector(
        '[name="employed_in"]'
    );

    const occupation = document.querySelector(
        '[name="occupation_id"]'
    );

    if (!employedIn || !occupation) {
        return;
    }

    /**
     * Find the Not Applicable occupation using the option's
     * data-code value.
     */
    const notApplicableOption = Array.from(
        occupation.options
    ).find((option) => {
        return option.dataset.code === 'NOT_APPLICABLE';
    });

    if (!notApplicableOption) {
        return;
    }

    const updateOccupation = () => {
        if (employedIn.value === 'NOT_WORKING') {
            occupation.value =
                notApplicableOption.value;
        }
    };

    employedIn.addEventListener(
        'change',
        updateOccupation
    );

    updateOccupation();
});