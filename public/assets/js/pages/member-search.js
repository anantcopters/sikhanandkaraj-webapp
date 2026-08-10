/**
 * Member Search page behaviour.
 *
 * Responsibilities:
 *
 * - retain the existing globally managed Choices.js selects;
 * - dynamically reload active City options after State changes;
 * - refresh the existing Choices City instance after option replacement;
 * - never perform Search business validation in JavaScript.
 */
(function (window, document) {
    'use strict';

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            const states =
                document.getElementById(
                    'searchStates'
                );

            const cities =
                document.getElementById(
                    'searchCities'
                );

            /*
             * Basic Search does not render City.
             */
            if (
                !states
                || !cities
            ) {
                return;
            }

            /**
             * Return selected State IDs from the original select.
             *
             * Choices keeps the underlying select values synchronized.
             *
             * @returns {string[]}
             */
            function selectedStateIds() {
                return Array.from(
                    states.selectedOptions
                )
                    .map(
                        function (option) {
                            return option.value;
                        }
                    )
                    .filter(Boolean);
            }

            /**
             * Return currently selected City IDs.
             *
             * Existing values are preserved when they remain valid after
             * refreshing the City master.
             *
             * @returns {string[]}
             */
            function selectedCityIds() {
                return Array.from(
                    cities.selectedOptions
                )
                    .map(
                        function (option) {
                            return option.value;
                        }
                    )
                    .filter(Boolean);
            }

            /**
             * Replace City options and rebuild its Choices instance.
             *
             * @param {Array<Object>} rows
             * @param {string[]} previousSelection
             *
             * @returns {void}
             */
            function replaceCities(
                rows,
                previousSelection
            ) {
                /*
                 * Destroy before mutating the underlying select.
                 */
                if (
                    window.SelectChoice
                    && typeof window.SelectChoice.destroy
                    === 'function'
                ) {
                    window.SelectChoice.destroy(
                        cities
                    );
                }

                cities.innerHTML = '';

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
                            previousSelection.includes(
                                id
                            );

                        cities.appendChild(
                            option
                        );
                    }
                );

                /*
                 * Reinitialize using the existing global project wrapper.
                 */
                if (
                    window.SelectChoice
                    && typeof window.SelectChoice.create
                    === 'function'
                ) {
                    window.SelectChoice.create(
                        cities
                    );
                }
            }

            /**
             * Load active City masters for selected States.
             *
             * @returns {Promise<void>}
             */
            async function loadCities() {
                const stateIds =
                    selectedStateIds();

                const previousCities =
                    selectedCityIds();

                if (
                    stateIds.length === 0
                ) {
                    replaceCities(
                        [],
                        []
                    );

                    return;
                }

                const params =
                    new URLSearchParams();

                stateIds.forEach(
                    function (stateId) {
                        params.append(
                            'state_ids[]',
                            stateId
                        );
                    }
                );

                try {
                    const response =
                        await window.fetch(
                            '/search/cities?'
                            + params.toString(),
                            {
                                headers: {
                                    Accept:
                                        'application/json'
                                }
                            }
                        );

                    if (!response.ok) {
                        throw new Error(
                            'City request failed.'
                        );
                    }

                    const payload =
                        await response.json();

                    const rows =
                        Array.isArray(
                            payload.cities
                        )
                            ? payload.cities
                            : [];

                    replaceCities(
                        rows,
                        previousCities
                    );
                } catch (error) {
                    /*
                     * Do not expose transport or internal error details to
                     * the member. Existing selections remain available.
                     */
                    console.error(
                        'Unable to load Search cities.'
                    );
                }
            }

            /*
             * Choices updates the original select and dispatches change.
             */
            states.addEventListener(
                'change',
                function () {
                    void loadCities();
                }
            );
        }
    );
})(window, document);