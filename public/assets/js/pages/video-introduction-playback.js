'use strict';

document.addEventListener(
    'DOMContentLoaded',
    () => {
        /**
         * Display an application-styled information modal.
         *
         * @param {string} message
         * @param {string} title
         *
         * @returns {void}
         */
        const showInformationModal = (
            message,
            title = 'Video Introduction'
        ) => {
            const modalElement =
                document.createElement('div');

            modalElement.className =
                'modal fade';

            modalElement.tabIndex = -1;

            modalElement.setAttribute(
                'aria-hidden',
                'true'
            );

            modalElement.innerHTML = `
                <div
                    class="modal-dialog
                        modal-dialog-centered">

                    <div class="modal-content">
                        <div class="modal-header">
                            <h2
                                class="modal-title fs-18">

                                ${title}
                            </h2>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Close">
                            </button>
                        </div>

                        <div class="modal-body">
                            <div
                                class="d-flex
                                    align-items-start gap-3">

                                <span
                                    class="avatar-sm
                                        flex-shrink-0">

                                    <span
                                        class="avatar-title
                                            rounded-circle
                                            bg-primary-subtle
                                            text-primary">

                                        <i
                                            class="ri-information-line
                                                fs-20"
                                            aria-hidden="true">
                                        </i>
                                    </span>
                                </span>

                                <p
                                    class="text-muted
                                        fs-13 mb-0"
                                    data-information-message>
                                </p>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-primary"
                                data-bs-dismiss="modal">

                                OK
                            </button>
                        </div>
                    </div>
                </div>
            `;

            const messageElement =
                modalElement.querySelector(
                    '[data-information-message]'
                );

            messageElement.textContent =
                String(message || '');

            document.body.appendChild(
                modalElement
            );

            const modal =
                new bootstrap.Modal(
                    modalElement
                );

            modalElement.addEventListener(
                'hidden.bs.modal',
                () => {
                    modal.dispose();
                    modalElement.remove();
                }
            );

            modal.show();
        };

        /**
         * Display the authenticated video.
         *
         * The permanent watermark is already embedded by FFmpeg.
         * No HTML watermark is added here.
         *
         * @param {string} videoUrl
         * @param {boolean} isFemaleMember
         *
         * @returns {void}
         */
        const showVideoModal = (
            videoUrl,
            isFemaleMember
        ) => {
            const modalElement =
                document.createElement('div');

            modalElement.className =
                'modal fade';

            modalElement.tabIndex = -1;

            modalElement.setAttribute(
                'aria-hidden',
                'true'
            );

            modalElement.innerHTML = `
                <div
                    class="modal-dialog
                        modal-dialog-centered
                        modal-lg">

                    <div class="modal-content">
                        <div class="modal-header">
                            <h2
                                class="modal-title fs-18">

                                Video Introduction
                            </h2>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Close">
                            </button>
                        </div>

                        <div class="modal-body">
                            ${isFemaleMember
                    ? `
                                        <div
                                            class="alert
                                                alert-warning
                                                fs-13">

                                            This video belongs to a
                                            female member. Please
                                            respect her privacy and
                                            do not record, copy or
                                            share it.
                                        </div>
                                    `
                    : ''
                }

                            <div
                                class="overflow-hidden
                                    rounded bg-dark">

                                <video
                                    class="w-100 d-block"
                                    controls
                                    controlsList="
                                        nodownload
                                        noplaybackrate
                                        noremoteplayback
                                    "
                                    disablePictureInPicture
                                    playsinline
                                    preload="metadata">
                                </video>
                            </div>

                            <p
                                class="color-pink
                                    fs-12 mt-2 mb-0">

                                Do not copy, record, share or
                                misuse this member's personal
                                video.
                            </p>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(
                modalElement
            );

            const video =
                modalElement.querySelector(
                    'video'
                );

            video.src = videoUrl;

            video.addEventListener(
                'contextmenu',
                (event) => {
                    event.preventDefault();
                }
            );

            const modal =
                new bootstrap.Modal(
                    modalElement
                );

            modalElement.addEventListener(
                'hidden.bs.modal',
                () => {
                    video.pause();
                    video.removeAttribute('src');
                    video.load();

                    modal.dispose();
                    modalElement.remove();
                }
            );

            modal.show();
        };

        document.addEventListener(
            'click',
            async (event) => {
                const trigger =
                    event.target.closest(
                        '[data-video-introduction-open]'
                    );

                if (!trigger) {
                    return;
                }

                if (
                    trigger.dataset.hidden === '1'
                ) {
                    showInformationModal(
                        'This member has an approved '
                        + 'Video Introduction but has '
                        + 'currently hidden it.'
                    );

                    return;
                }

                const playbackUrl = String(
                    trigger.dataset.playbackUrl
                    || ''
                ).trim();

                if (playbackUrl === '') {
                    showInformationModal(
                        'The Video Introduction is '
                        + 'currently unavailable.'
                    );

                    return;
                }

                const gender = String(
                    trigger.dataset.memberGender
                    || ''
                )
                    .trim()
                    .toUpperCase();

                const isFemaleMember = [
                    'F',
                    'FEMALE',
                ].includes(gender);

                trigger.disabled = true;

                try {
                    const response = await fetch(
                        playbackUrl,
                        {
                            headers: {
                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },

                            credentials:
                                'same-origin',
                        }
                    );

                    let data = {};

                    try {
                        data =
                            await response.json();
                    } catch (exception) {
                        data = {};
                    }

                    if (
                        !response.ok
                        || !data.url
                    ) {
                        throw new Error(
                            data.message
                            || 'The Video Introduction '
                            + 'is currently unavailable.'
                        );
                    }

                    showVideoModal(
                        data.url,
                        isFemaleMember
                    );
                } catch (exception) {
                    showInformationModal(
                        exception instanceof Error
                            ? exception.message
                            : 'The Video Introduction '
                            + 'is currently unavailable.'
                    );
                } finally {
                    trigger.disabled = false;
                }
            }
        );
    }
);