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
});