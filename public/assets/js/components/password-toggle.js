/**
 * Reusable password visibility controller.
 *
 * Any button containing data-password-toggle will control the password
 * input whose ID matches the attribute value.
 *
 * Example:
 *
 * <button data-password-toggle="password">
 */
(function (window, document) {
    'use strict';

    const TOGGLE_SELECTOR = '[data-password-toggle]';

    /**
     * Update the button icon and accessibility state.
     *
     * @param {HTMLButtonElement} button
     * @param {boolean} isVisible
     *
     * @returns {void}
     */
    function updateButton(button, isVisible) {
        const icon = button.querySelector('.mdi');

        button.setAttribute(
            'aria-label',
            isVisible
                ? 'Hide password'
                : 'Show password'
        );

        button.setAttribute(
            'aria-pressed',
            isVisible ? 'true' : 'false'
        );

        if (!icon) {
            return;
        }

        icon.classList.toggle(
            'mdi-eye-outline',
            isVisible
        );

        icon.classList.toggle(
            'mdi-eye-off-outline',
            !isVisible
        );
    }

    /**
     * Initialize one password visibility button.
     *
     * @param {HTMLButtonElement} button
     *
     * @returns {void}
     */
    function initializeButton(button) {
        if (
            button.dataset.passwordToggleInitialized
            === 'true'
        ) {
            return;
        }

        const inputId = button.dataset.passwordToggle;

        if (!inputId) {
            return;
        }

        const input = document.getElementById(inputId);

        if (
            !(input instanceof HTMLInputElement)
            || input.type !== 'password'
        ) {
            return;
        }

        button.dataset.passwordToggleInitialized = 'true';

        updateButton(button, false);

        button.addEventListener('click', function () {
            const shouldShow = input.type === 'password';

            input.type = shouldShow
                ? 'text'
                : 'password';

            updateButton(button, shouldShow);

            /**
             * Return focus to the password input and preserve its cursor
             * position when possible.
             */
            input.focus();

            const inputLength = input.value.length;

            input.setSelectionRange(
                inputLength,
                inputLength
            );
        });
    }

    /**
     * Initialize password controls within a document or dynamic container.
     *
     * @param {Document|HTMLElement} container
     *
     * @returns {void}
     */
    function init(container = document) {
        container
            .querySelectorAll(TOGGLE_SELECTOR)
            .forEach(function (button) {
                if (button instanceof HTMLButtonElement) {
                    initializeButton(button);
                }
            });
    }

    window.PasswordToggle = Object.freeze({
        init
    });

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            init(document);
        }
    );
})(window, document);