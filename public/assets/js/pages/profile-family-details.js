'use strict';

/**
 * Family Details page behaviour.
 *
 * Handles:
 * - State to City dependency.
 * - Submit-button loading state.
 */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById(
        'familyDetailsForm'
    );

    const submitButton = document.getElementById(
        'saveFamilyDetailsButton'
    );

    const stateSelect = document.getElementById(
        'familyStateId'
    );

    const citySelect = document.getElementById(
        'familyCityId'
    );

    /**
     * Normalize one API master record.
     *
     * The profile master APIs return value/label. Support id/name as a
     * defensive fallback so this utility remains reusable.
     *
     * @param {object} record
     *
     * @returns {{value: string, label: string}|null}
     */
    const normalizeRecord = (record) => {
        const value = String(
            record?.value ?? record?.id ?? ''
        ).trim();

        const label = String(
            record?.label ?? record?.name ?? ''
        ).trim();

        if (value === '' || label === '') {
            return null;
        }

        return {
            value,
            label,
        };
    };

    /**
     * Rebuild a Choices.js dependent dropdown.
     *
     * @param {HTMLSelectElement} select
     * @param {Array<object>} records
     * @param {string} placeholder
     * @param {string} selectedValue
     */
    const replaceDependentOptions = (
        select,
        records,
        placeholder,
        selectedValue = ''
    ) => {
        if (!select) {
            return;
        }

        const normalizedRecords = records
            .map(normalizeRecord)
            .filter((record) => record !== null);

        window.SelectChoice?.destroy(select);

        select.replaceChildren();

        const placeholderOption =
            document.createElement('option');

        placeholderOption.value = '';

        placeholderOption.textContent =
            normalizedRecords.length > 0
                ? placeholder
                : 'No options available';

        placeholderOption.selected =
            String(selectedValue) === '';

        select.appendChild(placeholderOption);

        normalizedRecords.forEach((record) => {
            const option =
                document.createElement('option');

            option.value = record.value;
            option.textContent = record.label;

            option.selected =
                record.value === String(selectedValue);

            select.appendChild(option);
        });

        select.disabled = normalizedRecords.length === 0;

        window.SelectChoice?.create(select);
    };

    /**
     * Reset a child dropdown when no valid parent is selected.
     *
     * @param {HTMLSelectElement} select
     * @param {string} placeholder
     */
    const resetDependentSelect = (
        select,
        placeholder
    ) => {
        if (!select) {
            return;
        }

        window.SelectChoice?.destroy(select);

        select.replaceChildren();

        const option = document.createElement('option');

        option.value = '';
        option.textContent = placeholder;
        option.selected = true;

        select.appendChild(option);
        select.disabled = true;

        window.SelectChoice?.create(select);
    };

    /**
     * Fetch dependent master options.
     *
     * @param {HTMLSelectElement} parentSelect
     * @param {HTMLSelectElement} childSelect
     * @param {string} placeholder
     * @param {string} selectedValue
     */
    const loadDependentOptions = async (
        parentSelect,
        childSelect,
        placeholder,
        selectedValue = ''
    ) => {
        if (!parentSelect || !childSelect) {
            return;
        }

        const parentId = String(
            parentSelect.value ?? ''
        ).trim();

        const urlTemplate = String(
            parentSelect.dataset
                .dependentUrlTemplate ?? ''
        ).trim();

        if (parentId === '') {
            resetDependentSelect(
                childSelect,
                placeholder
            );

            return;
        }

        if (
            urlTemplate === ''
            || !urlTemplate.includes('__PARENT_ID__')
        ) {
            console.error(
                'Dependent master URL template is missing or invalid.'
            );

            resetDependentSelect(
                childSelect,
                placeholder
            );

            return;
        }

        const requestUrl = urlTemplate.replace(
            '__PARENT_ID__',
            encodeURIComponent(parentId)
        );

        childSelect.disabled = true;

        try {
            const response = await fetch(
                requestUrl,
                {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With':
                            'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            if (!response.ok) {
                throw new Error(
                    `Dependent master request failed `
                    + `with status ${response.status}.`
                );
            }

            const payload = await response.json();

            const records = Array.isArray(payload.data)
                ? payload.data
                : [];

            replaceDependentOptions(
                childSelect,
                records,
                placeholder,
                selectedValue
            );
        } catch (error) {
            console.error(
                'Unable to load dependent master data.',
                error
            );

            resetDependentSelect(
                childSelect,
                placeholder
            );
        }
    };

    /**
     * Bind State to City.
     */
    if (stateSelect && citySelect) {
        stateSelect.addEventListener(
            'change',
            () => {
                citySelect.dataset.selectedValue = '';

                void loadDependentOptions(
                    stateSelect,
                    citySelect,
                    'Select city'
                );
            }
        );

        const selectedCityId = String(
            citySelect.dataset
                .selectedValue ?? ''
        ).trim();

        if (
            stateSelect.value !== ''
            && citySelect.options.length <= 1
        ) {
            void loadDependentOptions(
                stateSelect,
                citySelect,
                'Select city',
                selectedCityId
            );
        }
    }

    /**
     * Prevent duplicate valid submissions and show loading state.
     */
    form?.addEventListener('submit', (event) => {
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