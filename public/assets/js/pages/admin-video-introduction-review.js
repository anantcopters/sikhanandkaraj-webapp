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

        const validateDecision = () => {
            if (
                String(decision.value || '')
                    .trim() === ''
            ) {
                decision.setCustomValidity(
                    'Please select a decision.'
                );

                return false;
            }

            decision.setCustomValidity('');

            return true;
        };

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

            if (reasonRequired) {
                reason.setAttribute(
                    'minlength',
                    '10'
                );
            } else {
                reason.removeAttribute(
                    'minlength'
                );
            }

            const reasonLength =
                reason.value.trim().length;

            if (
                reasonRequired
                && reasonLength < 10
            ) {
                reason.setCustomValidity(
                    'Provide a clear reason of at '
                    + 'least 10 characters.'
                );

                return false;
            }

            reason.setCustomValidity('');

            return true;
        };

        const validateForm = () => {
            const validDecision =
                validateDecision();

            const validReason =
                validateReason();

            return validDecision
                && validReason;
        };

        decision.addEventListener(
            'change',
            () => {
                validateDecision();
                validateReason();
            }
        );

        reason.addEventListener(
            'input',
            validateReason
        );

        form.addEventListener(
            'submit',
            (event) => {
                const customValidationPassed =
                    validateForm();

                if (
                    !customValidationPassed
                    || !form.checkValidity()
                ) {
                    event.preventDefault();
                    event.stopPropagation();

                    form.classList.add(
                        'was-validated'
                    );

                    const invalidField =
                        form.querySelector(
                            ':invalid'
                        );

                    if (invalidField) {
                        invalidField.focus();
                    }
                }
            }
        );

        validateForm();
    }
);