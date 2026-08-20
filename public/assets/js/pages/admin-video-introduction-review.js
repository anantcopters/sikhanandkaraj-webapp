'use strict';

document.addEventListener(
    'DOMContentLoaded',
    () => {
        const form = document.querySelector(
            '[data-video-moderation-form]'
        );

        const openVideoModal = (
            videoUrl,
            memberName,
            profileReference
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
                        <div class="modal-header bg-info-subtle py-2">
                            <div>
                                <h2
                                    class="modal-title
                                        fs-18 mb-1"
                                    data-video-member-name>
                                </h2>

                                <p
                                    class="text-muted
                                        fs-12 mb-0">

                                    Profile ID:
                                    <strong
                                        class="text-body"
                                        data-video-profile-id>
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
                            <video
                                class="w-100 d-block
                                    rounded bg-dark"
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

                            <p
                                class="color-pink
                                    fs-14 mt-2 mb-0">

                                Review the complete recording
                                before saving a moderation
                                decision.
                            </p>
                        </div>
                    </div>
                </div>
            `;

            const memberNameElement =
                modalElement.querySelector(
                    '[data-video-member-name]'
                );

            const profileIdElement =
                modalElement.querySelector(
                    '[data-video-profile-id]'
                );

            const video =
                modalElement.querySelector(
                    'video'
                );

            memberNameElement.textContent =
                memberName !== ''
                    ? memberName
                    : 'Member';

            profileIdElement.textContent =
                profileReference !== ''
                    ? profileReference
                    : '—';

            video.src = videoUrl;

            video.addEventListener(
                'contextmenu',
                (event) => {
                    event.preventDefault();
                }
            );

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
            (event) => {
                const trigger =
                    event.target.closest(
                        '[data-admin-video-open]'
                    );

                if (!trigger) {
                    return;
                }

                const videoUrl = String(
                    trigger.dataset.videoUrl
                    || ''
                ).trim();

                if (videoUrl === '') {
                    return;
                }

                openVideoModal(
                    videoUrl,

                    String(
                        trigger.dataset.memberName
                        || ''
                    ).trim(),

                    String(
                        trigger.dataset
                            .profileReference
                        || ''
                    ).trim()
                );
            }
        );

        if (!form) {
            return;
        }

        const decision = form.querySelector(
            '[name="decision"]'
        );

        const reason = form.querySelector(
            '[name="reason"]'
        );

        if (!decision || !reason) {
            return;
        }

        const refreshFieldValidation = (
            field
        ) => {
            field.dispatchEvent(
                new Event(
                    'change',
                    {
                        bubbles: true,
                    }
                )
            );
        };

        const configureReasonValidation = () => {
            const selectedDecision = String(
                decision.value || ''
            )
                .trim()
                .toUpperCase();

            const reasonRequired = [
                'REJECT',
                'RESUBMIT',
            ].includes(selectedDecision);

            reason.required = reasonRequired;

            if (reasonRequired) {
                reason.setAttribute(
                    'minlength',
                    '10'
                );
            } else {
                reason.removeAttribute(
                    'minlength'
                );
            }

            if (
                reasonRequired
                && reason.value.trim().length < 10
            ) {
                reason.setCustomValidity(
                    'Provide a clear reason of at '
                    + 'least 10 characters.'
                );

                return;
            }

            reason.setCustomValidity('');
        };

        decision.addEventListener(
            'change',
            () => {
                decision.setCustomValidity('');

                configureReasonValidation();

                refreshFieldValidation(
                    reason
                );
            }
        );

        reason.addEventListener(
            'input',
            () => {
                configureReasonValidation();

                refreshFieldValidation(
                    reason
                );
            }
        );

        form.addEventListener(
            'submit',
            () => {
                decision.setCustomValidity('');

                configureReasonValidation();
            }
        );

        configureReasonValidation();
    }
);