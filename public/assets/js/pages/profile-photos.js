'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const uploadForm = document.getElementById(
        'profile-photo-upload-form'
    );

    const photoInput = document.getElementById(
        'profile-photo-input'
    );

    const preview = document.getElementById(
        'profile-photo-preview'
    );

    const fileName = document.getElementById(
        'profile-photo-file-name'
    );

    const submitButton = document.getElementById(
        'profile-photo-submit'
    );

    const spinner = document.getElementById(
        'profile-photo-spinner'
    );

    if (photoInput && preview && fileName) {
        photoInput.addEventListener('change', () => {
            const [file] = photoInput.files;

            if (!file) {
                preview.src = '';
                preview.classList.add('d-none');
                fileName.textContent = 'No photo selected';

                return;
            }

            fileName.textContent = file.name;

            const objectUrl = URL.createObjectURL(file);

            preview.onload = () => {
                URL.revokeObjectURL(objectUrl);
            };

            preview.src = objectUrl;
            preview.classList.remove('d-none');
        });
    }

    if (uploadForm && submitButton && spinner) {
        uploadForm.addEventListener('submit', () => {
            submitButton.disabled = true;
            spinner.classList.remove('d-none');
        });
    }

    document
        .querySelectorAll('.profile-photo-delete-form')
        .forEach((form) => {
            form.addEventListener('submit', (event) => {
                const confirmed = window.confirm(
                    'Delete this photo permanently?'
                );

                if (!confirmed) {
                    event.preventDefault();
                }
            });
        });
});