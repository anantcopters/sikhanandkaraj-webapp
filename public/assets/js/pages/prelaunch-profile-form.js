'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById(
        'prelaunch-profile-form'
    );

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    /**
     * Create the public endpoint URL using the selected ID.
     *
     * @param {string} template
     * @param {string} selectedId
     * @returns {string}
     */
    const buildUrl = (
        template,
        selectedId
    ) => {
        return template.replace(
            /\/0$/,
            `/${encodeURIComponent(selectedId)}`
        );
    };

    /**
 * Replace a dependent select's options and refresh
 * the existing global Choices.js component.
 *
 * @param {HTMLSelectElement} select
 * @param {Array<{id: string|number, name: string}>} items
 * @param {string} placeholder
 * @param {string} selectedValue
 *
 * @returns {void}
 */
    const updateSelectOptions = (
        select,
        items,
        placeholder,
        selectedValue = ''
    ) => {
        select.innerHTML = '';

        const placeholderOption =
            document.createElement(
                'option'
            );

        placeholderOption.value = '';
        placeholderOption.textContent =
            placeholder;

        placeholderOption.selected =
            selectedValue === '';

        select.appendChild(
            placeholderOption
        );

        items.forEach((item) => {
            const itemId = String(
                item.id
                ?? ''
            );

            const itemName = String(
                item.name
                ?? ''
            ).trim();

            if (
                itemId === ''
                || itemName === ''
            ) {
                return;
            }

            const option =
                document.createElement(
                    'option'
                );

            option.value =
                itemId;

            option.textContent =
                itemName;

            option.selected =
                itemId === selectedValue;

            select.appendChild(
                option
            );
        });

        select.disabled = false;

        if (
            window.SelectChoice
            && typeof window.SelectChoice.refresh
            === 'function'
        ) {
            window.SelectChoice.refresh(
                select
            );
        }
    };

    /**
     * Display loading state in a dependent dropdown.
     *
     * @param {HTMLSelectElement} select
     */
    const setSelectLoading = (select) => {
        const loadingChoices = [
            {
                value: '',
                label: 'Loading...',
                selected: true,
                disabled: true,
            },
        ];

        const choice = choicesById.get(
            select.id
        );

        if (choice) {
            choice.clearStore();

            choice.setChoices(
                loadingChoices,
                'value',
                'label',
                true
            );

            choice.disable();

            return;
        }

        select.innerHTML = '';

        const loadingOption =
            document.createElement('option');

        loadingOption.value = '';
        loadingOption.textContent =
            'Loading...';

        select.appendChild(
            loadingOption
        );

        select.disabled = true;
    };

    /**
     * Bind a source dropdown to its dependent dropdown.
     *
     * @param {HTMLSelectElement} source
     * @param {HTMLSelectElement} target
     * @param {string} template
     * @param {string} placeholder
     */
    const bindDependentSelect = (
        source,
        target,
        template,
        placeholder
    ) => {
        source.addEventListener(
            'change',
            async () => {
                const selectedId =
                    source.value.trim();

                if (selectedId === '') {
                    updateSelectOptions(
                        target,
                        [],
                        placeholder
                    );

                    return;
                }

                setSelectLoading(target);

                try {
                    const requestUrl = buildUrl(
                        template,
                        selectedId
                    );

                    const response = await fetch(
                        requestUrl,
                        {
                            method: 'GET',
                            credentials:
                                'same-origin',

                            headers: {
                                Accept:
                                    'application/json',

                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },
                        }
                    );

                    const payload =
                        await response.json();

                    if (
                        !response.ok
                        || payload.successful
                        !== true
                    ) {
                        throw new Error(
                            payload.message
                            || 'Options could not be loaded.'
                        );
                    }

                    const items = Array.isArray(
                        payload.items
                    )
                        ? payload.items
                        : [];

                    updateSelectOptions(
                        target,
                        items,
                        placeholder
                    );
                } catch (error) {
                    console.error(error);

                    updateSelectOptions(
                        target,
                        [],
                        'Unable to load options'
                    );
                }
            }
        );
    };

    /**
     * Set a select value and refresh the global Choices instance.
     *
     * @param {HTMLSelectElement} select
     * @param {string} value
     *
     * @returns {void}
     */
    const setChoiceValue = (
        select,
        value
    ) => {
        select.value = value;

        if (
            window.SelectChoice
            && typeof window.SelectChoice.refresh
            === 'function'
        ) {
            window.SelectChoice.refresh(
                select
            );
        }

        select.dispatchEvent(
            new Event(
                'change',
                {
                    bubbles: true,
                }
            )
        );
    };

    /**
     * Apply registration-style dependency between
     * Profile Created For and Gender.
     */
    const initializeProfileGenderDependency =
        () => {
            const profileCreatedFor =
                document.getElementById(
                    'profile_created_for'
                );

            const gender =
                document.getElementById(
                    'gender'
                );

            if (
                !(
                    profileCreatedFor
                    instanceof HTMLSelectElement
                )
                || !(
                    gender
                    instanceof HTMLSelectElement
                )
            ) {
                return;
            }

            profileCreatedFor.addEventListener(
                'change',
                () => {
                    const selectedValue =
                        profileCreatedFor.value;

                    if (
                        selectedValue === 'SON'
                        || selectedValue
                        === 'BROTHER'
                    ) {
                        setChoiceValue(
                            gender,
                            'MALE'
                        );

                        return;
                    }

                    if (
                        selectedValue
                        === 'DAUGHTER'
                        || selectedValue
                        === 'SISTER'
                    ) {
                        setChoiceValue(
                            gender,
                            'FEMALE'
                        );
                    }
                }
            );

            gender.addEventListener(
                'change',
                () => {
                    const profileValue =
                        profileCreatedFor.value;

                    const genderValue =
                        gender.value;

                    const maleConflict =
                        genderValue === 'MALE'
                        && (
                            profileValue
                            === 'DAUGHTER'
                            || profileValue
                            === 'SISTER'
                        );

                    const femaleConflict =
                        genderValue === 'FEMALE'
                        && (
                            profileValue === 'SON'
                            || profileValue
                            === 'BROTHER'
                        );

                    if (
                        maleConflict
                        || femaleConflict
                    ) {
                        setChoiceValue(
                            profileCreatedFor,
                            ''
                        );
                    }
                }
            );
        };

    /**
 * Initialize photo previews and client-side photo validation.
 *
 * @returns {void}
 */
    const initializePhotoPreviews = () => {
        const photoInputs =
            document.querySelectorAll(
                '.js-photo-input'
            );

        const supportedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];

        const maximumFileSize =
            5 * 1024 * 1024;

        photoInputs.forEach(
            (photoInput) => {
                if (
                    !(
                        photoInput
                        instanceof HTMLInputElement
                    )
                ) {
                    return;
                }

                photoInput.addEventListener(
                    'change',
                    () => {
                        photoInput.setCustomValidity(
                            ''
                        );

                        const previewTargetId =
                            photoInput.dataset
                                .previewTarget;

                        if (!previewTargetId) {
                            return;
                        }

                        const previewImage =
                            document.getElementById(
                                previewTargetId
                            );

                        const placeholder =
                            document.getElementById(
                                `${previewTargetId}-placeholder`
                            );

                        if (
                            !(
                                previewImage
                                instanceof HTMLImageElement
                            )
                        ) {
                            return;
                        }

                        const selectedFile =
                            photoInput.files
                                ? photoInput.files[0]
                                : null;

                        if (!selectedFile) {
                            previewImage.src = '';

                            previewImage.classList.add(
                                'd-none'
                            );

                            placeholder?.classList.remove(
                                'd-none'
                            );

                            photoInput.dispatchEvent(
                                new Event(
                                    'blur',
                                    {
                                        bubbles: true,
                                    }
                                )
                            );

                            return;
                        }

                        if (
                            !supportedTypes.includes(
                                selectedFile.type
                            )
                        ) {
                            photoInput.setCustomValidity(
                                'Please select a JPG, PNG or WebP image.'
                            );

                            photoInput.value = '';
                            previewImage.src = '';

                            previewImage.classList.add(
                                'd-none'
                            );

                            placeholder?.classList.remove(
                                'd-none'
                            );

                            photoInput.dispatchEvent(
                                new Event(
                                    'blur',
                                    {
                                        bubbles: true,
                                    }
                                )
                            );

                            return;
                        }

                        if (
                            selectedFile.size
                            > maximumFileSize
                        ) {
                            photoInput.setCustomValidity(
                                'The selected photograph must not exceed 5 MB.'
                            );

                            photoInput.value = '';
                            previewImage.src = '';

                            previewImage.classList.add(
                                'd-none'
                            );

                            placeholder?.classList.remove(
                                'd-none'
                            );

                            photoInput.dispatchEvent(
                                new Event(
                                    'blur',
                                    {
                                        bubbles: true,
                                    }
                                )
                            );

                            return;
                        }

                        const reader =
                            new FileReader();

                        reader.addEventListener(
                            'load',
                            () => {
                                const result =
                                    String(
                                        reader.result
                                        ?? ''
                                    );

                                previewImage.src =
                                    result;

                                previewImage.classList.remove(
                                    'd-none'
                                );

                                placeholder?.classList.add(
                                    'd-none'
                                );
                            }
                        );

                        reader.readAsDataURL(
                            selectedFile
                        );
                    }
                );
            }
        );
    };

    /**
 * Initialize explicit Field Officer verification.
 *
 * Verification errors are displayed through the standard inline
 * validation container. The larger result panel is reserved only
 * for successful verification.
 *
 * @returns {void}
 */
    const initializeFieldOfficerVerification = () => {
        const verifyButton =
            document.getElementById(
                'verify-field-officer'
            );

        const officerCodeInput =
            document.getElementById(
                'field_officer_code'
            );

        const verifiedOfficerInput =
            document.getElementById(
                'verified_field_officer_id'
            );

        const resultColumn =
            document.getElementById(
                'field-officer-result-column'
            );

        const resultContainer =
            document.getElementById(
                'field-officer-result'
            );

        const resultIcon =
            document.getElementById(
                'field-officer-result-icon'
            );

        const resultMessage =
            document.getElementById(
                'field-officer-result-message'
            );

        const resultCode =
            document.getElementById(
                'verified-field-officer-code'
            );

        const resultLocation =
            document.getElementById(
                'verified-field-officer-location'
            );

        const errorElement =
            document.getElementById(
                'field_officer_codeError'
            );

        const verifyLabel =
            document.getElementById(
                'verify-field-officer-label'
            );

        const verifyLoading =
            document.getElementById(
                'verify-field-officer-loading'
            );

        if (
            !(
                verifyButton
                instanceof HTMLButtonElement
            )
            || !(
                officerCodeInput
                instanceof HTMLInputElement
            )
            || !(
                verifiedOfficerInput
                instanceof HTMLInputElement
            )
            || !(
                resultColumn
                instanceof HTMLElement
            )
            || !(
                resultContainer
                instanceof HTMLElement
            )
            || !(
                resultMessage
                instanceof HTMLElement
            )
            || !(
                resultCode
                instanceof HTMLElement
            )
            || !(
                resultLocation
                instanceof HTMLElement
            )
        ) {
            return;
        }

        /**
         * Hide the successful verification panel.
         *
         * @returns {void}
         */
        const clearResult = () => {
            resultMessage.textContent = '';
            resultCode.textContent = '';
            resultLocation.textContent = '';

            resultColumn.classList.add(
                'd-none'
            );

            resultContainer.classList.remove(
                'alert-danger'
            );

            resultContainer.classList.add(
                'alert-success'
            );
        };

        /**
         * Remove verification-specific inline errors.
         *
         * Native required, pattern and length validation remain active.
         *
         * @returns {void}
         */
        const clearVerificationError = () => {
            officerCodeInput.setCustomValidity(
                ''
            );

            officerCodeInput.classList.remove(
                'is-invalid'
            );

            if (
                errorElement
                instanceof HTMLElement
            ) {
                errorElement.textContent = '';

                errorElement.classList.remove(
                    'd-block'
                );
            }
        };

        /**
         * Reset verification after the entered code changes.
         *
         * This intentionally does not mark the textbox invalid.
         *
         * @returns {void}
         */
        const resetVerification = () => {
            verifiedOfficerInput.value = '';

            clearVerificationError();
            clearResult();
        };

        /**
         * Display an inline verification error.
         *
         * The success panel is not used for errors because that would
         * duplicate the same message and enlarge the layout.
         *
         * @param {string} message
         *
         * @returns {void}
         */
        const showVerificationError = (
            message
        ) => {
            verifiedOfficerInput.value = '';

            clearResult();

            officerCodeInput.setCustomValidity(
                message
            );

            officerCodeInput.classList.add(
                'is-invalid'
            );

            if (
                errorElement
                instanceof HTMLElement
            ) {
                errorElement.textContent =
                    message;

                errorElement.classList.add(
                    'd-block'
                );
            }

            officerCodeInput.focus();
        };

        /**
         * Display successfully verified Field Officer details.
         *
         * @param {Object} officer
         * @param {string} enteredCode
         *
         * @returns {void}
         */
        const showVerificationSuccess = (
            officer,
            enteredCode
        ) => {
            const fullName =
                String(
                    officer.fullName
                    ?? 'Verified Field Officer'
                ).trim();

            const officerCode =
                String(
                    officer.officerCode
                    ?? enteredCode
                ).trim();

            const providedLocation =
                String(
                    officer.location
                    ?? ''
                ).trim();

            const generatedLocation = [
                officer.cityName,
                officer.stateName,
                officer.countryName,
            ]
                .filter(Boolean)
                .join(', ');

            clearVerificationError();

            resultMessage.textContent =
                fullName !== ''
                    ? fullName
                    : 'Verified Field Officer';

            resultCode.textContent =
                `Code: ${officerCode}`;

            resultLocation.textContent =
                providedLocation
                || generatedLocation;

            if (
                resultIcon
                instanceof HTMLElement
            ) {
                resultIcon.className =
                    'ri-checkbox-circle-line';
            }

            resultContainer.classList.remove(
                'alert-danger'
            );

            resultContainer.classList.add(
                'alert-success'
            );

            resultColumn.classList.remove(
                'd-none'
            );
        };

        /**
         * Enable verification-button loading state.
         *
         * @returns {void}
         */
        const showVerificationLoader = () => {
            verifyButton.disabled = true;

            verifyButton.setAttribute(
                'aria-disabled',
                'true'
            );

            verifyLabel?.classList.add(
                'd-none'
            );

            verifyLabel?.setAttribute(
                'aria-hidden',
                'true'
            );

            verifyLoading?.classList.remove(
                'd-none'
            );

            verifyLoading?.setAttribute(
                'aria-hidden',
                'false'
            );
        };

        /**
         * Restore verification button.
         *
         * @returns {void}
         */
        const hideVerificationLoader = () => {
            verifyButton.disabled = false;

            verifyButton.removeAttribute(
                'aria-disabled'
            );

            verifyLabel?.classList.remove(
                'd-none'
            );

            verifyLabel?.setAttribute(
                'aria-hidden',
                'false'
            );

            verifyLoading?.classList.add(
                'd-none'
            );

            verifyLoading?.setAttribute(
                'aria-hidden',
                'true'
            );
        };

        /*
         * Do not apply custom validity during page initialization.
         * The user must be allowed to enter and leave the field without
         * receiving a premature "Please verify" message.
         */
        verifiedOfficerInput.value = '';
        clearResult();

        officerCodeInput.addEventListener(
            'input',
            () => {
                officerCodeInput.value =
                    officerCodeInput.value
                        .toUpperCase();

                resetVerification();
            }
        );

        verifyButton.addEventListener(
            'click',
            async () => {
                const enteredCode =
                    officerCodeInput.value
                        .trim();

                /*
                 * Remove a previous server-verification error before
                 * checking normal HTML constraints.
                 */
                clearVerificationError();

                if (
                    !officerCodeInput
                        .checkValidity()
                ) {
                    officerCodeInput.dispatchEvent(
                        new Event(
                            'blur',
                            {
                                bubbles: true,
                            }
                        )
                    );

                    officerCodeInput.focus();

                    return;
                }

                const verificationUrl =
                    verifyButton.dataset
                        .verificationUrl
                    ?? '';

                if (verificationUrl === '') {
                    showVerificationError(
                        'Field Officer verification is currently unavailable.'
                    );

                    return;
                }

                const csrfInput =
                    document.getElementById(
                        'prelaunch-csrf-token'
                    );

                if (
                    !(
                        csrfInput
                        instanceof HTMLInputElement
                    )
                    || csrfInput.name === ''
                    || csrfInput.value === ''
                ) {
                    showVerificationError(
                        'The security token is unavailable. Please refresh the page and try again.'
                    );

                    return;
                }

                const requestBody =
                    new FormData();

                requestBody.append(
                    'field_officer_code',
                    enteredCode
                );

                requestBody.append(
                    csrfInput.name,
                    csrfInput.value
                );

                showVerificationLoader();

                try {
                    const response =
                        await fetch(
                            verificationUrl,
                            {
                                method: 'POST',
                                body: requestBody,
                                credentials:
                                    'same-origin',

                                headers: {
                                    Accept:
                                        'application/json',

                                    'X-Requested-With':
                                        'XMLHttpRequest',
                                },
                            }
                        );

                    let payload = {};

                    try {
                        payload =
                            await response.json();
                    } catch (parseError) {
                        throw new Error(
                            'The Field Officer verification service returned an invalid response.'
                        );
                    }

                    if (
                        payload.csrfName
                        && payload.csrfHash
                    ) {
                        csrfInput.name =
                            String(
                                payload.csrfName
                            );

                        csrfInput.value =
                            String(
                                payload.csrfHash
                            );
                    }

                    if (
                        !response.ok
                        || payload.successful
                        !== true
                    ) {
                        throw new Error(
                            payload.message
                            || 'The Field Officer code is invalid or inactive.'
                        );
                    }

                    const officer =
                        payload.fieldOfficer
                        ?? {};

                    const verifiedId =
                        String(
                            officer.id
                            ?? ''
                        ).trim();

                    if (verifiedId === '') {
                        throw new Error(
                            'Field Officer verification returned an invalid response.'
                        );
                    }

                    verifiedOfficerInput.value =
                        verifiedId;

                    showVerificationSuccess(
                        officer,
                        enteredCode
                    );
                } catch (error) {
                    const message =
                        error instanceof Error
                            ? error.message
                            : 'Field Officer verification failed.';

                    showVerificationError(
                        message
                    );
                } finally {
                    hideVerificationLoader();
                }
            }
        );

        /**
         * Verification must be complete before final form submission.
         *
         * This check runs only when the user actually attempts to save,
         * rather than whenever the textbox loses focus.
         */
        form.addEventListener(
            'submit',
            (event) => {
                const enteredCode =
                    officerCodeInput.value
                        .trim();

                if (
                    enteredCode !== ''
                    && verifiedOfficerInput
                        .value
                        .trim() === ''
                ) {
                    event.preventDefault();

                    showVerificationError(
                        'Please verify the Field Officer code before saving the profile.'
                    );
                }
            },
            true
        );
    };

    initializeProfileGenderDependency();
    initializePhotoPreviews();
    initializeFieldOfficerVerification();

    const stateSelect =
        document.getElementById('state_id');

    const citySelect =
        document.getElementById('city_id');

    if (
        stateSelect instanceof HTMLSelectElement
        && citySelect
        instanceof HTMLSelectElement
    ) {
        const cityUrlTemplate =
            stateSelect.dataset
                .cityUrlTemplate
            ?? '';

        if (cityUrlTemplate !== '') {
            bindDependentSelect(
                stateSelect,
                citySelect,
                cityUrlTemplate,
                'Select city'
            );
        }
    }

    const communitySelect =
        document.getElementById(
            'sikh_community_id'
        );

    const subcommunitySelect =
        document.getElementById(
            'sikh_subcommunity_id'
        );

    if (
        communitySelect
        instanceof HTMLSelectElement
        && subcommunitySelect
        instanceof HTMLSelectElement
    ) {
        const subcommunityUrlTemplate =
            communitySelect.dataset
                .subcommunityUrlTemplate
            ?? '';

        if (
            subcommunityUrlTemplate !== ''
        ) {
            bindDependentSelect(
                communitySelect,
                subcommunitySelect,
                subcommunityUrlTemplate,
                'Select sub-community'
            );
        }
    }
});