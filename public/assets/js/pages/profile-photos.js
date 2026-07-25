'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const uploadForm = document.getElementById(
        'profile-photo-upload-form'
    );

    const photoInput = document.getElementById(
        'profile-photo-input'
    );

    const previewWrapper = document.getElementById(
        'profile-photo-preview-wrapper'
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

    let currentObjectUrl = null;

    if (
        photoInput
        && previewWrapper
        && preview
        && fileName
    ) {
        photoInput.addEventListener('change', () => {
            const selectedFile = photoInput.files
                ? photoInput.files[0]
                : null;

            if (currentObjectUrl !== null) {
                URL.revokeObjectURL(currentObjectUrl);
                currentObjectUrl = null;
            }

            if (!selectedFile) {
                preview.removeAttribute('src');
                previewWrapper.classList.add('d-none');
                fileName.textContent = 'No photo selected';

                return;
            }

            fileName.textContent = selectedFile.name;

            currentObjectUrl = URL.createObjectURL(
                selectedFile
            );

            preview.src = currentObjectUrl;
            previewWrapper.classList.remove('d-none');
        });
    }

    if (uploadForm && submitButton && spinner) {
        uploadForm.addEventListener('submit', () => {
            submitButton.disabled = true;
            spinner.classList.remove('d-none');
        });
    }

    document
        .querySelectorAll('[data-photo-delete-form]')
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

    window.addEventListener('beforeunload', () => {
        if (currentObjectUrl !== null) {
            URL.revokeObjectURL(currentObjectUrl);
        }
    });
});