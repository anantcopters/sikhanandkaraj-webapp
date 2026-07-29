'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById(
        'prelaunch-profile-form'
    );

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const choicesById = new Map();

    /**
     * Initialize all Choices dropdowns.
     */
    const initializeChoices = () => {
        if (typeof window.Choices !== 'function') {
            console.error(
                'Choices.js is not loaded.'
            );

            return;
        }

        const selects = document.querySelectorAll(
            'select.js-choice'
        );

        selects.forEach((select) => {
            if (
                !(select instanceof HTMLSelectElement)
                || select.id === ''
                || choicesById.has(select.id)
            ) {
                return;
            }

            const choice = new window.Choices(
                select,
                {
                    searchEnabled:
                        select.options.length > 8,

                    searchChoices: true,
                    shouldSort: false,
                    itemSelectText: '',
                    allowHTML: false,
                    placeholder: true,
                    removeItemButton: false,
                }
            );

            choicesById.set(
                select.id,
                choice
            );
        });
    };

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
     * Update a Choices-enabled select.
     *
     * @param {HTMLSelectElement} select
     * @param {Array<{id: string|number, name: string}>} items
     * @param {string} placeholder
     * @param {string} selectedValue
     */
    const updateSelectOptions = (
        select,
        items,
        placeholder,
        selectedValue = ''
    ) => {
        const normalizedChoices = [
            {
                value: '',
                label: placeholder,
                selected: selectedValue === '',
                disabled: false,
            },
        ];

        items.forEach((item) => {
            const itemId = String(
                item.id ?? ''
            );

            const itemName = String(
                item.name ?? ''
            ).trim();

            if (
                itemId === ''
                || itemName === ''
            ) {
                return;
            }

            normalizedChoices.push({
                value: itemId,
                label: itemName,
                selected:
                    itemId === selectedValue,
                disabled: false,
            });
        });

        const choice = choicesById.get(
            select.id
        );

        if (choice) {
            choice.clearStore();

            choice.setChoices(
                normalizedChoices,
                'value',
                'label',
                true
            );

            choice.enable();

            return;
        }

        select.innerHTML = '';

        normalizedChoices.forEach(
            (normalizedChoice) => {
                const option =
                    document.createElement(
                        'option'
                    );

                option.value =
                    normalizedChoice.value;

                option.textContent =
                    normalizedChoice.label;

                option.selected =
                    normalizedChoice.selected;

                select.appendChild(option);
            }
        );

        select.disabled = false;
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
     * Set a Choices dropdown value.
     *
     * @param {HTMLSelectElement} select
     * @param {string} value
     */
    const setChoiceValue = (
        select,
        value
    ) => {
        const choice = choicesById.get(
            select.id
        );

        if (choice) {
            choice.removeActiveItems();
            choice.setChoiceByValue(value);
        } else {
            select.value = value;
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
     * Show selected photo thumbnails.
     */
    const initializePhotoPreviews = () => {
        const photoInputs =
            document.querySelectorAll(
                '.js-photo-input'
            );

        photoInputs.forEach((photoInput) => {
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

                        return;
                    }

                    const supportedTypes = [
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                    ];

                    if (
                        !supportedTypes.includes(
                            selectedFile.type
                        )
                    ) {
                        photoInput.value = '';

                        previewImage.src = '';

                        previewImage.classList.add(
                            'd-none'
                        );

                        placeholder?.classList.remove(
                            'd-none'
                        );

                        window.alert(
                            'Please select a JPG, PNG or WebP image.'
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
        });
    };

    /**
     * Initialize Field Officer verification.
     */
    const initializeFieldOfficerVerification =
        () => {
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

            const resultContainer =
                document.getElementById(
                    'field-officer-result'
                );

            const saveButton =
                document.getElementById(
                    'save-prelaunch-profile'
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
                    resultContainer
                    instanceof HTMLElement
                )
                || !(
                    saveButton
                    instanceof HTMLButtonElement
                )
            ) {
                return;
            }

            const verifyLabel =
                document.getElementById(
                    'verify-field-officer-label'
                );

            const verifyLoading =
                document.getElementById(
                    'verify-field-officer-loading'
                );

            const officerName =
                document.getElementById(
                    'verified-field-officer-name'
                );

            const officerCode =
                document.getElementById(
                    'verified-field-officer-code'
                );

            const officerLocation =
                document.getElementById(
                    'verified-field-officer-location'
                );

            const resetVerification = () => {
                verifiedOfficerInput.value = '';
                saveButton.disabled = true;

                resultContainer.classList.add(
                    'd-none'
                );

                resultContainer.classList.remove(
                    'alert-success',
                    'alert-danger'
                );
            };

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
                        officerCodeInput
                            .value
                            .trim();

                    if (enteredCode.length < 4) {
                        resultContainer.classList.remove(
                            'd-none',
                            'alert-success',
                            'alert-light'
                        );

                        resultContainer.classList.add(
                            'alert-danger'
                        );

                        resultContainer.textContent =
                            'Please enter a valid Field Officer code.';

                        return;
                    }

                    const verificationUrl =
                        verifyButton.dataset
                            .verificationUrl;

                    if (!verificationUrl) {
                        return;
                    }

                    verifyButton.disabled = true;

                    verifyLabel?.classList.add(
                        'd-none'
                    );

                    verifyLoading?.classList.remove(
                        'd-none'
                    );

                    const requestBody =
                        new FormData();

                    requestBody.append(
                        'field_officer_code',
                        enteredCode
                    );

                    const csrfInput =
                        form.querySelector(
                            'input[type="hidden"][name^="csrf"]'
                        );

                    if (
                        csrfInput
                        instanceof HTMLInputElement
                    ) {
                        requestBody.append(
                            csrfInput.name,
                            csrfInput.value
                        );
                    }

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

                        const payload =
                            await response.json();

                        if (
                            csrfInput
                            instanceof HTMLInputElement
                            && payload.csrfName
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
                                || 'Field Officer verification failed.'
                            );
                        }

                        const officer =
                            payload.fieldOfficer
                            ?? {};

                        const verifiedId =
                            String(
                                officer.id
                                ?? ''
                            );

                        verifiedOfficerInput.value =
                            verifiedId;

                        if (officerName) {
                            officerName.textContent =
                                String(
                                    officer.fullName
                                    ?? 'Verified Field Officer'
                                );
                        }

                        if (officerCode) {
                            const verifiedCode =
                                String(
                                    officer.officerCode
                                    ?? enteredCode
                                );

                            officerCode.textContent =
                                `Code: ${verifiedCode}`;
                        }

                        if (officerLocation) {
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

                            officerLocation.textContent =
                                providedLocation
                                || generatedLocation;
                        }

                        resultContainer.classList.remove(
                            'd-none',
                            'alert-danger',
                            'alert-light'
                        );

                        resultContainer.classList.add(
                            'alert-success'
                        );

                        saveButton.disabled =
                            verifiedId === '';
                    } catch (error) {
                        verifiedOfficerInput.value =
                            '';

                        saveButton.disabled = true;

                        resultContainer.classList.remove(
                            'd-none',
                            'alert-success',
                            'alert-light'
                        );

                        resultContainer.classList.add(
                            'alert-danger'
                        );

                        const errorMessage =
                            error instanceof Error
                                ? error.message
                                : 'Field Officer verification failed.';

                        resultContainer.textContent =
                            errorMessage;
                    } finally {
                        verifyButton.disabled = false;

                        verifyLabel?.classList.remove(
                            'd-none'
                        );

                        verifyLoading?.classList.add(
                            'd-none'
                        );
                    }
                }
            );
        };

    initializeChoices();
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