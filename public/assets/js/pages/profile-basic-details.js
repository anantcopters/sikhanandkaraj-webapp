'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const editor = document.getElementById(
        'basicDetailsOffcanvas'
    );

    const form = document.getElementById(
        'basicDetailsForm'
    );

    const submitButton = document.getElementById(
        'saveBasicDetailsButton'
    );

    const dateOfBirth = document.getElementById(
        'dateOfBirth'
    );

    const agePreview = document.getElementById(
        'memberAgePreview'
    );

    /**
     * Reopen the editor when server-side validation fails.
     */
    if (
        editor
        && editor.dataset.openOnError === 'true'
        && window.bootstrap
    ) {
        bootstrap.Offcanvas
            .getOrCreateInstance(editor)
            .show();
    }

    /**
     * Show the member's calculated age as helper text.
     */
    const updateAgePreview = () => {
        if (!dateOfBirth || !agePreview) {
            return;
        }

        if (dateOfBirth.value === '') {
            agePreview.textContent = '';
            return;
        }

        const birthDate = new Date(
            `${dateOfBirth.value}T00:00:00`
        );

        if (Number.isNaN(birthDate.getTime())) {
            agePreview.textContent = '';
            return;
        }

        const today = new Date();

        let age = today.getFullYear()
            - birthDate.getFullYear();

        const monthDifference = today.getMonth()
            - birthDate.getMonth();

        if (
            monthDifference < 0
            || (
                monthDifference === 0
                && today.getDate() < birthDate.getDate()
            )
        ) {
            age--;
        }

        agePreview.textContent = age >= 18
            ? `Current age: ${age} years`
            : '';
    };

    dateOfBirth?.addEventListener(
        'change',
        updateAgePreview
    );

    updateAgePreview();

    /**
     * Show the existing loading state and prevent duplicate submission.
     */
    form?.addEventListener('submit', () => {
        if (!submitButton) {
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
    });
});