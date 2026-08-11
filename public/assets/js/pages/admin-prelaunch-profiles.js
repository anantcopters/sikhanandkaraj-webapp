/**
 * Administrator prelaunch-profile list interactions.
 *
 * Choices.js is initialized globally by select-choice.js. This script only
 * handles automatic status-filter submission.
 */
(function (document) {
    'use strict';

    const STATUS_FORM_ID =
        'prelaunch-status-form';

    const STATUS_SELECT_ID =
        'prelaunch-status-filter';

    /**
     * Initialize the status filter.
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
                 * The status form intentionally does not carry the current
                 * page parameter. A changed status therefore starts at page 1.
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