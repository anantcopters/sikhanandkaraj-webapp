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
     * Initialize dependent state-city selection.
     *
     * @returns {void}
     */
    function initializeStateCity() {
        const stateSelect = document.getElementById(
            'partnerStateId'
        );

        const citySelect = document.getElementById(
            'partnerCityId'
        );

        if (
            !(stateSelect instanceof HTMLSelectElement)
            || !(citySelect instanceof HTMLSelectElement)
        ) {
            return;
        }

        const baseUrl = String(
            citySelect.dataset.citiesUrl || ''
        );

        if (baseUrl === '') {
            return;
        }

        /**
         * @param {Array<{value:string,label:string}>} cities
         * @param {string} selectedValue
         */
        function replaceCities(
            cities,
            selectedValue = ''
        ) {
            window.SelectChoice?.destroy(
                citySelect
            );

            citySelect.replaceChildren();

            const placeholder =
                document.createElement('option');

            placeholder.value = '';
            placeholder.textContent =
                cities.length > 0
                    ? 'Select city'
                    : 'No cities available';

            citySelect.appendChild(
                placeholder
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

                option.selected =
                    String(city.value)
                    === String(selectedValue);

                citySelect.appendChild(
                    option
                );
            });

            citySelect.disabled =
                cities.length === 0;

            window.SelectChoice?.create(
                citySelect
            );
        }

        /**
         * @param {string} stateId
         * @param {string} selectedCityId
         */
        async function loadCities(
            stateId,
            selectedCityId = ''
        ) {
            if (stateId === '') {
                replaceCities([], '');
                return;
            }

            citySelect.disabled = true;

            try {
                const response = await fetch(
                    `${baseUrl}/${encodeURIComponent(stateId)}`,
                    {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With':
                                'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
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
                    Array.isArray(payload.data)
                        ? payload.data
                        : [],
                    selectedCityId
                );
            } catch (error) {
                console.error(error);

                replaceCities([], '');
            }
        }

        stateSelect.addEventListener(
            'change',
            () => {
                loadCities(
                    stateSelect.value
                );
            }
        );

        const selectedCityId = String(
            citySelect.dataset.selectedCity
            || ''
        );

        if (
            stateSelect.value !== ''
            && citySelect.options.length <= 1
        ) {
            loadCities(
                stateSelect.value,
                selectedCityId
            );
        }
    }

    /**
     * Validate annual-income From/To ordering.
     *
     * This assumes master options are rendered in increasing
     * income order. Server-side validation remains authoritative.
     *
     * @returns {boolean}
     */
    function validateIncomeRange() {
        const item = String(
            form.dataset.preferenceItem || ''
        );

        if (item !== 'annual-income') {
            return true;
        }

        const from = document.getElementById(
            'annualIncomeFromId'
        );

        const to = document.getElementById(
            'annualIncomeToId'
        );

        if (
            !(from instanceof HTMLSelectElement)
            || !(to instanceof HTMLSelectElement)
            || from.value === ''
            || to.value === ''
        ) {
            return true;
        }

        if (
            from.selectedIndex
            > to.selectedIndex
        ) {
            to.setCustomValidity(
                'Minimum annual income cannot exceed maximum annual income.'
            );

            to.reportValidity();
            to.focus();

            return false;
        }

        to.setCustomValidity('');

        return true;
    }

    /**
     * Display the existing project loader.
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
            if (!validateIncomeRange()) {
                event.preventDefault();
                return;
            }

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

    initializeStateCity();
});