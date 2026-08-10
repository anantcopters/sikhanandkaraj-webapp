/**
 * Member Search page behaviour.
 *
 * Search intentionally reuses the Partner Preference visual multi-select,
 * but Search has different "Any" semantics:
 *
 * Partner Preference:
 *     Any = every value selected.
 *
 * Search:
 *     Any = no restriction = zero submitted values.
 *
 * This keeps Search URLs compact and correctly includes profiles whose
 * optional master-backed fields may be NULL.
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
             * Return currently selected values.
             *
             * Choices keeps the original select synchronized.
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
             * Recreate the existing project Choices instance.
             *
             * @param {HTMLSelectElement} select
             *
             * @returns {void}
             */
            function refreshChoice(select) {
                if (
                    !window.SelectChoice
                    || typeof window.SelectChoice.refresh
                    !== 'function'
                ) {
                    return;
                }

                window.SelectChoice.refresh(
                    select
                );
            }

            /**
 * Clear concrete Search selections.
 *
 * An empty selection represents "Any / no restriction".
 *
 * @param {HTMLSelectElement} select
 *
 * @returns {void}
 */
            function clearSelection(
                select
            ) {
                Array.from(
                    select.options
                ).forEach(
                    function (option) {
                        option.selected =
                            false;
                    }
                );

                /*
                 * Rebuild the existing Choices instance so its visible items match the
                 * underlying select.
                 */
                if (
                    window.SelectChoice
                    && typeof window.SelectChoice.refresh
                    === 'function'
                ) {
                    window.SelectChoice.refresh(
                        select
                    );
                }

                /*
                 * Do not disable the select.
                 *
                 * Members must immediately be able to reopen it after choosing Any.
                 */
            }

            /**
 * Synchronize Search "Any" presentation.
 *
 * Search differs from Partner Preference:
 *
 * - Any means no filtering;
 * - the Choices control must always remain visible;
 * - selecting a concrete value automatically clears Any;
 * - clicking Any clears concrete selections.
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

                /*
                 * Search must never enter the Partner Preference compact Any state.
                 *
                 * That state visually hides/covers the Choices control and therefore
                 * prevents members from opening the Search dropdown.
                 */
                container.classList.remove(
                    'is-any'
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
                        'true'
                    );
                }
            }

            /**
 * Synchronize one Search Any checkbox.
 *
 * No selected values means no filtering, therefore Any is checked.
 *
 * Any remains clickable and never disables the associated select.
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
                const selected =
                    selectedValues(
                        select
                    );

                checkbox.indeterminate =
                    false;

                /*
                 * Empty selection = no Search restriction.
                 */
                checkbox.checked =
                    !checkbox.disabled
                    && selected.length === 0;

                synchronizeAnyPresentation(
                    checkbox,
                    select
                );
            }

            /**
             * Enable/disable an Any control.
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

                synchronizeAny(
                    checkbox,
                    select
                );
            }

            /**
             * Initialize Search Any controls.
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

                        /*
                         * Empty Search criteria should initially appear as Any.
                         */
                        synchronizeAny(
                            element,
                            select
                        );

                        /*
                         * Checking Any clears that Search criterion.
                         */
                        element.addEventListener(
                            'change',
                            function () {
                                if (
                                    element.checked
                                ) {
                                    clearSelection(
                                        select
                                    );
                                }

                                synchronizeAny(
                                    element,
                                    select
                                );
                            }
                        );

                        /*
                         * Selecting any concrete value automatically clears Any.
                         */
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
             * Replace active City options while retaining selections that are
             * still valid for the newly selected States.
             *
             * @param {HTMLSelectElement} citySelect
             * @param {Array<Object>} rows
             * @param {string[]} previousSelections
             *
             * @returns {void}
             */
            function replaceCities(
                citySelect,
                rows,
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
                        rows
                            .map(
                                function (city) {
                                    return String(
                                        city.id
                                        ?? ''
                                    );
                                }
                            )
                            .filter(Boolean)
                    );

                const preservedSelections =
                    previousSelections.filter(
                        function (value) {
                            return validValues.has(
                                String(value)
                            );
                        }
                    );

                rows.forEach(
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
                    rows.length > 0;

                citySelect.disabled =
                    !hasCities;

                /*
                 * Recreate Choices only after new options have been installed.
                 */
                if (
                    window.SelectChoice
                    && typeof window.SelectChoice.create
                    === 'function'
                ) {
                    window.SelectChoice.create(
                        citySelect
                    );
                }

                setAnyDisabled(
                    citySelect,
                    !hasCities
                );

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
             * Initialize multi-state dependent City selection.
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

                const cityUrlInput =
                    document.getElementById(
                        'searchCitiesUrl'
                    );

                if (
                    !(stateSelect
                        instanceof HTMLSelectElement)
                    || !(citySelect
                        instanceof HTMLSelectElement)
                    || !(cityUrlInput
                        instanceof HTMLInputElement)
                ) {
                    return;
                }

                const baseUrl =
                    cityUrlInput.value.trim();

                if (baseUrl === '') {
                    return;
                }

                let requestController =
                    null;

                /**
                 * Load active Cities for selected States.
                 *
                 * @returns {Promise<void>}
                 */
                async function loadCities() {
                    const stateIds =
                        selectedValues(
                            stateSelect
                        );

                    const previousCities =
                        selectedValues(
                            citySelect
                        );

                    /*
                     * No State restriction means City cannot be meaningfully
                     * restricted either.
                     */
                    if (
                        stateIds.length === 0
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

                    stateIds.forEach(
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
                                baseUrl
                                + '?'
                                + query.toString(),
                                {
                                    method:
                                        'GET',

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
                            && error.name === 'AbortError'
                        ) {
                            return;
                        }

                        /*
                         * Keep browser diagnostics generic and do not expose
                         * application/database details to the member.
                         */
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
             * Initialize reusable Search controls.
             */
            initializeAnyControls();

            /*
             * Initialize State → City dependency separately.
             */
            initializeStateCity();
        }
    );
})(window, document);