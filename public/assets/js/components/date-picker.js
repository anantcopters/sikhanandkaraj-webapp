/**
 * Reusable Flatpickr date-picker manager.
 *
 * Usage:
 *
 * <input
 *     type="text"
 *     data-date-picker
 *     data-date-format="Y-m-d"
 *     data-alt-format="d M, Y"
 *     data-date-max="2026-01-01">
 *
 * Responsibilities:
 *
 * - Initialize date pickers declaratively.
 * - Keep the submitted value in Y-m-d format.
 * - Display a user-friendly alternate format.
 * - Support dynamic min/max dates.
 * - Expose create, destroy and refresh methods.
 */
(function (window, document) {
    'use strict';

    const DEFAULT_SELECTOR =
        'input[data-date-picker]';

    /**
     * Store Flatpickr instances without retaining removed elements.
     *
     * @type {WeakMap<HTMLInputElement, Object>}
     */
    const instances =
        new WeakMap();

    /**
     * Convert an attribute value to Boolean.
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
     * Return a configured date value or null.
     *
     * @param {string|undefined} value
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
     * Build Flatpickr configuration from data attributes.
     *
     * @param {HTMLInputElement} element
     *
     * @returns {Object}
     */
    function configurationFor(element) {
        const dateFormat =
            element.dataset.dateFormat
            || 'Y-m-d';

        const altFormat =
            element.dataset.altFormat
            || 'd M, Y';

        const allowInput =
            toBoolean(
                element.dataset.dateAllowInput,
                true
            );

        const defaultDate =
            normalizeDateOption(
                element.value
                || element.dataset.dateDefault
            );

        return {
            dateFormat,
            altInput: true,
            altFormat,
            allowInput,
            clickOpens: true,
            disableMobile: true,

            minDate: normalizeDateOption(
                element.dataset.dateMin
            ),

            maxDate: normalizeDateOption(
                element.dataset.dateMax
            ),

            defaultDate,

            /*
             * Keep native and custom validation systems informed.
             */
            onChange: function () {
                element.dispatchEvent(
                    new Event(
                        'change',
                        {
                            bubbles: true,
                        }
                    )
                );
            },

            onClose: function () {
                element.dispatchEvent(
                    new Event(
                        'blur',
                        {
                            bubbles: true,
                        }
                    )
                );
            }
        };
    }

    /**
     * Initialize one Flatpickr input.
     *
     * Repeated calls are safe.
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
        ) {
            return null;
        }

        if (
            !element.matches(
                DEFAULT_SELECTOR
            )
        ) {
            return null;
        }

        if (instances.has(element)) {
            return instances.get(element);
        }

        if (
            typeof window.flatpickr
            !== 'function'
        ) {
            console.error(
                'Flatpickr is not loaded. Ensure flatpickr.min.js '
                + 'is loaded before date-picker.js.'
            );

            return null;
        }

        const instance =
            window.flatpickr(
                element,
                configurationFor(element)
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
     * Initialize all date pickers inside a container.
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
     * Destroy one date-picker instance.
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
     * Rebuild one date picker after its data configuration changes.
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
     * Obtain the active Flatpickr instance.
     *
     * @param {HTMLInputElement} element
     *
     * @returns {Object|null}
     */
    function getInstance(element) {
        return instances.get(element)
            ?? null;
    }

    window.DatePicker =
        Object.freeze({
            init,
            create,
            destroy,
            refresh,
            getInstance
        });

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            init(document);
        }
    );
})(window, document);