/**
 * Global application JavaScript.
 *
 * This file contains JavaScript behaviour that is shared across the
 * application and should not be duplicated inside individual views.
 */

document.addEventListener('DOMContentLoaded', function () {
    /**
     * Initialize Bootstrap tooltips for all elements that explicitly opt in
     * using data-bs-toggle="tooltip".
     *
     * Using data attributes keeps tooltip configuration inside the view,
     * while this single initializer provides the behaviour application-wide.
     */
    const tooltipTriggerList = document.querySelectorAll(
        '[data-bs-toggle="tooltip"]'
    );

    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    /**
     * Adjust unusually tall member profile photographs.
     *
     * Normal photographs retain the existing object-fit: cover behaviour.
     * Tall photographs retain the standard thumbnail height while their
     * width is allowed to reduce according to the photograph's natural
     * aspect ratio.
     */
    const TALL_PHOTO_RATIO = 1.35;

    const profileThumbnails = document.querySelectorAll(
        '[data-member-profile-thumbnail]'
    );

    profileThumbnails.forEach(function (thumbnail) {
        const image = thumbnail.querySelector(
            '[data-member-profile-thumbnail-image]'
        );

        if (!image) {
            return;
        }

        const applyPhotoLayout = function () {
            if (
                image.naturalWidth <= 0
                || image.naturalHeight <= 0
            ) {
                return;
            }

            const imageRatio =
                image.naturalHeight
                / image.naturalWidth;

            thumbnail.classList.toggle(
                'is-tall-photo',
                imageRatio >= TALL_PHOTO_RATIO
            );
        };

        if (image.complete) {
            applyPhotoLayout();
            return;
        }

        image.addEventListener(
            'load',
            applyPhotoLayout,
            { once: true }
        );
    });
});