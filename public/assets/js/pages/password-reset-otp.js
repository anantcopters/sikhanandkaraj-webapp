'use strict';

/**
 * Password-reset OTP page behaviour.
 *
 * Responsibilities:
 * - Accept numeric OTP input only.
 * - Limit OTP to four digits.
 * - Display the database-backed OTP expiry countdown.
 * - Enable resend only after expiry.
 * - Prevent repeated form submissions.
 */
document.addEventListener('DOMContentLoaded', () => {
    const otpForm = document.querySelector(
        '[data-password-reset-otp-form]'
    );

    if (!(otpForm instanceof HTMLFormElement)) {
        return;
    }

    const otpInput = otpForm.querySelector('#otp');
    const submitButton = otpForm.querySelector(
        '#verifyPasswordResetOtpButton'
    );
    const submitText = submitButton?.querySelector(
        '.registration-submit__text'
    );
    const submitLoading = submitButton?.querySelector(
        '.registration-submit__loading'
    );

    const countdownElement = document.querySelector(
        '[data-password-reset-otp-countdown]'
    );
    const timerElement = document.querySelector(
        '[data-password-reset-otp-timer]'
    );
    const resendButton = document.querySelector(
        '[data-password-reset-resend-button]'
    );
    const resendForm = document.querySelector(
        '[data-password-reset-resend-form]'
    );

    let countdownInterval = null;
    let otpFormSubmitting = false;
    let resendFormSubmitting = false;

    /**
     * Remove every non-numeric character and limit the OTP to four digits.
     *
     * @param {string} value
     * @returns {string}
     */
    const normalizeOtp = (value) => {
        return value
            .replace(/\D/g, '')
            .slice(0, 4);
    };

    /**
     * Format seconds as MM:SS.
     *
     * @param {number} totalSeconds
     * @returns {string}
     */
    const formatRemainingTime = (totalSeconds) => {
        const safeSeconds = Math.max(
            0,
            Math.floor(totalSeconds)
        );

        const minutes = Math.floor(
            safeSeconds / 60
        );
        const seconds = safeSeconds % 60;

        return `${String(minutes).padStart(2, '0')}:` +
            `${String(seconds).padStart(2, '0')}`;
    };

    /**
     * Enable or disable the resend action.
     *
     * @param {boolean} enabled
     */
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

    /**
     * Mark the OTP as expired in the interface.
     */
    const markOtpExpired = () => {
        if (countdownElement instanceof HTMLElement) {
            countdownElement.textContent = '00:00';
        }

        if (timerElement instanceof HTMLElement) {
            timerElement.setAttribute(
                'data-expired',
                'true'
            );
        }

        setResendEnabled(true);

        if (countdownInterval !== null) {
            window.clearInterval(countdownInterval);
            countdownInterval = null;
        }
    };

    /**
     * Update the visible OTP countdown.
     */
    const updateCountdown = () => {
        const expiresAt = Number(
            otpForm.dataset.expiresAt ?? 0
        );

        if (
            !Number.isFinite(expiresAt)
            || expiresAt <= 0
        ) {
            markOtpExpired();
            return;
        }

        const currentTimestamp = Math.floor(
            Date.now() / 1000
        );

        const remainingSeconds =
            expiresAt - currentTimestamp;

        if (remainingSeconds <= 0) {
            markOtpExpired();
            return;
        }

        if (countdownElement instanceof HTMLElement) {
            countdownElement.textContent =
                formatRemainingTime(remainingSeconds);
        }

        setResendEnabled(false);
    };

    if (otpInput instanceof HTMLInputElement) {
        otpInput.addEventListener('input', () => {
            otpInput.value = normalizeOtp(
                otpInput.value
            );

            otpInput.setCustomValidity('');
        });

        otpInput.addEventListener('paste', (event) => {
            event.preventDefault();

            const pastedValue = event.clipboardData
                ?.getData('text') ?? '';

            otpInput.value = normalizeOtp(
                pastedValue
            );

            otpInput.dispatchEvent(
                new Event('input', {
                    bubbles: true,
                })
            );
        });
    }

    otpForm.addEventListener('submit', (event) => {
        if (otpFormSubmitting) {
            event.preventDefault();
            return;
        }

        if (!(otpInput instanceof HTMLInputElement)) {
            event.preventDefault();
            return;
        }

        otpInput.value = normalizeOtp(
            otpInput.value
        );

        if (!/^\d{4}$/.test(otpInput.value)) {
            event.preventDefault();

            otpInput.setCustomValidity(
                'Please enter the complete four-digit OTP.'
            );

            otpInput.reportValidity();
            otpInput.focus();

            return;
        }

        otpInput.setCustomValidity('');
        otpFormSubmitting = true;

        if (submitButton instanceof HTMLButtonElement) {
            submitButton.disabled = true;
        }

        if (submitText instanceof HTMLElement) {
            submitText.hidden = true;
        }

        if (submitLoading instanceof HTMLElement) {
            submitLoading.hidden = false;
        }
    });

    if (resendForm instanceof HTMLFormElement) {
        resendForm.addEventListener('submit', (event) => {
            if (
                resendFormSubmitting
                || (
                    resendButton instanceof HTMLButtonElement
                    && resendButton.disabled
                )
            ) {
                event.preventDefault();
                return;
            }

            resendFormSubmitting = true;

            if (
                resendButton instanceof HTMLButtonElement
            ) {
                resendButton.disabled = true;
                resendButton.textContent = 'Sending...';
            }
        });
    }

    updateCountdown();

    countdownInterval = window.setInterval(
        updateCountdown,
        1000
    );
});