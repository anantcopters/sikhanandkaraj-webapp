(function (window, document) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const modalElement = document.getElementById('aadhaarUploadModal');
        const fileInput = document.getElementById('aadhaarDocument');

        if (modalElement && modalElement.dataset.openOnLoad === 'true') {
            window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
        }

        if (!(fileInput instanceof HTMLInputElement)) {
            return;
        }

        fileInput.addEventListener('change', function () {
            fileInput.setCustomValidity('');
            fileInput.classList.remove('is-invalid');

            const file = fileInput.files && fileInput.files[0];

            if (!file) {
                return;
            }

            const allowedTypes = [
                'image/jpeg',
                'image/png',
                'application/pdf'
            ];

            if (!allowedTypes.includes(file.type) || file.size >= 1048576) {
                fileInput.setCustomValidity(
                    'Select a JPG, JPEG, PNG or PDF file smaller than 1 MB.'
                );
                fileInput.classList.add('is-invalid');
            }

            fileInput.dispatchEvent(
                new CustomEvent('app:validate-field')
            );
        });
    });
})(window, document);
