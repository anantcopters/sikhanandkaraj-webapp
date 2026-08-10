/**
 * Member Search page.
 *
 * Reuses the Partner Preference multi-select UX:
 *
 * - Choices.js;
 * - "Any" selection;
 * - dependent State → City loading;
 * - preserved valid City selections.
 *
 * Search business rules remain server-side.
 */
(function (window, document) {
    'use strict';

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            const form =
                document.getElementById(
                    'memberSearchForm'
                );

            if (
                !(form instanceof HTMLFormElement)
            ) {
                return;
            }

            /**
             * Return selectable option values.
             *
             * @param {HTMLSelectElement} select
             *
             * @returns {string[]}
             */
            function optionValues(select) {
                return Array.from(
                    select.options
                )
                    .filter(
                        function (option) {
                            return (
                                option.value !== ''
                                && !option.disabled
                            );
                        }
                    )
                    .map(
                        function (option) {
                            return option.value;
                        }
                    );
            }

            /**
             * Return selected values.
             *
             * @param {HTMLSelectElement} select
             *
             * @returns {string[]}
             */
            function selectedValues(select) {
                return Array.from(
                    select.selectedOptions
                )
                    .map(
                        function (option) {
                            return option.value;
                        }
                    )
                    .filter(Boolean);
            }

            /**
             * Synchronize compact Any presentation.
             *
             * @param {HTMLInputElement} checkbox
             * @param {HTMLSelectElement} select
             *
             * @returns {void}
             */
            function synchronizeAnyPresentation(
                checkbox,
                select
            ) {
                const container =
                    select.closest(
                        '[data-preference-multi-select]'
                    );

                if (
                    !(container instanceof HTMLElement)
                ) {
                    return;
                }

                const isAny =
                    checkbox.checked
                    && !checkbox.indeterminate
                    && !checkbox.disabled;

                container.classList.toggle(
                    'is-any',
                    isAny
                );

                const anyValue =
                    container.querySelector(
                        '.partner-preference-any-value'
                    );

                if (
                    anyValue instanceof HTMLElement
                ) {
                    anyValue.setAttribute(
                        'aria-hidden',
                        isAny
                            ? 'false'
                            : 'true'
                    );
                }
            }

            /**
             * Rebuild selection and its existing Choices instance.
             *
             * @param {HTMLSelectElement} select
             * @param {string[]} values
             *
             * @returns {void}
             */
            function setSelectedValues(
                select,
                values
            ) {
                const selected =
                    new Set(
                        values.map(String)
                    );

                Array.from(
                    select.options
                ).forEach(
                    function (option) {
                        option.selected =
                            selected.has(
                                option.value
                            );
                    }
                );

                if (
                    window.SelectChoice
                    && typeof window.SelectChoice.destroy
                    === 'function'
                    && typeof window.SelectChoice.create
                    === 'function'
                ) {
                    window.SelectChoice.destroy(
                        select
                    );

                    window.SelectChoice.create(
                        select
                    );
                }

                select.dispatchEvent(
                    new Event(
                        'change',
                        {
                            bubbles: true
                        }
                    )
                );
            }

            /**
             * Synchronize Any checkbox state.
             *
             * @param {HTMLInputElement} checkbox
             * @param {HTMLSelectElement} select
             *
             * @returns {void}
             */
            function synchronizeAny(
                checkbox,
                select
            ) {
                const all =
                    optionValues(
                        select
                    );

                const selected =
                    selectedValues(
                        select
                    );

                checkbox.checked =
                    all.length > 0
                    && selected.length
                    === all.length;

                checkbox.indeterminate =
                    selected.length > 0
                    && selected.length
                    < all.length;

                synchronizeAnyPresentation(
                    checkbox,
                    select
                );
            }

            /**
             * Enable/disable one Any checkbox.
             *
             * @param {HTMLSelectElement} select
             * @param {boolean} disabled
             *
             * @returns {void}
             */
            function setAnyDisabled(
                select,
                disabled
            ) {
                const checkbox =
                    form.querySelector(
                        `[data-select-all-target="${select.id}"]`
                    );

                if (
                    !(checkbox instanceof HTMLInputElement)
                ) {
                    return;
                }

                checkbox.disabled =
                    disabled;

                if (disabled) {
                    checkbox.checked =
                        false;

                    checkbox.indeterminate =
                        false;

                    synchronizeAnyPresentation(
                        checkbox,
                        select
                    );

                    return;
                }

                synchronizeAny(
                    checkbox,
                    select
                );
            }

            /**
             * Initialize all Search "Any" controls.
             *
             * @returns {void}
             */
            function initializeAnyControls() {
                form.querySelectorAll(
                    '[data-select-all-target]'
                ).forEach(
                    function (element) {
                        if (
                            !(element
                                instanceof HTMLInputElement)
                        ) {
                            return;
                        }

                        const targetId =
                            String(
                                element.dataset
                                    .selectAllTarget
                                || ''
                            );

                        const select =
                            document.getElementById(
                                targetId
                            );

                        if (
                            !(select
                                instanceof HTMLSelectElement)
                        ) {
                            return;
                        }

                        synchronizeAny(
                            element,
                            select
                        );

                        element.addEventListener(
                            'change',
                            function () {
                                setSelectedValues(
                                    select,
                                    element.checked
                                        ? optionValues(
                                            select
                                        )
                                        : []
                                );
                            }
                        );

                        select.addEventListener(
                            'change',
                            function () {
                                synchronizeAny(
                                    element,
                                    select
                                );
                            }
                        );
                    }
                );
            }

            /**
             * Replace City master options.
             *
             * @param {HTMLSelectElement} citySelect
             * @param {Array<Object>} cities
             * @param {string[]} previousSelections
             *
             * @returns {void}
             */
            function replaceCities(
                citySelect,
                cities,
                previousSelections
            ) {
                if (
                    window.SelectChoice
                    && typeof window.SelectChoice.destroy
                    === 'function'
                ) {
                    window.SelectChoice.destroy(
                        citySelect
                    );
                }

                citySelect.replaceChildren();

                const validValues =
                    new Set(
                        cities.map(
                            function (city) {
                                return String(
                                    city.id
                                    ?? ''
                                );
                            }
                        )
                    );

                const preservedSelections =
                    previousSelections.filter(
                        function (value) {
                            return validValues.has(
                                String(value)
                            );
                        }
                    );

                cities.forEach(
                    function (city) {
                        const id =
                            String(
                                city.id
                                ?? ''
                            );

                        const name =
                            String(
                                city.name
                                ?? ''
                            );

                        if (
                            id === ''
                            || name === ''
                        ) {
                            return;
                        }

                        const option =
                            document.createElement(
                                'option'
                            );

                        option.value =
                            id;

                        option.textContent =
                            name;

                        option.selected =
                            preservedSelections.includes(
                                id
                            );

                        citySelect.appendChild(
                            option
                        );
                    }
                );

                const hasCities =
                    cities.length > 0;

                citySelect.disabled =
                    !hasCities;

                setAnyDisabled(
                    citySelect,
                    !hasCities
                );

                if (
                    window.SelectChoice
                    && typeof window.SelectChoice.create
                    === 'function'
                ) {
                    window.SelectChoice.create(
                        citySelect
                    );
                }

                citySelect.dispatchEvent(
                    new Event(
                        'change',
                        {
                            bubbles: true
                        }
                    )
                );
            }

            /**
             * Initialize dependent State → City selection.
             *
             * @returns {void}
             */
            function initializeStateCity() {
                const stateSelect =
                    document.getElementById(
                        'stateIds'
                    );

                const citySelect =
                    document.getElementById(
                        'cityIds'
                    );

                const cityUrl =
                    document.getElementById(
                        'searchCitiesUrl'
                    );

                if (
                    !(stateSelect
                        instanceof HTMLSelectElement)
                    || !(citySelect
                        instanceof HTMLSelectElement)
                    || !(cityUrl
                        instanceof HTMLInputElement)
                ) {
                    return;
                }

                let requestController =
                    null;

                /**
                 * Reload Cities for selected States.
                 *
                 * @returns {Promise<void>}
                 */
                async function loadCities() {
                    const states =
                        selectedValues(
                            stateSelect
                        );

                    const previousCities =
                        selectedValues(
                            citySelect
                        );

                    if (
                        states.length === 0
                    ) {
                        replaceCities(
                            citySelect,
                            [],
                            []
                        );

                        return;
                    }

                    if (
                        requestController
                        instanceof AbortController
                    ) {
                        requestController.abort();
                    }

                    requestController =
                        new AbortController();

                    const query =
                        new URLSearchParams();

                    states.forEach(
                        function (stateId) {
                            query.append(
                                'state_ids[]',
                                stateId
                            );
                        }
                    );

                    try {
                        const response =
                            await window.fetch(
                                cityUrl.value
                                + '?'
                                + query.toString(),
                                {
                                    headers: {
                                        Accept:
                                            'application/json',

                                        'X-Requested-With':
                                            'XMLHttpRequest'
                                    },

                                    credentials:
                                        'same-origin',

                                    signal:
                                        requestController.signal
                                }
                            );

                        if (!response.ok) {
                            throw new Error(
                                'Unable to load cities.'
                            );
                        }

                        const payload =
                            await response.json();

                        replaceCities(
                            citySelect,

                            Array.isArray(
                                payload.cities
                            )
                                ? payload.cities
                                : [],

                            previousCities
                        );
                    } catch (error) {
                        if (
                            error instanceof DOMException
                            && error.name
                            === 'AbortError'
                        ) {
                            return;
                        }

                        console.error(
                            'Unable to load Search cities.'
                        );
                    }
                }

                stateSelect.addEventListener(
                    'change',
                    function () {
                        void loadCities();
                    }
                );
            }

            /*
             * Initialize generic Search multi-select behaviour first.
             */
            initializeAnyControls();

            /*
             * Then initialize the State → City dependency.
             */
            initializeStateCity();
        }
    );
})(window, document);