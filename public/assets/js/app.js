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
 * Standard thumbnails remain 160px × 160px.
 * For unusually tall photographs, retain the 160px height and calculate
 * the corresponding container width from the photograph's natural
 * aspect ratio.
 */
    const TALL_PHOTO_RATIO = 1.35;
    const THUMBNAIL_HEIGHT = 160;

    /**
     * Apply the appropriate presentation to one member thumbnail.
     */
    const initializeMemberThumbnail = function (thumbnail) {
        if (
            !(thumbnail instanceof HTMLElement)
            || thumbnail.dataset.thumbnailInitialized === 'true'
        ) {
            return;
        }

        const image = thumbnail.querySelector(
            '[data-member-profile-thumbnail-image]'
        );

        if (!(image instanceof HTMLImageElement)) {
            return;
        }

        thumbnail.dataset.thumbnailInitialized = 'true';

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

            const isTallPhoto =
                imageRatio >= TALL_PHOTO_RATIO;

            const photoColumn = thumbnail.closest(
                '.member-profile-photo-column'
            );

            if (!isTallPhoto) {
                thumbnail.classList.remove(
                    'is-tall-photo'
                );

                thumbnail.style.removeProperty(
                    '--member-thumbnail-width'
                );

                if (photoColumn) {
                    photoColumn.style.removeProperty(
                        '--member-photo-column-width'
                    );
                }

                return;
            }

            const thumbnailWidth =
                THUMBNAIL_HEIGHT
                * image.naturalWidth
                / image.naturalHeight;

            thumbnail.style.setProperty(
                '--member-thumbnail-width',
                `${thumbnailWidth}px`
            );

            thumbnail.classList.add(
                'is-tall-photo'
            );

            if (photoColumn) {
                photoColumn.style.setProperty(
                    '--member-photo-column-width',
                    `${thumbnailWidth}px`
                );
            }
        };

        if (
            image.complete
            && image.naturalWidth > 0
        ) {
            applyPhotoLayout();
            return;
        }

        image.addEventListener(
            'load',
            applyPhotoLayout,
            { once: true }
        );
    };

    /**
     * Initialize all member thumbnails contained by the supplied DOM node.
     */
    const initializeMemberThumbnails = function (root) {
        if (!(root instanceof Element)) {
            return;
        }

        if (
            root.matches(
                '[data-member-profile-thumbnail]'
            )
        ) {
            initializeMemberThumbnail(root);
        }

        root.querySelectorAll(
            '[data-member-profile-thumbnail]'
        ).forEach(
            initializeMemberThumbnail
        );
    };

    /*
     * Process thumbnails rendered with the initial page.
     */
    document.querySelectorAll(
        '[data-member-profile-thumbnail]'
    ).forEach(
        initializeMemberThumbnail
    );

    /*
     * Profile cards can also be inserted after the initial page load.
     *
     * Observe only added DOM nodes and initialize thumbnails contained
     * within those nodes. Already initialized thumbnails are ignored.
     */
    const memberThumbnailObserver =
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (!(node instanceof Element)) {
                        return;
                    }

                    initializeMemberThumbnails(node);
                });
            });
        });

    memberThumbnailObserver.observe(
        document.body,
        {
            childList: true,
            subtree: true,
        }
    );
});