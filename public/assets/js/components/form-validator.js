/**
 * Generic application form validator.
 *
 * Enhances forms marked with data-validate while relying on native HTML
 * constraints and server-side CI4 validation as the source of truth.
 */
(function (window, document) {
    'use strict';

    const FORM_SELECTOR = 'form[data-validate]';
    const FIELD_SELECTOR = [
        'input:not([type="hidden"]):not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])'
    ].join(',');

    /**
     * Return a user-friendly message for one invalid field.
     *
     * HTML data attributes can override generic messages:
     *
     * data-error-required
     * data-error-email
     * data-error-pattern
     * data-error-minlength
     * data-error-maxlength
     *
     * @param {HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement} field
     *
     * @returns {string}
     */
    function getErrorMessage(field) {
        const validity = field.validity;

        if (validity.valueMissing) {
            return field.dataset.errorRequired
                || 'This field is required.';
        }

        if (validity.typeMismatch) {
            return field.dataset.errorEmail
                || 'Please enter a valid value.';
        }

        if (validity.patternMismatch) {
            return field.dataset.errorPattern
                || 'Please enter a valid value.';
        }

        if (validity.tooShort) {
            return field.dataset.errorMinlength
                || `Please enter at least ${field.minLength} characters.`;
        }

        if (validity.tooLong) {
            return field.dataset.errorMaxlength
                || `Please enter no more than ${field.maxLength} characters.`;
        }

        return field.validationMessage
            || 'Please check this field.';
    }

    /**
     * Find or create the error element belonging to a field.
     */
    function getErrorElement(field) {
        const errorId = `${field.id}Error`;

        let errorElement = document.getElementById(errorId);

        if (errorElement) {
            return errorElement;
        }

        errorElement = document.createElement('div');
        errorElement.id = errorId;
        errorElement.className = 'invalid-feedback';
        errorElement.dataset.validationError = field.name;

        const choicesElement = field.nextElementSibling;

        if (
            field.matches('select[data-choices]')
            && choicesElement
            && choicesElement.classList.contains('choices')
        ) {
            choicesElement.insertAdjacentElement(
                'afterend',
                errorElement
            );
        } else {
            field.insertAdjacentElement('afterend', errorElement);
        }

        return errorElement;
    }

    /**
     * Synchronize validation state with Choices.js.
     */
    function updateChoicesState(field, isValid) {
        if (!field.matches('select[data-choices]')) {
            return;
        }

        const choicesElement = field.nextElementSibling;

        if (
            !choicesElement
            || !choicesElement.classList.contains('choices')
        ) {
            return;
        }

        choicesElement.classList.toggle('is-invalid', !isValid);
        choicesElement.classList.toggle('is-valid', isValid);
    }

    /**
     * Display an error for one field.
     */
    function showError(field, message) {
        const errorElement = getErrorElement(field);

        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', errorElement.id);

        errorElement.textContent = message;
        errorElement.classList.add('d-block');

        updateChoicesState(field, false);
    }

    /**
     * Remove the visible error for one field.
     */
    function clearError(field) {
        const errorElement = document.getElementById(
            `${field.id}Error`
        );

        field.classList.remove('is-invalid');
        field.removeAttribute('aria-invalid');

        if (errorElement) {
            errorElement.textContent = '';
            errorElement.classList.remove('d-block');
        }

        updateChoicesState(field, true);
    }

    /**
     * Validate one form field.
     */
    function validateField(field) {
        if (field.checkValidity()) {
            clearError(field);
            return true;
        }

        showError(field, getErrorMessage(field));

        return false;
    }

    /**
     * Validate radio groups as one logical field.
     */
    function validateRadioGroup(field, form) {
        const group = form.querySelectorAll(
            `input[type="radio"][name="${CSS.escape(field.name)}"]`
        );

        const isRequired = Array.from(group).some(
            (radio) => radio.required
        );

        const isChecked = Array.from(group).some(
            (radio) => radio.checked
        );

        if (!isRequired || isChecked) {
            group.forEach(clearError);
            return true;
        }

        showError(
            group[0],
            group[0].dataset.errorRequired
                || 'Please select an option.'
        );

        return false;
    }

    /**
     * Initialize one form.
     */
    function initializeForm(form) {
        const fields = Array.from(
            form.querySelectorAll(FIELD_SELECTOR)
        );

        form.addEventListener('submit', function (event) {
            let isFormValid = true;
            let firstInvalidField = null;
            const processedRadioGroups = new Set();

            fields.forEach(function (field) {
                let isFieldValid;

                if (field.type === 'radio') {
                    if (processedRadioGroups.has(field.name)) {
                        return;
                    }

                    processedRadioGroups.add(field.name);
                    isFieldValid = validateRadioGroup(field, form);
                } else {
                    isFieldValid = validateField(field);
                }

                if (!isFieldValid) {
                    isFormValid = false;
                    firstInvalidField ||= field;
                }
            });

            if (!isFormValid) {
                event.preventDefault();

                firstInvalidField?.focus();
            }
        });

        fields.forEach(function (field) {
            field.addEventListener('blur', function () {
                validateField(field);
            });

            field.addEventListener('input', function () {
                if (field.classList.contains('is-invalid')) {
                    validateField(field);
                }
            });

            field.addEventListener('change', function () {
                if (
                    field.classList.contains('is-invalid')
                    || field.type === 'radio'
                    || field.tagName === 'SELECT'
                ) {
                    validateField(field);
                }
            });
        });
    }

    function init(container = document) {
        container
            .querySelectorAll(FORM_SELECTOR)
            .forEach(initializeForm);
    }

    window.FormValidator = Object.freeze({
        init
    });

    document.addEventListener('DOMContentLoaded', function () {
        init();
    });
})(window, document);