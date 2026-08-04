'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById(
        'partnerPreferenceAdditionalForm'
    );

    const submitButton = document.getElementById(
        'saveAdditionalPreferenceButton'
    );

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    /**
     * Return all selectable option values.
     *
     * @param {HTMLSelectElement} select
     *
     * @returns {string[]}
     */
    function optionValues(select) {
        return Array.from(select.options)
            .filter((option) => {
                return (
                    option.value !== ''
                    && !option.disabled
                );
            })
            .map((option) => {
                return option.value;
            });
    }

    /**
     * Return currently selected values.
     *
     * @param {HTMLSelectElement} select
     *
     * @returns {string[]}
     */
    function selectedValues(select) {
        return Array.from(
            select.selectedOptions
        )
            .map((option) => {
                return option.value;
            })
            .filter((value) => {
                return value !== '';
            });
    }

    /**
     * Update Choices.js or native selection values.
     *
     * @param {HTMLSelectElement} select
     * @param {string[]} values
     *
     * @returns {void}
     */
    function setSelectedValues(select, values) {
        const normalized = new Set(
            values.map(String)
        );

        Array.from(select.options)
            .forEach((option) => {
                option.selected =
                    normalized.has(
                        option.value
                    );
            });

        /*
         * The project SelectChoice wrapper may expose a
         * refresh/recreate method. Recreate is used only when
         * it is available; native selection remains the fallback.
         */
        if (
            window.SelectChoice
            && typeof window.SelectChoice.destroy
            === 'function'
            && typeof window.SelectChoice.create
            === 'function'
        ) {
            window.SelectChoice.destroy(select);

            window.SelectChoice.create(select);
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
     * Synchronize one Select All checkbox.
     *
     * @param {HTMLInputElement} checkbox
     * @param {HTMLSelectElement} select
     *
     * @returns {void}
     */
    function synchronizeSelectAll(
        checkbox,
        select
    ) {
        const allValues = optionValues(select);

        const selected = selectedValues(select);

        checkbox.checked =
            allValues.length > 0
            && selected.length
            === allValues.length;

        checkbox.indeterminate =
            selected.length > 0
            && selected.length
            < allValues.length;
    }

    /**
     * Enable or disable the Select All control attached
     * to a multi-select field.
     *
     * @param {HTMLSelectElement} select
     * @param {boolean} disabled
     *
     * @returns {void}
     */
    function setSelectAllDisabled(
        select,
        disabled
    ) {
        const checkbox = form.querySelector(
            `[data-select-all-target="${select.id}"]`
        );

        if (
            !(
                checkbox
                instanceof HTMLInputElement
            )
        ) {
            return;
        }

        checkbox.disabled = disabled;

        if (disabled) {
            checkbox.checked = false;
            checkbox.indeterminate = false;

            return;
        }

        synchronizeSelectAll(
            checkbox,
            select
        );
    }

    /**
     * Initialize generic Select All controls.
     *
     * @returns {void}
     */
    function initializeSelectAll() {
        const checkboxes = form.querySelectorAll(
            '[data-select-all-target]'
        );

        checkboxes.forEach((element) => {
            if (
                !(element
                    instanceof HTMLInputElement)
            ) {
                return;
            }

            const targetId = String(
                element.dataset
                    .selectAllTarget || ''
            );

            const select =
                document.getElementById(
                    targetId
                );

            if (
                !(
                    select
                    instanceof HTMLSelectElement
                )
            ) {
                return;
            }

            synchronizeSelectAll(
                element,
                select
            );

            element.addEventListener(
                'change',
                () => {
                    setSelectedValues(
                        select,
                        element.checked
                            ? optionValues(select)
                            : []
                    );
                }
            );

            select.addEventListener(
                'change',
                () => {
                    synchronizeSelectAll(
                        element,
                        select
                    );
                }
            );
        });
    }

    /**
 * Replace city options while preserving selections that
 * still belong to one of the currently selected states.
 *
 * @param {HTMLSelectElement} citySelect
 * @param {Array<{
 *     value:string,
 *     label:string,
 *     stateId:string
 * }>} cities
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

        const validValues = new Set(
            cities.map((city) => {
                return String(city.value);
            })
        );

        const preservedSelections =
            previousSelections.filter(
                (value) => {
                    return validValues.has(
                        String(value)
                    );
                }
            );

        cities.forEach((city) => {
            const option =
                document.createElement(
                    'option'
                );

            option.value =
                String(city.value);

            option.textContent =
                String(city.label);

            option.dataset.stateId =
                String(city.stateId);

            option.selected =
                preservedSelections.includes(
                    String(city.value)
                );

            citySelect.appendChild(option);
        });

        const hasCities =
            cities.length > 0;

        citySelect.disabled =
            !hasCities;

        setSelectAllDisabled(
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
     * Initialize multi-state dependent city selection.
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
                'partnerCitiesUrl'
            );

        if (
            !(
                stateSelect
                instanceof HTMLSelectElement
            )
            || !(
                citySelect
                instanceof HTMLSelectElement
            )
            || !(
                cityUrlInput
                instanceof HTMLInputElement
            )
        ) {
            return;
        }

        setSelectAllDisabled(
            citySelect,
            citySelect.disabled
            || citySelect.options.length === 0
        );

        const baseUrl =
            cityUrlInput.value.trim();

        if (baseUrl === '') {
            return;
        }

        let requestController = null;

        /**
         * Load cities for every selected state.
         *
         * @returns {Promise<void>}
         */
        async function loadCities() {
            const stateIds =
                selectedValues(stateSelect);

            const previousCities =
                selectedValues(citySelect);

            if (stateIds.length === 0) {
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

            citySelect.disabled = true;

            const query = new URLSearchParams({
                state_ids:
                    stateIds.join(',')
            });

            try {
                const response = await fetch(
                    `${baseUrl}?${query.toString()}`,
                    {
                        method: 'GET',

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

                    Array.isArray(payload.data)
                        ? payload.data
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

                console.error(error);

                replaceCities(
                    citySelect,
                    [],
                    []
                );
            }
        }

        stateSelect.addEventListener(
            'change',
            () => {
                loadCities();
            }
        );
    }

    /**
     * Activate the existing project loader.
     *
     * @returns {void}
     */
    function showSavingState() {
        if (
            !(
                submitButton
                instanceof HTMLButtonElement
            )
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
    }

    form.addEventListener(
        'submit',
        (event) => {
            if (
                event.defaultPrevented
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

                showSavingState();
            }, 0);
        }
    );

    initializeSelectAll();

    initializeStateCity();
});