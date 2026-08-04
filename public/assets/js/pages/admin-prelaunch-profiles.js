/**
 * Administrator prelaunch profile-list interactions.
 *
 * Choices.js is initialized globally by select-choice.js. This page script
 * submits the status filter when its underlying select value changes.
 */
(function (document) {
    'use strict';

    const STATUS_FORM_ID =
        'prelaunch-status-form';

    const STATUS_SELECT_ID =
        'prelaunch-status-filter';

    /**
     * Initialize the prelaunch status filter.
     *
     * @returns {void}
     */
    function initialize() {
        const form = document.getElementById(
            STATUS_FORM_ID
        );

        const select = document.getElementById(
            STATUS_SELECT_ID
        );

        if (
            !(form instanceof HTMLFormElement)
            || !(select instanceof HTMLSelectElement)
        ) {
            return;
        }

        select.addEventListener(
            'change',
            function () {
                /*
                 * Do not carry the current page into a different status.
                 * The form includes only status and the optional search term.
                 */
                form.submit();
            }
        );
    }

    document.addEventListener(
        'DOMContentLoaded',
        initialize
    );
})(document);