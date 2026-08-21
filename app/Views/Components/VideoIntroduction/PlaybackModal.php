<?php

declare(strict_types=1);
?>

<div
    class="modal fade"
    id="videoIntroductionPlaybackModal"
    tabindex="-1"
    aria-labelledby="videoIntroductionPlaybackModalTitle"
    aria-hidden="true">

    <div
        class="modal-dialog
            modal-dialog-centered
            modal-lg">

        <div class="modal-content">
            <div
                class="modal-header
                    bg-info-subtle py-2">

                <div>
                    <h2
                        id="videoIntroductionPlaybackModalTitle"
                        class="modal-title fs-18 mb-1"
                        data-video-modal-member-name>

                        Video Introduction
                    </h2>

                    <p
                        class="text-muted fs-12 mb-0"
                        data-video-modal-profile-row>

                        Profile ID:

                        <strong
                            class="text-body"
                            data-video-modal-profile-reference>
                            —
                        </strong>
                    </p>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>
            </div>

            <div class="modal-body">
                <div
                    class="alert alert-warning fs-13 d-none"
                    role="alert"
                    data-video-modal-female-notice>

                    This video belongs to a female member.
                    Please respect her privacy and do not
                    record, copy or share it.
                </div>

                <div
                    class="overflow-hidden rounded bg-dark">

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
                        preload="metadata"
                        data-video-modal-player>
                    </video>
                </div>

                <p
                    class="color-pink fs-13 fw-medium mt-2 mb-0"
                    data-video-modal-message>

                    Do not copy, record, share or misuse
                    this member's personal video.
                </p>
            </div>
        </div>
    </div>
</div>