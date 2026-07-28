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
         * Safely replace select options.
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
            select.innerHTML = '';

            const placeholderOption =
                document.createElement(
                    'option'
                );

            placeholderOption.value = '';
            placeholderOption.textContent =
                placeholder;

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

                    populateSelect(
                        citySelect,
                        [],
                        'Select City'
                    );

                    if (!stateId) {
                        return;
                    }

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
                    } finally {
                        citySelect.disabled =
                            false;
                    }
                }
            );
        }

        /*
         * Client-side validation complements server validation.
         */
        form.addEventListener(
            'submit',
            function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                form.classList.add(
                    'was-validated'
                );
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