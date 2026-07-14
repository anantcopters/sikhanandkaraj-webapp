/**
 * ==========================================================================
 * Reusable Form Submit Loader
 * ==========================================================================
 *
 * Shows a loading state after a form passes client-side validation and
 * begins a normal browser submission.
 *
 * Usage:
 *
 * <form data-submit-loader>
 *     <button type="submit" data-submit-button>
 *         <span data-submit-idle>Submit</span>
 *         <span data-submit-loading class="d-none">Loading...</span>
 *     </button>
 * </form>
 *
 * The component:
 *
 * 1. Does not show the loader when validation prevents submission.
 * 2. Disables submit buttons to prevent duplicate requests.
 * 3. Restores buttons when the browser returns through the back cache.
 * 4. Supports more than one submit button in a form.
 */
(function (window, document) {
    'use strict';

    const FORM_SELECTOR = 'form[data-submit-loader]';
    const BUTTON_SELECTOR = '[data-submit-button]';

    /**
     * Put one submit button into its loading state.
     *
     * @param {HTMLButtonElement|HTMLInputElement} button
     *
     * @returns {void}
     */
    function showButtonLoader(button) {
        if (
            !(
                button instanceof HTMLButtonElement
                || button instanceof HTMLInputElement
            )
        ) {
            return;
        }

        button.disabled = true;
        button.setAttribute('aria-disabled', 'true');
        button.dataset.submitLoading = 'true';

        const idleContent = button.querySelector(
            '[data-submit-idle]'
        );

        const loadingContent = button.querySelector(
            '[data-submit-loading]'
        );

        if (idleContent) {
            idleContent.classList.add('d-none');
            idleContent.setAttribute('aria-hidden', 'true');
        }

        if (loadingContent) {
            loadingContent.classList.remove('d-none');
            loadingContent.setAttribute('aria-hidden', 'false');
        }
    }

    /**
     * Restore one submit button to its normal state.
     *
     * @param {HTMLButtonElement|HTMLInputElement} button
     *
     * @returns {void}
     */
    function resetButtonLoader(button) {
        if (
            !(
                button instanceof HTMLButtonElement
                || button instanceof HTMLInputElement
            )
        ) {
            return;
        }

        button.disabled = false;
        button.removeAttribute('aria-disabled');
        delete button.dataset.submitLoading;

        const idleContent = button.querySelector(
            '[data-submit-idle]'
        );

        const loadingContent = button.querySelector(
            '[data-submit-loading]'
        );

        if (idleContent) {
            idleContent.classList.remove('d-none');
            idleContent.setAttribute('aria-hidden', 'false');
        }

        if (loadingContent) {
            loadingContent.classList.add('d-none');
            loadingContent.setAttribute('aria-hidden', 'true');
        }
    }

    /**
     * Set all submit buttons in a form to loading.
     *
     * @param {HTMLFormElement} form
     *
     * @returns {void}
     */
    function showFormLoader(form) {
        if (form.dataset.submitLoading === 'true') {
            return;
        }

        form.dataset.submitLoading = 'true';
        form.setAttribute('aria-busy', 'true');

        form
            .querySelectorAll(BUTTON_SELECTOR)
            .forEach(showButtonLoader);
    }

    /**
     * Restore all submit buttons in a form.
     *
     * @param {HTMLFormElement} form
     *
     * @returns {void}
     */
    function resetFormLoader(form) {
        delete form.dataset.submitLoading;
        form.removeAttribute('aria-busy');

        form
            .querySelectorAll(BUTTON_SELECTOR)
            .forEach(resetButtonLoader);
    }

    /**
     * Initialize one form.
     *
     * @param {HTMLFormElement} form
     *
     * @returns {void}
     */
    function initializeForm(form) {
        if (form.dataset.submitLoaderInitialized === 'true') {
            return;
        }

        form.dataset.submitLoaderInitialized = 'true';

        form.addEventListener('submit', function (event) {
            /**
             * form-validator.js runs first.
             *
             * When validation fails, it calls preventDefault(). In that
             * situation the loader must not be shown.
             */
            if (event.defaultPrevented) {
                return;
            }

            /**
             * Prevent accidental repeated submissions.
             */
            if (form.dataset.submitLoading === 'true') {
                event.preventDefault();

                return;
            }

            showFormLoader(form);
        });
    }

    /**
     * Initialize all loader-enabled forms in a container.
     *
     * @param {Document|HTMLElement} container
     *
     * @returns {void}
     */
    function init(container = document) {
        container
            .querySelectorAll(FORM_SELECTOR)
            .forEach(function (form) {
                if (form instanceof HTMLFormElement) {
                    initializeForm(form);
                }
            });
    }

    /**
     * Restore forms when a page returns from browser back-forward cache.
     */
    window.addEventListener('pageshow', function () {
        document
            .querySelectorAll(FORM_SELECTOR)
            .forEach(function (form) {
                if (form instanceof HTMLFormElement) {
                    resetFormLoader(form);
                }
            });
    });

    window.SubmitLoader = Object.freeze({
        init,
        show: showFormLoader,
        reset: resetFormLoader
    });

    document.addEventListener('DOMContentLoaded', function () {
        init(document);
    });
})(window, document);