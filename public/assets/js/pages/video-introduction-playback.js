'use strict';

document.addEventListener(
    'DOMContentLoaded',
    () => {
        document.addEventListener(
            'click',
            async (event) => {
                const trigger = event.target.closest(
                    '[data-video-introduction-open]'
                );

                if (!trigger) {
                    return;
                }

                if (trigger.dataset.hidden === '1') {
                    window.alert(
                        'This member has an approved '
                        + 'Video Introduction but has '
                        + 'currently hidden it.'
                    );

                    return;
                }

                const memberGender = String(
                    trigger.dataset.memberGender
                    || ''
                )
                    .trim()
                    .toUpperCase();

                const isFemaleMember = [
                    'F',
                    'FEMALE',
                ].includes(memberGender);

                try {
                    const response = await fetch(
                        trigger.dataset.playbackUrl,
                        {
                            headers: {
                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },

                            credentials:
                                'same-origin',
                        }
                    );

                    const data =
                        await response.json();

                    if (!response.ok || !data.url) {
                        throw new Error(
                            data.message
                            || 'Video unavailable.'
                        );
                    }

                    const modal =
                        document.createElement('div');

                    modal.className = 'modal fade';

                    modal.tabIndex = -1;

                    modal.innerHTML = `
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

                                                    This video belongs
                                                    to a female member.
                                                    Please respect her
                                                    privacy and do not
                                                    record, copy or
                                                    share it.
                                                </div>
                                            `
                            : ''
                        }

                                    <div
                                        class="position-relative
                                            overflow-hidden
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

                                        <span
                                            class="position-absolute
                                                top-0 end-0 m-2
                                                px-2 py-1 rounded
                                                bg-dark
                                                bg-opacity-50
                                                text-white fs-12
                                                pe-none
                                                user-select-none">

                                            SikhanAndKaraj
                                        </span>
                                    </div>

                                    <p
                                        class="text-muted
                                            fs-12 mt-2 mb-0">

                                        Do not copy, record, share or
                                        misuse this member's personal
                                        video.
                                    </p>
                                </div>
                            </div>
                        </div>
                    `;

                    document.body.appendChild(modal);

                    const video =
                        modal.querySelector('video');

                    video.src = data.url;

                    video.addEventListener(
                        'contextmenu',
                        (contextEvent) => {
                            contextEvent.preventDefault();
                        }
                    );

                    const instance =
                        new bootstrap.Modal(modal);

                    modal.addEventListener(
                        'hidden.bs.modal',
                        () => {
                            video.pause();
                            video.removeAttribute('src');
                            video.load();

                            instance.dispose();
                            modal.remove();
                        }
                    );

                    instance.show();
                } catch (exception) {
                    window.alert(
                        exception instanceof Error
                            ? exception.message
                            : 'Video unavailable.'
                    );
                }
            }
        );
    }
);