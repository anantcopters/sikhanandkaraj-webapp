'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('aboutMeForm');

    const textarea = document.querySelector(
        '[data-about-me-input]'
    );

    const counter = document.querySelector(
        '[data-about-me-count]'
    );

    const errorElement = document.getElementById(
        'aboutMeError'
    );

    if (
        !(form instanceof HTMLFormElement)
        || !(textarea instanceof HTMLTextAreaElement)
        || !(counter instanceof HTMLElement)
        || !(errorElement instanceof HTMLElement)
    ) {
        return;
    }

    const maxWords = 120;

    const words = (value) => {
        return value
            .trim()
            .match(
                /[\p{L}\p{N}]+(?:['’-][\p{L}\p{N}]+)*/gu
            )
            ?? [];
    };

    const containsLink = (value) => {
        return (
            /\bhttps?:\/\/\S+/iu.test(value)
            || /\bwww\.\S+/iu.test(value)
            || /\b[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z]{2,})+(?:\/\S*)?\b/iu
                .test(value)
        );
    };

    const showError = (message) => {
        textarea.classList.add(
            'is-invalid'
        );

        textarea.setAttribute(
            'aria-invalid',
            'true'
        );

        errorElement.textContent =
            message;

        errorElement.classList.add(
            'd-block'
        );
    };

    const clearError = () => {
        textarea.classList.remove(
            'is-invalid'
        );

        textarea.removeAttribute(
            'aria-invalid'
        );

        errorElement.textContent = '';

        errorElement.classList.remove(
            'd-block'
        );
    };

    const validateAboutMe = () => {
        const count =
            words(textarea.value).length;

        if (count > maxWords) {
            showError(
                textarea.dataset.errorMaxWords
                || `About Me cannot exceed ${maxWords} words.`
            );

            return false;
        }

        if (containsLink(textarea.value)) {
            showError(
                textarea.dataset.errorLink
                || 'Links and website addresses are not allowed.'
            );

            return false;
        }

        /*
         * Required-field validation remains with the
         * application's generic FormValidator.
         */
        if (textarea.value.trim() !== '') {
            clearError();
        }

        return true;
    };

    const updateCount = () => {
        const count =
            words(textarea.value).length;

        counter.textContent =
            `${count} of ${maxWords} words`;

        counter.classList.toggle(
            'text-danger',
            count > maxWords
        );

        counter.classList.toggle(
            'text-muted',
            count <= maxWords
        );
    };

    textarea.addEventListener(
        'input',
        () => {
            updateCount();

            /*
             * Revalidate immediately once this field has
             * entered an invalid state.
             */
            if (
                textarea.classList.contains(
                    'is-invalid'
                )
            ) {
                validateAboutMe();
            }
        }
    );

    updateCount();

    form.addEventListener(
        'submit',
        (event) => {
            if (!validateAboutMe()) {
                event.preventDefault();

                textarea.focus();

                return;
            }

            const submitButton =
                document.getElementById(
                    'saveAboutMeButton'
                );

            if (
                !(submitButton
                    instanceof HTMLButtonElement)
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

            event.preventDefault();

            form.dataset.submitting =
                'true';

            submitButton.disabled =
                true;

            submitButton
                .querySelector(
                    '.registration-submit__label'
                )
                ?.classList.add(
                    'd-none'
                );

            submitButton
                .querySelector(
                    '.registration-submit__loading'
                )
                ?.classList.remove(
                    'd-none'
                );

            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    form.submit();
                });
            });
        }
    );
});