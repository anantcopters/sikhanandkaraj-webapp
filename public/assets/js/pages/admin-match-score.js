'use strict';

/**
 * Super Admin Match Score configuration.
 *
 * Server validation remains authoritative.
 * This script only provides immediate form feedback.
 */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(
        '[data-match-score-form]'
    );

    if (!form) {
        return;
    }

    const inputs = Array.from(
        form.querySelectorAll(
            '[data-match-score-weight]'
        )
    );

    const totalElement = form.querySelector(
        '[data-match-score-total]'
    );

    const totalWrapper = form.querySelector(
        '[data-match-score-total-wrapper]'
    );

    const totalMessage = form.querySelector(
        '[data-match-score-total-message]'
    );

    if (
        inputs.length === 0
        || !totalElement
        || !totalWrapper
        || !totalMessage
    ) {
        return;
    }

    const calculateTotal = () => {
        return inputs.reduce(
            (total, input) => {
                const value = Number.parseInt(
                    input.value,
                    10
                );

                return total
                    + (
                        Number.isFinite(value)
                            ? value
                            : 0
                    );
            },
            0
        );
    };

    const renderTotal = () => {
        const total = calculateTotal();

        totalElement.textContent =
            String(total);

        const isValid =
            total === 100;

        totalWrapper.classList.toggle(
            'is-invalid',
            !isValid
        );

        totalMessage.classList.toggle(
            'text-danger',
            !isValid
        );

        totalMessage.classList.toggle(
            'form-text',
            isValid
        );

        totalMessage.textContent =
            isValid
                ? 'The total is exactly 100%.'
                : 'The Match Score weights must total exactly 100%.';

        return isValid;
    };

    inputs.forEach((input) => {
        input.addEventListener(
            'input',
            renderTotal
        );
    });

    form.addEventListener(
        'submit',
        (event) => {
            if (!renderTotal()) {
                event.preventDefault();
                event.stopPropagation();

                totalWrapper.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });
            }
        }
    );

    renderTotal();
});