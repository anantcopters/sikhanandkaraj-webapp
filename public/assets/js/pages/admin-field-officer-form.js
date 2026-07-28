'use strict';

document.addEventListener(
    'DOMContentLoaded',
    function () {
        const form = document.querySelector(
            '[data-field-officer-form]'
        );

        if (!form) {
            return;
        }

        const stateSelect = form.querySelector(
            '[data-state-select]'
        );

        const citySelect = form.querySelector(
            '[data-city-select]'
        );

        const citiesBaseUrl = String(
            form.dataset.citiesUrl || ''
        ).replace(/\/$/, '');

        /**
         * Replace native city options and rebuild the existing
         * project Choices.js component.
         *
         * @param {HTMLSelectElement} select
         * @param {Array<{value: string, label: string}>} options
         * @param {string} placeholder
         */
        function populateSelect(
            select,
            options,
            placeholder
        ) {
            /*
             * Follow the existing project pattern used by Basic Details:
             * destroy Choices, update native options and recreate Choices.
             */
            window.SelectChoice?.destroy(select);

            select.replaceChildren();

            const placeholderOption =
                document.createElement(
                    'option'
                );

            placeholderOption.value = '';
            placeholderOption.textContent =
                placeholder;

            placeholderOption.selected = true;

            select.appendChild(
                placeholderOption
            );

            options.forEach(
                function (item) {
                    const option =
                        document.createElement(
                            'option'
                        );

                    option.value =
                        String(item.value);

                    option.textContent =
                        String(item.label);

                    select.appendChild(
                        option
                    );
                }
            );

            select.disabled =
                options.length === 0;

            window.SelectChoice?.create(select);
        }

        /**
         * Retrieve select options from the server.
         *
         * @param {string} url
         * @returns {Promise<Array<{value: string, label: string}>>}
         */
        async function fetchOptions(url) {
            const response = await fetch(
                url,
                {
                    method: 'GET',

                    headers: {
                        Accept:
                            'application/json',

                        'X-Requested-With':
                            'XMLHttpRequest'
                    },

                    credentials:
                        'same-origin'
                }
            );

            if (!response.ok) {
                throw new Error(
                    'Master data could not be loaded.'
                );
            }

            const payload =
                await response.json();

            return Array.isArray(
                payload.data
            )
                ? payload.data
                : [];
        }

        if (
            stateSelect
            && citySelect
        ) {
            stateSelect.addEventListener(
                'change',
                async function () {
                    const stateId =
                        stateSelect.value;

                    if (!stateId) {
                        populateSelect(
                            citySelect,
                            [],
                            'Select City'
                        );

                        return;
                    }

                    window.SelectChoice?.destroy(
                        citySelect
                    );

                    citySelect.disabled = true;

                    try {
                        const cities =
                            await fetchOptions(
                                citiesBaseUrl
                                + '/'
                                + encodeURIComponent(
                                    stateId
                                )
                            );

                        populateSelect(
                            citySelect,
                            cities,
                            cities.length > 0
                                ? 'Select City'
                                : 'No City Available'
                        );
                    } catch (error) {
                        populateSelect(
                            citySelect,
                            [],
                            'Unable to Load Cities'
                        );
                    }
                }
            );
        }

        /*
        * Show browser validation feedback only when the form is invalid.
        *
        * Do not add Bootstrap's was-validated class for a valid submission,
        * because that displays green validation ticks not used elsewhere in
        * the application.
        */
        form.addEventListener(
            'submit',
            function (event) {
                if (form.checkValidity()) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                form.classList.add(
                    'was-validated'
                );

                const firstInvalidField =
                    form.querySelector(
                        ':invalid'
                    );

                if (
                    firstInvalidField
                    instanceof HTMLElement
                ) {
                    firstInvalidField.focus();
                }
            }
        );

        const mobileInput =
            form.querySelector(
                'input[name="mobile_number"]'
            );

        if (mobileInput) {
            mobileInput.addEventListener(
                'input',
                function () {
                    mobileInput.value =
                        mobileInput.value
                            .replace(/\D/g, '')
                            .slice(0, 10);
                }
            );
        }

        const upiInput =
            form.querySelector(
                'input[name="upi_id"]'
            );

        if (upiInput) {
            upiInput.addEventListener(
                'blur',
                function () {
                    upiInput.value =
                        upiInput.value
                            .trim()
                            .toLowerCase();
                }
            );
        }
    }
);