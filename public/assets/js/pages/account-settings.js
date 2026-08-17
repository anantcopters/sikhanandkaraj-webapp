(function () {
    'use strict';

    function initializeValidation() {
        document.querySelectorAll(
            '[data-account-password-form], '
            + '[data-account-email-form], '
            + '[data-account-contact-form]'
        ).forEach(function (form) {
            form.addEventListener(
                'submit',
                function (event) {
                    form.querySelectorAll(
                        'input, textarea'
                    ).forEach(function (field) {
                        if (
                            field instanceof
                            HTMLTextAreaElement
                        ) {
                            field.value = field.value
                                .replace(/\s+/g, ' ')
                                .trim();
                        }

                        field.classList.toggle(
                            'is-invalid',
                            !field.checkValidity()
                        );
                    });

                    const password =
                        form.querySelector(
                            '[name="password"]'
                        );

                    const confirmation =
                        form.querySelector(
                            '[name="password_confirmation"]'
                        );

                    if (
                        password
                        && confirmation
                        && password.value
                        !== confirmation.value
                    ) {
                        confirmation.classList.add(
                            'is-invalid'
                        );

                        event.preventDefault();

                        confirmation.focus();

                        return;
                    }

                    if (!form.checkValidity()) {
                        event.preventDefault();

                        form.querySelector(
                            '.is-invalid'
                        )?.focus();
                    }
                }
            );
        });
    }

    function showNotice() {
        const notice = document.querySelector(
            '[data-account-notice]'
        );

        if (!notice) {
            return;
        }

        const logoutForm =
            document.getElementById(
                'accountSettingsLogoutForm'
            );

        const logoutAfterClose =
            notice.dataset.logoutAfterClose
            === '1';

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
            && logoutForm
        ) {
            logoutForm.submit();
        }
    }

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            initializeValidation();
            showNotice();
        }
    );
})();