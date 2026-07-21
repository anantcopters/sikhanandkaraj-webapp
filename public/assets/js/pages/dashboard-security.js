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

        let cooldownStarted = false;

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

            /*
            * CodeIgniter regenerates the CSRF token after each valid POST.
            * Replace the old hidden value before another AJAX submission.
            */
            updateCsrfToken(
                form,
                result.csrf
            );

            /*
             * Redirect the member to login only after they acknowledge
             * that the authenticated session has expired.
             */
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

            /*
             * A verification email was successfully queued.
             * Keep the form visible but disable the button temporarily.
             */
            if (
                response.ok
                && result.success
            ) {
                showFeedbackModal({
                    type:
                        result.type
                        || 'success',
                    title:
                        result.title
                        || 'Verification email sent',
                    message:
                        result.message
                        || 'Please check your inbox.',
                    buttonText:
                        result.buttonText
                        || 'Okay'
                });

                startVerificationCooldown(
                    submitButton,
                    label,
                    loading,
                    getRetryAfter(
                        result.retryAfter,
                        60
                    )
                );

                cooldownStarted = true;

                return;
            }

            /*
             * The server rejected the request because the resend
             * cooldown is still active. Continue the countdown using
             * the server-provided remaining duration.
             */
            if (
                !response.ok
                && hasValidRetryAfter(
                    result.retryAfter
                )
            ) {
                showFeedbackModal({
                    type:
                        result.type
                        || 'warning',
                    title:
                        result.title
                        || 'Please wait',
                    message:
                        result.message
                        || 'Please wait before trying again.',
                    buttonText:
                        result.buttonText
                        || 'Okay'
                });

                startVerificationCooldown(
                    submitButton,
                    label,
                    loading,
                    result.retryAfter
                );

                cooldownStarted = true;

                return;
            }

            /*
             * Business-rule failures such as a missing or invalid
             * email address do not start a cooldown.
             */
            showFeedbackModal({
                type:
                    result.type
                    || 'warning',
                title:
                    result.title
                    || 'Unable to send email',
                message:
                    result.message
                    || 'Please try again.',
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
            /*
             * A cooldown manages the button state itself. For every
             * other outcome, restore the normal button state.
             */
            if (!cooldownStarted) {
                setLoadingState(
                    submitButton,
                    label,
                    loading,
                    false
                );
            }
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
 * Replace the form's CSRF token after an AJAX request.
 *
 * CodeIgniter regenerates the token after every accepted POST when
 * Security::$regenerate is enabled.
 *
 * @param {HTMLFormElement} form
 * @param {Object|null} csrf
 */
function updateCsrfToken(
    form,
    csrf
) {
    if (
        !csrf
        || typeof csrf.tokenName !== 'string'
        || typeof csrf.tokenHash !== 'string'
        || csrf.tokenName === ''
        || csrf.tokenHash === ''
    ) {
        return;
    }

    let csrfInput = form.querySelector(
        'input[name="' + csrf.tokenName + '"]'
    );

    /*
     * Normally csrf_field() already creates the input. Creating it
     * defensively keeps this function reusable for other AJAX forms.
     */
    if (!csrfInput) {
        csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = csrf.tokenName;

        form.appendChild(csrfInput);
    }

    csrfInput.value = csrf.tokenHash;
}

/**
 * Open the shared application feedback modal.
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
     * Defensive fallback for cases where the shared modal JavaScript
     * failed to load.
     */
    window.alert(
        options.message
        || 'The request was completed.'
    );

    if (
        typeof options.onClose
        === 'function'
    ) {
        options.onClose();
    }
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

/**
 * Determine whether the API returned a valid resend duration.
 *
 * @param {*} retryAfter
 * @returns {boolean}
 */
function hasValidRetryAfter(retryAfter) {
    const parsedRetryAfter = Number.parseInt(
        retryAfter,
        10
    );

    return Number.isInteger(parsedRetryAfter)
        && parsedRetryAfter > 0;
}

/**
 * Return a safe resend duration.
 *
 * @param {*} retryAfter
 * @param {number} fallbackSeconds
 * @returns {number}
 */
function getRetryAfter(
    retryAfter,
    fallbackSeconds
) {
    if (hasValidRetryAfter(retryAfter)) {
        return Number.parseInt(
            retryAfter,
            10
        );
    }

    return fallbackSeconds;
}

/**
 * Prevent repeated verification-email requests during cooldown.
 *
 * @param {HTMLButtonElement} button
 * @param {HTMLElement} label
 * @param {HTMLElement} loading
 * @param {number} seconds
 */
function startVerificationCooldown(
    button,
    label,
    loading,
    seconds
) {
    loading.classList.add('d-none');
    label.classList.remove('d-none');

    let remaining = Math.max(
        1,
        Number.parseInt(seconds, 10) || 60
    );

    let timer = null;

    button.disabled = true;

    button.setAttribute(
        'aria-busy',
        'false'
    );

    function updateLabel() {
        if (remaining <= 0) {
            if (timer !== null) {
                window.clearInterval(timer);
            }

            label.textContent =
                'Resend verification email';

            button.disabled = false;

            return;
        }

        label.textContent =
            'Resend in '
            + remaining
            + 's';

        remaining -= 1;
    }

    updateLabel();

    timer = window.setInterval(
        updateLabel,
        1000
    );
}