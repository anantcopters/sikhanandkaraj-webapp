'use strict';

document.addEventListener('DOMContentLoaded', () => {
    /**
     * Determine whether a field currently contains an error rendered
     * by the server after a validation redirect.
     *
     * Server errors remain authoritative until the user changes the
     * corresponding field.
     *
     * @param {HTMLElement} field
     * @returns {boolean}
     */
    const hasServerValidationError = (
        field
    ) => {
        if (
            !(
                field
                instanceof HTMLInputElement
                || field
                instanceof HTMLSelectElement
                || field
                instanceof HTMLTextAreaElement
            )
        ) {
            return false;
        }

        if (
            !field.classList.contains(
                'is-invalid'
            )
        ) {
            return false;
        }

        const fieldName =
            field.name.trim();

        if (fieldName === '') {
            return false;
        }

        const errorElement =
            form.querySelector(
                `[data-validation-error="${CSS.escape(
                    fieldName
                )}"]`
            );

        return (
            errorElement
            instanceof HTMLElement
            && errorElement.textContent
                .trim() !== ''
        );
    };

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
        let activeController = null;

        source.addEventListener(
            'change',
            async () => {
                if (activeController) {
                    activeController.abort();
                }

                activeController = new AbortController();

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
                                signal: activeController.signal,
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
                    if (error?.name === 'AbortError') {
                        return;
                    }

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
 * Initialize Air Datepicker for member DOB.
 *
 * UI value:
 * DD/MM/YYYY
 *
 * Submitted value:
 * YYYY-MM-DD
 *
 * CI4 server-side validation remains authoritative.
 *
 * @returns {void}
 */
    const initializeDateOfBirthAge = () => {
        const dateOfBirth =
            document.getElementById(
                'date_of_birth'
            );

        const dateOfBirthDisplay =
            document.getElementById(
                'date_of_birth_display'
            );

        const pickerButton =
            document.getElementById(
                'date-of-birth-picker-button'
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
                dateOfBirthDisplay
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
         * Format local Date as YYYY-MM-DD.
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
         * Convert YYYY-MM-DD to a local Date.
         *
         * DOB is a calendar date and must not be converted
         * through UTC.
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
                    .exec(
                        value
                    );

            if (!match) {
                return null;
            }

            const year =
                Number(
                    match[1]
                );

            const month =
                Number(
                    match[2]
                );

            const day =
                Number(
                    match[3]
                );

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
         * Format DOB for the UI.
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
         * Calculate completed age.
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
         * Latest DOB allowed by the minimum-age rule.
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
         * Clear visible and hidden DOB validation.
         *
         * @returns {void}
         */
        const clearDateError = () => {
            dateOfBirth.setCustomValidity(
                ''
            );

            dateOfBirthDisplay
                .setCustomValidity(
                    ''
                );

            dateOfBirth.classList.remove(
                'is-invalid'
            );

            dateOfBirthDisplay
                .classList.remove(
                    'is-invalid'
                );

            dateOfBirth.removeAttribute(
                'aria-invalid'
            );

            dateOfBirthDisplay
                .removeAttribute(
                    'aria-invalid'
                );

            if (
                errorElement
                instanceof HTMLElement
            ) {
                errorElement.textContent =
                    '';

                errorElement.classList.remove(
                    'd-block'
                );
            }
        };

        /**
         * Show DOB validation next to the visual input.
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

            dateOfBirthDisplay
                .setCustomValidity(
                    message
                );

            dateOfBirth.classList.add(
                'is-invalid'
            );

            dateOfBirthDisplay
                .classList.add(
                    'is-invalid'
                );

            dateOfBirth.setAttribute(
                'aria-invalid',
                'true'
            );

            dateOfBirthDisplay
                .setAttribute(
                    'aria-invalid',
                    'true'
                );

            datePreview.textContent =
                '';

            agePreview.textContent =
                '';

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
         * Validate the real YYYY-MM-DD DOB value.
         *
         * @returns {boolean}
         */
        const validateAndDisplayAge =
            () => {
                const selectedValue =
                    dateOfBirth.value
                        .trim();

                datePreview.textContent =
                    '';

                agePreview.textContent =
                    '';

                if (selectedValue === '') {
                    showDateError(
                        'Please select the member’s date of birth.'
                    );

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

                /*
                 * Synchronize the visual value in case DOB came
                 * from CI4 old input.
                 */
                dateOfBirthDisplay.value =
                    formatDisplayDate(
                        birthDate
                    );

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

        const latestEligibleDate =
            getLatestEligibleBirthDate();

        /*
         * Restore a previously submitted DOB after a CI4
         * validation redirect.
         */
        const existingBirthDate =
            parseIsoDate(
                dateOfBirth.value
                    .trim()
            );

        /*
         * When no previous DOB exists, open the calendar
         * around a useful adult DOB rather than today's date.
         *
         * 30 years old is only the initial calendar position;
         * it is not a validation restriction.
         */
        const initialCalendarDate =
            existingBirthDate
            ?? latestEligibleDate;

        let datePicker = null;

        const englishLocale = {
            days: [
                'Sunday',
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
            ],

            daysShort: [
                'Sun',
                'Mon',
                'Tue',
                'Wed',
                'Thu',
                'Fri',
                'Sat',
            ],

            daysMin: [
                'Su',
                'Mo',
                'Tu',
                'We',
                'Th',
                'Fr',
                'Sa',
            ],

            months: [
                'January',
                'February',
                'March',
                'April',
                'May',
                'June',
                'July',
                'August',
                'September',
                'October',
                'November',
                'December',
            ],

            monthsShort: [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec',
            ],

            today: 'Today',
            clear: 'Clear',

            dateFormat:
                'dd/MM/yyyy',

            timeFormat:
                'hh:mm aa',

            /*
             * Sunday = 0.
             *
             * Change to 1 if you want Monday to be the
             * first day of the week.
             */
            firstDay: 0,
        };

        /*
         * Air Datepicker is page enhancement only.
         *
         * If the library fails to load, the server still remains
         * protected. The user will receive the normal required DOB
         * validation instead of submitting an invalid value.
         */
        if (
            typeof window.AirDatepicker
            === 'function'
        ) {
            datePicker =
                new window.AirDatepicker(
                    dateOfBirthDisplay,
                    {
                        locale:
                            englishLocale,

                        dateFormat:
                            'dd/MM/yyyy',

                        altField:
                            dateOfBirth,

                        altFieldDateFormat:
                            'yyyy-MM-dd',

                        selectedDates:
                            existingBirthDate
                                instanceof Date
                                ? [
                                    existingBirthDate,
                                ]
                                : [],

                        startDate:
                            initialCalendarDate,

                        maxDate:
                            latestEligibleDate,

                        multipleDates:
                            false,

                        range:
                            false,

                        autoClose:
                            true,

                        keyboardNav:
                            true,

                        position:
                            'bottom left',

                        isMobile: true,
                        autoClose: true,

                        /*
                         * Use full English month names
                         * in the month-selection screen.
                         */
                        monthsField:
                            'months',

                        view:
                            'days',

                        minView:
                            'days',

                        onSelect: ({
                            date,
                        }) => {
                            if (
                                !(
                                    date
                                    instanceof Date
                                )
                            ) {
                                dateOfBirth.value =
                                    '';

                                dateOfBirthDisplay.value =
                                    '';

                                datePreview.textContent =
                                    '';

                                agePreview.textContent =
                                    '';

                                clearDateError();

                                return;
                            }

                            dateOfBirth.value =
                                formatIsoDate(
                                    date
                                );

                            dateOfBirthDisplay.value =
                                formatDisplayDate(
                                    date
                                );

                            validateAndDisplayAge();

                            dateOfBirth.dispatchEvent(
                                new Event(
                                    'change',
                                    {
                                        bubbles: true,
                                    }
                                )
                            );
                        },
                    }
                );
        }

        /*
         * Explicit calendar icon.
         */
        if (
            pickerButton
            instanceof HTMLButtonElement
        ) {
            pickerButton.addEventListener(
                'click',
                () => {
                    if (datePicker) {
                        datePicker.show();

                        return;
                    }

                    dateOfBirthDisplay.focus();
                }
            );
        }

        /*
         * Clicking/focusing the visual field opens the
         * Air Datepicker automatically.
         */
        dateOfBirthDisplay.addEventListener(
            'click',
            () => {
                if (datePicker) {
                    datePicker.show();
                }
            }
        );

        /*
         * Preserve an authoritative server-rendered error
         * immediately after redirect.
         */
        if (
            hasServerValidationError(
                dateOfBirth
            )
        ) {
            dateOfBirthDisplay.classList.add(
                'is-invalid'
            );

            dateOfBirthDisplay.setAttribute(
                'aria-invalid',
                'true'
            );
        } else if (
            existingBirthDate
            instanceof Date
        ) {
            validateAndDisplayAge();
        }

        /*
         * DOB is represented by a hidden business field,
         * therefore page-specific client validation must run
         * before the generic form submit completes.
         */
        form.addEventListener(
            'submit',
            (event) => {
                if (
                    validateAndDisplayAge()
                ) {
                    return;
                }

                event.preventDefault();

                dateOfBirthDisplay.focus();

                if (datePicker) {
                    datePicker.show();
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
            const updateGenderState = (preserveServerError = false) => {
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
                    if (
                        !preserveServerError
                        || !hasServerValidationError(
                            gender
                        )
                    ) {
                        clearGenderValidation();
                    }

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
                if (
                    !preserveServerError
                    || !hasServerValidationError(
                        gender
                    )
                ) {
                    clearGenderValidation();
                }
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
                         *
                         * This is an actual user interaction, therefore any
                         * server-rendered Gender error belongs to the previous
                         * submitted state and may now be cleared.
                         */
                        setGenderValue(
                            ''
                        );
                    }

                    /*
                     * User changed the relationship.
                     * Do not preserve stale server validation.
                     */
                    updateGenderState(
                        false
                    );
                }
            );

            /*
             * Restore the correct initial state after first render, old input
             * or a server-side validation redirect.
             *
             * Server-rendered errors remain authoritative until the user
             * changes the corresponding field.
             */
            updateGenderState(
                true
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

                /*
                 * Configuration is attached to each individual input in
                 * Photos.php. querySelectorAll() returns a NodeList and does
                 * not expose dataset values.
                 */
                const configuredMaximumFileSize =
                    Number.parseInt(
                        photoInput.dataset
                            .maximumFileSize
                        ?? '',
                        10
                    );

                const maximumFileSize =
                    Number.isFinite(
                        configuredMaximumFileSize
                    )
                        && configuredMaximumFileSize > 0
                        ? configuredMaximumFileSize
                        : 18 * 1024 * 1024;

                const maximumFileSizeLabel =
                    photoInput.dataset
                        .maximumFileSizeLabel
                    ?? '18 MB';

                const minimumWidth =
                    Number.parseInt(
                        photoInput.dataset
                            .minimumWidth
                        ?? '300',
                        10
                    );

                const minimumHeight =
                    Number.parseInt(
                        photoInput.dataset
                            .minimumHeight
                        ?? '300',
                        10
                    );

                photoInput.addEventListener(
                    'change',
                    () => {
                        /*
                         * Clear any validation message left by an earlier
                         * invalid file selection.
                         */
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

                            /*
                             * Validate the required field after the user cancels
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
                                `The selected photograph must not exceed ${maximumFileSizeLabel}.`
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

                            refreshPhotoValidation(
                                photoInput
                            );

                            return;
                        }

                        const imageUrl =
                            URL.createObjectURL(
                                selectedFile
                            );

                        const image =
                            new Image();

                        image.addEventListener(
                            'load',
                            () => {
                                const width =
                                    image.naturalWidth;

                                const height =
                                    image.naturalHeight;

                                URL.revokeObjectURL(
                                    imageUrl
                                );

                                if (
                                    width < minimumWidth
                                    || height < minimumHeight
                                ) {
                                    photoInput.setCustomValidity(
                                        `The photograph must be at least ${minimumWidth} × ${minimumHeight} pixels.`
                                    );

                                    photoInput.value = '';

                                    previewImage.src = '';

                                    previewImage.classList.add(
                                        'd-none'
                                    );

                                    placeholder?.classList.remove(
                                        'd-none'
                                    );

                                    refreshPhotoValidation(
                                        photoInput
                                    );

                                    return;
                                }

                                /*
                                 * Dimension validation succeeded. Continue with preview.
                                 */
                                const reader =
                                    new FileReader();

                                reader.addEventListener(
                                    'load',
                                    () => {
                                        previewImage.src =
                                            typeof reader.result
                                                === 'string'
                                                ? reader.result
                                                : '';

                                        previewImage.classList.remove(
                                            'd-none'
                                        );

                                        placeholder?.classList.add(
                                            'd-none'
                                        );

                                        photoInput.setCustomValidity(
                                            ''
                                        );

                                        refreshPhotoValidation(
                                            photoInput
                                        );
                                    }
                                );

                                reader.readAsDataURL(
                                    selectedFile
                                );
                            }
                        );

                        image.addEventListener(
                            'error',
                            () => {
                                URL.revokeObjectURL(
                                    imageUrl
                                );

                                photoInput.setCustomValidity(
                                    'The selected photograph could not be read.'
                                );

                                photoInput.value = '';

                                refreshPhotoValidation(
                                    photoInput
                                );
                            }
                        );

                        image.src =
                            imageUrl;

                        return;

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

                                /*
                                 * Remove any previous Bootstrap invalid state
                                 * after a valid photograph is selected.
                                 */
                                refreshPhotoValidation(
                                    photoInput
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
 * Initialize explicit SAK Volunteer verification.
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

        /*
         * QA/development do not render the SAK Volunteer
         * component, therefore this function safely becomes
         * a no-op there.
         */
        if (
            !(verifyButton instanceof HTMLButtonElement)
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
                resultMessage
                instanceof HTMLElement
            )
        ) {
            return;
        }

        const clearResult = () => {
            resultMessage.textContent = '';

            if (
                resultCode
                instanceof HTMLElement
            ) {
                resultCode.textContent = '';
            }

            if (
                resultLocation
                instanceof HTMLElement
            ) {
                resultLocation.textContent = '';
            }

            resultColumn.classList.add(
                'd-none'
            );
        };

        const clearVerificationError = () => {
            officerCodeInput
                .setCustomValidity('');

            officerCodeInput
                .classList.remove(
                    'is-invalid'
                );

            officerCodeInput
                .removeAttribute(
                    'aria-invalid'
                );

            if (
                errorElement
                instanceof HTMLElement
            ) {
                errorElement.textContent = '';

                errorElement.classList
                    .remove('d-block');
            }
        };

        const resetVerification = () => {
            verifiedOfficerInput.value = '';

            clearVerificationError();
            clearResult();
        };

        const showVerificationError = (
            message
        ) => {
            verifiedOfficerInput.value = '';

            clearResult();

            officerCodeInput
                .setCustomValidity(
                    message
                );

            officerCodeInput
                .classList.add(
                    'is-invalid'
                );

            officerCodeInput
                .setAttribute(
                    'aria-invalid',
                    'true'
                );

            if (
                errorElement
                instanceof HTMLElement
            ) {
                errorElement.textContent =
                    message;

                errorElement.classList
                    .add('d-block');
            }

            officerCodeInput.focus();
        };

        const showVerificationSuccess = (
            officer
        ) => {
            const fullName = String(
                officer.fullName
                ?? ''
            ).trim();

            const officerCode = String(
                officer.officerCode
                ?? ''
            ).trim();

            const location = String(
                officer.location
                ?? ''
            ).trim();

            clearVerificationError();

            resultMessage.textContent =
                fullName;

            if (
                resultCode
                instanceof HTMLElement
            ) {
                resultCode.textContent =
                    officerCode !== ''
                        ? `ID: ${officerCode}`
                        : '';
            }

            if (
                resultLocation
                instanceof HTMLElement
            ) {
                resultLocation.textContent =
                    location;
            }

            resultColumn.classList.remove(
                'd-none'
            );
        };

        const showLoader = () => {
            verifyButton.disabled = true;

            verifyButton.setAttribute(
                'aria-busy',
                'true'
            );

            verifyLabel?.classList.add(
                'd-none'
            );

            verifyLoading?.classList.remove(
                'd-none'
            );
        };

        const hideLoader = () => {
            verifyButton.disabled = false;

            verifyButton.removeAttribute(
                'aria-busy'
            );

            verifyLabel?.classList.remove(
                'd-none'
            );

            verifyLoading?.classList.add(
                'd-none'
            );
        };

        /*
         * Never restore verification after a validation redirect.
         *
         * The code must be verified again because the hidden ID
         * is deliberately not trusted as persistent state.
         */
        verifiedOfficerInput.value = '';

        officerCodeInput.addEventListener(
            'input',
            () => {
                officerCodeInput.value =
                    officerCodeInput.value
                        .toUpperCase()
                        .replace(
                            /\s+/g,
                            ''
                        );

                resetVerification();
            }
        );

        verifyButton.addEventListener(
            'click',
            async () => {
                clearVerificationError();

                const enteredCode =
                    officerCodeInput.value
                        .trim()
                        .toUpperCase();

                if (
                    !officerCodeInput
                        .checkValidity()
                ) {
                    officerCodeInput
                        .reportValidity();

                    officerCodeInput.focus();

                    return;
                }

                const verificationUrl =
                    verifyButton.dataset
                        .verificationUrl
                    ?? '';

                if (verificationUrl === '') {
                    showVerificationError(
                        'SAK Volunteer verification '
                        + 'is currently unavailable.'
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
                        'The security token is unavailable. '
                        + 'Please refresh the page and try again.'
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

                showLoader();

                try {
                    const response =
                        await fetch(
                            verificationUrl,
                            {
                                method:
                                    'POST',

                                body:
                                    requestBody,

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
                    } catch (error) {
                        throw new Error(
                            'The SAK Volunteer verification '
                            + 'service returned an invalid response.'
                        );
                    }

                    /*
                     * CI4 regenerates the CSRF token after POST.
                     *
                     * Save Profile must use the new token.
                     */
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
                            || 'The SAK Volunteer code '
                            + 'is invalid or inactive.'
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

                    if (
                        verifiedId === ''
                    ) {
                        throw new Error(
                            'SAK Volunteer verification '
                            + 'returned an invalid response.'
                        );
                    }

                    verifiedOfficerInput.value =
                        verifiedId;

                    showVerificationSuccess(
                        officer
                    );
                } catch (error) {
                    showVerificationError(
                        error instanceof Error
                            ? error.message
                            : 'SAK Volunteer verification failed.'
                    );
                } finally {
                    hideLoader();
                }
            }
        );

        /*
         * Production field exists only when verification
         * is required.
         *
         * Therefore its presence itself determines whether
         * the save must be blocked.
         */
        form.addEventListener(
            'submit',
            (event) => {
                if (
                    officerCodeInput.value
                        .trim() === ''
                ) {
                    return;
                }

                if (
                    verifiedOfficerInput.value
                        .trim() !== ''
                ) {
                    return;
                }

                event.preventDefault();

                showVerificationError(
                    'Please verify the SAK Volunteer '
                    + 'before saving the profile.'
                );
            },
            true
        );
    };

    /**
 * Show the parent's-mobile recommendation when the
 * prelaunch profile represents a female member.
 *
 * The Prelaunch form uses a Gender select with values
 * MALE and FEMALE. Gender may also be assigned
 * automatically from Profile Created For.
 *
 * @returns {void}
 */
    const initializeFemaleMobileRecommendation = () => {
        const genderSelect =
            document.getElementById(
                'gender'
            );

        const recommendation =
            document.getElementById(
                'femaleMobileRecommendation'
            );

        if (
            !(
                genderSelect
                instanceof HTMLSelectElement
            )
            || !(
                recommendation
                instanceof HTMLElement
            )
        ) {
            return;
        }

        /**
         * Show or hide the recommendation using the
         * current value of the original Gender select.
         *
         * Choices.js keeps the original select synchronized
         * and the existing profile-gender dependency dispatches
         * a native change event after assigning Gender.
         *
         * @returns {void}
         */
        const updateRecommendation = () => {
            const selectedGender =
                genderSelect.value
                    .trim()
                    .toUpperCase();

            recommendation.classList.toggle(
                'd-none',
                selectedGender !== 'FEMALE'
            );
        };

        genderSelect.addEventListener(
            'change',
            updateRecommendation
        );

        /*
         * Restore the correct state after initial page load,
         * old input or a server-side validation redirect.
         */
        updateRecommendation();
    };

    /**
     * Initialize the prelaunch profile saving modal.
     *
     * The current form uses a standard multipart submission. Messages are
     * elapsed-time guidance and do not claim measured backend completion.
     *
     * @returns {void}
     */
    const initializeSavingModal = () => {
        const modalElement =
            document.getElementById(
                'prelaunchSavingModal'
            );

        const messageElement =
            document.getElementById(
                'prelaunchSavingModalMessage'
            );

        const progressBar =
            document.getElementById(
                'prelaunchSavingProgressBar'
            );

        if (
            !(modalElement instanceof HTMLElement)
            || !(messageElement instanceof HTMLElement)
            || !(progressBar instanceof HTMLElement)
            || typeof bootstrap === 'undefined'
        ) {
            return;
        }

        const modal =
            bootstrap.Modal.getOrCreateInstance(
                modalElement,
                {
                    backdrop: 'static',
                    keyboard: false,
                }
            );

        const stages = [
            {
                delay: 0,
                message:
                    'Saving profile details…',
                progress: 18,
            },
            {
                delay: 1200,
                message:
                    'Uploading and optimizing photographs…',
                progress: 45,
            },
            {
                delay: 4200,
                message:
                    'Saving additional information…',
                progress: 72,
            },
            {
                delay: 7500,
                message:
                    'Almost done. Please keep this page open…',
                progress: 90,
            },
            {
                delay: 12000,
                message:
                    'Large photographs can take a little longer to process…',
                progress: 96,
            },
        ];

        let timers = [];
        let modalVisible = false;

        /**
         * Stop all pending message transitions.
         *
         * @returns {void}
         */
        const clearTimers = () => {
            timers.forEach(
                (timerId) => {
                    window.clearTimeout(
                        timerId
                    );
                }
            );

            timers = [];
        };

        /**
         * Restore the default modal content.
         *
         * @returns {void}
         */
        const resetModal = () => {
            clearTimers();

            messageElement.textContent =
                stages[0].message;

            progressBar.style.width =
                `${stages[0].progress}%`;
        };

        /**
         * Show the processing modal.
         *
         * @returns {void}
         */
        const showModal = () => {
            if (modalVisible) {
                return;
            }

            modalVisible = true;
            resetModal();

            modal.show();

            stages.forEach(
                (stage) => {
                    const timerId =
                        window.setTimeout(
                            () => {
                                messageElement.textContent =
                                    stage.message;

                                progressBar.style.width =
                                    `${stage.progress}%`;
                            },
                            stage.delay
                        );

                    timers.push(
                        timerId
                    );
                }
            );
        };

        /**
         * Hide and reset the modal when submission is cancelled.
         *
         * @returns {void}
         */
        const hideModal = () => {
            clearTimers();

            modalVisible = false;

            modal.hide();
            resetModal();
        };

        /*
         * Wait until every synchronous validation handler has completed.
         */
        form.addEventListener(
            'submit',
            (event) => {
                window.setTimeout(
                    () => {
                        if (
                            event.defaultPrevented
                            || !form.checkValidity()
                        ) {
                            hideModal();

                            return;
                        }

                        showModal();
                    },
                    0
                );
            }
        );

        /*
         * Hide the modal when native validation prevents submission.
         */
        form.addEventListener(
            'invalid',
            () => {
                hideModal();
            },
            true
        );

        /*
         * The page may return through browser back-forward cache.
         */
        window.addEventListener(
            'pageshow',
            () => {
                hideModal();
            }
        );

        /*
         * Hide it when the reusable validator reports an invalid form.
         */
        form.addEventListener(
            'app:form-invalid',
            () => {
                hideModal();
            }
        );
    };

    initializeProfileGenderDependency();
    initializeDateOfBirthAge();
    initializePhotoPreviews();
    initializeFieldOfficerVerification();
    initializeSavingModal();
    initializeFemaleMobileRecommendation();

    /**
 * Initialize State → City dependency.
 */
    const countrySelect =
        document.getElementById(
            'country_id'
        );

    const stateSelect =
        document.getElementById(
            'state_id'
        );

    const citySelect =
        document.getElementById(
            'city_id'
        );

    if (
        countrySelect
        instanceof HTMLSelectElement
        && stateSelect
        instanceof HTMLSelectElement
        && citySelect
        instanceof HTMLSelectElement
    ) {
        const stateUrlTemplate =
            countrySelect.dataset
                .stateUrlTemplate
            ?? '';

        countrySelect.addEventListener(
            'change',
            () => {
                updateSelectOptions(
                    citySelect,
                    [],
                    'Select state first'
                );
            }
        );

        if (stateUrlTemplate !== '') {
            bindDependentSelect(
                countrySelect,
                stateSelect,
                stateUrlTemplate,
                'Select state'
            );
        }
    }

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

    /**
     * Reload the form when the browser restores it from back-forward cache.
     *
     * A restored form may contain an expired CSRF token or stale custom
     * validation state.
     */
    window.addEventListener(
        'pageshow',
        (event) => {
            if (event.persisted) {
                window.location.reload();
            }
        }
    );
});
