/**
 * Member Search page behaviour.
 *
 * Responsibilities:
 *
 * 1. Client-side validation for Search field relationships.
 * 2. State → City dependent master loading.
 * 3. Choices.js refresh after City options change.
 *
 * Important:
 *
 * Choices.js hides the original <select>. Native browser validation such as
 * setCustomValidity() + reportValidity() must therefore not be used on these
 * selects because the browser cannot focus the hidden original control.
 *
 * Server-side Search validation remains authoritative.
 */
(function (window, document) {
    'use strict';

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            /*
             * ------------------------------------------------------------------
             * Local UI variables
             * ------------------------------------------------------------------
             */

            const form =
                document.getElementById(
                    'memberSearchForm'
                );

            const ageFrom =
                document.getElementById(
                    'ageFrom'
                );

            const ageTo =
                document.getElementById(
                    'ageTo'
                );

            const heightFrom =
                document.getElementById(
                    'heightFrom'
                );

            const heightTo =
                document.getElementById(
                    'heightTo'
                );

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

            const ageRangeError =
                document.getElementById(
                    'ageRangeError'
                );

            const heightRangeError =
                document.getElementById(
                    'heightRangeError'
                );

            const stateCityError =
                document.getElementById(
                    'stateCityError'
                );

            if (
                !(form instanceof HTMLFormElement)
            ) {
                return;
            }

            /*
             * ------------------------------------------------------------------
             * Generic helpers
             * ------------------------------------------------------------------
             */

            /**
             * Return one scalar select value as a number.
             *
             * @param {HTMLSelectElement|null} select
             *
             * @returns {number|null}
             */
            function numericSelectValue(
                select
            ) {
                if (
                    !(select
                        instanceof HTMLSelectElement)
                ) {
                    return null;
                }

                const value =
                    String(
                        select.value
                        || ''
                    ).trim();

                if (value === '') {
                    return null;
                }

                const number =
                    Number(value);

                return Number.isFinite(
                    number
                )
                    ? number
                    : null;
            }

            /**
             * Return selected values from one multi-select.
             *
             * Choices synchronizes its visible component with the original
             * select element, so selectedOptions remains authoritative.
             *
             * @param {HTMLSelectElement|null} select
             *
             * @returns {string[]}
             */
            function selectedValues(
                select
            ) {
                if (
                    !(select
                        instanceof HTMLSelectElement)
                ) {
                    return [];
                }

                return Array.from(
                    select.selectedOptions
                )
                    .map(
                        function (option) {
                            return String(
                                option.value
                                || ''
                            ).trim();
                        }
                    )
                    .filter(Boolean);
            }

            /**
             * Return the selected Height measurement in centimetres.
             *
             * @param {HTMLSelectElement|null} select
             *
             * @returns {number|null}
             */
            function selectedHeightCm(
                select
            ) {
                if (
                    !(select
                        instanceof HTMLSelectElement)
                ) {
                    return null;
                }

                const option =
                    select.options[
                    select.selectedIndex
                    ];

                if (
                    !(option
                        instanceof HTMLOptionElement)
                ) {
                    return null;
                }

                const value =
                    String(
                        option.dataset.heightCm
                        || ''
                    ).trim();

                if (value === '') {
                    return null;
                }

                const height =
                    Number(value);

                return Number.isFinite(
                    height
                )
                    ? height
                    : null;
            }

            /**
             * Return the visible Choices container for a select.
             *
             * @param {HTMLSelectElement|null} select
             *
             * @returns {HTMLElement|null}
             */
            function choiceContainer(
                select
            ) {
                if (
                    !(select
                        instanceof HTMLSelectElement)
                ) {
                    return null;
                }

                /*
                 * Choices inserts its visible .choices element immediately
                 * adjacent to the hidden original select.
                 */
                const sibling =
                    select.nextElementSibling;

                return sibling
                    instanceof HTMLElement
                    && sibling.classList.contains(
                        'choices'
                    )
                    ? sibling
                    : null;
            }

            /**
             * Apply/remove visible invalid state from one Choices control.
             *
             * @param {HTMLSelectElement|null} select
             * @param {boolean} invalid
             *
             * @returns {void}
             */
            function setChoiceInvalid(
                select,
                invalid
            ) {
                const container =
                    choiceContainer(
                        select
                    );

                if (!container) {
                    return;
                }

                const inner =
                    container.querySelector(
                        '.choices__inner'
                    );

                if (
                    !(inner instanceof HTMLElement)
                ) {
                    return;
                }

                /*
                 * Reuse Bootstrap's existing validation class.
                 * No new CSS class is introduced.
                 */
                inner.classList.toggle(
                    'border-danger',
                    invalid
                );
            }

            /**
             * Show one inline validation message.
             *
             * @param {HTMLElement|null} element
             * @param {string} message
             *
             * @returns {void}
             */
            function showError(
                element,
                message
            ) {
                if (
                    !(element
                        instanceof HTMLElement)
                ) {
                    return;
                }

                element.textContent =
                    message;

                element.hidden =
                    false;
            }

            /**
             * Clear one inline validation message.
             *
             * @param {HTMLElement|null} element
             *
             * @returns {void}
             */
            function clearError(
                element
            ) {
                if (
                    !(element
                        instanceof HTMLElement)
                ) {
                    return;
                }

                element.textContent =
                    '';

                element.hidden =
                    true;
            }

            /**
             * Focus the visible Choices control.
             *
             * @param {HTMLSelectElement|null} select
             *
             * @returns {void}
             */
            function focusChoice(
                select
            ) {
                const container =
                    choiceContainer(
                        select
                    );

                if (!container) {
                    return;
                }

                const focusable =
                    container.querySelector(
                        '.choices__inner, '
                        + '.choices__input'
                    );

                if (
                    focusable instanceof HTMLElement
                ) {
                    focusable.focus();
                }
            }

            /*
             * ------------------------------------------------------------------
             * Age range validation
             * ------------------------------------------------------------------
             */

            /**
             * Validate Age From / Age To.
             *
             * Either side may remain Any.
             *
             * @param {boolean} focusInvalid
             *
             * @returns {boolean}
             */
            function validateAgeRange(
                focusInvalid
            ) {
                clearError(
                    ageRangeError
                );

                setChoiceInvalid(
                    ageFrom,
                    false
                );

                setChoiceInvalid(
                    ageTo,
                    false
                );

                const minimumAge =
                    numericSelectValue(
                        ageFrom
                    );

                const maximumAge =
                    numericSelectValue(
                        ageTo
                    );

                if (
                    minimumAge === null
                    || maximumAge === null
                ) {
                    return true;
                }

                if (
                    minimumAge
                    > maximumAge
                ) {
                    showError(
                        ageRangeError,
                        'Age To must be greater than or equal to Age From.'
                    );

                    setChoiceInvalid(
                        ageFrom,
                        true
                    );

                    setChoiceInvalid(
                        ageTo,
                        true
                    );

                    if (focusInvalid) {
                        focusChoice(
                            ageTo
                        );
                    }

                    return false;
                }

                return true;
            }

            /*
             * ------------------------------------------------------------------
             * Height range validation
             * ------------------------------------------------------------------
             */

            /**
             * Validate Height From / Height To using actual centimetres.
             *
             * @param {boolean} focusInvalid
             *
             * @returns {boolean}
             */
            function validateHeightRange(
                focusInvalid
            ) {
                clearError(
                    heightRangeError
                );

                setChoiceInvalid(
                    heightFrom,
                    false
                );

                setChoiceInvalid(
                    heightTo,
                    false
                );

                const minimumHeight =
                    selectedHeightCm(
                        heightFrom
                    );

                const maximumHeight =
                    selectedHeightCm(
                        heightTo
                    );

                if (
                    minimumHeight === null
                    || maximumHeight === null
                ) {
                    return true;
                }

                if (
                    minimumHeight
                    > maximumHeight
                ) {
                    showError(
                        heightRangeError,
                        'Height To must be greater than or equal to Height From.'
                    );

                    setChoiceInvalid(
                        heightFrom,
                        true
                    );

                    setChoiceInvalid(
                        heightTo,
                        true
                    );

                    if (focusInvalid) {
                        focusChoice(
                            heightTo
                        );
                    }

                    return false;
                }

                return true;
            }

            /*
             * ------------------------------------------------------------------
             * State / City validation
             * ------------------------------------------------------------------
             */

            /**
             * Validate State / City relationship.
             *
             * @param {boolean} focusInvalid
             *
             * @returns {boolean}
             */
            function validateStateCity(
                focusInvalid
            ) {
                clearError(
                    stateCityError
                );

                setChoiceInvalid(
                    stateSelect,
                    false
                );

                setChoiceInvalid(
                    citySelect,
                    false
                );

                const stateIds =
                    selectedValues(
                        stateSelect
                    );

                const cityIds =
                    selectedValues(
                        citySelect
                    );

                if (
                    cityIds.length > 0
                    && stateIds.length === 0
                ) {
                    showError(
                        stateCityError,
                        'Please select State Living In before selecting City Living In.'
                    );

                    setChoiceInvalid(
                        stateSelect,
                        true
                    );

                    setChoiceInvalid(
                        citySelect,
                        true
                    );

                    if (focusInvalid) {
                        focusChoice(
                            stateSelect
                        );
                    }

                    return false;
                }

                return true;
            }

            /*
             * ------------------------------------------------------------------
             * Complete Search validation
             * ------------------------------------------------------------------
             */

            /**
             * Validate all Search relationships.
             *
             * The first invalid rule receives focus.
             *
             * @returns {boolean}
             */
            function validateSearch() {
                /*
                 * Validate without focusing first so all messages are updated.
                 */
                const ageValid =
                    validateAgeRange(
                        false
                    );

                const heightValid =
                    validateHeightRange(
                        false
                    );

                const stateCityValid =
                    validateStateCity(
                        false
                    );

                if (!ageValid) {
                    focusChoice(
                        ageTo
                    );

                    return false;
                }

                if (!heightValid) {
                    focusChoice(
                        heightTo
                    );

                    return false;
                }

                if (!stateCityValid) {
                    focusChoice(
                        stateSelect
                    );

                    return false;
                }

                return true;
            }

            /*
             * ------------------------------------------------------------------
             * Live validation
             * ------------------------------------------------------------------
             */

            if (
                ageFrom
                instanceof HTMLSelectElement
            ) {
                ageFrom.addEventListener(
                    'change',
                    function () {
                        validateAgeRange(
                            false
                        );
                    }
                );
            }

            if (
                ageTo
                instanceof HTMLSelectElement
            ) {
                ageTo.addEventListener(
                    'change',
                    function () {
                        validateAgeRange(
                            false
                        );
                    }
                );
            }

            if (
                heightFrom
                instanceof HTMLSelectElement
            ) {
                heightFrom.addEventListener(
                    'change',
                    function () {
                        validateHeightRange(
                            false
                        );
                    }
                );
            }

            if (
                heightTo
                instanceof HTMLSelectElement
            ) {
                heightTo.addEventListener(
                    'change',
                    function () {
                        validateHeightRange(
                            false
                        );
                    }
                );
            }

            /*
             * ------------------------------------------------------------------
             * State → City dynamic master
             * ------------------------------------------------------------------
             */

            if (
                stateSelect
                instanceof HTMLSelectElement
                && citySelect
                instanceof HTMLSelectElement
                && cityUrlInput
                instanceof HTMLInputElement
            ) {
                const cityBaseUrl =
                    String(
                        cityUrlInput.value
                        || ''
                    ).trim();

                let requestController =
                    null;

                /**
                 * Replace City options and recreate its Choices instance.
                 *
                 * @param {Array<Object>} rows
                 * @param {string[]} previousSelections
                 *
                 * @returns {void}
                 */
                function replaceCities(
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

                    const validCityIds =
                        new Set(
                            rows
                                .map(
                                    function (city) {
                                        return String(
                                            city.id
                                            ?? ''
                                        ).trim();
                                    }
                                )
                                .filter(Boolean)
                        );

                    const retainedCityIds =
                        previousSelections.filter(
                            function (cityId) {
                                return validCityIds.has(
                                    cityId
                                );
                            }
                        );

                    rows.forEach(
                        function (city) {
                            const cityId =
                                String(
                                    city.id
                                    ?? ''
                                ).trim();

                            const cityName =
                                String(
                                    city.name
                                    ?? ''
                                ).trim();

                            if (
                                cityId === ''
                                || cityName === ''
                            ) {
                                return;
                            }

                            const option =
                                document.createElement(
                                    'option'
                                );

                            option.value =
                                cityId;

                            option.textContent =
                                cityName;

                            option.selected =
                                retainedCityIds.includes(
                                    cityId
                                );

                            citySelect.appendChild(
                                option
                            );
                        }
                    );

                    const hasStates =
                        selectedValues(
                            stateSelect
                        ).length > 0;

                    citySelect.disabled =
                        !hasStates
                        || rows.length === 0;

                    if (
                        window.SelectChoice
                        && typeof window.SelectChoice.create
                        === 'function'
                    ) {
                        window.SelectChoice.create(
                            citySelect
                        );
                    }

                    validateStateCity(
                        false
                    );
                }

                /**
                 * Load Cities for current State selection.
                 *
                 * @returns {Promise<void>}
                 */
                async function loadCities() {
                    const stateIds =
                        selectedValues(
                            stateSelect
                        );

                    const previousCityIds =
                        selectedValues(
                            citySelect
                        );

                    if (
                        stateIds.length === 0
                    ) {
                        replaceCities(
                            [],
                            []
                        );

                        return;
                    }

                    if (
                        cityBaseUrl === ''
                    ) {
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
                                cityBaseUrl
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
                                'City master request failed.'
                            );
                        }

                        const payload =
                            await response.json();

                        replaceCities(
                            Array.isArray(
                                payload.cities
                            )
                                ? payload.cities
                                : [],
                            previousCityIds
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
                        validateStateCity(
                            false
                        );

                        void loadCities();
                    }
                );

                citySelect.addEventListener(
                    'change',
                    function () {
                        validateStateCity(
                            false
                        );
                    }
                );
            }

            /*
             * ------------------------------------------------------------------
             * Final Search submit validation
             * ------------------------------------------------------------------
             */

            form.addEventListener(
                'submit',
                function (event) {
                    if (
                        validateSearch()
                    ) {
                        return;
                    }

                    event.preventDefault();

                    event.stopPropagation();
                }
            );

            /*
             * Validate restored Back-to-Search criteria silently.
             */
            validateAgeRange(
                false
            );

            validateHeightRange(
                false
            );

            validateStateCity(
                false
            );
        }
    );
})(window, document);