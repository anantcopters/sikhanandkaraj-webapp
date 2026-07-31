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
 * Replace a dependent select's options and rebuild its global
 * Choices.js instance.
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
        /*
         * Choices creates its own DOM structure. Destroy it before changing
         * the original select to avoid stale or duplicated options.
         */
        if (
            window.SelectChoice
            && typeof window.SelectChoice.destroy
            === 'function'
        ) {
            window.SelectChoice.destroy(
                select
            );
        }

        select.replaceChildren();

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
            const itemId =
                String(
                    item.id
                    ?? ''
                ).trim();

            const itemName =
                String(
                    item.name
                    ?? item.label
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
                itemId === String(
                    selectedValue
                );

            select.appendChild(
                option
            );
        });

        select.disabled =
            items.length === 0;

        if (items.length === 0) {
            select.value = '';
        }

        if (
            window.SelectChoice
            && typeof window.SelectChoice.create
            === 'function'
        ) {
            window.SelectChoice.create(
                select
            );
        }
    };

    /**
     * Display a loading option in a dependent dropdown.
     *
     * @param {HTMLSelectElement} select
     *
     * @returns {void}
     */
    const setSelectLoading = (
        select
    ) => {
        if (
            window.SelectChoice
            && typeof window.SelectChoice.destroy
            === 'function'
        ) {
            window.SelectChoice.destroy(
                select
            );
        }

        select.replaceChildren();

        const loadingOption =
            document.createElement(
                'option'
            );

        loadingOption.value = '';
        loadingOption.textContent =
            'Loading...';

        loadingOption.selected = true;
        loadingOption.disabled = true;

        select.appendChild(
            loadingOption
        );

        select.disabled = true;

        if (
            window.SelectChoice
            && typeof window.SelectChoice.create
            === 'function'
        ) {
            window.SelectChoice.create(
                select
            );
        }
    };

    /**
     * Load a dependent select from a public JSON endpoint.
     *
     * @param {HTMLSelectElement} source
     * @param {HTMLSelectElement} target
     * @param {string} template
     * @param {string} placeholder
     *
     * @returns {void}
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

                /*
                 * Clear any previous dependent value immediately.
                 */
                if (selectedId === '') {
                    updateSelectOptions(
                        target,
                        [],
                        placeholder
                    );

                    return;
                }

                setSelectLoading(
                    target
                );

                try {
                    const requestUrl =
                        buildUrl(
                            template,
                            selectedId
                        );

                    const response =
                        await fetch(
                            requestUrl,
                            {
                                method: 'GET',
                                credentials:
                                    'same-origin',

                                headers: {
                                    Accept:
                                        'application/json',

                                    'X-Requested-With':
                                        'XMLHttpRequest'
                                }
                            }
                        );

                    let payload = {};

                    try {
                        payload =
                            await response.json();
                    } catch (parseError) {
                        throw new Error(
                            'The server returned an invalid response.'
                        );
                    }

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

                    const items =
                        Array.isArray(
                            payload.items
                        )
                            ? payload.items
                            : [];

                    updateSelectOptions(
                        target,
                        items,
                        items.length > 0
                            ? placeholder
                            : 'No options available'
                    );
                } catch (error) {
                    console.error(
                        'Dependent dropdown loading failed.',
                        error
                    );

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
 * Initialize native DOB validation, formatted preview and age display.
 *
 * The native date input always submits YYYY-MM-DD. A separate helper
 * displays the selected date as DD/MM/YYYY.
 *
 * @returns {void}
 */
    const initializeDateOfBirthAge = () => {
        const dateOfBirth =
            document.getElementById(
                'date_of_birth'
            );

        const datePreview =
            document.getElementById(
                'date-of-birth-preview'
            );

        const agePreview =
            document.getElementById(
                'member-age-preview'
            );

        const errorElement =
            document.getElementById(
                'date_of_birthError'
            );

        if (
            !(
                dateOfBirth
                instanceof HTMLInputElement
            )
            || !(
                datePreview
                instanceof HTMLElement
            )
            || !(
                agePreview
                instanceof HTMLElement
            )
        ) {
            return;
        }

        const minimumAge =
            Number.parseInt(
                dateOfBirth.dataset
                    .minimumAge
                ?? '18',
                10
            );

        /**
         * Format a Date object as YYYY-MM-DD using local values.
         *
         * @param {Date} date
         *
         * @returns {string}
         */
        const formatIsoDate = (
            date
        ) => {
            const year =
                String(
                    date.getFullYear()
                );

            const month =
                String(
                    date.getMonth() + 1
                ).padStart(
                    2,
                    '0'
                );

            const day =
                String(
                    date.getDate()
                ).padStart(
                    2,
                    '0'
                );

            return `${year}-${month}-${day}`;
        };

        /**
         * Convert YYYY-MM-DD into a real local Date without UTC shifts.
         *
         * @param {string} value
         *
         * @returns {Date|null}
         */
        const parseIsoDate = (
            value
        ) => {
            const match =
                /^(\d{4})-(\d{2})-(\d{2})$/
                    .exec(value);

            if (!match) {
                return null;
            }

            const year =
                Number(match[1]);

            const month =
                Number(match[2]);

            const day =
                Number(match[3]);

            const parsedDate =
                new Date(
                    year,
                    month - 1,
                    day
                );

            const isValidDate =
                parsedDate.getFullYear()
                === year
                && parsedDate.getMonth()
                === month - 1
                && parsedDate.getDate()
                === day;

            return isValidDate
                ? parsedDate
                : null;
        };

        /**
         * Convert a Date object to DD/MM/YYYY.
         *
         * @param {Date} date
         *
         * @returns {string}
         */
        const formatDisplayDate = (
            date
        ) => {
            const day =
                String(
                    date.getDate()
                ).padStart(
                    2,
                    '0'
                );

            const month =
                String(
                    date.getMonth() + 1
                ).padStart(
                    2,
                    '0'
                );

            const year =
                String(
                    date.getFullYear()
                );

            return `${day}/${month}/${year}`;
        };

        /**
         * Calculate completed years of age.
         *
         * @param {Date} birthDate
         *
         * @returns {number}
         */
        const calculateAge = (
            birthDate
        ) => {
            const today =
                new Date();

            let age =
                today.getFullYear()
                - birthDate.getFullYear();

            const monthDifference =
                today.getMonth()
                - birthDate.getMonth();

            if (
                monthDifference < 0
                || (
                    monthDifference === 0
                    && today.getDate()
                    < birthDate.getDate()
                )
            ) {
                age--;
            }

            return age;
        };

        /**
         * Return the latest DOB allowed for the minimum age.
         *
         * @returns {Date}
         */
        const getLatestEligibleBirthDate =
            () => {
                const today =
                    new Date();

                return new Date(
                    today.getFullYear()
                    - minimumAge,
                    today.getMonth(),
                    today.getDate()
                );
            };

        /**
         * Clear DOB-specific validation.
         *
         * @returns {void}
         */
        const clearDateError = () => {
            dateOfBirth.setCustomValidity(
                ''
            );

            dateOfBirth.classList.remove(
                'is-invalid'
            );

            dateOfBirth.removeAttribute(
                'aria-invalid'
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
         * Display a DOB validation error.
         *
         * @param {string} message
         *
         * @returns {void}
         */
        const showDateError = (
            message
        ) => {
            dateOfBirth.setCustomValidity(
                message
            );

            dateOfBirth.classList.add(
                'is-invalid'
            );

            dateOfBirth.setAttribute(
                'aria-invalid',
                'true'
            );

            datePreview.textContent = '';
            agePreview.textContent = '';

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
        };

        /**
         * Validate DOB and refresh its formatted date and age.
         *
         * @returns {boolean}
         */
        const validateAndDisplayAge =
            () => {
                const selectedValue =
                    dateOfBirth.value
                        .trim();

                datePreview.textContent = '';
                agePreview.textContent = '';

                /*
                 * Required validation remains with the shared form
                 * validator and native browser constraint validation.
                 */
                if (selectedValue === '') {
                    clearDateError();

                    return false;
                }

                const birthDate =
                    parseIsoDate(
                        selectedValue
                    );

                if (
                    !(
                        birthDate
                        instanceof Date
                    )
                ) {
                    showDateError(
                        'Please enter a valid date of birth.'
                    );

                    return false;
                }

                const latestEligibleDate =
                    getLatestEligibleBirthDate();

                if (
                    birthDate
                    > latestEligibleDate
                ) {
                    showDateError(
                        `The member must be at least ${minimumAge} years old.`
                    );

                    return false;
                }

                clearDateError();

                datePreview.textContent =
                    `Selected date: ${formatDisplayDate(
                        birthDate
                    )
                    }`;

                agePreview.textContent =
                    `Current age: ${calculateAge(
                        birthDate
                    )
                    } years`;

                return true;
            };

        /*
         * Synchronize the native maximum date with the user's local date.
         */
        dateOfBirth.max =
            formatIsoDate(
                getLatestEligibleBirthDate()
            );

        dateOfBirth.addEventListener(
            'change',
            validateAndDisplayAge
        );

        dateOfBirth.addEventListener(
            'input',
            () => {
                if (
                    dateOfBirth.value === ''
                ) {
                    clearDateError();

                    datePreview.textContent =
                        '';

                    agePreview.textContent =
                        '';

                    return;
                }

                validateAndDisplayAge();
            }
        );

        /*
         * Restore previews after a server-validation redirect.
         */
        if (
            dateOfBirth.value !== ''
        ) {
            validateAndDisplayAge();
        }

        /*
         * Final DOB verification before form submission.
         */
        form.addEventListener(
            'submit',
            (event) => {
                if (
                    dateOfBirth.value !== ''
                    && !validateAndDisplayAge()
                ) {
                    event.preventDefault();

                    dateOfBirth.focus();
                }
            },
            true
        );
    };

    /**
 * Initialize Profile Created For and Gender dependency.
 *
 * Behaviour follows the homepage registration flow:
 *
 * - Show Gender where it must be selected manually.
 * - Hide Gender where the selected relationship determines it.
 * - Automatically assign Male/Female for deterministic relationships.
 *
 * The original select receives native change events from Choices.js,
 * so no Choices-specific event binding is required.
 *
 * @returns {void}
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

            const genderContainer =
                document.getElementById(
                    'gender-container'
                );

            const genderError =
                document.getElementById(
                    'genderError'
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
                || !(
                    genderContainer
                    instanceof HTMLElement
                )
            ) {
                return;
            }

            /**
             * Relationships which determine gender without requiring
             * another user selection.
             *
             * @type {Object<string, string>}
             */
            const fixedGenderByRelationship = {
                SON: 'MALE',
                BROTHER: 'MALE',
                DAUGHTER: 'FEMALE',
                SISTER: 'FEMALE',
            };

            /**
             * Clear the reusable Gender validation state.
             *
             * @returns {void}
             */
            const clearGenderValidation = () => {
                gender.setCustomValidity(
                    ''
                );

                gender.classList.remove(
                    'is-invalid'
                );

                gender.removeAttribute(
                    'aria-invalid'
                );

                if (
                    genderError
                    instanceof HTMLElement
                ) {
                    genderError.textContent = '';

                    genderError.classList.remove(
                        'd-block'
                    );
                }

                /*
                 * Choices.js renders a separate visual container.
                 * Refresh it after removing validation classes.
                 */
                if (
                    window.SelectChoice
                    && typeof window.SelectChoice
                        .refresh
                    === 'function'
                ) {
                    window.SelectChoice.refresh(
                        gender
                    );
                }
            };

            /**
             * Set the Gender value and refresh the reusable Choices
             * component.
             *
             * @param {string} value
             *
             * @returns {void}
             */
            const setGenderValue = (
                value
            ) => {
                gender.value = value;

                if (
                    window.SelectChoice
                    && typeof window.SelectChoice
                        .refresh
                    === 'function'
                ) {
                    window.SelectChoice.refresh(
                        gender
                    );
                }

                gender.dispatchEvent(
                    new Event(
                        'change',
                        {
                            bubbles: true,
                        }
                    )
                );
            };

            /**
             * Synchronize the Gender field with the selected relationship.
             *
             * @returns {void}
             */
            const updateGenderState = () => {
                const selectedRelationship =
                    profileCreatedFor.value
                        .trim()
                        .toUpperCase();

                const fixedGender =
                    fixedGenderByRelationship[
                    selectedRelationship
                    ]
                    ?? '';

                const requiresManualGender =
                    fixedGender === '';

                genderContainer.classList.toggle(
                    'd-none',
                    !requiresManualGender
                );

                gender.required =
                    requiresManualGender;

                gender.disabled = false;

                if (fixedGender !== '') {
                    clearGenderValidation();

                    setGenderValue(
                        fixedGender
                    );

                    return;
                }

                /*
                 * On first page load or after server validation failure,
                 * preserve the old Gender selection for Self, Relative or
                 * Friend. Only clear an automatically assigned value when
                 * the relationship changes interactively.
                 */
                clearGenderValidation();
            };

            profileCreatedFor.addEventListener(
                'change',
                () => {
                    const selectedRelationship =
                        profileCreatedFor.value
                            .trim()
                            .toUpperCase();

                    const fixedGender =
                        fixedGenderByRelationship[
                        selectedRelationship
                        ]
                        ?? '';

                    if (fixedGender === '') {
                        /*
                         * A relationship such as Self, Relative or Friend
                         * needs a fresh explicit Gender selection.
                         */
                        setGenderValue(
                            ''
                        );
                    }

                    updateGenderState();
                }
            );

            /*
             * Restore the correct state after refresh, old input, or a
             * server-side validation failure.
             */
            updateGenderState();
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
            Number.parseInt(
                photoInputs.dataset
                    .maximumFileSize
                ?? '18874368',
                10
            );

        const maximumFileSizeLabel =
            photoInputs.dataset
                .maximumFileSizeLabel
            ?? '18 MB';

        if (
            Number.isFinite(maximumFileSize)
            && maximumFileSize > 0
            && selectedFile.size
            > maximumFileSize
        ) {
            photoInputs.setCustomValidity(
                `The selected photograph must not exceed ${maximumFileSizeLabel}.`
            );

            /*
             * Clear the rejected file so it cannot be submitted.
             */
            photoInputs.value = '';

            return;
        }

        /**
         * Ask the global form validator to refresh one file field.
         *
         * This is required after setting a custom validity message because
         * the additional photograph validation runs after the validator's
         * native change handler.
         *
         * @param {HTMLInputElement} photoInput
         *
         * @returns {void}
         */
        const refreshPhotoValidation = (
            photoInput
        ) => {
            photoInput.dispatchEvent(
                new CustomEvent(
                    'app:validate-field',
                    {
                        bubbles: false,
                    }
                )
            );
        };

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
                            `The selected photograph must not exceed ${maximumFileSizeLabel}.`
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

                            /*
                             * Validate the required file field after the user cancels
                             * or clears the file selection.
                             */
                            refreshPhotoValidation(
                                photoInput
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

                            /*
                            * Render the custom validation message through the global
                            * form validator.
                            */
                            refreshPhotoValidation(
                                photoInput
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

                            /*
                             * Clear the rejected file so it cannot be submitted.
                             */
                            photoInput.value = '';

                            previewImage.src = '';

                            previewImage.classList.add(
                                'd-none'
                            );

                            placeholder?.classList.remove(
                                'd-none'
                            );

                            /*
                             * The custom validity was applied after the native change
                             * validation ran, so explicitly refresh the field state.
                             */
                            refreshPhotoValidation(
                                photoInput
                            );

                            return;
                        }

                        const reader =
                            new FileReader();

                        reader.addEventListener(
                            'load',
                            () => {
                                previewImage.src =
                                    typeof reader.result === 'string'
                                        ? reader.result
                                        : '';

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
    initializeDateOfBirthAge();
    initializePhotoPreviews();
    initializeFieldOfficerVerification();

    /**
 * Initialize State → City dependency.
 */
    const stateSelect =
        document.getElementById(
            'state_id'
        );

    const citySelect =
        document.getElementById(
            'city_id'
        );

    if (
        stateSelect
        instanceof HTMLSelectElement
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
});