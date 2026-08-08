'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const modalElement = document.querySelector(
        '[data-profile-gallery-modal]'
    );

    const carouselElement = document.querySelector(
        '[data-profile-gallery-carousel]'
    );

    if (
        !modalElement
        || !carouselElement
        || typeof bootstrap === 'undefined'
    ) {
        return;
    }

    const slides = Array.from(
        carouselElement.querySelectorAll(
            '[data-gallery-slide]'
        )
    );

    const positionLabel = modalElement.querySelector(
        '[data-profile-gallery-position]'
    );

    if (
        slides.length === 0
        || !positionLabel
    ) {
        return;
    }

    const carousel = bootstrap.Carousel
        .getOrCreateInstance(
            carouselElement,
            {
                interval: false,
                touch: true,
                wrap: true
            }
        );

    /*
     * Keep signed URLs only in memory for the lifetime of this page.
     */
    const photoUrlCache = new Map();

    /*
     * Keep one active request controller per slide so requests can be
     * cancelled cleanly when the modal closes.
     */
    const activeRequests = new Map();

    /**
     * Return the numeric slide index.
     */
    function getSlideIndex(slide) {
        const index = Number(
            slide.dataset.slideIndex
        );

        return Number.isInteger(index)
            ? index
            : 0;
    }

    /**
     * Update "N of total" text.
     */
    function updatePosition(index) {
        positionLabel.textContent =
            `${index + 1} of ${slides.length}`;
    }

    /**
     * Show a loading state for one carousel slide.
     */
    function showLoading(slide) {
        const loadingPanel = slide.querySelector(
            '[data-slide-loading]'
        );

        const errorPanel = slide.querySelector(
            '[data-slide-error]'
        );

        const image = slide.querySelector(
            '[data-slide-image]'
        );

        loadingPanel?.classList.remove(
            'd-none'
        );

        errorPanel?.classList.add(
            'd-none'
        );

        if (errorPanel) {
            errorPanel.textContent = '';
        }

        image?.classList.add(
            'd-none'
        );
    }

    /**
     * Show one slide error.
     */
    function showError(slide, message) {
        const loadingPanel = slide.querySelector(
            '[data-slide-loading]'
        );

        const errorPanel = slide.querySelector(
            '[data-slide-error]'
        );

        const image = slide.querySelector(
            '[data-slide-image]'
        );

        loadingPanel?.classList.add(
            'd-none'
        );

        image?.classList.add(
            'd-none'
        );

        if (image) {
            image.removeAttribute(
                'src'
            );
        }

        if (errorPanel) {
            errorPanel.textContent = message;
            errorPanel.classList.remove(
                'd-none'
            );
        }
    }

    /**
     * Display the viewer-authorized medium profile photo.
     *
     * The profile gallery deliberately never exposes original
     * uploaded photographs.
     */
    function displayImage(
        slide,
        mediumUrl
    ) {
        const loadingPanel = slide.querySelector(
            '[data-slide-loading]'
        );

        const errorPanel = slide.querySelector(
            '[data-slide-error]'
        );

        const image = slide.querySelector(
            '[data-slide-image]'
        );

        if (!image) {
            return;
        }

        function revealImage() {
            loadingPanel?.classList.add(
                'd-none'
            );

            errorPanel?.classList.add(
                'd-none'
            );

            image.classList.remove(
                'd-none'
            );

            image.onload = null;
            image.onerror = null;
        }

        function failImage() {
            image.onload = null;
            image.onerror = null;

            showError(
                slide,
                'The enlarged photo could not be loaded.'
            );
        }

        image.onload = revealImage;
        image.onerror = failImage;

        image.src = mediumUrl;
    }

    /**
     * Request the original and medium URLs for one slide.
     */
    async function fetchPhotoUrls(
        slide,
        photoId,
        endpoint
    ) {
        if (photoUrlCache.has(photoId)) {
            return photoUrlCache.get(
                photoId
            );
        }

        const controller =
            new AbortController();

        activeRequests.set(
            photoId,
            controller
        );

        const response = await fetch(
            endpoint,
            {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With':
                        'XMLHttpRequest'
                },
                credentials: 'same-origin',
                cache: 'no-store',
                signal: controller.signal
            }
        );

        let payload;

        try {
            payload = await response.json();
        } catch (error) {
            throw new Error(
                'The enlarged photo could not be loaded.'
            );
        } finally {
            activeRequests.delete(
                photoId
            );
        }

        if (
            !response.ok
            || payload.status !== 'success'
        ) {
            throw new Error(
                payload.message
                || 'The enlarged photo could not be loaded.'
            );
        }

        const urls = {
            mediumUrl: String(
                payload.data?.mediumUrl
                || ''
            ).trim()
        };

        if (urls.mediumUrl === '') {
            throw new Error(
                'The enlarged photo is unavailable.'
            );
        }

        /*
         * Owner profile:
         *     originalUrl + mediumUrl
         *
         * Other-member profile:
         *     mediumUrl only
         *
         * At least one authorized display URL must exist.
         */
        if (
            urls.originalUrl === ''
            && urls.mediumUrl === ''
        ) {
            throw new Error(
                'The enlarged photo is unavailable.'
            );
        }

        photoUrlCache.set(
            photoId,
            urls
        );

        return urls;
    }

    /**
     * Lazily load one carousel slide.
     */
    async function loadSlide(slide) {
        if (
            !(slide instanceof HTMLElement)
            || slide.dataset.loaded === '1'
        ) {
            return;
        }

        const photoId = String(
            slide.dataset.photoId
            || ''
        ).trim();

        const endpoint = String(
            slide.dataset.modalUrlEndpoint
            || ''
        ).trim();

        if (
            photoId === ''
            || endpoint === ''
        ) {
            showError(
                slide,
                'The selected photo is invalid.'
            );

            return;
        }

        showLoading(slide);

        try {
            const urls = await fetchPhotoUrls(
                slide,
                photoId,
                endpoint
            );

            slide.dataset.loaded = '1';

            displayImage(
                slide,
                urls.mediumUrl
            );
        } catch (error) {
            if (
                error instanceof DOMException
                && error.name === 'AbortError'
            ) {
                return;
            }

            showError(
                slide,
                error instanceof Error
                    ? error.message
                    : 'The enlarged photo could not be loaded.'
            );
        }
    }

    /**
     * Return the currently active carousel slide.
     */
    function activeSlide() {
        return carouselElement.querySelector(
            '.carousel-item.active'
        );
    }

    /*
     * Open the carousel on the thumbnail selected by the member.
     */
    modalElement.addEventListener(
        'show.bs.modal',
        (event) => {
            const trigger = event.relatedTarget;

            let requestedIndex = 0;

            if (trigger instanceof HTMLElement) {
                const index = Number(
                    trigger.dataset.slideIndex
                );

                if (
                    Number.isInteger(index)
                    && index >= 0
                    && index < slides.length
                ) {
                    requestedIndex = index;
                }
            }

            carousel.to(
                requestedIndex
            );

            updatePosition(
                requestedIndex
            );
        }
    );

    /*
     * Load the selected slide after the modal is visible.
     */
    modalElement.addEventListener(
        'shown.bs.modal',
        () => {
            const slide = activeSlide();

            if (slide instanceof HTMLElement) {
                const index = getSlideIndex(
                    slide
                );

                updatePosition(
                    index
                );

                loadSlide(
                    slide
                );
            }
        }
    );

    /*
     * Load each new slide only after navigation reaches it.
     */
    carouselElement.addEventListener(
        'slid.bs.carousel',
        (event) => {
            const index = Number(
                event.to
            );

            if (
                Number.isInteger(index)
                && index >= 0
                && index < slides.length
            ) {
                updatePosition(
                    index
                );

                loadSlide(
                    slides[index]
                );
            }
        }
    );

    /*
     * Cancel in-flight requests when the gallery closes.
     */
    modalElement.addEventListener(
        'hidden.bs.modal',
        () => {
            activeRequests.forEach(
                (controller) => {
                    controller.abort();
                }
            );

            activeRequests.clear();
        }
    );
});