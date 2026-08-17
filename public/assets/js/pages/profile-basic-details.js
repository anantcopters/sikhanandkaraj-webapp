'use strict';

document.addEventListener('DOMContentLoaded', () => {

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

    const birthDay = document.getElementById(
        'birthDay'
    );

    const birthMonth = document.getElementById(
        'birthMonth'
    );

    const birthYear = document.getElementById(
        'birthYear'
    );

    const dateOfBirthError = document.getElementById(
        'dateOfBirthError'
    );

    function setDateOfBirthError(message) {
        if (!dateOfBirthError) {
            return;
        }

        dateOfBirthError.textContent = message;
        dateOfBirthError.classList.toggle(
            'd-block',
            message !== ''
        );

        [
            birthDay,
            birthMonth,
            birthYear
        ].forEach((field) => {
            if (!field) {
                return;
            }

            field.classList.toggle(
                'is-invalid',
                message !== ''
            );
        });
    }

    function synchronizeDateOfBirth() {
        if (
            !dateOfBirth
            || !birthDay
            || !birthMonth
            || !birthYear
        ) {
            return false;
        }

        if (
            birthDay.value === ''
            || birthMonth.value === ''
            || birthYear.value === ''
        ) {
            dateOfBirth.value = '';

            setDateOfBirthError(
                'Please select complete date of birth.'
            );

            return false;
        }

        const candidateDate = [
            birthYear.value,
            birthMonth.value,
            birthDay.value
        ].join('-');

        const parsedDate = new Date(
            `${candidateDate}T00:00:00`
        );

        const isRealDate =
            !Number.isNaN(parsedDate.getTime())
            && parsedDate.getFullYear()
            === Number(birthYear.value)
            && parsedDate.getMonth() + 1
            === Number(birthMonth.value)
            && parsedDate.getDate()
            === Number(birthDay.value);

        if (!isRealDate) {
            dateOfBirth.value = '';

            setDateOfBirthError(
                'Please select a valid date of birth.'
            );

            return false;
        }

        const today = new Date();
        const maximumAdultDate = new Date(
            today.getFullYear() - 18,
            today.getMonth(),
            today.getDate()
        );

        if (parsedDate > maximumAdultDate) {
            dateOfBirth.value = '';

            setDateOfBirthError(
                'The member must be at least 18 years old.'
            );

            return false;
        }

        dateOfBirth.value = candidateDate;

        setDateOfBirthError('');
        updateAgePreview();

        return true;
    }

    [
        birthDay,
        birthMonth,
        birthYear
    ].forEach((field) => {
        field?.addEventListener(
            'change',
            synchronizeDateOfBirth
        );
    });

    /**
     * Show child-related fields only when marital status is not Never Married.
     *
     * @returns {void}
     */
    function initializeChildrenDetailsVisibility() {
        const maritalStatus =
            document.getElementById(
                'maritalStatusId'
            );

        const childrenDetailSections =
            document.querySelectorAll(
                '[data-children-details]'
            );

        const numberOfChildren =
            document.getElementById(
                'numberOfChildren'
            );

        const livingTogetherInputs =
            document.querySelectorAll(
                'input[name="children_living_together"]'
            );

        if (
            !(maritalStatus instanceof HTMLSelectElement)
            || childrenDetailSections.length === 0
            || !(numberOfChildren instanceof HTMLInputElement)
        ) {
            return;
        }

        /**
         * Return the selected marital-status master code.
         *
         * @returns {string}
         */
        const selectedMaritalStatusCode = () => {
            const selectedOption =
                maritalStatus.options[
                maritalStatus.selectedIndex
                ];

            return String(
                selectedOption
                    ?.dataset
                    ?.maritalStatusCode
                ?? ''
            )
                .trim()
                .toUpperCase();
        };

        /**
         * Synchronize child-field visibility and submitted state.
         *
         * @returns {void}
         */
        const updateChildrenDetails = () => {
            const maritalStatusCode =
                selectedMaritalStatusCode();

            const shouldShow =
                maritalStatus.value !== ''
                && maritalStatusCode
                !== 'NEVER_MARRIED';

            childrenDetailSections.forEach(
                (section) => {
                    if (!(section instanceof HTMLElement)) {
                        return;
                    }

                    section.classList.toggle(
                        'd-none',
                        !shouldShow
                    );
                }
            );

            numberOfChildren.disabled =
                !shouldShow;

            livingTogetherInputs.forEach(
                (input) => {
                    if (!(input instanceof HTMLInputElement)) {
                        return;
                    }

                    input.disabled =
                        !shouldShow;
                }
            );

            if (shouldShow) {
                return;
            }

            /*
             * Clear stale child information when marital status changes
             * to Never Married.
             */
            numberOfChildren.value = '';

            livingTogetherInputs.forEach(
                (input) => {
                    if (input instanceof HTMLInputElement) {
                        input.checked = false;
                    }
                }
            );
        };

        maritalStatus.addEventListener(
            'change',
            updateChildrenDetails
        );

        updateChildrenDetails();
    }

    /*
     * Initialize marital-status-dependent children fields after the form
     * elements are available.
     */
    initializeChildrenDetailsVisibility();

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

    updateAgePreview();

    /**
     * Show the existing loading state and prevent duplicate submission.
     */
    form?.addEventListener('submit', (event) => {
        const isDateOfBirthValid =
            synchronizeDateOfBirth();

        if (!isDateOfBirthValid) {
            event.preventDefault();

            birthDay?.focus();

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
 * Configure dependent State and City Choices.js fields.
 */
function initializeStateCityDependency() {
    const countrySelect = document.getElementById('countryId');
    const stateSelect = document.getElementById('stateId');
    const citySelect = document.getElementById('cityId');

    if (!countrySelect || !stateSelect || !citySelect) {
        return;
    }

    const statesBaseUrl = countrySelect.dataset.statesUrl;
    const citiesBaseUrl = citySelect.dataset.citiesUrl;

    if (!statesBaseUrl || !citiesBaseUrl) {
        return;
    }

    let stateRequestController = null;
    let cityRequestController = null;

    function replaceStateOptions(states) {
        window.SelectChoice?.destroy(stateSelect);
        stateSelect.replaceChildren();

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = states.length > 0
            ? 'Select state'
            : 'No states available';
        stateSelect.appendChild(placeholder);

        states.forEach((state) => {
            const option = document.createElement('option');
            option.value = String(state.value ?? '');
            option.textContent = String(state.label ?? '');
            stateSelect.appendChild(option);
        });

        stateSelect.disabled = states.length === 0;
        window.SelectChoice?.create(stateSelect);
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

        cities.forEach(function (city) {
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
        cityRequestController?.abort();
        cityRequestController = new AbortController();

        try {
            const response = await fetch(
                `${citiesBaseUrl}/${encodeURIComponent(stateId)}`,
                {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    signal: cityRequestController.signal
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
            if (error?.name === 'AbortError') {
                return;
            }

            console.error(error);

            replaceCityOptions([], '');
        }
    }

    async function loadStates(countryId) {
        replaceCityOptions([], '');

        if (!countryId) {
            replaceStateOptions([]);
            return;
        }

        stateRequestController?.abort();
        stateRequestController = new AbortController();
        stateSelect.disabled = true;

        try {
            const response = await fetch(
                `${statesBaseUrl}/${encodeURIComponent(countryId)}`,
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    signal: stateRequestController.signal
                }
            );

            if (!response.ok) {
                throw new Error('Unable to load states.');
            }

            const payload = await response.json();
            replaceStateOptions(
                Array.isArray(payload.data) ? payload.data : []
            );
        } catch (error) {
            if (error?.name === 'AbortError') {
                return;
            }

            console.error(error);
            replaceStateOptions([]);
        }
    }

    countrySelect.addEventListener('change', () => {
        void loadStates(countrySelect.value);
    });

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
