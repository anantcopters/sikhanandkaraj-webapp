(function (window, document) {
    'use strict';

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            const rejectModal =
                document.getElementById(
                    'rejectAadhaarModal'
                );

            if (
                rejectModal
                && rejectModal.dataset.openOnLoad === 'true'
                && window.bootstrap
            ) {
                window.bootstrap.Modal
                    .getOrCreateInstance(
                        rejectModal
                    )
                    .show();
            }

            const approvalForm =
                document.querySelector(
                    '[data-aadhaar-approval-form]'
                );

            const rejectionForm =
                document.querySelector(
                    '[data-aadhaar-rejection-form]'
                );

            const name =
                document.getElementById(
                    'aadhaarName'
                );

            const day =
                document.getElementById(
                    'aadhaarBirthDay'
                );

            const month =
                document.getElementById(
                    'aadhaarBirthMonth'
                );

            const year =
                document.getElementById(
                    'aadhaarBirthYear'
                );

            const dobError =
                document.getElementById(
                    'aadhaarDobError'
                );

            const rejectionReason =
                document.getElementById(
                    'aadhaarRejectionReason'
                );

            /**
             * Normalize repeated whitespace in a text value.
             *
             * @param {string} value
             *
             * @returns {string}
             */
            function normalizeText(value) {
                return String(value || '')
                    .trim()
                    .replace(/\s+/gu, ' ');
            }

            /**
             * Validate the Aadhaar name using the same rules as
             * MemberAadhaarValidation::approvalRules().
             *
             * @returns {boolean}
             */
            function validateName() {
                if (!name) {
                    return false;
                }

                const value =
                    normalizeText(
                        name.value
                    );

                name.setCustomValidity('');

                if (value.length < 2) {
                    name.setCustomValidity(
                        'Enter the name shown on Aadhaar.'
                    );

                    return false;
                }

                if (value.length > 100) {
                    name.setCustomValidity(
                        'Aadhaar name cannot exceed 100 characters.'
                    );

                    return false;
                }

                if (
                    !/^[\p{L}\p{M} .'-]+$/u.test(
                        value
                    )
                ) {
                    name.setCustomValidity(
                        'Aadhaar name contains unsupported characters.'
                    );

                    return false;
                }

                return true;
            }

            /**
             * Return true when the selected date is real and the
             * person is at least 18 years old.
             *
             * @returns {boolean}
             */
            function validateDateOfBirth() {
                if (
                    !day
                    || !month
                    || !year
                ) {
                    return false;
                }

                day.setCustomValidity('');
                month.setCustomValidity('');
                year.setCustomValidity('');

                if (
                    day.value === ''
                    || month.value === ''
                    || year.value === ''
                ) {
                    day.setCustomValidity(
                        'Select the complete Aadhaar date of birth.'
                    );

                    showDobError(
                        'Select the complete Aadhaar date of birth.'
                    );

                    return false;
                }

                const selectedYear =
                    Number(year.value);

                const selectedMonth =
                    Number(month.value);

                const selectedDay =
                    Number(day.value);

                const selectedDate =
                    new Date(
                        selectedYear,
                        selectedMonth - 1,
                        selectedDay
                    );

                if (
                    selectedDate.getFullYear()
                    !== selectedYear
                    || selectedDate.getMonth()
                    !== selectedMonth - 1
                    || selectedDate.getDate()
                    !== selectedDay
                ) {
                    day.setCustomValidity(
                        'Select a valid Aadhaar date of birth.'
                    );

                    showDobError(
                        'Select a valid Aadhaar date of birth.'
                    );

                    return false;
                }

                const today =
                    new Date();

                let age =
                    today.getFullYear()
                    - selectedYear;

                const monthDifference =
                    today.getMonth()
                    - selectedDate.getMonth();

                if (
                    monthDifference < 0
                    || (
                        monthDifference === 0
                        && today.getDate()
                        < selectedDate.getDate()
                    )
                ) {
                    age -= 1;
                }

                if (age < 18) {
                    day.setCustomValidity(
                        'The member must be at least 18 years old.'
                    );

                    showDobError(
                        'The member must be at least 18 years old.'
                    );

                    return false;
                }

                hideDobError();

                return true;
            }

            /**
             * Display the DOB validation message.
             *
             * @param {string} message
             */
            function showDobError(message) {
                if (!dobError) {
                    return;
                }

                dobError.textContent =
                    message;

                dobError.classList.remove(
                    'invalid-feedback'
                );

                dobError.classList.add(
                    'text-danger',
                    'fs-12',
                    'mt-1'
                );
            }

            /**
             * Hide the DOB validation message after correction.
             */
            function hideDobError() {
                if (!dobError) {
                    return;
                }

                dobError.classList.remove(
                    'text-danger',
                    'fs-12',
                    'mt-1'
                );

                dobError.classList.add(
                    'invalid-feedback'
                );
            }

            /**
             * Validate rejection reason using the same limits as
             * MemberAadhaarValidation::rejectionRules().
             *
             * @returns {boolean}
             */
            function validateRejectionReason() {
                if (!rejectionReason) {
                    return false;
                }

                const value =
                    normalizeText(
                        rejectionReason.value
                    );

                rejectionReason
                    .setCustomValidity('');

                if (value.length < 3) {
                    rejectionReason
                        .setCustomValidity(
                            'Enter a rejection reason of at least 3 characters.'
                        );

                    return false;
                }

                if (value.length > 500) {
                    rejectionReason
                        .setCustomValidity(
                            'Rejection reason cannot exceed 500 characters.'
                        );

                    return false;
                }

                return true;
            }

            if (name) {
                name.addEventListener(
                    'input',
                    validateName
                );

                name.addEventListener(
                    'blur',
                    function () {
                        name.value =
                            normalizeText(
                                name.value
                            );

                        validateName();
                    }
                );
            }

            [
                day,
                month,
                year
            ].forEach(function (field) {
                if (!field) {
                    return;
                }

                field.addEventListener(
                    'change',
                    validateDateOfBirth
                );
            });

            if (rejectionReason) {
                rejectionReason.addEventListener(
                    'input',
                    validateRejectionReason
                );

                rejectionReason.addEventListener(
                    'blur',
                    function () {
                        rejectionReason.value =
                            normalizeText(
                                rejectionReason.value
                            );

                        validateRejectionReason();
                    }
                );
            }

            /*
             * Native form validation runs before the reusable confirmation
             * modal receives the submit event. Therefore an invalid form
             * never opens the confirmation modal.
             */
            if (approvalForm) {
                approvalForm.addEventListener(
                    'submit',
                    function () {
                        validateName();
                        validateDateOfBirth();
                    },
                    true
                );
            }

            if (rejectionForm) {
                rejectionForm.addEventListener(
                    'submit',
                    function () {
                        validateRejectionReason();
                    },
                    true
                );
            }
        }
    );
})(window, document);