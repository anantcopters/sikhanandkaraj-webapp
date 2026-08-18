(function (window, document) {
    'use strict';

    /**
     * Apply the password-confirmation business rule.
     *
     * Generic required, pattern and length validation is handled by
     * form-validator.js through native HTML constraints.
     */
    function initializePasswordConfirmation() {
        const form = document.querySelector(
            '[data-account-password-form]'
        );

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const password = form.querySelector(
            '[name="password"]'
        );

        const confirmation = form.querySelector(
            '[name="password_confirmation"]'
        );

        if (
            !(password instanceof HTMLInputElement)
            || !(confirmation instanceof HTMLInputElement)
        ) {
            return;
        }

        function synchronizePasswordConfirmation() {
            const hasConfirmation =
                confirmation.value !== '';

            const passwordsMatch =
                password.value === confirmation.value;

            confirmation.setCustomValidity(
                hasConfirmation && !passwordsMatch
                    ? (
                        confirmation.dataset
                            .errorPasswordMatch
                        || 'The passwords do not match.'
                    )
                    : ''
            );

            confirmation.dispatchEvent(
                new CustomEvent(
                    'app:validate-field'
                )
            );
        }

        password.addEventListener(
            'input',
            synchronizePasswordConfirmation
        );

        confirmation.addEventListener(
            'input',
            synchronizePasswordConfirmation
        );

        synchronizePasswordConfirmation();
    }

    /**
     * Display the server-provided completion modal.
     */
    function showNotice() {
        const notice = document.querySelector(
            '[data-account-notice]'
        );

        if (!(notice instanceof HTMLElement)) {
            return;
        }

        const logoutForm = document.getElementById(
            'accountSettingsLogoutForm'
        );

        const logoutAfterClose =
            notice.dataset.logoutAfterClose === '1';

        if (
            window.AppFeedbackModal
            && typeof window.AppFeedbackModal.show
            === 'function'
        ) {
            window.AppFeedbackModal.show({
                type:
                    notice.dataset.noticeType
                    || 'success',

                title:
                    notice.dataset.noticeTitle
                    || 'Completed',

                message:
                    notice.dataset.noticeMessage
                    || 'The action was completed.',

                buttonText:
                    logoutAfterClose
                        ? 'Log in again'
                        : 'Okay',

                onClose: function () {
                    if (
                        logoutAfterClose
                        && logoutForm
                        instanceof HTMLFormElement
                    ) {
                        logoutForm.submit();
                    }
                }
            });

            return;
        }

        window.alert(
            notice.dataset.noticeMessage
            || 'The action was completed.'
        );

        if (
            logoutAfterClose
            && logoutForm instanceof HTMLFormElement
        ) {
            logoutForm.submit();
        }
    }

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            initializePasswordConfirmation();
            showNotice();
        }
    );
})(window, document);