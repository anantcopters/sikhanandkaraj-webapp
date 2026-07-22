'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const editor = document.getElementById(
        'basicDetailsOffcanvas'
    );

    const form = document.getElementById(
        'basicDetailsForm'
    );

    const submitButton = document.getElementById(
        'saveBasicDetailsButton'
    );

    const dateOfBirth = document.getElementById(
        'dateOfBirth'
    );

    const agePreview = document.getElementById(
        'memberAgePreview'
    );

    /**
     * Reopen the editor when server-side validation fails.
     */
    if (
        editor
        && editor.dataset.openOnError === 'true'
        && window.bootstrap
    ) {
        bootstrap.Offcanvas
            .getOrCreateInstance(editor)
            .show();
    }

    /**
     * Show the member's calculated age as helper text.
     */
    const updateAgePreview = () => {
        if (!dateOfBirth || !agePreview) {
            return;
        }

        if (dateOfBirth.value === '') {
            agePreview.textContent = '';
            return;
        }

        const birthDate = new Date(
            `${dateOfBirth.value}T00:00:00`
        );

        if (Number.isNaN(birthDate.getTime())) {
            agePreview.textContent = '';
            return;
        }

        const today = new Date();

        let age = today.getFullYear()
            - birthDate.getFullYear();

        const monthDifference = today.getMonth()
            - birthDate.getMonth();

        if (
            monthDifference < 0
            || (
                monthDifference === 0
                && today.getDate() < birthDate.getDate()
            )
        ) {
            age--;
        }

        agePreview.textContent = age >= 18
            ? `Current age: ${age} years`
            : '';
    };

    dateOfBirth?.addEventListener(
        'change',
        updateAgePreview
    );

    updateAgePreview();

    /**
     * Show the existing loading state and prevent duplicate submission.
     */
    form?.addEventListener('submit', () => {
        if (!submitButton) {
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
    });
});

/**
 * Configure dependent State and City Choices.js fields.
 */
function initializeStateCityDependency() {
    const stateSelect = document.getElementById('stateId');
    const citySelect = document.getElementById('cityId');

    if (!stateSelect || !citySelect) {
        return;
    }

    const citiesBaseUrl = citySelect.dataset.citiesUrl;

    if (!citiesBaseUrl) {
        return;
    }

    /**
     * Replace the native city options and rebuild Choices.js.
     *
     * @param {Array<{value: string, label: string}>} cities
     * @param {string} selectedCityId
     */
    function replaceCityOptions(
        cities,
        selectedCityId
    ) {
        window.SelectChoice?.destroy(citySelect);

        citySelect.innerHTML = '';

        const placeholder = document.createElement('option');

        placeholder.value = '';
        placeholder.textContent = cities.length > 0
            ? 'Select city'
            : 'No cities available';

        citySelect.appendChild(placeholder);

        cities.forEach(function (city) {
            const option = document.createElement('option');

            option.value = String(city.value);
            option.textContent = String(city.label);
            option.selected =
                String(city.value) === String(selectedCityId);

            citySelect.appendChild(option);
        });

        citySelect.disabled = cities.length === 0;

        window.SelectChoice?.create(citySelect);
    }

    /**
     * Load active cities for a selected state.
     *
     * @param {string} stateId
     * @param {string} selectedCityId
     */
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

            const cities = Array.isArray(payload.data)
                ? payload.data
                : [];

            replaceCityOptions(
                cities,
                selectedCityId
            );
        } catch (error) {
            console.error(error);

            replaceCityOptions([], '');
        }
    }

    stateSelect.addEventListener(
        'change',
        function () {
            loadCities(stateSelect.value);
        }
    );

    /*
     * Saved edit data is already server-rendered. This fallback loads
     * it only when a state exists but no city options were rendered.
     */
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
}

document.addEventListener(
    'DOMContentLoaded',
    initializeStateCityDependency
);