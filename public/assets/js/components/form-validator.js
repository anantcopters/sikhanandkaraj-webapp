/**
 * Generic application form validator.
 *
 * Enhances forms marked with data-validate while relying on native HTML
 * constraints. CI4 server-side validation remains authoritative.
 */
(function (window, document) {
    'use strict';

    const FORM_SELECTOR = 'form[data-validate]';

    const FIELD_SELECTOR = [
        'input:not([type="hidden"]):not([disabled])'
        + ':not([data-validation-ignore])',

        'select:not([disabled])'
        + ':not([data-validation-ignore])',

        'textarea:not([disabled])'
        + ':not([data-validation-ignore])'
    ].join(',');

    /**
     * Return a user-friendly validation message.
     *
     * @param {HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement} field
     *
     * @returns {string}
     */
    function getErrorMessage(field) {
        const validity = field.validity;

        if (validity.customError) {
            return field.validationMessage
                || 'Please check this field.';
        }

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
     * Return the Choices.js visual wrapper for a select.
     *
     * The global SelectChoice component initializes selects through
     * data-choice, so validation must use the same contract.
     *
     * @param {HTMLSelectElement} field
     *
     * @returns {HTMLElement|null}
     */
    function getChoicesElement(field) {
        if (!field.matches('select[data-choice]')) {
            return null;
        }

        /*
         * Choices.js normally wraps the original select inside its
         * generated .choices container, therefore looking only at the
         * next sibling is not reliable.
         */
        const parent = field.closest(
            '.choices'
        );

        if (parent instanceof HTMLElement) {
            return parent;
        }

        const sibling =
            field.nextElementSibling;

        if (
            sibling instanceof HTMLElement
            && sibling.classList.contains(
                'choices'
            )
        ) {
            return sibling;
        }

        const previousSibling =
            field.previousElementSibling;

        if (
            previousSibling instanceof HTMLElement
            && previousSibling.classList.contains(
                'choices'
            )
        ) {
            return previousSibling;
        }

        return null;
    }

    /**
     * Find or create the error element belonging to a field.
     *
     * Existing server-rendered elements are preferred so client-side
     * validation and server-side validation use the same message area.
     *
     * @param {HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement} field
     *
     * @returns {HTMLElement}
     */
    function getErrorElement(field) {
        const form = field.form;

        /**
         * First look for an error element identified by the field name.
         *
         * This is especially important for radio groups, where both radio
         * inputs share one error message below the complete group.
         */
        if (form && field.name) {
            const namedError = form.querySelector(
                `[data-validation-error="${CSS.escape(field.name)}"]`
            );

            if (namedError) {
                return namedError;
            }
        }

        const errorId = `${field.id}Error`;

        const existingError = document.getElementById(errorId);

        if (existingError) {
            return existingError;
        }

        const errorElement = document.createElement('div');

        errorElement.id = errorId;
        errorElement.className = 'invalid-feedback';
        errorElement.dataset.validationError = field.name;

        const choicesElement = getChoicesElement(field);

        if (choicesElement) {
            choicesElement.insertAdjacentElement(
                'afterend',
                errorElement
            );
        } else {
            field.insertAdjacentElement(
                'afterend',
                errorElement
            );
        }

        return errorElement;
    }

    /**
     * Synchronize a select's validation state with Choices.js.
     *
     * @param {HTMLSelectElement} field
     * @param {boolean} isValid
     *
     * @returns {void}
     */
    function updateChoicesState(field, isValid) {
        const choicesElement = getChoicesElement(field);

        if (!choicesElement) {
            return;
        }

        choicesElement.classList.toggle(
            'is-invalid',
            !isValid
        );

        choicesElement.classList.toggle(
            'is-valid',
            isValid
        );
    }

    /**
     * Display an error for one non-radio field.
     *
     * @param {HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement} field
     * @param {string} message
     *
     * @returns {void}
     */
    function showError(field, message) {
        const errorElement = getErrorElement(field);

        field.classList.add('is-invalid');
        field.classList.remove('is-valid');

        field.setAttribute('aria-invalid', 'true');
        field.setAttribute(
            'aria-describedby',
            errorElement.id
        );

        errorElement.textContent = message;
        errorElement.classList.add('d-block');

        updateChoicesState(field, false);
    }

    /**
     * Clear an error from one non-radio field.
     *
     * @param {HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement} field
     *
     * @returns {void}
     */
    function clearError(field) {
        const errorElement = getErrorElement(field);

        field.classList.remove('is-invalid');
        field.classList.remove('is-valid');

        field.removeAttribute('aria-invalid');

        errorElement.textContent = '';
        errorElement.classList.remove('d-block');

        updateChoicesState(field, true);
    }

    /**
     * Validate one normal field.
     *
     * @param {HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement} field
     *
     * @returns {boolean}
     */
    function validateField(field) {
        if (field.checkValidity()) {
            clearError(field);

            return true;
        }

        showError(
            field,
            getErrorMessage(field)
        );

        return false;
    }

    /**
     * Return every radio in one named group.
     *
     * @param {HTMLFormElement} form
     * @param {string} name
     *
     * @returns {HTMLInputElement[]}
     */
    function getRadioGroup(form, name) {
        return Array.from(
            form.querySelectorAll(
                `input[type="radio"][name="${CSS.escape(name)}"]`
            )
        );
    }

    /**
     * Show one group-level error for a radio group.
     *
     * @param {HTMLInputElement[]} group
     * @param {string} message
     *
     * @returns {void}
     */
    function showRadioGroupError(group, message) {
        if (group.length === 0) {
            return;
        }

        const firstRadio = group[0];

        const errorElement = getErrorElement(firstRadio);

        group.forEach(function (radio) {
            radio.classList.add('is-invalid');
            radio.classList.remove('is-valid');

            radio.setAttribute(
                'aria-invalid',
                'true'
            );

            radio.setAttribute(
                'aria-describedby',
                errorElement.id
            );
        });

        errorElement.textContent = message;
        errorElement.classList.add('d-block');
    }

    /**
     * Clear the group-level error from a radio group.
     *
     * @param {HTMLInputElement[]} group
     *
     * @returns {void}
     */
    function clearRadioGroupError(group) {
        if (group.length === 0) {
            return;
        }

        const errorElement = getErrorElement(group[0]);

        group.forEach(function (radio) {
            radio.classList.remove('is-invalid');
            radio.classList.remove('is-valid');

            radio.removeAttribute('aria-invalid');
        });

        errorElement.textContent = '';
        errorElement.classList.remove('d-block');
    }

    /**
     * Validate a radio group as one logical field.
     *
     * @param {HTMLInputElement} field
     * @param {HTMLFormElement} form
     *
     * @returns {boolean}
     */
    function validateRadioGroup(field, form) {
        const group = getRadioGroup(
            form,
            field.name
        );

        const isRequired = group.some(function (radio) {
            return radio.required;
        });

        const isChecked = group.some(function (radio) {
            return radio.checked;
        });

        if (!isRequired || isChecked) {
            clearRadioGroupError(group);

            return true;
        }

        showRadioGroupError(
            group,
            field.dataset.errorRequired
            || 'Please select an option.'
        );

        return false;
    }

    /**
     * Focus the visual Choices control or the original field.
     *
     * @param {HTMLElement} field
     *
     * @returns {void}
     */
    function focusInvalidField(field) {
        if (
            field instanceof HTMLSelectElement
            && field.matches('select[data-choice]')
        ) {
            const choicesElement = getChoicesElement(field);

            const choiceInput = choicesElement
                ? choicesElement.querySelector(
                    '.choices__input, .choices__inner'
                )
                : null;

            if (choiceInput instanceof HTMLElement) {
                choiceInput.focus();

                return;
            }
        }

        field.focus();
    }

    /**
     * Initialize one form.
     *
     * @param {HTMLFormElement} form
     *
     * @returns {void}
     */
    function initializeForm(form) {
        if (form.dataset.validationInitialized === 'true') {
            return;
        }

        form.dataset.validationInitialized = 'true';

        const fields = Array.from(
            form.querySelectorAll(FIELD_SELECTOR)
        );

        /*
        * Preserve server-rendered validation errors after a redirect.
        *
        * PHP marks invalid controls with is-invalid and renders the
        * authoritative server message inside data-validation-error.
        *
        * Choices.js renders a separate visual control, therefore its
        * wrapper also needs to receive the invalid state.
        */
        fields.forEach(function (field) {
            if (
                !field.classList.contains(
                    'is-invalid'
                )
            ) {
                return;
            }

            const errorElement =
                getErrorElement(field);

            const serverMessage =
                errorElement.textContent
                    .trim();

            if (serverMessage === '') {
                return;
            }

            field.setAttribute(
                'aria-invalid',
                'true'
            );

            field.setAttribute(
                'aria-describedby',
                errorElement.id
            );

            errorElement.classList.add(
                'd-block'
            );

            updateChoicesState(
                field,
                false
            );
        });

        form.addEventListener('submit', function (event) {
            let isFormValid = true;
            let firstInvalidField = null;

            const processedRadioGroups = new Set();

            fields.forEach(function (field) {
                let isFieldValid = true;

                if (
                    field instanceof HTMLInputElement
                    && field.type === 'radio'
                ) {
                    if (
                        processedRadioGroups.has(field.name)
                    ) {
                        return;
                    }

                    processedRadioGroups.add(field.name);

                    isFieldValid = validateRadioGroup(
                        field,
                        form
                    );
                } else {
                    isFieldValid = validateField(field);
                }

                if (!isFieldValid) {
                    isFormValid = false;

                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                }
            });

            if (!isFormValid) {
                event.preventDefault();

                if (firstInvalidField) {
                    focusInvalidField(
                        firstInvalidField
                    );
                }
            }
        });

        fields.forEach(function (field) {
            /**
             * Radio buttons are validated at group level.
             */
            if (
                field instanceof HTMLInputElement
                && field.type === 'radio'
            ) {
                field.addEventListener(
                    'change',
                    function () {
                        validateRadioGroup(
                            field,
                            form
                        );
                    }
                );

                return;
            }

            /**
 * File controls open an operating-system file dialog.
 *
 * Opening that dialog can cause the input to blur before the user has
 * selected or cancelled a file. Therefore, file inputs must not be
 * validated on blur.
 *
 * They are validated after change and during final form submission.
 */
            if (
                field instanceof HTMLInputElement
                && field.type === 'file'
            ) {
                /*
                 * Validate after a native file selection.
                 *
                 * Page-specific scripts may perform additional checks such as
                 * file size, image dimensions or business-specific restrictions.
                 */
                field.addEventListener(
                    'change',
                    function () {
                        validateField(
                            field
                        );
                    }
                );

                /*
                 * Allow page-specific code to request validation again after it
                 * has applied a custom validity message.
                 *
                 * Example:
                 *
                 * field.setCustomValidity('Custom validation message.');
                 *
                 * field.dispatchEvent(
                 *     new CustomEvent(
                 *         'app:validate-field',
                 *         {
                 *             bubbles: false,
                 *         }
                 *     )
                 * );
                 */
                field.addEventListener(
                    'app:validate-field',
                    function () {
                        validateField(
                            field
                        );
                    }
                );

                return;
            }

            /**
             * Normal text, date, number and textarea controls are validated when
             * the user leaves the field.
             */
            field.addEventListener(
                'blur',
                function () {
                    validateField(
                        field
                    );
                }
            );

            field.addEventListener(
                'input',
                function () {
                    /*
                     * Avoid displaying an error while the user initially types.
                     * Revalidate live only after the field has already failed.
                     */
                    if (
                        field.classList.contains(
                            'is-invalid'
                        )
                    ) {
                        validateField(
                            field
                        );
                    }
                }
            );

            field.addEventListener(
                'change',
                function () {
                    if (
                        field.classList.contains(
                            'is-invalid'
                        )
                        || field.tagName === 'SELECT'
                    ) {
                        validateField(
                            field
                        );
                    }
                }
            );
        });
    }

    /**
     * Initialize all validation-enabled forms inside a container.
     *
     * @param {Document|HTMLElement} container
     *
     * @returns {void}
     */
    function init(container = document) {
        container
            .querySelectorAll(FORM_SELECTOR)
            .forEach(initializeForm);
    }

    window.FormValidator = Object.freeze({
        init
    });

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            init(document);
        }
    );
})(window, document);