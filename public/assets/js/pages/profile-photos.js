'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const uploadForm = document.getElementById(
        'profile-photo-upload-form'
    );

    const photoInput = document.getElementById(
        'profile-photo-input'
    );

    const uploadVisibilitySelect =
        document.getElementById(
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

    const maximumFileSize =
        10 * 1024 * 1024;

    let currentObjectUrl = null;

    /**
     * Initialize the upload visibility Choices field.
     */
    if (uploadVisibilitySelect) {
        window.SelectChoice?.create(
            uploadVisibilitySelect
        );
    }

    /**
     * Initialize each uploaded photo visibility field.
     */
    document
        .querySelectorAll(
            '[data-photo-visibility-choice]'
        )
        .forEach((selectElement) => {
            window.SelectChoice?.create(
                selectElement
            );
        });

    /**
     * Display or clear file validation feedback.
     *
     * @param {string} message
     */
    function setPhotoError(message) {
        if (!photoInput) {
            return;
        }

        photoInput.setCustomValidity(message);

        photoInput.classList.toggle(
            'is-invalid',
            message !== ''
        );

        if (!photoError) {
            return;
        }

        photoError.textContent = message !== ''
            ? message
            : 'Please select a valid JPEG or PNG photo.';
    }

    /**
     * Remove the browser-created preview URL.
     */
    function clearPreview() {
        if (currentObjectUrl !== null) {
            URL.revokeObjectURL(
                currentObjectUrl
            );

            currentObjectUrl = null;
        }

        preview?.removeAttribute('src');

        previewWrapper?.classList.add(
            'd-none'
        );
    }

    /**
     * Validate the selected upload before submission.
     *
     * @returns {boolean}
     */
    function validateSelectedPhoto() {
        if (!photoInput) {
            return false;
        }

        const selectedFile =
            photoInput.files?.[0] ?? null;

        if (!selectedFile) {
            setPhotoError(
                'Please select a photo to upload.'
            );

            return false;
        }

        const extension = selectedFile.name
            .split('.')
            .pop()
            ?.toLowerCase() ?? '';

        if (
            !allowedMimeTypes.includes(
                selectedFile.type
            )
            || !allowedExtensions.includes(
                extension
            )
        ) {
            setPhotoError(
                'Only JPEG, JPG and PNG photos are allowed.'
            );

            return false;
        }

        if (
            selectedFile.size >
            maximumFileSize
        ) {
            setPhotoError(
                'The photo must not exceed 10 MB.'
            );

            return false;
        }

        setPhotoError('');

        return true;
    }

    /**
     * Toggle a submit button loading state.
     *
     * @param {HTMLButtonElement} button
     * @param {string} labelSelector
     * @param {string} loadingSelector
     */
    function showButtonLoading(
        button,
        labelSelector,
        loadingSelector
    ) {
        button.disabled = true;

        button.setAttribute(
            'aria-busy',
            'true'
        );

        button
            .querySelector(labelSelector)
            ?.classList.add('d-none');

        const loadingElement =
            button.querySelector(
                loadingSelector
            );

        loadingElement?.classList.remove(
            'd-none'
        );

        loadingElement?.classList.add(
            'd-inline-flex'
        );
    }

    photoInput?.addEventListener(
        'change',
        () => {
            clearPreview();

            const selectedFile =
                photoInput.files?.[0] ?? null;

            if (!selectedFile) {
                if (fileName) {
                    fileName.textContent =
                        'No photo selected';
                }

                setPhotoError('');

                return;
            }

            if (fileName) {
                fileName.textContent =
                    selectedFile.name;
            }

            if (!validateSelectedPhoto()) {
                return;
            }

            if (
                !preview
                || !previewWrapper
            ) {
                return;
            }

            currentObjectUrl =
                URL.createObjectURL(
                    selectedFile
                );

            preview.src = currentObjectUrl;

            previewWrapper.classList.remove(
                'd-none'
            );
        }
    );

    uploadForm?.addEventListener(
        'submit',
        (event) => {
            /*
             * Do not add was-validated to the whole form.
             * It incorrectly applies green valid styling to
             * the optional main-photo checkbox.
             */
            const photoIsValid =
                validateSelectedPhoto();

            const visibilityIsValid =
                uploadVisibilitySelect
                    ? uploadVisibilitySelect
                        .checkValidity()
                    : false;

            if (
                !photoIsValid
                || !visibilityIsValid
                || !uploadForm.checkValidity()
            ) {
                event.preventDefault();
                event.stopPropagation();

                if (!visibilityIsValid) {
                    uploadVisibilitySelect
                        ?.classList.add(
                            'is-invalid'
                        );
                }

                photoInput?.focus();

                return;
            }

            uploadVisibilitySelect
                ?.classList.remove(
                    'is-invalid'
                );

            if (!submitButton) {
                return;
            }

            window.setTimeout(() => {
                if (
                    event.defaultPrevented
                    || !uploadForm.checkValidity()
                ) {
                    return;
                }

                showButtonLoading(
                    submitButton,
                    '.registration-submit__label',
                    '.registration-submit__loading'
                );
            }, 0);
        }
    );

    uploadVisibilitySelect?.addEventListener(
        'change',
        () => {
            uploadVisibilitySelect
                .classList.toggle(
                    'is-invalid',
                    !uploadVisibilitySelect
                        .checkValidity()
                );
        }
    );

    document
        .querySelectorAll(
            '[data-photo-visibility-form]'
        )
        .forEach((form) => {
            form.addEventListener(
                'submit',
                (event) => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();

                        return;
                    }

                    const saveButton =
                        event.submitter
                            instanceof HTMLButtonElement
                            ? event.submitter
                            : form.querySelector(
                                '[data-photo-visibility-button]'
                            );

                    if (
                        !(saveButton
                            instanceof HTMLButtonElement)
                    ) {
                        return;
                    }

                    window.setTimeout(() => {
                        if (event.defaultPrevented) {
                            return;
                        }

                        showButtonLoading(
                            saveButton,
                            '[data-visibility-label]',
                            '[data-visibility-loading]'
                        );
                    }, 0);
                }
            );
        });

    document
        .querySelectorAll(
            '[data-photo-primary-form]'
        )
        .forEach((form) => {
            form.addEventListener(
                'submit',
                (event) => {
                    const primaryButton =
                        event.submitter
                            instanceof HTMLButtonElement
                            ? event.submitter
                            : form.querySelector(
                                '[data-photo-primary-button]'
                            );

                    if (
                        !(primaryButton
                            instanceof HTMLButtonElement)
                    ) {
                        return;
                    }

                    window.setTimeout(() => {
                        if (event.defaultPrevented) {
                            return;
                        }

                        showButtonLoading(
                            primaryButton,
                            '[data-primary-label]',
                            '[data-primary-loading]'
                        );
                    }, 0);
                }
            );
        });

    document
        .querySelectorAll(
            '[data-photo-delete-form]'
        )
        .forEach((form) => {
            form.addEventListener(
                'submit',
                (event) => {
                    /*
                     * The reusable confirmation component intercepts the
                     * first submission. This handler runs only after the
                     * member confirms deletion.
                     */
                    if (event.defaultPrevented) {
                        return;
                    }

                    const deleteButton =
                        event.submitter
                            instanceof HTMLButtonElement
                            ? event.submitter
                            : form.querySelector(
                                '[data-photo-delete-button]'
                            );

                    if (
                        !(deleteButton
                            instanceof HTMLButtonElement)
                    ) {
                        return;
                    }

                    window.setTimeout(() => {
                        if (event.defaultPrevented) {
                            return;
                        }

                        deleteButton.disabled = true;

                        deleteButton.setAttribute(
                            'aria-busy',
                            'true'
                        );

                        deleteButton
                            .querySelector(
                                '[data-delete-label]'
                            )
                            ?.classList.add(
                                'd-none'
                            );

                        const loadingElement =
                            deleteButton.querySelector(
                                '[data-delete-loading]'
                            );

                        loadingElement?.classList.remove(
                            'd-none'
                        );

                        loadingElement?.classList.add(
                            'd-inline-flex'
                        );
                    }, 0);
                }
            );
        });

    window.addEventListener(
        'beforeunload',
        clearPreview
    );
});