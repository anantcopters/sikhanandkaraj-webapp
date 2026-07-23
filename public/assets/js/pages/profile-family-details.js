'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById(
        'familyDetailsForm'
    );

    const submitButton = document.getElementById(
        'saveFamilyDetailsButton'
    );

    const brothers = document.getElementById(
        'brothersCount'
    );

    const marriedBrothers = document.getElementById(
        'marriedBrothersCount'
    );

    const sisters = document.getElementById(
        'sistersCount'
    );

    const marriedSisters = document.getElementById(
        'marriedSistersCount'
    );

    const siblingError = document.getElementById(
        'familySiblingValidationError'
    );

    function setSiblingError(message) {
        if (siblingError) {
            siblingError.textContent = message;
        }
    }

    /**
     * Validate married sibling counts against total siblings.
     */
    function validateSiblingCounts() {
        const brotherCount = Number(
            brothers?.value || 0
        );

        const marriedBrotherCount = Number(
            marriedBrothers?.value || 0
        );

        const sisterCount = Number(
            sisters?.value || 0
        );

        const marriedSisterCount = Number(
            marriedSisters?.value || 0
        );

        marriedBrothers?.classList.remove('is-invalid');
        marriedSisters?.classList.remove('is-invalid');

        if (marriedBrotherCount > brotherCount) {
            marriedBrothers?.classList.add('is-invalid');

            setSiblingError(
                'Married brothers cannot exceed '
                + 'the total number of brothers.'
            );

            return false;
        }

        if (marriedSisterCount > sisterCount) {
            marriedSisters?.classList.add('is-invalid');

            setSiblingError(
                'Married sisters cannot exceed '
                + 'the total number of sisters.'
            );

            return false;
        }

        setSiblingError('');

        return true;
    }

    [
        brothers,
        marriedBrothers,
        sisters,
        marriedSisters
    ].forEach((field) => {
        field?.addEventListener(
            'change',
            validateSiblingCounts
        );
    });

    form?.addEventListener('submit', (event) => {
        if (!validateSiblingCounts()) {
            event.preventDefault();
            return;
        }

        if (
            !submitButton
            || event.defaultPrevented
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
        }, 0);
    });
});

/**
 * Configure Family State and City Choices.js fields.
 */
document.addEventListener('DOMContentLoaded', () => {
    const stateSelect = document.getElementById(
        'familyStateId'
    );

    const citySelect = document.getElementById(
        'familyCityId'
    );

    if (!stateSelect || !citySelect) {
        return;
    }

    const citiesBaseUrl = citySelect.dataset.citiesUrl;

    if (!citiesBaseUrl) {
        return;
    }

    function replaceCityOptions(
        cities,
        selectedCityId = ''
    ) {
        window.SelectChoice?.destroy(citySelect);

        citySelect.replaceChildren();

        const placeholder = document.createElement(
            'option'
        );

        placeholder.value = '';

        placeholder.textContent = cities.length > 0
            ? 'Select city'
            : 'No cities available';

        placeholder.selected = selectedCityId === '';

        citySelect.appendChild(placeholder);

        cities.forEach((city) => {
            const option = document.createElement(
                'option'
            );

            option.value = String(city.value);
            option.textContent = String(city.label);

            option.selected =
                String(city.value)
                === String(selectedCityId);

            citySelect.appendChild(option);
        });

        citySelect.disabled = cities.length === 0;

        window.SelectChoice?.create(citySelect);
    }

    async function loadCities(
        stateId,
        selectedCityId = ''
    ) {
        if (!stateId) {
            replaceCityOptions([], '');
            return;
        }

        citySelect.disabled = true;

        try {
            const response = await fetch(
                `${citiesBaseUrl}/${encodeURIComponent(stateId)}`,
                {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }
            );

            if (!response.ok) {
                throw new Error(
                    'Unable to load cities.'
                );
            }

            const payload = await response.json();

            replaceCityOptions(
                Array.isArray(payload.data)
                    ? payload.data
                    : [],
                selectedCityId
            );
        } catch (error) {
            console.error(error);
            replaceCityOptions([], '');
        }
    }

    stateSelect.addEventListener(
        'change',
        () => {
            loadCities(stateSelect.value);
        }
    );

    const selectedCityId =
        citySelect.dataset.selectedCity || '';

    if (
        stateSelect.value
        && citySelect.options.length <= 1
    ) {
        loadCities(
            stateSelect.value,
            selectedCityId
        );
    }
});