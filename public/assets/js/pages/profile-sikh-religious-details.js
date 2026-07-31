'use strict';

/**
 * Display the form loader and prevent duplicate submissions.
 */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById(
        'sikhReligiousDetailsForm'
    );

    const submitButton = document.getElementById(
        'saveSikhReligiousDetailsButton'
    );

    if (
        !(form instanceof HTMLFormElement)
        || !(submitButton instanceof HTMLButtonElement)
    ) {
        return;
    }

    const submitLabel = submitButton.querySelector(
        '.registration-submit__label'
    );

    const submitLoader = submitButton.querySelector(
        '.registration-submit__loading'
    );

    let isSubmitting = false;

    form.addEventListener('submit', (event) => {
        if (
            event.defaultPrevented
            || !form.checkValidity()
        ) {
            return;
        }

        if (isSubmitting) {
            event.preventDefault();
            return;
        }

        event.preventDefault();
        isSubmitting = true;

        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');

        submitLabel?.classList.add('d-none');
        submitLoader?.classList.remove('d-none');

        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => {
                form.submit();
            });
        });
    });
});

/**
 * Load sub-communities for the selected Sikh community.
 */
document.addEventListener('DOMContentLoaded', () => {
    const community = document.getElementById(
        'sikhCommunityId'
    );

    const subcommunity = document.getElementById(
        'sikhSubcommunityId'
    );

    if (
        !(community instanceof HTMLSelectElement)
        || !(subcommunity instanceof HTMLSelectElement)
    ) {
        return;
    }

    const baseUrl =
        subcommunity.dataset.subcommunitiesUrl ?? '';

    if (baseUrl === '') {
        return;
    }

    function replaceSubcommunities(
        rows,
        selectedValue = ''
    ) {
        window.SelectChoice?.destroy(subcommunity);

        subcommunity.replaceChildren();

        const placeholder =
            document.createElement('option');

        placeholder.value = '';

        placeholder.textContent = rows.length > 0
            ? 'Select sub-community'
            : 'No sub-communities available';

        subcommunity.appendChild(placeholder);

        rows.forEach((row) => {
            const option =
                document.createElement('option');

            option.value = String(row.value ?? '');
            option.textContent = String(row.label ?? '');

            option.selected =
                String(row.value ?? '')
                === String(selectedValue);

            subcommunity.appendChild(option);
        });

        subcommunity.disabled = rows.length === 0;

        window.SelectChoice?.create(subcommunity);
    }

    async function loadSubcommunities(
        communityId,
        selectedValue = ''
    ) {
        if (communityId === '') {
            replaceSubcommunities([], '');
            return;
        }

        try {
            const response = await fetch(
                `${baseUrl}/${encodeURIComponent(
                    communityId
                )}`,
                {
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
                    'Unable to load sub-communities.'
                );
            }

            const payload = await response.json();

            replaceSubcommunities(
                Array.isArray(payload.data)
                    ? payload.data
                    : [],
                selectedValue
            );
        } catch (error) {
            console.error(error);
            replaceSubcommunities([], '');
        }
    }

    community.addEventListener('change', () => {
        loadSubcommunities(community.value);
    });
});

/**
 * Load cities for the selected birth state.
 */
document.addEventListener('DOMContentLoaded', () => {
    const state = document.getElementById(
        'birthStateId'
    );

    const city = document.getElementById(
        'birthCityId'
    );

    if (
        !(state instanceof HTMLSelectElement)
        || !(city instanceof HTMLSelectElement)
    ) {
        return;
    }

    const baseUrl = city.dataset.citiesUrl ?? '';

    if (baseUrl === '') {
        return;
    }

    function replaceCities(
        rows,
        selectedValue = ''
    ) {
        window.SelectChoice?.destroy(city);

        city.replaceChildren();

        const placeholder =
            document.createElement('option');

        placeholder.value = '';

        placeholder.textContent = rows.length > 0
            ? 'Select city'
            : 'No cities available';

        city.appendChild(placeholder);

        rows.forEach((row) => {
            const option =
                document.createElement('option');

            option.value = String(row.value ?? '');
            option.textContent = String(row.label ?? '');

            option.selected =
                String(row.value ?? '')
                === String(selectedValue);

            city.appendChild(option);
        });

        city.disabled = rows.length === 0;

        window.SelectChoice?.create(city);
    }

    async function loadCities(
        stateId,
        selectedValue = ''
    ) {
        if (stateId === '') {
            replaceCities([], '');
            return;
        }

        try {
            const response = await fetch(
                `${baseUrl}/${encodeURIComponent(
                    stateId
                )}`,
                {
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

            const payload = await response.json();

            replaceCities(
                Array.isArray(payload.data)
                    ? payload.data
                    : [],
                selectedValue
            );
        } catch (error) {
            console.error(error);
            replaceCities([], '');
        }
    }

    state.addEventListener('change', () => {
        loadCities(state.value);
    });
});