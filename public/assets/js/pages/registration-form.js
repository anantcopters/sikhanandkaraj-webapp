'use strict';

/**
 * Shared authentication form behaviour.
 *
 * Used by:
 * - Forgot-password identifier screen.
 * - Password-reset OTP screen.
 * - Set-new-password screen.
 *
 * Responsibilities:
 * - Client-side identifier validation.
 * - OTP normalization and validation.
 * - Password-policy validation.
 * - Password-confirmation validation.
 * - Password visibility toggles.
 * - OTP expiry countdown.
 * - OTP resend-button state.
 * - Duplicate-submit prevention.
 * - Submit-button loading state.
 *
 * Server-side validation remains authoritative.
 */
document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll(
        '[data-registration-form]'
    );

    forms.forEach((form) => {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        initializeForm(form);
    });

    initializePasswordToggles();
});

/**
 * Initialize one authentication form.
 *
 * @param {HTMLFormElement} form
 * @returns {void}
 */
function initializeForm(form) {
    const identifierInput = form.querySelector(
        '[data-identifier]'
    );

    const otpInput = form.querySelector(
        '[data-otp-input]'
    );

    const passwordInput = form.querySelector(
        '[data-password]'
    );

    const passwordConfirmationInput = form.querySelector(
        '[data-password-confirmation]'
    );

    let isSubmitting = false;

    if (identifierInput instanceof HTMLInputElement) {
        identifierInput.addEventListener('input', () => {
            clearFieldError(identifierInput);
        });

        identifierInput.addEventListener('blur', () => {
            validateIdentifier(identifierInput);
        });
    }

    if (otpInput instanceof HTMLInputElement) {
        otpInput.addEventListener('input', () => {
            otpInput.value = normalizeOtp(
                otpInput.value
            );

            clearFieldError(otpInput);
        });

        otpInput.addEventListener('paste', (event) => {
            event.preventDefault();

            const pastedValue =
                event.clipboardData?.getData('text') ?? '';

            otpInput.value = normalizeOtp(
                pastedValue
            );

            clearFieldError(otpInput);
        });

        otpInput.addEventListener('blur', () => {
            validateOtp(otpInput);
        });
    }

    if (passwordInput instanceof HTMLInputElement) {
        passwordInput.addEventListener('input', () => {
            clearFieldError(passwordInput);

            if (
                passwordConfirmationInput
                instanceof HTMLInputElement
                && passwordConfirmationInput.value !== ''
            ) {
                validatePasswordConfirmation(
                    passwordInput,
                    passwordConfirmationInput
                );
            }
        });

        passwordInput.addEventListener('blur', () => {
            validatePassword(passwordInput);
        });
    }

    if (
        passwordConfirmationInput
        instanceof HTMLInputElement
    ) {
        passwordConfirmationInput.addEventListener(
            'input',
            () => {
                clearFieldError(
                    passwordConfirmationInput
                );
            }
        );

        passwordConfirmationInput.addEventListener(
            'blur',
            () => {
                if (
                    passwordInput
                    instanceof HTMLInputElement
                ) {
                    validatePasswordConfirmation(
                        passwordInput,
                        passwordConfirmationInput
                    );
                }
            }
        );
    }

    if (form.matches('[data-otp-form]')) {
        initializeOtpTimer(form);
    }

    form.addEventListener('submit', (event) => {
        if (isSubmitting) {
            event.preventDefault();
            return;
        }

        let isValid = true;

        if (identifierInput instanceof HTMLInputElement) {
            isValid =
                validateIdentifier(identifierInput)
                && isValid;
        }

        if (otpInput instanceof HTMLInputElement) {
            isValid =
                validateOtp(otpInput)
                && isValid;
        }

        if (passwordInput instanceof HTMLInputElement) {
            isValid =
                validatePassword(passwordInput)
                && isValid;
        }

        if (
            passwordInput instanceof HTMLInputElement
            && passwordConfirmationInput
            instanceof HTMLInputElement
        ) {
            isValid =
                validatePasswordConfirmation(
                    passwordInput,
                    passwordConfirmationInput
                )
                && isValid;
        }

        if (!isValid) {
            event.preventDefault();

            const firstInvalidField = form.querySelector(
                '.is-invalid'
            );

            if (
                firstInvalidField
                instanceof HTMLElement
            ) {
                firstInvalidField.focus();
            }

            return;
        }

        isSubmitting = true;
        activateSubmitLoader(form);
    });
}

/**
 * Validate email address or Indian mobile number.
 *
 * Accepted mobile formats:
 * - 10 digits.
 * - +91 followed by 10 digits.
 * - 91 followed by 10 digits.
 *
 * @param {HTMLInputElement} input
 * @returns {boolean}
 */
function validateIdentifier(input) {
    const value = input.value.trim();

    if (value === '') {
        setFieldError(
            input,
            input.dataset.errorRequired
            ?? 'This field is required.'
        );

        return false;
    }

    const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(
        value
    );

    const normalizedMobile = value.replace(
        /\D/g,
        ''
    );

    const isMobile =
        /^[6-9]\d{9}$/.test(normalizedMobile)
        || /^91[6-9]\d{9}$/.test(normalizedMobile);

    if (!isEmail && !isMobile) {
        setFieldError(
            input,
            input.dataset.errorInvalid
            ?? 'Enter a valid email address or mobile number.'
        );

        return false;
    }

    clearFieldError(input);

    return true;
}

/**
 * Validate a four-digit OTP.
 *
 * @param {HTMLInputElement} input
 * @returns {boolean}
 */
function validateOtp(input) {
    input.value = normalizeOtp(
        input.value
    );

    if (input.value === '') {
        setFieldError(
            input,
            input.dataset.errorRequired
            ?? 'Please enter the OTP.'
        );

        return false;
    }

    if (!/^\d{4}$/.test(input.value)) {
        setFieldError(
            input,
            input.dataset.errorInvalid
            ?? 'Enter the complete four-digit OTP.'
        );

        return false;
    }

    clearFieldError(input);

    return true;
}

/**
 * Normalize an OTP value.
 *
 * @param {string} value
 * @returns {string}
 */
function normalizeOtp(value) {
    return value
        .replace(/\D/g, '')
        .slice(0, 4);
}

/**
 * Validate the project password policy.
 *
 * Required:
 * - Minimum 10 characters.
 * - At least one uppercase character.
 * - At least one lowercase character.
 * - At least one number.
 * - At least one special character.
 *
 * @param {HTMLInputElement} input
 * @returns {boolean}
 */
function validatePassword(input) {
    const value = input.value;

    if (value === '') {
        setFieldError(
            input,
            input.dataset.errorRequired
            ?? 'Please enter a password.'
        );

        return false;
    }

    const isValid =
        value.length >= 10
        && /[A-Z]/.test(value)
        && /[a-z]/.test(value)
        && /\d/.test(value)
        && /[^A-Za-z0-9]/.test(value);

    if (!isValid) {
        setFieldError(
            input,
            input.dataset.errorInvalid
            ?? 'Enter a valid password.'
        );

        return false;
    }

    clearFieldError(input);

    return true;
}

/**
 * Validate password confirmation.
 *
 * @param {HTMLInputElement} passwordInput
 * @param {HTMLInputElement} confirmationInput
 * @returns {boolean}
 */
function validatePasswordConfirmation(
    passwordInput,
    confirmationInput
) {
    if (confirmationInput.value === '') {
        setFieldError(
            confirmationInput,
            confirmationInput.dataset.errorRequired
            ?? 'Please confirm your password.'
        );

        return false;
    }

    if (
        confirmationInput.value
        !== passwordInput.value
    ) {
        setFieldError(
            confirmationInput,
            confirmationInput.dataset.errorMismatch
            ?? 'Passwords do not match.'
        );

        return false;
    }

    clearFieldError(confirmationInput);

    return true;
}

/**
 * Show a field error.
 *
 * @param {HTMLInputElement} input
 * @param {string} message
 * @returns {void}
 */
function setFieldError(input, message) {
    input.classList.add('is-invalid');
    input.setAttribute(
        'aria-invalid',
        'true'
    );

    const fieldName = input.name;

    if (fieldName === '') {
        return;
    }

    const form = input.closest('form');

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const errorElement = form.querySelector(
        `[data-field-error="${escapeSelectorValue(
            fieldName
        )}"]`
    );

    if (errorElement instanceof HTMLElement) {
        errorElement.textContent = message;
    }
}

/**
 * Clear a field error.
 *
 * @param {HTMLInputElement} input
 * @returns {void}
 */
function clearFieldError(input) {
    input.classList.remove('is-invalid');
    input.removeAttribute('aria-invalid');

    const fieldName = input.name;

    if (fieldName === '') {
        return;
    }

    const form = input.closest('form');

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const errorElement = form.querySelector(
        `[data-field-error="${escapeSelectorValue(
            fieldName
        )}"]`
    );

    if (errorElement instanceof HTMLElement) {
        errorElement.textContent = '';
    }
}

/**
 * Activate the submit-button loading state.
 *
 * @param {HTMLFormElement} form
 * @returns {void}
 */
function activateSubmitLoader(form) {
    const submitButton = form.querySelector(
        '[data-submit-button]'
    );

    const idleContent = form.querySelector(
        '[data-submit-idle]'
    );

    const loadingContent = form.querySelector(
        '[data-submit-loading]'
    );

    if (submitButton instanceof HTMLButtonElement) {
        submitButton.disabled = true;
        submitButton.setAttribute(
            'aria-disabled',
            'true'
        );
    }

    if (idleContent instanceof HTMLElement) {
        idleContent.classList.add('d-none');
        idleContent.setAttribute(
            'aria-hidden',
            'true'
        );
    }

    if (loadingContent instanceof HTMLElement) {
        loadingContent.classList.remove('d-none');
        loadingContent.removeAttribute('hidden');
        loadingContent.setAttribute(
            'aria-hidden',
            'false'
        );
    }
}

/**
 * Initialize OTP expiry countdown and resend action.
 *
 * The server remains responsible for enforcing expiry and resend rules.
 *
 * @param {HTMLFormElement} form
 * @returns {void}
 */
function initializeOtpTimer(form) {
    const expiresAt = Number(
        form.dataset.expiresAt ?? 0
    );

    const countdownElement = document.querySelector(
        '[data-otp-countdown]'
    );

    const timerWrapper = document.querySelector(
        '[data-otp-timer-wrapper]'
    );

    const resendButton = document.querySelector(
        '[data-resend-button]'
    );

    const resendForm = document.querySelector(
        '[data-resend-form]'
    );

    let timerId = null;
    let resendSubmitting = false;

    const setResendEnabled = (enabled) => {
        if (!(resendButton instanceof HTMLButtonElement)) {
            return;
        }

        resendButton.disabled = !enabled;
        resendButton.setAttribute(
            'aria-disabled',
            enabled ? 'false' : 'true'
        );
    };

    const markExpired = () => {
        if (countdownElement instanceof HTMLElement) {
            countdownElement.textContent = '00:00';
        }

        if (timerWrapper instanceof HTMLElement) {
            timerWrapper.innerHTML =
                '<span class="text-danger fw-semibold">' +
                'OTP has expired.' +
                '</span>';
        }

        setResendEnabled(true);

        if (timerId !== null) {
            window.clearInterval(timerId);
            timerId = null;
        }
    };

    const updateTimer = () => {
        if (
            !Number.isFinite(expiresAt)
            || expiresAt <= 0
        ) {
            markExpired();
            return;
        }

        const currentTimestamp = Math.floor(
            Date.now() / 1000
        );

        const remainingSeconds =
            expiresAt - currentTimestamp;

        if (remainingSeconds <= 0) {
            markExpired();
            return;
        }

        const minutes = Math.floor(
            remainingSeconds / 60
        );

        const seconds =
            remainingSeconds % 60;

        if (countdownElement instanceof HTMLElement) {
            countdownElement.textContent =
                `${String(minutes).padStart(2, '0')}:` +
                `${String(seconds).padStart(2, '0')}`;
        }

        setResendEnabled(false);
    };

    if (resendForm instanceof HTMLFormElement) {
        resendForm.addEventListener(
            'submit',
            (event) => {
                if (
                    resendSubmitting
                    || (
                        resendButton
                        instanceof HTMLButtonElement
                        && resendButton.disabled
                    )
                ) {
                    event.preventDefault();
                    return;
                }

                resendSubmitting = true;

                if (
                    resendButton
                    instanceof HTMLButtonElement
                ) {
                    resendButton.disabled = true;
                    resendButton.textContent =
                        'Sending...';
                }
            }
        );
    }

    updateTimer();

    timerId = window.setInterval(
        updateTimer,
        1000
    );
}

/**
 * Initialize all password visibility buttons.
 *
 * @returns {void}
 */
function initializePasswordToggles() {
    const toggleButtons = document.querySelectorAll(
        '[data-password-toggle]'
    );

    toggleButtons.forEach((button) => {
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        button.addEventListener('click', () => {
            const inputId =
                button.dataset.passwordToggle ?? '';

            if (inputId === '') {
                return;
            }

            const input = document.getElementById(
                inputId
            );

            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            const showPassword =
                input.type === 'password';

            input.type = showPassword
                ? 'text'
                : 'password';

            button.setAttribute(
                'aria-pressed',
                showPassword ? 'true' : 'false'
            );

            button.setAttribute(
                'aria-label',
                showPassword
                    ? 'Hide password'
                    : 'Show password'
            );

            const icon = button.querySelector(
                '.mdi'
            );

            if (icon instanceof HTMLElement) {
                icon.classList.toggle(
                    'mdi-eye-off-outline',
                    !showPassword
                );

                icon.classList.toggle(
                    'mdi-eye-outline',
                    showPassword
                );
            }

            input.focus();
        });
    });
}

/**
 * Escape an attribute value used inside a query selector.
 *
 * @param {string} value
 * @returns {string}
 */
function escapeSelectorValue(value) {
    if (
        typeof CSS !== 'undefined'
        && typeof CSS.escape === 'function'
    ) {
        return CSS.escape(value);
    }

    return value.replace(
        /["\\]/g,
        '\\$&'
    );
}