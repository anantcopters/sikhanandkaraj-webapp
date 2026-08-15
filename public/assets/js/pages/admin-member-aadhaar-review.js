(function (window, document) {
    'use strict';

    /**
     * Initialize the Aadhaar DOB composite field.
     *
     * Required-field rendering, error placement, focus handling and
     * is-invalid state are owned by FormValidator.
     */
    function initializeDateOfBirth() {
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

        if (
            !(day instanceof HTMLSelectElement)
            || !(month instanceof HTMLSelectElement)
            || !(year instanceof HTMLSelectElement)
        ) {
            return;
        }

        /**
         * Apply custom validity only for a complete but impossible
         * calendar date. Empty fields remain owned by the native
         * required constraint and FormValidator.
         */
        function validateDateOfBirth() {
            day.setCustomValidity('');

            if (
                day.value === ''
                || month.value === ''
                || year.value === ''
            ) {
                return;
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
                    'Please select a valid Aadhaar date of birth.'
                );
            }
        }

        [
            day,
            month,
            year
        ].forEach(function (field) {
            field.addEventListener(
                'change',
                validateDateOfBirth
            );
        });

        validateDateOfBirth();
    }

    /**
     * Normalize text values before validation and submission.
     *
     * Field validation and error rendering remain owned by the
     * shared FormValidator.
     */
    function initializeTextNormalization() {
        document.querySelectorAll(
            [
                'input[name="aadhaar_name"]',
                'textarea[name="rejection_reason"]'
            ].join(',')
        ).forEach(function (field) {
            field.addEventListener(
                'blur',
                function () {
                    field.value = field.value
                        .replace(/\s+/gu, ' ')
                        .trim();
                }
            );
        });
    }

    /**
     * Reopen only the rejection modal after rejection
     * server-validation failure.
     */
    function reopenRejectionModal() {
        const modalElement =
            document.getElementById(
                'rejectAadhaarModal'
            );

        if (
            !modalElement
            || modalElement.dataset.openOnLoad
            !== 'true'
            || typeof window.bootstrap
            === 'undefined'
        ) {
            return;
        }

        window.bootstrap.Modal
            .getOrCreateInstance(
                modalElement
            )
            .show();
    }

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            initializeDateOfBirth();
            initializeTextNormalization();
            reopenRejectionModal();
        }
    );
})(window, document);