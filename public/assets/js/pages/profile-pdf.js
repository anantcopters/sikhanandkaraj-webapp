'use strict';

document.addEventListener(
    'DOMContentLoaded',
    () => {
        const button =
            document.querySelector(
                '[data-profile-pdf-button]'
            );

        const modalElement =
            document.getElementById(
                'profilePdfModal'
            );

        if (
            !button
            || !modalElement
            || typeof bootstrap === 'undefined'
        ) {
            return;
        }

        const modal =
            bootstrap.Modal
                .getOrCreateInstance(
                    modalElement
                );

        const progress =
            modalElement.querySelector(
                '[data-profile-pdf-progress]'
            );

        const message =
            modalElement.querySelector(
                '[data-profile-pdf-message]'
            );

        const spinner =
            modalElement.querySelector(
                '[data-profile-pdf-spinner]'
            );

        const success =
            modalElement.querySelector(
                '[data-profile-pdf-success]'
            );

        const errorIcon =
            modalElement.querySelector(
                '[data-profile-pdf-error]'
            );

        const closeArea =
            modalElement.querySelector(
                '[data-profile-pdf-close]'
            );

        let timer = null;

        const setProgress = (
            value,
            text
        ) => {
            if (progress) {
                progress.style.width =
                    `${value}%`;

                progress.setAttribute(
                    'aria-valuenow',
                    String(value)
                );
            }

            if (
                message
                && text
            ) {
                message.textContent =
                    text;
            }
        };

        const reset = () => {
            if (timer !== null) {
                window.clearInterval(
                    timer
                );
            }

            timer = null;

            spinner?.classList.remove(
                'd-none'
            );

            success?.classList.add(
                'd-none'
            );

            errorIcon?.classList.add(
                'd-none'
            );

            closeArea?.classList.add(
                'd-none'
            );

            progress?.classList.remove(
                'bg-danger'
            );

            setProgress(
                15,
                'Preparing profile details...'
            );
        };

        const showError = (
            text
        ) => {
            if (timer !== null) {
                window.clearInterval(
                    timer
                );
            }

            timer = null;

            spinner?.classList.add(
                'd-none'
            );

            success?.classList.add(
                'd-none'
            );

            errorIcon?.classList.remove(
                'd-none'
            );

            closeArea?.classList.remove(
                'd-none'
            );

            progress?.classList.add(
                'bg-danger'
            );

            setProgress(
                100,
                text
            );
        };

        const download = (
            blob,
            filename
        ) => {
            const url =
                URL.createObjectURL(
                    blob
                );

            const link =
                document.createElement(
                    'a'
                );

            link.href =
                url;

            link.download =
                filename;

            document.body.appendChild(
                link
            );

            link.click();
            link.remove();

            window.setTimeout(
                () => {
                    URL.revokeObjectURL(
                        url
                    );
                },
                1000
            );
        };

        const filename = (
            response
        ) => {
            const disposition =
                response.headers.get(
                    'Content-Disposition'
                ) || '';

            const match =
                disposition.match(
                    /filename="([^"]+)"/i
                );

            return match?.[1]
                || 'sikhanandkaraj-profile.pdf';
        };

        /*
         * Do not blindly call response.json().
         *
         * Development error pages, PHP warnings or framework
         * exceptions may return HTML/text instead of valid JSON.
         */
        const errorMessage = async (
            response
        ) => {
            const fallback =
                'The profile PDF could not '
                + 'be created. Please try again.';

            let body = '';

            try {
                body =
                    await response.text();
            } catch (error) {
                return fallback;
            }

            if (
                typeof body !== 'string'
                || body.trim() === ''
            ) {
                return fallback;
            }

            try {
                const payload =
                    JSON.parse(
                        body
                    );

                if (
                    payload
                    && typeof payload.message
                    === 'string'
                    && payload.message.trim()
                    !== ''
                ) {
                    return payload.message
                        .trim();
                }
            } catch (error) {
                /*
                 * Not JSON.
                 *
                 * Do not expose raw PHP/HTML error output
                 * to the member UI.
                 */
            }

            return fallback;
        };

        button.addEventListener(
            'click',
            async () => {
                const url =
                    button.dataset
                        .profilePdfUrl
                    || '';

                const csrfName =
                    button.dataset
                        .profilePdfCsrfName
                    || '';

                const csrfHash =
                    button.dataset
                        .profilePdfCsrfHash
                    || '';

                if (
                    url === ''
                    || csrfName === ''
                    || csrfHash === ''
                ) {
                    return;
                }

                reset();

                modal.show();

                button.disabled =
                    true;

                let current = 15;

                timer =
                    window.setInterval(
                        () => {
                            if (
                                current >= 84
                            ) {
                                return;
                            }

                            current += 3;

                            let text =
                                'Preparing profile details...';

                            if (
                                current >= 35
                            ) {
                                text =
                                    'Applying profile privacy...';
                            }

                            if (
                                current >= 55
                            ) {
                                text =
                                    'Designing your profile PDF...';
                            }

                            if (
                                current >= 72
                            ) {
                                text =
                                    'Finalizing your PDF...';
                            }

                            setProgress(
                                current,
                                text
                            );
                        },
                        300
                    );

                try {
                    const formData =
                        new FormData();

                    formData.append(
                        csrfName,
                        csrfHash
                    );

                    const response =
                        await fetch(
                            url,
                            {
                                method:
                                    'POST',

                                credentials:
                                    'same-origin',

                                headers: {
                                    'X-Requested-With':
                                        'XMLHttpRequest',
                                },

                                body:
                                    formData,
                            }
                        );

                    if (!response.ok) {
                        throw new Error(
                            await errorMessage(
                                response
                            )
                        );
                    }

                    /*
                     * A successful endpoint must return PDF.
                     *
                     * This prevents an HTML login/error page from
                     * accidentally being downloaded as ".pdf".
                     */
                    const contentType =
                        (
                            response.headers.get(
                                'Content-Type'
                            )
                            || ''
                        ).toLowerCase();

                    if (
                        !contentType.includes(
                            'application/pdf'
                        )
                    ) {
                        throw new Error(
                            'The server did not return '
                            + 'a valid profile PDF.'
                        );
                    }

                    if (timer !== null) {
                        window.clearInterval(
                            timer
                        );

                        timer = null;
                    }

                    setProgress(
                        94,
                        'Saving your profile PDF...'
                    );

                    const blob =
                        await response.blob();

                    if (
                        blob.size <= 0
                    ) {
                        throw new Error(
                            'The generated PDF was empty.'
                        );
                    }

                    download(
                        blob,
                        filename(
                            response
                        )
                    );

                    spinner?.classList.add(
                        'd-none'
                    );

                    success?.classList.remove(
                        'd-none'
                    );

                    setProgress(
                        100,
                        'Profile PDF created successfully.'
                    );

                    window.setTimeout(
                        () => {
                            window.location.reload();
                        },
                        900
                    );
                } catch (error) {
                    showError(
                        error instanceof Error
                            ? error.message
                            : (
                                'The profile PDF could not '
                                + 'be created. Please try again.'
                            )
                    );
                } finally {
                    button.disabled =
                        false;
                }
            }
        );
    }
);