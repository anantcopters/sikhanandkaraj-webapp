'use strict';

document.addEventListener(
    'DOMContentLoaded',
    () => {
        /**
         * @param {string} message
         * @returns {void}
         */
        const showInformationModal = (message) => {
            if (
                window.AppFeedbackModal
                && typeof window.AppFeedbackModal.show
                === 'function'
            ) {
                window.AppFeedbackModal.show({
                    type: 'info',
                    title: 'Video Introduction',
                    message: String(message || ''),
                    buttonText: 'Okay'
                });

                return;
            }

            console.error(message);
        };

        document.addEventListener(
            'click',
            async (event) => {
                const trigger = event.target.closest(
                    '[data-video-introduction-open]'
                );

                if (!trigger) {
                    return;
                }

                event.preventDefault();

                if (trigger.dataset.hidden === '1') {
                    showInformationModal(
                        'This member has an approved '
                        + 'Video Introduction but has '
                        + 'currently hidden it.'
                    );

                    return;
                }

                const playbackUrl = String(
                    trigger.dataset.playbackUrl || ''
                ).trim();

                if (playbackUrl === '') {
                    showInformationModal(
                        'The Video Introduction is '
                        + 'currently unavailable.'
                    );

                    return;
                }

                if (
                    !window.AppVideoIntroductionModal
                    || typeof window.AppVideoIntroductionModal
                        .show !== 'function'
                ) {
                    showInformationModal(
                        'The video player could not be opened.'
                    );

                    return;
                }

                const gender = String(
                    trigger.dataset.memberGender || ''
                )
                    .trim()
                    .toUpperCase();

                const isFemaleMember = [
                    'F',
                    'FEMALE'
                ].includes(gender);

                const originalDisabled =
                    trigger.disabled === true;

                trigger.disabled = true;

                try {
                    const response = await fetch(
                        playbackUrl,
                        {
                            headers: {
                                'X-Requested-With':
                                    'XMLHttpRequest'
                            },
                            credentials: 'same-origin'
                        }
                    );

                    let data = {};

                    try {
                        data = await response.json();
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

                    window.AppVideoIntroductionModal.show({
                        videoUrl: data.url,

                        memberName: String(
                            trigger.dataset.memberName || ''
                        ).trim(),

                        profileReference: String(
                            trigger.dataset
                                .profileReference || ''
                        ).trim(),

                        isFemaleMember: isFemaleMember,
                        isAdminReview: false
                    });
                } catch (exception) {
                    showInformationModal(
                        exception instanceof Error
                            ? exception.message
                            : 'The Video Introduction '
                            + 'is currently unavailable.'
                    );
                } finally {
                    trigger.disabled = originalDisabled;
                }
            }
        );
    }
);