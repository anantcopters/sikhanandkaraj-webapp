'use strict';

/**
 * Family Details page behaviour.
 *
 * Handles:
 * - Community -> Sub-community dependency.
 * - State -> City dependency.
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

    const appendOptions = (
        select,
        records,
        selectedValue = ''
    ) => {
        records.forEach((record) => {
            const option = document.createElement('option');

            option.value = String(record.id ?? '');
            option.textContent = String(record.name ?? '');

            if (option.value === String(selectedValue)) {
                option.selected = true;
            }

            select.appendChild(option);
        });
    };

    const loadDependentOptions = async ({
        parentSelect,
        childSelect,
        url,
        placeholder,
        selectedValue = '',
    }) => {
        if (!parentSelect || !childSelect) {
            return;
        }

        const parentId = String(
            parentSelect.value ?? ''
        ).trim();

        resetSelect(
            childSelect,
            placeholder,
            true
        );

        if (parentId === '' || url === '') {
            return;
        }

        childSelect.disabled = true;

        try {
            const requestUrl = new URL(
                url,
                window.location.origin
            );

            requestUrl.searchParams.set(
                'parent_id',
                parentId
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
                    'Dependent master request failed.'
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

            console.error(error);
        }
    };

    if (communitySelect && subcommunitySelect) {
        communitySelect.addEventListener(
            'change',
            () => {
                void loadDependentOptions({
                    parentSelect: communitySelect,
                    childSelect: subcommunitySelect,
                    url:
                        communitySelect.dataset
                            .subcommunityUrl ?? '',
                    placeholder:
                        'Select sub-community',
                });
            }
        );
    }

    if (stateSelect && citySelect) {
        stateSelect.addEventListener(
            'change',
            () => {
                void loadDependentOptions({
                    parentSelect: stateSelect,
                    childSelect: citySelect,
                    url:
                        stateSelect.dataset.cityUrl
                        ?? '',
                    placeholder: 'Select city',
                });
            }
        );
    }

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