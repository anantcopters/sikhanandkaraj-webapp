'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const otpInputs = Array.from(
        document.querySelectorAll('.registration-otp-input')
    );

    const form = document.getElementById('registrationOtpForm');
    const resetButton = document.getElementById('resetOtpButton');
    const verifyButton = document.getElementById('verifyOtpButton');
    const resendButton = document.getElementById('resendOtpButton');
    const timerContainer = document.querySelector(
        '[data-otp-expiry]'
    );
    const timerElement = document.getElementById('otpTimer');
    const timerMessage = document.getElementById('otpTimerMessage');
    const expiredMessage = document.getElementById(
        'otpExpiredMessage'
    );

    /**
     * Move automatically between the four OTP inputs.
     */
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '').slice(0, 1);

            if (
                input.value !== ''
                && index < otpInputs.length - 1
            ) {
                otpInputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', (event) => {
            if (
                event.key === 'Backspace'
                && input.value === ''
                && index > 0
            ) {
                otpInputs[index - 1].focus();
            }

            if (
                event.key === 'ArrowLeft'
                && index > 0
            ) {
                event.preventDefault();
                otpInputs[index - 1].focus();
            }

            if (
                event.key === 'ArrowRight'
                && index < otpInputs.length - 1
            ) {
                event.preventDefault();
                otpInputs[index + 1].focus();
            }
        });

        /**
         * Allow pasting all four digits into any OTP box.
         */
        input.addEventListener('paste', (event) => {
            const pastedValue = event.clipboardData
                .getData('text')
                .replace(/\D/g, '')
                .slice(0, 4);

            if (pastedValue.length !== 4) {
                return;
            }

            event.preventDefault();

            pastedValue.split('').forEach((digit, digitIndex) => {
                otpInputs[digitIndex].value = digit;
            });

            otpInputs[3].focus();
        });
    });

    resetButton?.addEventListener('click', () => {
        otpInputs.forEach((input) => {
            input.value = '';
        });

        otpInputs[0]?.focus();
    });

    form?.addEventListener('submit', (event) => {
        const otp = otpInputs
            .map((input) => input.value)
            .join('');

        if (!/^\d{4}$/.test(otp)) {
            event.preventDefault();
            otpInputs.find((input) => input.value === '')?.focus();
        }
    });

    /**
     * The timer is based on the database expiry timestamp.
     *
     * Refreshing the page does not restart three minutes. The server
     * sends the original expires_at value back to the browser.
     */
    const expiryMilliseconds = Number(
        timerContainer?.dataset.otpExpiry ?? 0
    );

    const markExpired = () => {
        timerMessage?.classList.add('d-none');
        expiredMessage?.classList.remove('d-none');

        if (verifyButton) {
            verifyButton.disabled = true;
        }

        if (resendButton) {
            resendButton.disabled = false;
        }
    };

    const updateTimer = () => {
        const remainingMilliseconds =
            expiryMilliseconds - Date.now();

        if (remainingMilliseconds <= 0) {
            markExpired();
            return false;
        }

        const totalSeconds = Math.ceil(
            remainingMilliseconds / 1000
        );

        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;

        if (timerElement) {
            timerElement.textContent =
                `${String(minutes).padStart(2, '0')}:`
                + `${String(seconds).padStart(2, '0')}`;
        }

        return true;
    };

    if (updateTimer()) {
        const timerInterval = window.setInterval(() => {
            if (!updateTimer()) {
                window.clearInterval(timerInterval);
            }
        }, 1000);
    }

    otpInputs[0]?.focus();
});

