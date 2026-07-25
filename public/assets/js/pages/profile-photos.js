'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const uploadForm = document.getElementById(
        'profile-photo-upload-form'
    );

    const photoInput = document.getElementById(
        'profile-photo-input'
    );

    const visibilitySelect = document.getElementById(
        'profile-photo-visibility'
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

    const photoError = document.getElementById(
        'profile-photo-error'
    );

    const submitButton = document.getElementById(
        'profile-photo-submit'
    );

    const allowedMimeTypes = [
        'image/jpeg',
        'image/png'
    ];

    const allowedExtensions = [
        'jpg',
        'jpeg',
        'png'
    ];

    const maximumFileSize = 10 * 1024 * 1024;

    let currentObjectUrl = null;

    /**
     * Initialize the project-standard Choices.js wrapper.
     */
    if (visibilitySelect) {
        window.SelectChoice?.create(
            visibilitySelect
        );
    }

    /**
     * Display or clear the file-field error.
     *
     * @param {string} message
     */
    function setPhotoError(message) {
        if (!photoInput || !photoError) {
            return;
        }

        photoInput.setCustomValidity(message);

        photoError.textContent = message !== ''
            ? message
            : 'Please select a valid JPEG or PNG photo.';

        photoInput.classList.toggle(
            'is-invalid',
            message !== ''
        );
    }

    /**
     * Clear the current preview URL safely.
     */
    function clearPreview() {
        if (currentObjectUrl !== null) {
            URL.revokeObjectURL(currentObjectUrl);
            currentObjectUrl = null;
        }

        preview?.removeAttribute('src');
        previewWrapper?.classList.add('d-none');
    }

    /**
     * Validate the selected photo before form submission.
     *
     * @returns {boolean}
     */
    function validateSelectedPhoto() {
        if (!photoInput) {
            return false;
        }

        const selectedFile = photoInput.files
            ? photoInput.files[0]
            : null;

        if (!selectedFile) {
            setPhotoError(
                'Please select a photo to upload.'
            );

            return false;
        }

        const extension = selectedFile.name
            .split('.')
            .pop()
            ?.toLowerCase() || '';

        if (
            !allowedMimeTypes.includes(selectedFile.type)
            || !allowedExtensions.includes(extension)
        ) {
            setPhotoError(
                'Only JPEG, JPG and PNG photos are allowed.'
            );

            return false;
        }

        if (selectedFile.size > maximumFileSize) {
            setPhotoError(
                'The photo must not exceed 10 MB.'
            );

            return false;
        }

        setPhotoError('');

        return true;
    }

    photoInput?.addEventListener('change', () => {
        clearPreview();

        const selectedFile = photoInput.files
            ? photoInput.files[0]
            : null;

        if (!selectedFile) {
            if (fileName) {
                fileName.textContent =
                    'No photo selected';
            }

            setPhotoError('');

            return;
        }

        if (fileName) {
            fileName.textContent = selectedFile.name;
        }

        if (!validateSelectedPhoto()) {
            return;
        }

        if (
            preview
            && previewWrapper
        ) {
            currentObjectUrl = URL.createObjectURL(
                selectedFile
            );

            preview.src = currentObjectUrl;

            previewWrapper.classList.remove(
                'd-none'
            );
        }
    });

    uploadForm?.addEventListener(
        'submit',
        (event) => {
            uploadForm.classList.add(
                'was-validated'
            );

            const photoIsValid =
                validateSelectedPhoto();

            if (
                !photoIsValid
                || !uploadForm.checkValidity()
            ) {
                event.preventDefault();
                event.stopPropagation();

                photoInput?.focus();

                return;
            }

            if (!submitButton) {
                return;
            }

            /*
             * Delay the loading state until all synchronous validation
             * handlers have finished.
             */
            window.setTimeout(() => {
                if (
                    event.defaultPrevented
                    || !uploadForm.checkValidity()
                ) {
                    return;
                }

                submitButton.disabled = true;

                submitButton.setAttribute(
                    'aria-busy',
                    'true'
                );

                submitButton
                    .querySelector(
                        '.registration-submit__label'
                    )
                    ?.classList.add('d-none');

                submitButton
                    .querySelector(
                        '.registration-submit__loading'
                    )
                    ?.classList.remove('d-none');
            }, 0);
        }
    );

    document
        .querySelectorAll(
            '[data-photo-delete-form]'
        )
        .forEach((form) => {
            form.addEventListener(
                'submit',
                (event) => {
                    const confirmed = window.confirm(
                        'Delete this photo permanently?'
                    );

                    if (!confirmed) {
                        event.preventDefault();
                    }
                }
            );
        });

    window.addEventListener(
        'beforeunload',
        clearPreview
    );
});