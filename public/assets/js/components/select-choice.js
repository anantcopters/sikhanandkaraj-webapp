/**
 * Global Choices.js select manager.
 *
 * Automatically enhances normal select elements while allowing individual
 * fields to override behaviour through data attributes.
 *
 * Usage:
 *
 * <select class="form-select">
 * <select class="form-select" data-choice-search="false">
 * <select class="form-select" data-choice-remove="true" multiple>
 * <select class="form-select" data-choice-ignore>
 */
(function (window, document) {
    'use strict';

    /**
     * Default selector.
     *
     * Every select is enhanced unless it explicitly contains
     * the data-choice-ignore attribute.
     */
    const DEFAULT_SELECTOR = 'select[data-choice]';

    /**
     * Store instances against their original select elements.
     *
     * WeakMap prevents detached DOM elements from being retained in memory.
     *
     * @type {WeakMap<HTMLSelectElement, Choices>}
     */
    const instances = new WeakMap();

    /**
     * Convert a data attribute string to a Boolean.
     *
     * @param {string|null} value
     * @param {boolean} defaultValue
     *
     * @returns {boolean}
     */
    function toBoolean(value, defaultValue) {
        if (value === null || value === undefined || value === '') {
            return defaultValue;
        }

        return value !== 'false' && value !== '0';
    }

    /**
     * Determine whether search should be enabled.
     *
     * Search is disabled by default for small lists because it avoids
     * rendering an unnecessary search field.
     *
     * It can be forced through:
     *
     * data-choice-search="true"
     *
     * @param {HTMLSelectElement} element
     *
     * @returns {boolean}
     */
    function shouldEnableSearch(element) {
        const configuredValue = element.dataset.choiceSearch;

        if (configuredValue !== undefined) {
            return toBoolean(configuredValue, true);
        }

        return element.options.length > 8;
    }

    /**
     * Build Choices.js configuration for one select element.
     *
     * @param {HTMLSelectElement} element
     *
     * @returns {Object}
     */
    function getConfiguration(element) {
        const isMultiple = element.multiple;

        return {
            allowHTML: false,
            shouldSort: false,
            searchEnabled: shouldEnableSearch(element),
            searchChoices: true,
            searchFloor: 1,
            searchResultLimit: 20,

            placeholder: true,
            placeholderValue:
                element.dataset.choicePlaceholder
                || element.getAttribute('placeholder')
                || null,

            searchPlaceholderValue:
                element.dataset.choiceSearchPlaceholder
                || 'Type to search',

            noResultsText:
                element.dataset.choiceNoResults
                || 'No results found',

            noChoicesText:
                element.dataset.choiceNoChoices
                || 'No options available',

            itemSelectText: '',

            removeItemButton:
                isMultiple
                && toBoolean(element.dataset.choiceRemove, true),

            duplicateItemsAllowed: false,

            /**
             * Keep the dropdown usable inside Bootstrap modals,
             * cards and responsive containers.
             */
            position: 'auto'
        };
    }

    /**
     * Enhance one select element.
     *
     * Repeated calls are safe because an existing element is not
     * initialized more than once.
     *
     * @param {HTMLSelectElement} element
     *
     * @returns {Choices|null}
     */
    function create(element) {
        if (!(element instanceof HTMLSelectElement)) {
            return null;
        }

        if (element.hasAttribute('data-choice-ignore')) {
            return null;
        }

        if (instances.has(element)) {
            return instances.get(element);
        }

        if (typeof window.Choices !== 'function') {
            console.error(
                'Choices.js is not loaded. Ensure choices.min.js is loaded '
                + 'before select-choice.js.'
            );

            return null;
        }

        const instance = new window.Choices(
            element,
            getConfiguration(element)
        );

        instances.set(element, instance);
        element.dataset.choiceInitialized = 'true';

        return instance;
    }

    /**
     * Initialize select elements inside a page or a specific container.
     *
     * This can safely be called after an AJAX response or modal load.
     *
     * @param {Document|HTMLElement} container
     *
     * @returns {void}
     */
    function init(container) {
        const root = container || document;

        if (
            root instanceof HTMLSelectElement
            && root.matches(DEFAULT_SELECTOR)
        ) {
            create(root);
            return;
        }

        root.querySelectorAll(DEFAULT_SELECTOR).forEach(create);
    }

    /**
     * Destroy one Choices instance and restore the original select.
     *
     * @param {HTMLSelectElement} element
     *
     * @returns {void}
     */
    function destroy(element) {
        const instance = instances.get(element);

        if (!instance) {
            return;
        }

        instance.destroy();
        instances.delete(element);

        delete element.dataset.choiceInitialized;
    }

    /**
     * Rebuild an enhanced select.
     *
     * Use this after replacing its options through JavaScript.
     *
     * @param {HTMLSelectElement} element
     *
     * @returns {Choices|null}
     */
    function refresh(element) {
        destroy(element);

        return create(element);
    }

    /**
     * Destroy every initialized select inside a container.
     *
     * Useful before removing dynamic modal or AJAX content.
     *
     * @param {Document|HTMLElement} container
     *
     * @returns {void}
     */
    function destroyAll(container) {
        const root = container || document;

        root.querySelectorAll(
            'select[data-choice-initialized="true"]'
        ).forEach(destroy);
    }

    /**
     * Public API for dynamic pages.
     */
    window.SelectChoice = Object.freeze({
        init,
        create,
        destroy,
        destroyAll,
        refresh
    });

    /**
     * Initialize all server-rendered select boxes.
     */
    document.addEventListener('DOMContentLoaded', function () {
        init(document);
    });
})(window, document);