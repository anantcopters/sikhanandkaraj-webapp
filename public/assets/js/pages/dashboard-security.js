'use strict';

document.addEventListener('DOMContentLoaded', () => {
    initializeDashboardSecurity();
    initializeEmailVerification();
});

/**
 * Protect the authenticated dashboard from browser-history restores.
 */
function initializeDashboardSecurity() {
    const logoutForm = document.getElementById(
        'dashboardLogoutForm'
    );

    if (!logoutForm) {
        return;
    }

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

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            window.location.reload();
        }
    });
}

/**
 * Submit the verification-email request without refreshing the page.
 */
function initializeEmailVerification() {
    const form = document.getElementById(
        'emailVerificationForm'
    );

    if (!form) {
        return;
    }

    const submitButton = document.getElementById(
        'emailVerificationSubmit'
    );

    const label = form.querySelector(
        '.email-verification-submit__label'
    );

    const loading = form.querySelector(
        '.registration-submit__loading'
    );

    if (
        !submitButton
        || !label
        || !loading
    ) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (submitButton.disabled) {
            return;
        }

        setLoadingState(
            submitButton,
            label,
            loading,
            true
        );

        try {
            const formData = new FormData(form);

            const response = await fetch(
                form.action,
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With':
                            'XMLHttpRequest',
                        'Accept':
                            'application/json'
                    },
                    credentials: 'same-origin'
                }
            );

            const result = await readJsonResponse(
                response
            );

            if (
                response.status === 401
                && result.redirectUrl
            ) {
                showFeedbackModal({
                    type: 'warning',
                    title:
                        result.title
                        || 'Session expired',
                    message:
                        result.message
                        || 'Please log in again.',
                    buttonText: 'Log in',
                    onClose: function () {
                        window.location.href =
                            result.redirectUrl;
                    }
                });

                return;
            }

            showFeedbackModal({
                type:
                    result.type
                    || (
                        response.ok
                            ? 'success'
                            : 'error'
                    ),
                title:
                    result.title
                    || (
                        response.ok
                            ? 'Request completed'
                            : 'Unable to complete request'
                    ),
                message:
                    result.message
                    || (
                        response.ok
                            ? 'The request was completed.'
                            : 'Please try again.'
                    ),
                buttonText:
                    result.buttonText
                    || 'Okay'
            });
        } catch (error) {
            showFeedbackModal({
                type: 'error',
                title: 'Unable to send email',
                message:
                    'The request could not be completed. '
                    + 'Please check your connection and '
                    + 'try again.',
                buttonText: 'Okay'
            });
        } finally {
            setLoadingState(
                submitButton,
                label,
                loading,
                false
            );
        }
    });
}

/**
 * Safely parse a JSON response.
 *
 * @param {Response} response
 * @returns {Promise<Object>}
 */
async function readJsonResponse(response) {
    const contentType =
        response.headers.get('content-type')
        || '';

    if (!contentType.includes('application/json')) {
        throw new Error(
            'Expected a JSON response.'
        );
    }

    return response.json();
}

/**
 * Open the application feedback modal.
 *
 * @param {Object} options
 */
function showFeedbackModal(options) {
    if (
        window.AppFeedbackModal
        && typeof window.AppFeedbackModal.show
        === 'function'
    ) {
        window.AppFeedbackModal.show(options);
        return;
    }

    /*
     * Defensive fallback. This should only run when the shared
     * modal JavaScript failed to load.
     */
    window.alert(
        options.message
        || 'The request was completed.'
    );
}

/**
 * Toggle the verification-email button loading state.
 *
 * @param {HTMLButtonElement} button
 * @param {HTMLElement} label
 * @param {HTMLElement} loading
 * @param {boolean} isLoading
 */
function setLoadingState(
    button,
    label,
    loading,
    isLoading
) {
    button.disabled = isLoading;
    button.setAttribute(
        'aria-busy',
        isLoading ? 'true' : 'false'
    );

    label.classList.toggle(
        'd-none',
        isLoading
    );

    loading.classList.toggle(
        'd-none',
        !isLoading
    );
}