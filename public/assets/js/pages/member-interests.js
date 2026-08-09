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
                        const button =
                            form.querySelector(
                                '[data-interest-submit]'
                            );

                        if (!button) {
                            return;
                        }

                        button.disabled = true;

                        const idle =
                            button.querySelector(
                                '.registration-submit__idle'
                            );

                        const loading =
                            button.querySelector(
                                '.registration-submit__loading'
                            );

                        if (idle) {
                            idle.style.display =
                                'none';
                        }

                        if (loading) {
                            loading.style.display =
                                'inline-flex';
                        }
                    }
                );
            }
        );
    }
);