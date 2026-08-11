/**
 * Administrator prelaunch-profile approval.
 *
 * Responsibilities:
 *
 * - normalize the mobile number;
 * - preserve the existing generic client validation;
 * - show the migration loader only after validation succeeds;
 * - prevent accidental duplicate submissions;
 * - update the loader message while the request is being processed.
 *
 * Server-side validation remains authoritative.
 */
(function (window, document) {
    'use strict';

    const FORM_SELECTOR =
        '[data-prelaunch-approval-form]';

    const MOBILE_SELECTOR =
        '#mobile_number';

    const SUBMIT_SELECTOR =
        '#prelaunch-contact-approval-submit';

    const MODAL_ID =
        'prelaunchApprovalSavingModal';

    const MESSAGE_ID =
        'prelaunchApprovalSavingModalMessage';

    const PROGRESS_ID =
        'prelaunchApprovalSavingProgressBar';

    /**
     * Keep only numeric mobile-number characters.
     *
     * @param {HTMLInputElement} mobileInput
     *
     * @returns {void}
     */
    function normalizeMobile(mobileInput) {
        mobileInput.value = mobileInput.value
            .replace(/\D+/g, '')
            .slice(0, 10);
    }

    /**
     * Return the Bootstrap approval loader.
     *
     * @returns {bootstrap.Modal|null}
     */
    function getLoaderModal() {
        const modalElement = document.getElementById(
            MODAL_ID
        );

        if (
            !modalElement
            || !window.bootstrap
            || !window.bootstrap.Modal
        ) {
            return null;
        }

        return window.bootstrap.Modal
            .getOrCreateInstance(
                modalElement,
                {
                    backdrop: 'static',
                    keyboard: false
                }
            );
    }

    /**
     * Update the loader text and progress indicator.
     *
     * @param {string} message
     * @param {number} percentage
     *
     * @returns {void}
     */
    function updateLoader(message, percentage) {
        const messageElement =
            document.getElementById(
                MESSAGE_ID
            );

        const progressElement =
            document.getElementById(
                PROGRESS_ID
            );

        if (messageElement) {
            messageElement.textContent = message;
        }

        if (progressElement) {
            const safePercentage = Math.max(
                0,
                Math.min(
                    100,
                    percentage
                )
            );

            progressElement.style.width =
                `${safePercentage}%`;

            progressElement.setAttribute(
                'aria-valuenow',
                String(safePercentage)
            );
        }
    }

    /**
     * Start staged progress messaging.
     *
     * This is intentionally visual progress rather than server-reported
     * upload progress. The normal POST navigation finishes the operation.
     *
     * @returns {number[]}
     */
    function startProgressMessages() {
        const timers = [];

        updateLoader(
            'Validating contact information…',
            15
        );

        timers.push(
            window.setTimeout(
                function () {
                    updateLoader(
                        'Creating the member account…',
                        35
                    );
                },
                900
            )
        );

        timers.push(
            window.setTimeout(
                function () {
                    updateLoader(
                        'Processing approved photographs…',
                        55
                    );
                },
                2200
            )
        );

        timers.push(
            window.setTimeout(
                function () {
                    updateLoader(
                        'Uploading image variants securely…',
                        75
                    );
                },
                4200
            )
        );

        timers.push(
            window.setTimeout(
                function () {
                    updateLoader(
                        'Finalizing profile migration…',
                        90
                    );
                },
                7000
            )
        );

        return timers;
    }

    /**
     * Initialize the approval form.
     *
     * @param {HTMLFormElement} form
     *
     * @returns {void}
     */
    function initializeForm(form) {
        if (
            form.dataset
                .prelaunchApprovalInitialized
            === 'true'
        ) {
            return;
        }

        form.dataset
            .prelaunchApprovalInitialized =
            'true';

        const mobileInput =
            form.querySelector(
                MOBILE_SELECTOR
            );

        const submitButton =
            form.querySelector(
                SUBMIT_SELECTOR
            );

        if (
            mobileInput
            instanceof HTMLInputElement
        ) {
            mobileInput.addEventListener(
                'input',
                function () {
                    normalizeMobile(
                        mobileInput
                    );
                }
            );

            mobileInput.addEventListener(
                'paste',
                function () {
                    /*
                     * Wait until the browser has inserted the pasted value.
                     */
                    window.setTimeout(
                        function () {
                            normalizeMobile(
                                mobileInput
                            );
                        },
                        0
                    );
                }
            );
        }

        form.addEventListener(
            'submit',
            function (event) {
                /*
                 * The generic validator was registered first because its script is
                 * loaded before this page script. Therefore event.defaultPrevented
                 * already represents its validation result here.
                 */
                if (
                    event.defaultPrevented
                    || !form.checkValidity()
                ) {
                    return;
                }

                if (
                    form.dataset.submitting
                    === 'true'
                ) {
                    event.preventDefault();

                    return;
                }

                form.dataset.submitting =
                    'true';

                if (
                    submitButton
                    instanceof HTMLButtonElement
                ) {
                    submitButton.disabled = true;

                    submitButton.setAttribute(
                        'aria-disabled',
                        'true'
                    );
                }

                const loaderModal =
                    getLoaderModal();

                startProgressMessages();

                if (loaderModal) {
                    loaderModal.show();
                }

                /*
                 * Do not preventDefault(). The valid POST continues normally.
                 */
            }
        );
    }

    /**
     * Initialize the page.
     *
     * @returns {void}
     */
    function initialize() {
        document
            .querySelectorAll(
                FORM_SELECTOR
            )
            .forEach(
                function (form) {
                    if (
                        form
                        instanceof HTMLFormElement
                    ) {
                        initializeForm(
                            form
                        );
                    }
                }
            );
    }

    document.addEventListener(
        'DOMContentLoaded',
        initialize
    );
})(window, document);