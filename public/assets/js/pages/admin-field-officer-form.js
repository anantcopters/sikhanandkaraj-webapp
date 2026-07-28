'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector(
        '[data-field-officer-form]'
    );

    if (!form) {
        return;
    }

    const countrySelect = form.querySelector(
        '[data-country-select]'
    );

    const stateSelect = form.querySelector(
        '[data-state-select]'
    );

    const citySelect = form.querySelector(
        '[data-city-select]'
    );

    const statesBaseUrl = String(
        form.dataset.statesUrl || ''
    ).replace(/\/$/, '');

    const citiesBaseUrl = String(
        form.dataset.citiesUrl || ''
    ).replace(/\/$/, '');

    /**
     * Replace dependent select options safely.
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
            document.createElement('option');

        placeholderOption.value = '';
        placeholderOption.textContent = placeholder;

        select.appendChild(placeholderOption);

        options.forEach(function (item) {
            const option =
                document.createElement('option');

            option.value = String(item.value);
            option.textContent = String(item.label);

            select.appendChild(option);
        });
    }

    /**
     * @param {HTMLSelectElement} select
     * @param {string} placeholder
     */
    function resetSelect(select, placeholder) {
        populateSelect(
            select,
            [],
            placeholder
        );

        select.disabled = false;
    }

    /**
     * Request JSON master data.
     *
     * @param {string} url
     * @returns {Promise<Array<{value: string, label: string}>>}
     */
    async function fetchOptions(url) {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(
                'Master data could not be loaded.'
            );
        }

        const payload = await response.json();

        return Array.isArray(payload.data)
            ? payload.data
            : [];
    }

    if (
        countrySelect
        && stateSelect
        && citySelect
    ) {
        countrySelect.addEventListener(
            'change',
            async function () {
                const countryId =
                    countrySelect.value;

                resetSelect(
                    stateSelect,
                    'Select State'
                );

                resetSelect(
                    citySelect,
                    'Select City'
                );

                if (!countryId) {
                    return;
                }

                stateSelect.disabled = true;

                try {
                    const states = await fetchOptions(
                        statesBaseUrl
                        + '/'
                        + encodeURIComponent(countryId)
                    );

                    populateSelect(
                        stateSelect,
                        states,
                        states.length > 0
                            ? 'Select State'
                            : 'No State Available'
                    );
                } catch (error) {
                    populateSelect(
                        stateSelect,
                        [],
                        'Unable to Load States'
                    );
                } finally {
                    stateSelect.disabled = false;
                }
            }
        );

        stateSelect.addEventListener(
            'change',
            async function () {
                const stateId =
                    stateSelect.value;

                resetSelect(
                    citySelect,
                    'Select City'
                );

                if (!stateId) {
                    return;
                }

                citySelect.disabled = true;

                try {
                    const cities = await fetchOptions(
                        citiesBaseUrl
                        + '/'
                        + encodeURIComponent(stateId)
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
                    citySelect.disabled = false;
                }
            }
        );
    }

    /*
     * Bootstrap-compatible client validation.
     * Server validation remains authoritative.
     */
    form.addEventListener(
        'submit',
        function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }
    );

    const mobileInput = form.querySelector(
        'input[name="mobile_number"]'
    );

    if (mobileInput) {
        mobileInput.addEventListener(
            'input',
            function () {
                mobileInput.value = mobileInput.value
                    .replace(/\D/g, '')
                    .slice(0, 10);
            }
        );
    }

    const upiInput = form.querySelector(
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
});