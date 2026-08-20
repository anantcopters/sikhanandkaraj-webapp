'use strict';

document.addEventListener(
    'DOMContentLoaded',
    () => {
        const form = document.querySelector(
            '[data-video-moderation-form]'
        );

        if (!form) {
            return;
        }

        const decision = form.querySelector(
            '[name="decision"]'
        );

        const reason = form.querySelector(
            '[name="reason"]'
        );

        if (!decision || !reason) {
            return;
        }

        const validateReason = () => {
            const decisionValue = String(
                decision.value || ''
            )
                .trim()
                .toUpperCase();

            const reasonRequired = [
                'REJECT',
                'RESUBMIT',
            ].includes(decisionValue);

            reason.required = reasonRequired;

            if (!reasonRequired) {
                reason.setCustomValidity('');

                return;
            }

            if (reason.value.trim().length < 10) {
                reason.setCustomValidity(
                    'Provide a clear reason of at '
                    + 'least 10 characters.'
                );

                return;
            }

            reason.setCustomValidity('');
        };

        decision.addEventListener(
            'change',
            validateReason
        );

        reason.addEventListener(
            'input',
            validateReason
        );

        form.addEventListener(
            'submit',
            (event) => {
                validateReason();

                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();

                    form.classList.add(
                        'was-validated'
                    );

                    form
                        .querySelector(
                            ':invalid'
                        )
                        ?.focus();
                }
            }
        );

        validateReason();
    }
);