'use strict';

/**
 * Family Details page behaviour.
 *
 * Handles:
 * - Community to Sub-community dependency.
 * - State to City dependency.
 * - Married sibling count constraints.
 */
document.addEventListener('DOMContentLoaded', () => {
    const communitySelect = document.getElementById(
        'familyCommunityId'
    );

    const subcommunitySelect = document.getElementById(
        'familySubcommunityId'
    );

    const stateSelect = document.getElementById(
        'familyStateId'
    );

    const citySelect = document.getElementById(
        'familyCityId'
    );

    /**
     * Clear a dependent select and restore its placeholder.
     *
     * @param {HTMLSelectElement|null} select
     * @param {string} placeholder
     * @param {boolean} disabled
     */
    const resetSelect = (
        select,
        placeholder,
        disabled = true
    ) => {
        if (!select) {
            return;
        }

        select.innerHTML = '';

        const option = document.createElement('option');

        option.value = '';
        option.textContent = placeholder;

        select.appendChild(option);
        select.disabled = disabled;

        select.dispatchEvent(
            new Event('change', {
                bubbles: true,
            })
        );
    };

    /**
     * Append API records to a select.
     *
     * Supports the standard value/label response while retaining
     * compatibility with id/name records.
     *
     * @param {HTMLSelectElement} select
     * @param {Array<object>} records
     * @param {string} selectedValue
     */
    const appendOptions = (
        select,
        records,
        selectedValue = ''
    ) => {
        records.forEach((record) => {
            const value = String(
                record.value ?? record.id ?? ''
            );

            const label = String(
                record.label ?? record.name ?? ''
            );

            if (value === '' || label === '') {
                return;
            }

            const option = document.createElement('option');

            option.value = value;
            option.textContent = label;

            if (value === String(selectedValue)) {
                option.selected = true;
            }

            select.appendChild(option);
        });
    };

    /**
     * Load child master options for a selected parent.
     *
     * @param {object} configuration
     * @param {HTMLSelectElement} configuration.parentSelect
     * @param {HTMLSelectElement} configuration.childSelect
     * @param {string} configuration.placeholder
     * @param {string} configuration.selectedValue
     */
    const loadDependentOptions = async ({
        parentSelect,
        childSelect,
        placeholder,
        selectedValue = '',
    }) => {
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

        resetSelect(
            childSelect,
            placeholder,
            true
        );

        if (parentId === '' || urlTemplate === '') {
            return;
        }

        const resolvedUrl = urlTemplate.replace(
            '__PARENT_ID__',
            encodeURIComponent(parentId)
        );

        /*
         * Reject an invalid or incompletely configured URL template.
         */
        if (
            resolvedUrl.includes('__PARENT_ID__')
            || resolvedUrl === urlTemplate
        ) {
            console.error(
                'Dependent master URL template is invalid.'
            );

            return;
        }

        try {
            const requestUrl = new URL(
                resolvedUrl,
                window.location.origin
            );

            const response = await fetch(
                requestUrl.toString(),
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
                    `Dependent master request failed: `
                    + `${response.status}`
                );
            }

            const payload = await response.json();

            const records = Array.isArray(payload.data)
                ? payload.data
                : [];

            appendOptions(
                childSelect,
                records,
                selectedValue
            );

            childSelect.disabled = false;

            childSelect.dispatchEvent(
                new Event('change', {
                    bubbles: true,
                })
            );
        } catch (error) {
            resetSelect(
                childSelect,
                placeholder,
                true
            );

            console.error(
                'Unable to load dependent master data.',
                error
            );
        }
    };

    /*
     * Community to Sub-community.
     */
    if (communitySelect && subcommunitySelect) {
        communitySelect.addEventListener(
            'change',
            () => {
                const selectedValue = String(
                    subcommunitySelect.dataset
                        .selectedValue ?? ''
                );

                void loadDependentOptions({
                    parentSelect: communitySelect,
                    childSelect: subcommunitySelect,
                    placeholder: 'Select sub-community',
                    selectedValue,
                });

                /*
                 * The stored value is used only for initial reload.
                 * It must not be reused after a manual parent change.
                 */
                subcommunitySelect.dataset.selectedValue = '';
            }
        );
    }

    /*
     * State to City.
     */
    if (stateSelect && citySelect) {
        stateSelect.addEventListener(
            'change',
            () => {
                const selectedValue = String(
                    citySelect.dataset
                        .selectedValue ?? ''
                );

                void loadDependentOptions({
                    parentSelect: stateSelect,
                    childSelect: citySelect,
                    placeholder: 'Select city',
                    selectedValue,
                });

                citySelect.dataset.selectedValue = '';
            }
        );
    }

    /**
     * Restrict married siblings to the selected total.
     *
     * @param {string} totalId
     * @param {string} marriedId
     */
    const validateSiblingCount = (
        totalId,
        marriedId
    ) => {
        const totalSelect =
            document.getElementById(totalId);

        const marriedSelect =
            document.getElementById(marriedId);

        if (!totalSelect || !marriedSelect) {
            return;
        }

        const enforceMaximum = () => {
            const total = Number.parseInt(
                totalSelect.value,
                10
            );

            const married = Number.parseInt(
                marriedSelect.value,
                10
            );

            Array.from(
                marriedSelect.options
            ).forEach((option) => {
                const count = Number.parseInt(
                    option.value,
                    10
                );

                option.disabled =
                    Number.isFinite(total)
                    && Number.isFinite(count)
                    && count > total;
            });

            if (
                Number.isFinite(total)
                && Number.isFinite(married)
                && married > total
            ) {
                marriedSelect.value = String(total);

                marriedSelect.dispatchEvent(
                    new Event('change', {
                        bubbles: true,
                    })
                );
            }
        };

        totalSelect.addEventListener(
            'change',
            enforceMaximum
        );

        enforceMaximum();
    };

    validateSiblingCount(
        'brothersCount',
        'marriedBrothersCount'
    );

    validateSiblingCount(
        'sistersCount',
        'marriedSistersCount'
    );
});