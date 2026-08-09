'use strict';

document.addEventListener(
    'DOMContentLoaded',
    () => {
        const forms =
            document.querySelectorAll(
                '[data-interest-action-form]'
            );

        forms.forEach(
            (form) => {
                form.addEventListener(
                    'submit',
                    () => {
                        /*
                         * Disable all Interest action buttons
                         * on this profile while its response
                         * is being saved.
                         */
                        const card =
                            form.closest(
                                '.card'
                            );

                        const buttons =
                            card
                                ? card.querySelectorAll(
                                    '[data-interest-submit]'
                                )
                                : [];

                        buttons.forEach(
                            (button) => {
                                button.disabled =
                                    true;
                            }
                        );

                        const button =
                            form.querySelector(
                                '[data-interest-submit]'
                            );

                        if (!button) {
                            return;
                        }

                        const idle =
                            button.querySelector(
                                '.registration-submit__idle'
                            );

                        const loading =
                            button.querySelector(
                                '.registration-submit__loading'
                            );

                        if (idle) {
                            idle.classList.add(
                                'd-none'
                            );
                        }

                        if (loading) {
                            loading.classList.remove(
                                'd-none'
                            );
                        }
                    }
                );
            }
        );
    }
);