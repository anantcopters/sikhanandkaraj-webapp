'use strict';

document.addEventListener(
    'DOMContentLoaded',
    () => {
        const eligibility =
            document.querySelector(
                '[data-coupon-eligibility]'
            );

        const genderContainer =
            document.querySelector(
                '[data-coupon-gender-container]'
            );

        const membersContainer =
            document.querySelector(
                '[data-coupon-members-container]'
            );

        const gender =
            genderContainer?.querySelector(
                '[name="eligible_gender"]'
            );

        const members =
            membersContainer?.querySelector(
                '[name="member_ids[]"]'
            );

        const syncEligibility =
            () => {
                const value =
                    eligibility?.value ?? '';

                const isGender =
                    value === 'GENDER';

                const isSelected =
                    value === 'SELECTED';

                genderContainer
                    ?.classList
                    .toggle(
                        'd-none',
                        !isGender
                    );

                membersContainer
                    ?.classList
                    .toggle(
                        'd-none',
                        !isSelected
                    );

                if (
                    gender
                    && !gender.disabled
                ) {
                    gender.required =
                        isGender;
                }

                if (
                    members
                    && !members.disabled
                ) {
                    members.required =
                        isSelected;
                }
            };

        eligibility?.addEventListener(
            'change',
            syncEligibility
        );

        syncEligibility();

        const discountType =
            document.querySelector(
                '[data-coupon-discount-type]'
            );

        const discountValue =
            document.querySelector(
                '[name="discount_value"]'
            );

        const syncDiscount =
            () => {
                if (
                    !discountValue
                    || discountValue.disabled
                    || discountValue.readOnly
                ) {
                    return;
                }

                if (
                    discountType?.value
                    === 'PERCENTAGE'
                ) {
                    discountValue.min =
                        '1';

                    discountValue.max =
                        '90';

                    discountValue.step =
                        '1';

                    return;
                }

                discountValue.min =
                    '0.01';

                discountValue
                    .removeAttribute(
                        'max'
                    );

                discountValue.step =
                    '0.01';
            };

        discountType?.addEventListener(
            'change',
            syncDiscount
        );

        /*
 * Coupon geography follows the same dependent master-data flow used by
 * member Basic Details:
 *
 * Country -> State -> City.
 */
        const countrySelect =
            document.getElementById(
                'couponCountryId'
            );

        const stateSelect =
            document.getElementById(
                'couponStateId'
            );

        const citySelect =
            document.getElementById(
                'couponCityId'
            );

        if (
            countrySelect
            && stateSelect
            && citySelect
        ) {
            const statesBaseUrl =
                countrySelect.dataset.statesUrl;

            const citiesBaseUrl =
                citySelect.dataset.citiesUrl;

            let stateRequestController = null;
            let cityRequestController = null;

            function replaceStateOptions(
                states
            ) {
                window.SelectChoice?.destroy(
                    stateSelect
                );

                stateSelect.replaceChildren();

                const placeholder =
                    document.createElement(
                        'option'
                    );

                placeholder.value = '';
                placeholder.textContent =
                    'All States';

                stateSelect.appendChild(
                    placeholder
                );

                states.forEach(
                    function (state) {
                        const option =
                            document.createElement(
                                'option'
                            );

                        option.value =
                            String(
                                state.value
                                ?? ''
                            );

                        option.textContent =
                            String(
                                state.label
                                ?? ''
                            );

                        stateSelect.appendChild(
                            option
                        );
                    }
                );

                stateSelect.disabled =
                    countrySelect.value === '';

                window.SelectChoice?.create(
                    stateSelect
                );
            }

            function replaceCityOptions(
                cities,
                selectedCityId = ''
            ) {
                window.SelectChoice?.destroy(
                    citySelect
                );

                citySelect.replaceChildren();

                const placeholder =
                    document.createElement(
                        'option'
                    );

                placeholder.value = '';
                placeholder.textContent =
                    'All Cities';

                placeholder.selected =
                    selectedCityId === '';

                citySelect.appendChild(
                    placeholder
                );

                cities.forEach(
                    function (city) {
                        const option =
                            document.createElement(
                                'option'
                            );

                        option.value =
                            String(
                                city.value
                                ?? ''
                            );

                        option.textContent =
                            String(
                                city.label
                                ?? ''
                            );

                        option.selected =
                            String(
                                city.value
                            )
                            === String(
                                selectedCityId
                            );

                        citySelect.appendChild(
                            option
                        );
                    }
                );

                citySelect.disabled =
                    stateSelect.value === '';

                window.SelectChoice?.create(
                    citySelect
                );
            }

            async function loadCities(
                stateId,
                selectedCityId = ''
            ) {
                if (!stateId) {
                    replaceCityOptions([]);
                    return;
                }

                cityRequestController?.abort();

                cityRequestController =
                    new AbortController();

                try {
                    const response =
                        await fetch(
                            `${citiesBaseUrl}/${encodeURIComponent(stateId)}`,
                            {
                                headers: {
                                    'Accept':
                                        'application/json',

                                    'X-Requested-With':
                                        'XMLHttpRequest'
                                },

                                credentials:
                                    'same-origin',

                                signal:
                                    cityRequestController
                                        .signal
                            }
                        );

                    if (!response.ok) {
                        throw new Error(
                            'Unable to load cities.'
                        );
                    }

                    const payload =
                        await response.json();

                    replaceCityOptions(
                        Array.isArray(
                            payload.data
                        )
                            ? payload.data
                            : [],
                        selectedCityId
                    );
                } catch (error) {
                    if (
                        error?.name
                        === 'AbortError'
                    ) {
                        return;
                    }

                    console.error(error);

                    replaceCityOptions([]);
                }
            }

            async function loadStates(
                countryId
            ) {
                replaceCityOptions([]);

                if (!countryId) {
                    replaceStateOptions([]);
                    return;
                }

                stateRequestController?.abort();

                stateRequestController =
                    new AbortController();

                try {
                    const response =
                        await fetch(
                            `${statesBaseUrl}/${encodeURIComponent(countryId)}`,
                            {
                                headers: {
                                    'Accept':
                                        'application/json',

                                    'X-Requested-With':
                                        'XMLHttpRequest'
                                },

                                credentials:
                                    'same-origin',

                                signal:
                                    stateRequestController
                                        .signal
                            }
                        );

                    if (!response.ok) {
                        throw new Error(
                            'Unable to load states.'
                        );
                    }

                    const payload =
                        await response.json();

                    replaceStateOptions(
                        Array.isArray(
                            payload.data
                        )
                            ? payload.data
                            : []
                    );
                } catch (error) {
                    if (
                        error?.name
                        === 'AbortError'
                    ) {
                        return;
                    }

                    console.error(error);

                    replaceStateOptions([]);
                }
            }

            countrySelect.addEventListener(
                'change',
                function () {
                    void loadStates(
                        countrySelect.value
                    );
                }
            );

            stateSelect.addEventListener(
                'change',
                function () {
                    void loadCities(
                        stateSelect.value
                    );
                }
            );

            const selectedCityId =
                citySelect.dataset
                    .selectedCity
                ?? '';

            if (
                stateSelect.value
                && citySelect.options.length <= 1
            ) {
                void loadCities(
                    stateSelect.value,
                    selectedCityId
                );
            }
        }

        syncDiscount();
    }
);