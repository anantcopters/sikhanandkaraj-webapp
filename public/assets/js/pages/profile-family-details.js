'use strict';

/**
 * Family Details page behaviour.
 *
 * Handles:
 * - Community to Sub-community dependency.
 * - State to City dependency.
 * - Married sibling count constraints.
 * - Submit-button loading state.
 */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById(
        'familyDetailsForm'
    );

    const submitButton = document.getElementById(
        'saveFamilyDetailsButton'
    );

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
     * Directly changing native select options is insufficient because
     * Choices.js maintains its own rendered option list.
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

        /*
         * Destroy the current Choices.js instance before modifying the
         * underlying native select.
         */
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

        /*
         * Recreate Choices.js so the visible control matches the native
         * select options.
         */
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
     * The parent select must provide:
     *
     * data-dependent-url-template="/path/__PARENT_ID__"
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

        /*
         * Disable the native field while the request is running. The
         * current Choices.js instance remains visible until replacement.
         */
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
     * Bind Community to Sub-community.
     */
    if (communitySelect && subcommunitySelect) {
        communitySelect.addEventListener(
            'change',
            () => {
                /*
                 * A saved value is relevant only during the initial page
                 * restoration. A manual Community change must clear it.
                 */
                subcommunitySelect.dataset.selectedValue = '';

                void loadDependentOptions(
                    communitySelect,
                    subcommunitySelect,
                    'Select sub-community'
                );
            }
        );

        const selectedSubcommunityId = String(
            subcommunitySelect.dataset
                .selectedValue ?? ''
        ).trim();

        /*
         * Normally the server renders saved Sub-community options.
         * This fallback handles validation reloads or missing child data.
         */
        if (
            communitySelect.value !== ''
            && subcommunitySelect.options.length <= 1
        ) {
            void loadDependentOptions(
                communitySelect,
                subcommunitySelect,
                'Select sub-community',
                selectedSubcommunityId
            );
        }
    }

    /**
     * Bind State to City.
     */
    if (stateSelect && citySelect) {
        stateSelect.addEventListener(
            'change',
            () => {
                /*
                 * Clear the previously selected City when State changes.
                 */
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

        /*
         * Normally the server renders saved City options. This fallback
         * loads them when a State exists but the City list is empty.
         */
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
     * Restrict married sibling count to total sibling count.
     *
     * @param {string} totalSelectId
     * @param {string} marriedSelectId
     */
    const initializeSiblingConstraint = (
        totalSelectId,
        marriedSelectId
    ) => {
        const totalSelect = document.getElementById(
            totalSelectId
        );

        const marriedSelect = document.getElementById(
            marriedSelectId
        );

        if (!totalSelect || !marriedSelect) {
            return;
        }

        const enforceConstraint = () => {
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
                const optionCount = Number.parseInt(
                    option.value,
                    10
                );

                option.disabled =
                    Number.isFinite(total)
                    && Number.isFinite(optionCount)
                    && optionCount > total;
            });

            if (
                Number.isFinite(total)
                && Number.isFinite(married)
                && married > total
            ) {
                /*
                 * Choices.js must be rebuilt after changing or disabling
                 * its underlying options.
                 */
                window.SelectChoice?.destroy(
                    marriedSelect
                );

                marriedSelect.value = String(total);

                window.SelectChoice?.create(
                    marriedSelect
                );
            }
        };

        totalSelect.addEventListener(
            'change',
            enforceConstraint
        );

        enforceConstraint();
    };

    initializeSiblingConstraint(
        'brothersCount',
        'marriedBrothersCount'
    );

    initializeSiblingConstraint(
        'sistersCount',
        'marriedSistersCount'
    );

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