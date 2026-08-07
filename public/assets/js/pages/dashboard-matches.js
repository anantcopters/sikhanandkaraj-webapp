(function () {
    'use strict';

    /**
     * Scroll one member collection.
     *
     * @param {HTMLElement} container
     * @param {number} direction
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