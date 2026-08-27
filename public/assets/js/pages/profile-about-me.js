'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('aboutMeForm');

    const textarea = document.querySelector(
        '[data-about-me-input]'
    );

    const counter = document.querySelector(
        '[data-about-me-count]'
    );

    if (
        !(form instanceof HTMLFormElement)
        || !(textarea instanceof HTMLTextAreaElement)
        || !(counter instanceof HTMLElement)
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
        updateCount
    );

    updateCount();

    form.addEventListener(
        'submit',
        (event) => {
            const count =
                words(textarea.value).length;

            if (count > maxWords) {
                event.preventDefault();

                textarea.classList.add(
                    'is-invalid'
                );

                textarea.focus();

                return;
            }

            if (containsLink(textarea.value)) {
                event.preventDefault();

                textarea.classList.add(
                    'is-invalid'
                );

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