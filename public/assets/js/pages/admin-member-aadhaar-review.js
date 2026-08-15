(function (document) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const rejectModal = document.getElementById(
            'rejectAadhaarModal'
        );

        /*
         * Reopen only the rejection modal when rejection validation fails.
         * The approval confirmation is not invoked in this workflow.
         */
        if (
            rejectModal
            && rejectModal.dataset.openOnLoad === 'true'
        ) {
            window.bootstrap.Modal
                .getOrCreateInstance(rejectModal)
                .show();
        }

        const day = document.getElementById('aadhaarBirthDay');
        const month = document.getElementById(
            'aadhaarBirthMonth'
        );
        const year = document.getElementById(
            'aadhaarBirthYear'
        );

        if (!day || !month || !year) {
            return;
        }

        [day, month, year].forEach(function (field) {
            field.addEventListener('change', function () {
                [day, month, year].forEach(function (item) {
                    item.setCustomValidity('');
                });

                if (
                    !day.value
                    || !month.value
                    || !year.value
                ) {
                    return;
                }

                const selected = new Date(
                    Number(year.value),
                    Number(month.value) - 1,
                    Number(day.value)
                );

                if (
                    selected.getFullYear()
                    !== Number(year.value)
                    || selected.getMonth()
                    !== Number(month.value) - 1
                    || selected.getDate()
                    !== Number(day.value)
                ) {
                    day.setCustomValidity(
                        'Please select a valid date.'
                    );
                }
            });
        });
    });
})(document);