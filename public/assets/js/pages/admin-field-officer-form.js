'use strict';

document.addEventListener(
    'DOMContentLoaded',
    function () {
        const form = document.querySelector(
            '[data-field-officer-form]'
        );

        if (!form) {
            return;
        }

        const stateSelect = form.querySelector(
            '[data-state-select]'
        );

        const citySelect = form.querySelector(
            '[data-city-select]'
        );

        const fullNameInput = form.querySelector(
            'input[name="full_name"]'
        );

        const mobileInput = form.querySelector(
            'input[name="mobile_number"]'
        );

        const aadhaarInput = form.querySelector(
            'input[name="aadhaar_number"]'
        );

        const panInput = form.querySelector(
            'input[name="pan_number"]'
        );

        const countrySelect = form.querySelector(
            'select[name="country_id"]'
        );

        const addressInput = form.querySelector(
            'textarea[name="address"]'
        );

        const upiInput = form.querySelector(
            'input[name="upi_id"]'
        );

        const captchaInput = form.querySelector(
            'input[name="captcha_answer"]'
        );

        const citiesBaseUrl = String(
            form.dataset.citiesUrl || ''
        ).replace(
            /\/$/,
            ''
        );

        const namePattern =
            /^[\p{L}\p{M} .'\-]+$/u;

        const mobilePattern =
            /^[6-9][0-9]{9}$/;

        const aadhaarPattern =
            /^[0-9]{12}$/;

        const panPattern =
            /^[A-Z]{5}[0-9]{4}[A-Z]$/;

        const upiPattern =
            /^[A-Za-z0-9._-]{2,256}@[A-Za-z][A-Za-z0-9.-]{1,63}$/;

        const captchaPattern =
            /^[0-9]{1,2}$/;


        /**
         * Return an existing Bootstrap feedback element
         * associated with the field.
         *
         * @param {HTMLElement} field
         * @returns {HTMLElement|null}
         */
        function feedbackElement(
            field
        ) {
            if (!field) {
                return null;
            }

            const parent =
                field.closest(
                    '.col-12'
                )
                || field.parentElement;

            if (!parent) {
                return null;
            }

            return parent.querySelector(
                '.invalid-feedback'
            );
        }


        /**
         * Mark a field invalid and display a client-side
         * validation message.
         *
         * @param {HTMLElement} field
         * @param {string} message
         */
        function setInvalid(
            field,
            message
        ) {
            if (!field) {
                return;
            }

            field.classList.add(
                'is-invalid'
            );

            field.setAttribute(
                'aria-invalid',
                'true'
            );

            const feedback =
                feedbackElement(
                    field
                );

            if (feedback) {
                feedback.textContent =
                    message;
            }
        }


        /**
         * Remove the client-side invalid state.
         *
         * Server validation remains the final authority.
         *
         * @param {HTMLElement} field
         */
        function clearInvalid(
            field
        ) {
            if (!field) {
                return;
            }

            field.classList.remove(
                'is-invalid'
            );

            field.removeAttribute(
                'aria-invalid'
            );
        }


        /**
         * Validate SAK Volunteer name.
         *
         * Mirrors:
         * FieldOfficerValidation::createRules()
         *
         * @returns {boolean}
         */
        function validateFullName() {
            if (!fullNameInput) {
                /*
                 * Name/mobile are intentionally absent
                 * from Admin edit mode.
                 */
                return true;
            }

            const value =
                fullNameInput.value
                    .trim();

            if (value === '') {
                setInvalid(
                    fullNameInput,
                    'Name is required.'
                );

                return false;
            }

            if (value.length < 2) {
                setInvalid(
                    fullNameInput,
                    'Name must contain at least 2 characters.'
                );

                return false;
            }

            if (value.length > 150) {
                setInvalid(
                    fullNameInput,
                    'Name must not exceed 150 characters.'
                );

                return false;
            }

            if (
                !namePattern.test(
                    value
                )
            ) {
                setInvalid(
                    fullNameInput,
                    'Name contains unsupported characters.'
                );

                return false;
            }

            clearInvalid(
                fullNameInput
            );

            return true;
        }


        /**
         * Validate Indian mobile number.
         *
         * @returns {boolean}
         */
        function validateMobile() {
            if (!mobileInput) {
                return true;
            }

            const value =
                mobileInput.value
                    .trim();

            if (value === '') {
                setInvalid(
                    mobileInput,
                    'Mobile number is required.'
                );

                return false;
            }

            if (
                !mobilePattern.test(
                    value
                )
            ) {
                setInvalid(
                    mobileInput,
                    'Enter a valid 10-digit Indian mobile number.'
                );

                return false;
            }

            clearInvalid(
                mobileInput
            );

            return true;
        }


        /**
         * Validate Aadhaar number.
         *
         * @returns {boolean}
         */
        function validateAadhaar() {
            if (!aadhaarInput) {
                return true;
            }

            const value =
                aadhaarInput.value
                    .trim();

            if (value === '') {
                setInvalid(
                    aadhaarInput,
                    'Aadhaar number is required.'
                );

                return false;
            }

            if (
                !aadhaarPattern.test(
                    value
                )
            ) {
                setInvalid(
                    aadhaarInput,
                    'Enter a valid 12-digit Aadhaar number.'
                );

                return false;
            }

            clearInvalid(
                aadhaarInput
            );

            return true;
        }


        /**
         * Validate PAN number.
         *
         * @returns {boolean}
         */
        function validatePan() {
            if (!panInput) {
                return true;
            }

            const value =
                panInput.value
                    .trim()
                    .toUpperCase();

            if (value === '') {
                setInvalid(
                    panInput,
                    'PAN number is required.'
                );

                return false;
            }

            if (
                !panPattern.test(
                    value
                )
            ) {
                setInvalid(
                    panInput,
                    'Enter a valid PAN number, for example ABCDE1234F.'
                );

                return false;
            }

            clearInvalid(
                panInput
            );

            return true;
        }


        /**
         * Validate required master-data select.
         *
         * @param {HTMLSelectElement|null} select
         * @param {string} label
         * @returns {boolean}
         */
        function validateRequiredSelect(
            select,
            label
        ) {
            if (!select) {
                return true;
            }

            const value =
                String(
                    select.value || ''
                ).trim();

            if (
                value === ''
                || !/^[1-9][0-9]*$/.test(
                    value
                )
            ) {
                setInvalid(
                    select,
                    'Select a valid '
                    + label
                    + '.'
                );

                return false;
            }

            clearInvalid(
                select
            );

            return true;
        }


        /**
         * Validate optional address.
         *
         * @returns {boolean}
         */
        function validateAddress() {
            if (!addressInput) {
                return true;
            }

            const value =
                addressInput.value
                    .trim();

            if (
                value.length > 500
            ) {
                setInvalid(
                    addressInput,
                    'Address must not exceed 500 characters.'
                );

                return false;
            }

            clearInvalid(
                addressInput
            );

            return true;
        }


        /**
         * Validate optional UPI ID.
         *
         * @returns {boolean}
         */
        function validateUpi() {
            if (!upiInput) {
                return true;
            }

            const value =
                upiInput.value
                    .trim();

            if (value === '') {
                clearInvalid(
                    upiInput
                );

                return true;
            }

            if (
                value.length > 150
            ) {
                setInvalid(
                    upiInput,
                    'UPI ID must not exceed 150 characters.'
                );

                return false;
            }

            if (
                !upiPattern.test(
                    value
                )
            ) {
                setInvalid(
                    upiInput,
                    'Enter a valid UPI ID, for example name@bank.'
                );

                return false;
            }

            clearInvalid(
                upiInput
            );

            return true;
        }


        /**
         * Validate registration CAPTCHA format.
         *
         * The actual answer is intentionally verified only
         * on the server.
         *
         * @returns {boolean}
         */
        function validateCaptcha() {
            if (!captchaInput) {
                return true;
            }

            const value =
                captchaInput.value
                    .trim();

            if (value === '') {
                setInvalid(
                    captchaInput,
                    'Please enter the security verification answer.'
                );

                return false;
            }

            if (
                !captchaPattern.test(
                    value
                )
            ) {
                setInvalid(
                    captchaInput,
                    'Please enter a valid security verification answer.'
                );

                return false;
            }

            clearInvalid(
                captchaInput
            );

            return true;
        }


        /**
         * Validate the complete form before submission.
         *
         * Server-side validation remains mandatory and
         * authoritative.
         *
         * @returns {boolean}
         */
        function validateForm() {
            const results = [
                validateFullName(),
                validateMobile(),
                validateAadhaar(),
                validatePan(),

                validateRequiredSelect(
                    countrySelect,
                    'country'
                ),

                validateRequiredSelect(
                    stateSelect,
                    'state'
                ),

                validateRequiredSelect(
                    citySelect,
                    'city'
                ),

                validateAddress(),
                validateUpi(),
                validateCaptcha()
            ];

            const valid =
                results.every(
                    function (result) {
                        return result === true;
                    }
                );

            if (!valid) {
                const firstInvalid =
                    form.querySelector(
                        '.is-invalid'
                    );

                if (
                    firstInvalid
                    && typeof firstInvalid.focus
                    === 'function'
                ) {
                    firstInvalid.focus();
                }
            }

            return valid;
        }


        /**
         * Replace native city options and rebuild the
         * existing project Choices.js component.
         *
         * @param {HTMLSelectElement} select
         * @param {Array<{value: string, label: string}>} options
         * @param {string} placeholder
         */
        function populateSelect(
            select,
            options,
            placeholder
        ) {
            window.SelectChoice?.destroy(
                select
            );

            select.replaceChildren();

            const placeholderOption =
                document.createElement(
                    'option'
                );

            placeholderOption.value = '';

            placeholderOption.textContent =
                placeholder;

            placeholderOption.selected =
                true;

            select.appendChild(
                placeholderOption
            );

            options.forEach(
                function (item) {
                    const option =
                        document.createElement(
                            'option'
                        );

                    option.value =
                        String(
                            item.value
                        );

                    option.textContent =
                        String(
                            item.label
                        );

                    select.appendChild(
                        option
                    );
                }
            );

            select.disabled =
                options.length === 0;

            window.SelectChoice?.create(
                select
            );
        }


        /**
         * Retrieve city options from the server.
         *
         * @param {string} url
         * @returns {Promise<Array<{value: string, label: string}>>}
         */
        async function fetchOptions(
            url
        ) {
            const response =
                await fetch(
                    url,
                    {
                        method:
                            'GET',

                        headers: {
                            Accept:
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest'
                        },

                        credentials:
                            'same-origin'
                    }
                );

            if (!response.ok) {
                throw new Error(
                    'Master data could not be loaded.'
                );
            }

            const payload =
                await response.json();

            return Array.isArray(
                payload.data
            )
                ? payload.data
                : [];
        }


        /*
         * --------------------------------------------------
         * INPUT NORMALIZATION
         * --------------------------------------------------
         */

        if (fullNameInput) {
            fullNameInput.addEventListener(
                'blur',
                function () {
                    fullNameInput.value =
                        fullNameInput.value
                            .trim()
                            .replace(
                                /\s+/g,
                                ' '
                            );

                    validateFullName();
                }
            );

            fullNameInput.addEventListener(
                'input',
                function () {
                    clearInvalid(
                        fullNameInput
                    );
                }
            );
        }


        if (mobileInput) {
            mobileInput.addEventListener(
                'input',
                function () {
                    mobileInput.value =
                        mobileInput.value
                            .replace(
                                /\D/g,
                                ''
                            )
                            .slice(
                                0,
                                10
                            );

                    clearInvalid(
                        mobileInput
                    );
                }
            );

            mobileInput.addEventListener(
                'blur',
                validateMobile
            );
        }


        if (aadhaarInput) {
            aadhaarInput.addEventListener(
                'input',
                function () {
                    aadhaarInput.value =
                        aadhaarInput.value
                            .replace(
                                /\D/g,
                                ''
                            )
                            .slice(
                                0,
                                12
                            );

                    clearInvalid(
                        aadhaarInput
                    );
                }
            );

            aadhaarInput.addEventListener(
                'blur',
                validateAadhaar
            );
        }


        if (panInput) {
            panInput.addEventListener(
                'input',
                function () {
                    panInput.value =
                        panInput.value
                            .toUpperCase()
                            .replace(
                                /[^A-Z0-9]/g,
                                ''
                            )
                            .slice(
                                0,
                                10
                            );

                    clearInvalid(
                        panInput
                    );
                }
            );

            panInput.addEventListener(
                'blur',
                validatePan
            );
        }


        if (addressInput) {
            addressInput.addEventListener(
                'input',
                function () {
                    clearInvalid(
                        addressInput
                    );
                }
            );

            addressInput.addEventListener(
                'blur',
                validateAddress
            );
        }


        if (upiInput) {
            upiInput.addEventListener(
                'input',
                function () {
                    clearInvalid(
                        upiInput
                    );
                }
            );

            upiInput.addEventListener(
                'blur',
                function () {
                    upiInput.value =
                        upiInput.value
                            .trim()
                            .toLowerCase();

                    validateUpi();
                }
            );
        }


        if (captchaInput) {
            captchaInput.addEventListener(
                'input',
                function () {
                    captchaInput.value =
                        captchaInput.value
                            .replace(
                                /\D/g,
                                ''
                            )
                            .slice(
                                0,
                                2
                            );

                    clearInvalid(
                        captchaInput
                    );
                }
            );

            captchaInput.addEventListener(
                'blur',
                validateCaptcha
            );
        }


        /*
         * --------------------------------------------------
         * MASTER DATA
         * --------------------------------------------------
         */

        if (countrySelect) {
            countrySelect.addEventListener(
                'change',
                function () {
                    clearInvalid(
                        countrySelect
                    );
                }
            );
        }


        if (
            stateSelect
            && citySelect
        ) {
            stateSelect.addEventListener(
                'change',
                async function () {
                    clearInvalid(
                        stateSelect
                    );

                    clearInvalid(
                        citySelect
                    );

                    const stateId =
                        stateSelect.value;

                    if (!stateId) {
                        populateSelect(
                            citySelect,
                            [],
                            'Select City'
                        );

                        return;
                    }

                    window.SelectChoice?.destroy(
                        citySelect
                    );

                    citySelect.disabled =
                        true;

                    try {
                        const cities =
                            await fetchOptions(
                                citiesBaseUrl
                                + '/'
                                + encodeURIComponent(
                                    stateId
                                )
                            );

                        populateSelect(
                            citySelect,
                            cities,
                            cities.length > 0
                                ? 'Select City'
                                : 'No City Available'
                        );
                    } catch (error) {
                        populateSelect(
                            citySelect,
                            [],
                            'Unable to Load Cities'
                        );
                    }
                }
            );

            citySelect.addEventListener(
                'change',
                function () {
                    clearInvalid(
                        citySelect
                    );
                }
            );
        }


        /*
         * --------------------------------------------------
         * FORM SUBMISSION
         * --------------------------------------------------
         */

        form.addEventListener(
            'submit',
            function (event) {
                if (
                    !validateForm()
                ) {
                    event.preventDefault();

                    event.stopImmediatePropagation();

                    return;
                }

                /*
                 * Keep the normalized values consistent
                 * with server-side normalization.
                 */
                if (fullNameInput) {
                    fullNameInput.value =
                        fullNameInput.value
                            .trim()
                            .replace(
                                /\s+/g,
                                ' '
                            );
                }

                if (panInput) {
                    panInput.value =
                        panInput.value
                            .trim()
                            .toUpperCase();
                }

                if (upiInput) {
                    upiInput.value =
                        upiInput.value
                            .trim()
                            .toLowerCase();
                }

                if (addressInput) {
                    addressInput.value =
                        addressInput.value
                            .trim();
                }
            },
            true
        );
    }
);