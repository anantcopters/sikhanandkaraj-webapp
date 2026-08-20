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
            if (
                !window.AppVideoIntroductionModal
                || typeof window.AppVideoIntroductionModal.show
                !== 'function'
            ) {
                return;
            }

            window.AppVideoIntroductionModal.show({
                videoUrl: videoUrl,
                memberName: memberName,
                profileReference: profileReference,
                isFemaleMember: false,
                isAdminReview: true
            });
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