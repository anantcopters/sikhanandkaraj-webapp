'use strict';

/**
 * Family Details page behaviour.
 *
 * Handles:
 * - State to City dependency.
 * - Optional Field Officer verification.
 * - Field Officer verification enforcement before submit.
 * - Submit-button loading state.
 */
document.addEventListener(
    'DOMContentLoaded',
    () => {
        const form =
            document.getElementById(
                'familyDetailsForm'
            );

        const submitButton =
            document.getElementById(
                'saveFamilyDetailsButton'
            );

        const stateSelect =
            document.getElementById(
                'familyStateId'
            );

        const citySelect =
            document.getElementById(
                'familyCityId'
            );

        const fieldOfficerCodeInput =
            document.getElementById(
                'fieldOfficerCode'
            );

        const fieldOfficerNameInput =
            document.getElementById(
                'fieldOfficerName'
            );

        const verifyFieldOfficerButton =
            document.getElementById(
                'verifyFieldOfficerButton'
            );

        const fieldOfficerMessage =
            document.getElementById(
                'fieldOfficerVerificationMessage'
            );

        /*
         * A readonly Field Officer code means that assignment was
         * already persisted and therefore cannot be changed.
         */
        let verifiedFieldOfficerCode =
            fieldOfficerCodeInput?.readOnly === true
                ? String(
                    fieldOfficerCodeInput.value
                    ?? ''
                ).trim()
                : '';

        /**
         * Normalize one API master record.
         *
         * @param {object} record
         *
         * @returns {{
         *     value: string,
         *     label: string
         * }|null}
         */
        const normalizeRecord = (record) => {
            const value = String(
                record?.value
                ?? record?.id
                ?? ''
            ).trim();

            const label = String(
                record?.label
                ?? record?.name
                ?? ''
            ).trim();

            if (
                value === ''
                || label === ''
            ) {
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

            const normalizedRecords =
                records
                    .map(normalizeRecord)
                    .filter(
                        (record) =>
                            record !== null
                    );

            window.SelectChoice
                ?.destroy(select);

            select.replaceChildren();

            const placeholderOption =
                document.createElement(
                    'option'
                );

            placeholderOption.value = '';

            placeholderOption.textContent =
                normalizedRecords.length > 0
                    ? placeholder
                    : 'No options available';

            placeholderOption.selected =
                String(selectedValue) === '';

            select.appendChild(
                placeholderOption
            );

            normalizedRecords.forEach(
                (record) => {
                    const option =
                        document
                            .createElement(
                                'option'
                            );

                    option.value =
                        record.value;

                    option.textContent =
                        record.label;

                    option.selected =
                        record.value
                        === String(
                            selectedValue
                        );

                    select.appendChild(
                        option
                    );
                }
            );

            select.disabled =
                normalizedRecords.length
                === 0;

            window.SelectChoice
                ?.create(select);
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

            window.SelectChoice
                ?.destroy(select);

            select.replaceChildren();

            const option =
                document.createElement(
                    'option'
                );

            option.value = '';
            option.textContent =
                placeholder;
            option.selected = true;

            select.appendChild(option);

            select.disabled = true;

            window.SelectChoice
                ?.create(select);
        };

        /**
         * Fetch dependent master options.
         *
         * @param {HTMLSelectElement} parentSelect
         * @param {HTMLSelectElement} childSelect
         * @param {string} placeholder
         * @param {string} selectedValue
         */
        const loadDependentOptions =
            async (
                parentSelect,
                childSelect,
                placeholder,
                selectedValue = ''
            ) => {
                if (
                    !parentSelect
                    || !childSelect
                ) {
                    return;
                }

                const parentId = String(
                    parentSelect.value
                    ?? ''
                ).trim();

                const urlTemplate =
                    String(
                        parentSelect
                            .dataset
                            .dependentUrlTemplate
                        ?? ''
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
                    || !urlTemplate.includes(
                        '__PARENT_ID__'
                    )
                ) {
                    console.error(
                        'Dependent master URL '
                        + 'template is missing '
                        + 'or invalid.'
                    );

                    resetDependentSelect(
                        childSelect,
                        placeholder
                    );

                    return;
                }

                const requestUrl =
                    urlTemplate.replace(
                        '__PARENT_ID__',
                        encodeURIComponent(
                            parentId
                        )
                    );

                childSelect.disabled = true;

                try {
                    const response =
                        await fetch(
                            requestUrl,
                            {
                                method: 'GET',

                                headers: {
                                    Accept:
                                        'application/json',

                                    'X-Requested-With':
                                        'XMLHttpRequest',
                                },

                                credentials:
                                    'same-origin',
                            }
                        );

                    if (!response.ok) {
                        throw new Error(
                            'Dependent master '
                            + 'request failed '
                            + 'with status '
                            + `${response.status}.`
                        );
                    }

                    const payload =
                        await response.json();

                    const records =
                        Array.isArray(
                            payload.data
                        )
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
                        'Unable to load '
                        + 'dependent master data.',
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
        if (
            stateSelect
            && citySelect
        ) {
            stateSelect.addEventListener(
                'change',
                () => {
                    citySelect.dataset
                        .selectedValue = '';

                    void loadDependentOptions(
                        stateSelect,
                        citySelect,
                        'Select city'
                    );
                }
            );

            const selectedCityId =
                String(
                    citySelect.dataset
                        .selectedValue
                    ?? ''
                ).trim();

            if (
                stateSelect.value !== ''
                && citySelect.options.length
                <= 1
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
 * Update the Family Details form CSRF token after
 * an AJAX POST.
 *
 * CSRF regeneration is enabled in this application,
 * therefore Verify receives a new token which must
 * replace the old form token before Save.
 *
 * @param {object} csrf
 */
        const updateCsrfToken = (csrf) => {
            if (
                !form
                || !csrf
                || typeof csrf !== 'object'
            ) {
                return;
            }

            const tokenName = String(
                csrf.name ?? ''
            ).trim();

            const tokenHash = String(
                csrf.hash ?? ''
            ).trim();

            if (
                tokenName === ''
                || tokenHash === ''
            ) {
                return;
            }

            const csrfInput =
                form.querySelector(
                    `input[name="${CSS.escape(
                        tokenName
                    )}"]`
                );

            if (csrfInput) {
                csrfInput.value =
                    tokenHash;
            }
        };

        /**
         * Clear the local verification state whenever the
         * entered Field Officer code changes.
         */
        fieldOfficerCodeInput
            ?.addEventListener(
                'input',
                () => {
                    if (
                        fieldOfficerCodeInput
                            .readOnly
                    ) {
                        return;
                    }

                    const normalizedCode =
                        String(
                            fieldOfficerCodeInput
                                .value
                            ?? ''
                        )
                            .toUpperCase()
                            .replace(
                                /\s+/g,
                                ''
                            );

                    fieldOfficerCodeInput.value =
                        normalizedCode;

                    verifiedFieldOfficerCode = '';

                    fieldOfficerCodeInput
                        .setCustomValidity('');

                    if (fieldOfficerNameInput) {
                        fieldOfficerNameInput.value =
                            '';
                    }

                    if (fieldOfficerMessage) {
                        fieldOfficerMessage
                            .textContent = '';

                        fieldOfficerMessage
                            .classList.remove(
                                'text-success',
                                'text-danger'
                            );
                    }
                }
            );

        /**
         * Verify Field Officer code.
         */
        verifyFieldOfficerButton
            ?.addEventListener(
                'click',
                async () => {
                    if (
                        !fieldOfficerCodeInput
                        || !form
                    ) {
                        return;
                    }

                    const code =
                        String(
                            fieldOfficerCodeInput
                                .value
                            ?? ''
                        )
                            .trim()
                            .toUpperCase();

                    verifiedFieldOfficerCode = '';

                    fieldOfficerCodeInput
                        .setCustomValidity('');

                    if (fieldOfficerNameInput) {
                        fieldOfficerNameInput.value =
                            '';
                    }

                    if (fieldOfficerMessage) {
                        fieldOfficerMessage
                            .classList.remove(
                                'text-success',
                                'text-danger'
                            );
                    }

                    if (code === '') {
                        fieldOfficerCodeInput
                            .setCustomValidity(
                                'Please enter a Field Officer ID.'
                            );

                        fieldOfficerCodeInput
                            .reportValidity();

                        return;
                    }

                    if (
                        !/^FOSAK[0-9]{6}$/.test(
                            code
                        )
                    ) {
                        fieldOfficerCodeInput
                            .setCustomValidity(
                                'Please enter a valid '
                                + 'Field Officer ID.'
                            );

                        fieldOfficerCodeInput
                            .reportValidity();

                        return;
                    }

                    const verificationUrl =
                        String(
                            fieldOfficerCodeInput
                                .dataset
                                .verifyUrl
                            ?? ''
                        ).trim();

                    if (verificationUrl === '') {
                        if (fieldOfficerMessage) {
                            fieldOfficerMessage
                                .textContent =
                                'Field Officer verification '
                                + 'is currently unavailable.';

                            fieldOfficerMessage
                                .classList.add(
                                    'text-danger'
                                );
                        }

                        return;
                    }

                    const csrfInput =
                        form.querySelector(
                            'input[name="sak_csrf_token"]'
                        );

                    if (!csrfInput) {
                        if (fieldOfficerMessage) {
                            fieldOfficerMessage
                                .textContent =
                                'Unable to verify Field Officer. '
                                + 'Please reload the page.';

                            fieldOfficerMessage
                                .classList.add(
                                    'text-danger'
                                );
                        }

                        return;
                    }

                    verifyFieldOfficerButton.disabled =
                        true;

                    verifyFieldOfficerButton
                        .setAttribute(
                            'aria-busy',
                            'true'
                        );

                    const originalLabel =
                        verifyFieldOfficerButton
                            .textContent;

                    verifyFieldOfficerButton.textContent =
                        'Verifying...';

                    if (fieldOfficerMessage) {
                        fieldOfficerMessage
                            .textContent =
                            'Verifying Field Officer...';
                    }

                    try {
                        const requestData =
                            new FormData();

                        requestData.append(
                            'code',
                            code
                        );

                        requestData.append(
                            csrfInput.name,
                            csrfInput.value
                        );

                        const response =
                            await fetch(
                                verificationUrl,
                                {
                                    method: 'POST',

                                    headers: {
                                        Accept:
                                            'application/json',

                                        'X-Requested-With':
                                            'XMLHttpRequest',
                                    },

                                    credentials:
                                        'same-origin',

                                    body:
                                        requestData,
                                }
                            );

                        const contentType =
                            response.headers.get(
                                'content-type'
                            ) ?? '';

                        if (
                            !contentType.includes(
                                'application/json'
                            )
                        ) {
                            throw new Error(
                                'Field Officer verification '
                                + 'request failed.'
                            );
                        }

                        const payload =
                            await response.json();

                        /*
                         * Update CSRF even when verification fails,
                         * because CI4 regenerated the token for the POST.
                         */
                        updateCsrfToken(
                            payload.csrf
                        );

                        if (
                            !response.ok
                            || payload.success
                            !== true
                        ) {
                            throw new Error(
                                String(
                                    payload.message
                                    ?? 'The Field Officer ID '
                                    + 'could not be verified.'
                                )
                            );
                        }

                        const verifiedCode =
                            String(
                                payload.data
                                    ?.officer_code
                                ?? ''
                            )
                                .trim()
                                .toUpperCase();

                        const officerName =
                            String(
                                payload.data
                                    ?.full_name
                                ?? ''
                            ).trim();

                        if (
                            verifiedCode === ''
                            || officerName === ''
                        ) {
                            throw new Error(
                                'The Field Officer could '
                                + 'not be verified.'
                            );
                        }

                        fieldOfficerCodeInput.value =
                            verifiedCode;

                        verifiedFieldOfficerCode =
                            verifiedCode;

                        fieldOfficerCodeInput
                            .setCustomValidity('');

                        if (fieldOfficerNameInput) {
                            fieldOfficerNameInput.value =
                                officerName;
                        }

                        if (fieldOfficerMessage) {
                            fieldOfficerMessage
                                .textContent =
                                'Field Officer verified successfully.';

                            fieldOfficerMessage
                                .classList.remove(
                                    'text-danger'
                                );

                            fieldOfficerMessage
                                .classList.add(
                                    'text-success'
                                );
                        }
                    } catch (error) {
                        fieldOfficerCodeInput
                            .setCustomValidity(
                                'Please verify a valid '
                                + 'Field Officer ID.'
                            );

                        if (fieldOfficerMessage) {
                            fieldOfficerMessage
                                .textContent =
                                error instanceof Error
                                    ? error.message
                                    : 'The Field Officer ID '
                                    + 'could not be verified.';

                            fieldOfficerMessage
                                .classList.remove(
                                    'text-success'
                                );

                            fieldOfficerMessage
                                .classList.add(
                                    'text-danger'
                                );
                        }
                    } finally {
                        verifyFieldOfficerButton.disabled =
                            false;

                        verifyFieldOfficerButton
                            .removeAttribute(
                                'aria-busy'
                            );

                        verifyFieldOfficerButton
                            .textContent =
                            originalLabel
                            ?? 'Verify';
                    }
                }
            );

        /**
         * Prevent an entered but unverified Field Officer ID
         * from being submitted.
         *
         * Empty remains valid because the field is optional.
         */
        form?.addEventListener(
            'submit',
            (event) => {
                if (
                    fieldOfficerCodeInput
                    && !fieldOfficerCodeInput
                        .readOnly
                ) {
                    const submittedCode =
                        String(
                            fieldOfficerCodeInput
                                .value
                            ?? ''
                        )
                            .trim()
                            .toUpperCase();

                    if (
                        submittedCode !== ''
                        && submittedCode
                        !== verifiedFieldOfficerCode
                    ) {
                        event.preventDefault();

                        fieldOfficerCodeInput
                            .setCustomValidity(
                                'Please verify the '
                                + 'Field Officer ID '
                                + 'before saving.'
                            );

                        fieldOfficerCodeInput
                            .reportValidity();

                        if (
                            fieldOfficerMessage
                        ) {
                            fieldOfficerMessage
                                .textContent =
                                'Verify the Field '
                                + 'Officer ID before '
                                + 'saving.';

                            fieldOfficerMessage
                                .classList
                                .remove(
                                    'text-success'
                                );

                            fieldOfficerMessage
                                .classList
                                .add(
                                    'text-danger'
                                );
                        }

                        return;
                    }

                    fieldOfficerCodeInput
                        .setCustomValidity(
                            ''
                        );
                }

                if (
                    !submitButton
                    || event.defaultPrevented
                    || !form.checkValidity()
                ) {
                    return;
                }

                window.setTimeout(
                    () => {
                        if (
                            event.defaultPrevented
                            || !form.checkValidity()
                        ) {
                            return;
                        }

                        submitButton.disabled =
                            true;

                        submitButton
                            .setAttribute(
                                'aria-busy',
                                'true'
                            );

                        submitButton
                            .querySelector(
                                '.registration-submit__label'
                            )
                            ?.classList.add(
                                'd-none'
                            );

                        submitButton
                            .querySelector(
                                '.registration-submit__loading'
                            )
                            ?.classList.remove(
                                'd-none'
                            );
                    },
                    0
                );
            }
        );
    }
);