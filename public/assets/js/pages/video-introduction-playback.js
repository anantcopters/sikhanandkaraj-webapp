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

                if (
                    trigger.dataset.hidden === '1'
                ) {
                    window.alert(
                        'This member has an approved '
                        + 'Video Introduction but has '
                        + 'currently hidden it from viewing.'
                    );

                    return;
                }

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

                    if (
                        !response.ok
                        || !data.url
                    ) {
                        throw new Error(
                            data.message
                            || 'Video unavailable.'
                        );
                    }

                    const modal =
                        document.createElement('div');

                    modal.className =
                        'modal fade';

                    modal.tabIndex = -1;

                    modal.innerHTML = `
                        <div
                            class="modal-dialog
                                modal-dialog-centered">

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
                                    <video
                                        class="w-100 rounded"
                                        controls
                                        playsinline
                                        preload="metadata">
                                    </video>

                                    <p
                                        class="text-muted
                                            fs-12 mt-2 mb-0">

                                        Do not copy, share or
                                        misuse this member's
                                        personal video.
                                    </p>
                                </div>
                            </div>
                        </div>
                    `;

                    document.body.appendChild(
                        modal
                    );

                    modal
                        .querySelector('video')
                        .src = data.url;

                    const instance =
                        new bootstrap.Modal(
                            modal
                        );

                    modal.addEventListener(
                        'hidden.bs.modal',
                        () => {
                            modal
                                .querySelector('video')
                                ?.pause();

                            instance.dispose();

                            modal.remove();
                        }
                    );

                    instance.show();
                } catch (exception) {
                    window.alert(
                        exception.message
                    );
                }
            }
        );
    }
);