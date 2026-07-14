'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const logoutForm = document.getElementById(
        'dashboardLogoutForm'
    );

    if (!logoutForm) {
        return;
    }

    /**
     * Add one controlled history entry.
     *
     * Pressing Back from the dashboard triggers popstate and submits
     * the secure POST logout form.
     */
    window.history.pushState(
        { dashboard: true },
        '',
        window.location.href
    );

    let logoutStarted = false;

    window.addEventListener('popstate', () => {
        if (logoutStarted) {
            return;
        }

        logoutStarted = true;
        logoutForm.submit();
    });

    /**
     * When a page is restored from the browser back-forward cache,
     * reload it so the server authentication filter is executed.
     */
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            window.location.reload();
        }
    });
});

