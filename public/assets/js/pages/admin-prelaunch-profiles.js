/**
 * Administrator prelaunch profile-list interactions.
 *
 * The global SelectChoice component enhances the status select. This file
 * handles only page-specific filter submission.
 */
(function (document) {
    'use strict';

    const STATUS_FORM_ID =
        'prelaunch-status-form';

    const STATUS_SELECT_ID =
        'prelaunch-status-filter';

    /**
     * Initialize automatic status-filter submission.
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
                 * A status change always returns to page one because the
                 * previous page may not exist for the newly selected status.
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