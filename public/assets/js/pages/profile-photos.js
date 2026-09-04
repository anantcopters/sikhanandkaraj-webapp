'use strict';

document.addEventListener('DOMContentLoaded', () => {

    const uploadAdjuster =
        document.getElementById(
            'profile-photo-adjuster'
        );

    const uploadFocalX =
        document.getElementById(
            'profile-photo-focal-x'
        );

    const uploadFocalY =
        document.getElementById(
            'profile-photo-focal-y'
        );

    const positionModal =
        document.getElementById(
            'photo-position-modal'
        );

    const positionForm =
        document.getElementById(
            'photo-position-form'
        );

    const positionImage =
        positionModal?.querySelector(
            '[data-existing-photo-adjuster-image]'
        );

    const positionAdjuster =
        positionModal?.querySelector(
            '[data-existing-photo-adjuster]'
        );

    const positionX =
        document.getElementById(
            'photo-position-x'
        );

    const positionY =
        document.getElementById(
            'photo-position-y'
        );

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

    const configuredMaximumFileSize =
        Number.parseInt(
            photoInput?.dataset
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
            : 10 * 1024 * 1024;

    const maximumFileSizeLabel =
        photoInput?.dataset
            .maximumFileSizeLabel
        ?? '10 MB';

    const minimumWidth =
        Number.parseInt(
            photoInput?.dataset
                .minimumWidth
            ?? '300',
            10
        );

    const minimumHeight =
        Number.parseInt(
            photoInput?.dataset
                .minimumHeight
            ?? '300',
            10
        );

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
 * Make a cover image draggable by updating its CSS object-position.
 *
 * @param {HTMLElement|null} container
 * @param {HTMLImageElement|null} image
 * @param {HTMLInputElement|null} xInput
 * @param {HTMLInputElement|null} yInput
 */
    function initializePhotoAdjuster(
        container,
        image,
        xInput,
        yInput
    ) {
        if (
            !(container instanceof HTMLElement)
            || !(image instanceof HTMLImageElement)
            || !(xInput instanceof HTMLInputElement)
            || !(yInput instanceof HTMLInputElement)
        ) {
            return;
        }

        let dragging = false;
        let startClientX = 0;
        let startClientY = 0;
        let startX = 50;
        let startY = 20;

        const clamp = (value) =>
            Math.max(
                0,
                Math.min(100, value)
            );

        const applyPosition = () => {
            const x = clamp(
                Number.parseInt(
                    xInput.value || '50',
                    10
                )
            );

            const y = clamp(
                Number.parseInt(
                    yInput.value || '20',
                    10
                )
            );

            xInput.value =
                String(Math.round(x));

            yInput.value =
                String(Math.round(y));

            image.style.objectPosition =
                `${xInput.value}% ${yInput.value}%`;
        };

        container.addEventListener(
            'pointerdown',
            (event) => {
                dragging = true;

                startClientX =
                    event.clientX;

                startClientY =
                    event.clientY;

                startX =
                    Number.parseInt(
                        xInput.value || '50',
                        10
                    );

                startY =
                    Number.parseInt(
                        yInput.value || '20',
                        10
                    );

                container.setPointerCapture(
                    event.pointerId
                );
            }
        );

        container.addEventListener(
            'pointermove',
            (event) => {
                if (!dragging) {
                    return;
                }

                const rect =
                    container
                        .getBoundingClientRect();

                if (
                    rect.width <= 0
                    || rect.height <= 0
                ) {
                    return;
                }

                const deltaX =
                    (
                        event.clientX
                        - startClientX
                    )
                    / rect.width
                    * 100;

                const deltaY =
                    (
                        event.clientY
                        - startClientY
                    )
                    / rect.height
                    * 100;

                /*
                 * Moving the photograph right/down means the visible focal
                 * point moves left/up inside the source image.
                 */
                xInput.value =
                    String(
                        Math.round(
                            clamp(
                                startX
                                - deltaX
                            )
                        )
                    );

                yInput.value =
                    String(
                        Math.round(
                            clamp(
                                startY
                                - deltaY
                            )
                        )
                    );

                applyPosition();
            }
        );

        const stopDragging = () => {
            dragging = false;
        };

        container.addEventListener(
            'pointerup',
            stopDragging
        );

        container.addEventListener(
            'pointercancel',
            stopDragging
        );

        container.addEventListener(
            'lostpointercapture',
            stopDragging
        );

        container.photoPositionApply =
            applyPosition;

        applyPosition();
    }

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
 * Read image dimensions without uploading the file.
 *
 * @param {File} file
 * @returns {Promise<{width:number,height:number}>}
 */
    function readImageDimensions(file) {
        return new Promise(
            (resolve, reject) => {
                const objectUrl =
                    URL.createObjectURL(
                        file
                    );

                const image =
                    new Image();

                image.addEventListener(
                    'load',
                    () => {
                        const dimensions = {
                            width:
                                image.naturalWidth,
                            height:
                                image.naturalHeight
                        };

                        URL.revokeObjectURL(
                            objectUrl
                        );

                        resolve(
                            dimensions
                        );
                    }
                );

                image.addEventListener(
                    'error',
                    () => {
                        URL.revokeObjectURL(
                            objectUrl
                        );

                        reject(
                            new Error(
                                'Image dimensions could not be read.'
                            )
                        );
                    }
                );

                image.src =
                    objectUrl;
            }
        );
    }

    /**
     * Validate the selected upload before submission.
     *
     * @returns {boolean}
     */
    async function validateSelectedPhoto() {
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
                `The photo must not exceed ${maximumFileSizeLabel}.`
            );

            return false;
        }

        try {
            const dimensions =
                await readImageDimensions(
                    selectedFile
                );

            if (
                dimensions.width < minimumWidth
                || dimensions.height < minimumHeight
            ) {
                setPhotoError(
                    `The photo must be at least ${minimumWidth} × ${minimumHeight} pixels.`
                );

                return false;
            }
        } catch (error) {
            setPhotoError(
                'The selected file is not a valid image.'
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

    initializePhotoAdjuster(
        uploadAdjuster,
        preview,
        uploadFocalX,
        uploadFocalY
    );

    photoInput?.addEventListener(
        'change',
        async () => {
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

            const photoIsValid =
                await validateSelectedPhoto();

            if (!photoIsValid) {
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

            if (uploadFocalX) {
                uploadFocalX.value = '50';
            }

            if (uploadFocalY) {
                uploadFocalY.value = '20';
            }

            preview.src = currentObjectUrl;

            uploadAdjuster
                ?.photoPositionApply?.();

            previewWrapper.classList.remove(
                'd-none'
            );
        }
    );

    uploadForm?.addEventListener(
        'submit',
        async (event) => {
            /*
             * Do not add was-validated to the whole form.
             * It incorrectly applies green valid styling to
             * the optional main-photo checkbox.
             */
            event.preventDefault();

            const photoIsValid =
                await validateSelectedPhoto();

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

            if (submitButton) {
                showButtonLoading(
                    submitButton,
                    '.registration-submit__label',
                    '.registration-submit__loading'
                );
            }

            /*
            * requestSubmit() would fire this same handler again.
            * Native submit() continues only after our asynchronous checks succeed.
            */
            uploadForm.submit();

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

    /**
 * Show loading state while saving photo visibility.
 *
 * Visibility forms submit normally, so the loader must be
 * applied synchronously before browser navigation begins.
 */
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

                        form.reportValidity();

                        return;
                    }

                    /*
                     * The visibility button belongs to this form,
                     * so resolve it locally rather than searching
                     * the whole document.
                     */
                    const saveButton =
                        event.submitter
                            instanceof HTMLButtonElement
                            ? event.submitter
                            : form.querySelector(
                                '[data-photo-visibility-button]'
                            );

                    if (
                        !(
                            saveButton
                            instanceof HTMLButtonElement
                        )
                    ) {
                        return;
                    }

                    /*
                     * Do this immediately.
                     *
                     * A setTimeout() here is unreliable because
                     * native form submission begins page navigation
                     * as soon as this event handler returns.
                     */
                    showButtonLoading(
                        saveButton,
                        '[data-visibility-label]',
                        '[data-visibility-loading]'
                    );
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

                        showButtonLoading(
                            deleteButton,
                            '[data-delete-label]',
                            '[data-delete-loading]'
                        );

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

    document
        .querySelectorAll(
            '[data-photo-position-button]'
        )
        .forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    if (
                        !positionForm
                        || !positionImage
                        || !positionX
                        || !positionY
                    ) {
                        return;
                    }

                    const photoId =
                        Number.parseInt(
                            button.dataset
                                .photoId
                            ?? '',
                            10
                        );

                    if (
                        !Number.isInteger(photoId)
                        || photoId <= 0
                    ) {
                        return;
                    }

                    const positionUrl =
                        button.dataset.positionUrl
                        ?? '';

                    if (positionUrl === '') {
                        return;
                    }

                    positionForm.action =
                        positionUrl;

                    positionImage.src =
                        button.dataset
                            .photoUrl
                        ?? '';

                    positionX.value =
                        button.dataset
                            .focalX
                        ?? '50';

                    positionY.value =
                        button.dataset
                            .focalY
                        ?? '20';

                    positionAdjuster
                        ?.photoPositionApply?.();
                }
            );
        });

    positionForm?.addEventListener(
        'submit',
        (event) => {
            if (!positionForm.checkValidity()) {
                event.preventDefault();

                positionForm.reportValidity();

                return;
            }

            const button =
                event.submitter;

            if (
                !(button
                    instanceof HTMLButtonElement)
            ) {
                return;
            }

            showButtonLoading(
                button,
                '[data-position-label]',
                '[data-position-loading]'
            );
        }
    );

    initializePhotoAdjuster(
        positionAdjuster,
        positionImage,
        positionX,
        positionY
    );
});