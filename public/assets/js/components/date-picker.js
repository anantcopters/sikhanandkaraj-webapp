/**
 * Reusable Flatpickr date-picker manager.
 *
 * The original input remains responsible for:
 *
 * - field name
 * - submitted Y-m-d value
 * - native/custom validity
 *
 * Flatpickr's alternate input is responsible only for the visible,
 * human-friendly date.
 */
(function (window, document) {
    'use strict';

    const DEFAULT_SELECTOR =
        'input[data-date-picker]';

    /**
     * Flatpickr instances stored against their original inputs.
     *
     * @type {WeakMap<HTMLInputElement, Object>}
     */
    const instances =
        new WeakMap();

    /**
     * Convert a data-attribute value to Boolean.
     *
     * @param {string|null|undefined} value
     * @param {boolean} defaultValue
     *
     * @returns {boolean}
     */
    function toBoolean(
        value,
        defaultValue
    ) {
        if (
            value === null
            || value === undefined
            || value === ''
        ) {
            return defaultValue;
        }

        return value !== 'false'
            && value !== '0';
    }

    /**
     * Normalize optional date configuration.
     *
     * @param {string|null|undefined} value
     *
     * @returns {string|null}
     */
    function normalizeDateOption(value) {
        const normalizedValue =
            String(value ?? '').trim();

        return normalizedValue !== ''
            ? normalizedValue
            : null;
    }

    /**
     * Copy validation state from the submitted input to Flatpickr's
     * visible alternate input.
     *
     * Bootstrap's default validation background icon is intentionally
     * removed through CSS for date-picker alternate inputs.
     *
     * @param {HTMLInputElement} element
     * @param {Object} instance
     *
     * @returns {void}
     */
    function synchronizeVisualState(
        element,
        instance
    ) {
        const alternateInput =
            instance.altInput;

        if (
            !(
                alternateInput
                instanceof HTMLInputElement
            )
        ) {
            return;
        }

        const isInvalid =
            element.classList.contains(
                'is-invalid'
            )
            || element.getAttribute(
                'aria-invalid'
            ) === 'true'
            || !element.validity.valid;

        const isValid =
            element.classList.contains(
                'is-valid'
            )
            && !isInvalid;

        alternateInput.classList.toggle(
            'is-invalid',
            isInvalid
        );

        alternateInput.classList.toggle(
            'is-valid',
            isValid
        );

        if (isInvalid) {
            alternateInput.setAttribute(
                'aria-invalid',
                'true'
            );
        } else {
            alternateInput.removeAttribute(
                'aria-invalid'
            );
        }
    }

    /**
     * Decorate Flatpickr's generated alternate input.
     *
     * @param {HTMLInputElement} element
     * @param {Object} instance
     *
     * @returns {void}
     */
    function configureAlternateInput(
        element,
        instance
    ) {
        const alternateInput =
            instance.altInput;

        if (
            !(
                alternateInput
                instanceof HTMLInputElement
            )
        ) {
            return;
        }

        /*
         * Flatpickr copies the original classes. Remove validation
         * classes first; they are reapplied through synchronized state.
         */
        alternateInput.classList.remove(
            'is-invalid',
            'is-valid'
        );

        alternateInput.classList.add(
            'date-picker__display'
        );

        alternateInput.setAttribute(
            'autocomplete',
            'off'
        );

        alternateInput.setAttribute(
            'inputmode',
            'none'
        );

        alternateInput.setAttribute(
            'aria-describedby',
            element.getAttribute(
                'aria-describedby'
            ) ?? ''
        );

        const placeholder =
            element.getAttribute(
                'placeholder'
            );

        if (placeholder) {
            alternateInput.setAttribute(
                'placeholder',
                placeholder
            );
        }

        synchronizeVisualState(
            element,
            instance
        );
    }

    /**
     * Build Flatpickr configuration.
     *
     * @param {HTMLInputElement} element
     *
     * @returns {Object}
     */
    function configurationFor(element) {
        const dateFormat =
            element.dataset.dateFormat
            || 'Y-m-d';

        const alternateFormat =
            element.dataset.altFormat
            || 'd M, Y';

        const allowInput =
            toBoolean(
                element.dataset
                    .dateAllowInput,
                true
            );

        const defaultDate =
            normalizeDateOption(
                element.value
                || element.dataset
                    .dateDefault
            );

        return {
            dateFormat,
            altInput: true,
            altFormat:
                alternateFormat,
            allowInput,
            clickOpens: true,
            disableMobile: true,
            monthSelectorType:
                'dropdown',

            minDate:
                normalizeDateOption(
                    element.dataset
                        .dateMin
                ),

            maxDate:
                normalizeDateOption(
                    element.dataset
                        .dateMax
                ),

            defaultDate,

            onReady: function (
                selectedDates,
                dateString,
                instance
            ) {
                configureAlternateInput(
                    element,
                    instance
                );
            },

            onChange: function (
                selectedDates,
                dateString,
                instance
            ) {
                /*
                 * Flatpickr has already updated the submitted input.
                 */
                element.setCustomValidity(
                    ''
                );

                element.dispatchEvent(
                    new Event(
                        'change',
                        {
                            bubbles: true
                        }
                    )
                );

                synchronizeVisualState(
                    element,
                    instance
                );
            },

            onClose: function (
                selectedDates,
                dateString,
                instance
            ) {
                element.dispatchEvent(
                    new Event(
                        'blur',
                        {
                            bubbles: true
                        }
                    )
                );

                synchronizeVisualState(
                    element,
                    instance
                );
            }
        };
    }

    /**
     * Initialize one date picker.
     *
     * @param {HTMLInputElement} element
     *
     * @returns {Object|null}
     */
    function create(element) {
        if (
            !(
                element
                instanceof HTMLInputElement
            )
            || !element.matches(
                DEFAULT_SELECTOR
            )
        ) {
            return null;
        }

        if (instances.has(element)) {
            return instances.get(
                element
            );
        }

        if (
            typeof window.flatpickr
            !== 'function'
        ) {
            console.error(
                'Flatpickr is not loaded. '
                + 'Load flatpickr.min.js before date-picker.js.'
            );

            return null;
        }

        const instance =
            window.flatpickr(
                element,
                configurationFor(
                    element
                )
            );

        instances.set(
            element,
            instance
        );

        element.dataset
            .datePickerInitialized =
            'true';

        return instance;
    }

    /**
     * Initialize all declarative date pickers.
     *
     * @param {Document|HTMLElement} container
     *
     * @returns {void}
     */
    function init(container) {
        const root =
            container || document;

        if (
            root
            instanceof HTMLInputElement
            && root.matches(
                DEFAULT_SELECTOR
            )
        ) {
            create(root);

            return;
        }

        root.querySelectorAll(
            DEFAULT_SELECTOR
        ).forEach(create);
    }

    /**
     * Destroy one date picker.
     *
     * @param {HTMLInputElement} element
     *
     * @returns {void}
     */
    function destroy(element) {
        const instance =
            instances.get(element);

        if (!instance) {
            return;
        }

        instance.destroy();

        instances.delete(element);

        delete element.dataset
            .datePickerInitialized;
    }

    /**
     * Recreate a date picker.
     *
     * @param {HTMLInputElement} element
     *
     * @returns {Object|null}
     */
    function refresh(element) {
        destroy(element);

        return create(element);
    }

    /**
     * Return the Flatpickr instance.
     *
     * @param {HTMLInputElement} element
     *
     * @returns {Object|null}
     */
    function getInstance(element) {
        return instances.get(element)
            ?? null;
    }

    /**
     * Refresh the visible validation state.
     *
     * Call this after page-specific validation changes the original
     * input's is-invalid or aria-invalid state.
     *
     * @param {HTMLInputElement} element
     *
     * @returns {void}
     */
    function refreshValidation(element) {
        const instance =
            instances.get(element);

        if (!instance) {
            return;
        }

        synchronizeVisualState(
            element,
            instance
        );
    }

    window.DatePicker =
        Object.freeze({
            init,
            create,
            destroy,
            refresh,
            getInstance,
            refreshValidation
        });

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            init(document);
        }
    );
})(window, document);