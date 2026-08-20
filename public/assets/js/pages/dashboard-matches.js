(function () {
    'use strict';

    /**
     * Move profile modals outside dashboard scroll containers.
     *
     * ProfileThumbnail is rendered inside a horizontal overflow container.
     * Bootstrap modals must be direct body children so they are not clipped
     * by that container.
     *
     * @returns {void}
     */
    function moveProfileModalsToBody() {
        document
            .querySelectorAll(
                '[data-dashboard-profile-modal]'
            )
            .forEach(function (modalElement) {
                if (
                    !(
                        modalElement
                        instanceof HTMLElement
                    )
                    || modalElement.parentElement
                    === document.body
                ) {
                    return;
                }

                document.body.appendChild(
                    modalElement
                );
            });
    }

    /**
     * Scroll one member collection.
     *
     * @param {HTMLElement} container
     * @param {number} direction
     *
     * @returns {void}
     */
    function scrollProfiles(
        container,
        direction
    ) {
        const firstCard = container.querySelector(
            '.dashboard-profile-card'
        );

        const width = firstCard
            instanceof HTMLElement
            ? firstCard.getBoundingClientRect()
                .width + 16
            : 260;

        container.scrollBy({
            left: width * direction,
            behavior: 'smooth'
        });
    }

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            moveProfileModalsToBody();
        }
    );

    document.addEventListener(
        'click',
        function (event) {
            const button = event.target.closest(
                '[data-profile-scroll-previous],'
                + '[data-profile-scroll-next]'
            );

            if (
                !(button instanceof HTMLElement)
            ) {
                return;
            }

            const target = String(
                button.dataset
                    .profileScrollTarget
                ?? ''
            ).trim();

            if (target === '') {
                return;
            }

            const container =
                document.querySelector(
                    '[data-profile-scroll="'
                    + CSS.escape(target)
                    + '"]'
                );

            if (
                !(container instanceof HTMLElement)
            ) {
                return;
            }

            const direction =
                button.hasAttribute(
                    'data-profile-scroll-previous'
                )
                    ? -1
                    : 1;

            scrollProfiles(
                container,
                direction
            );
        }
    );
})();