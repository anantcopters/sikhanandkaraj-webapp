(function (window, document) {
    'use strict';

    const modalElement = document.getElementById(
        'videoIntroductionPlaybackModal'
    );

    if (
        !modalElement
        || typeof bootstrap === 'undefined'
    ) {
        return;
    }

    const video = modalElement.querySelector(
        '[data-video-modal-player]'
    );

    const memberNameElement = modalElement.querySelector(
        '[data-video-modal-member-name]'
    );

    const profileRowElement = modalElement.querySelector(
        '[data-video-modal-profile-row]'
    );

    const profileReferenceElement =
        modalElement.querySelector(
            '[data-video-modal-profile-reference]'
        );

    const femaleNoticeElement = modalElement.querySelector(
        '[data-video-modal-female-notice]'
    );

    const messageElement = modalElement.querySelector(
        '[data-video-modal-message]'
    );

    if (
        !video
        || !memberNameElement
        || !profileRowElement
        || !profileReferenceElement
        || !femaleNoticeElement
        || !messageElement
    ) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(
        modalElement
    );

    /**
     * @param {{
     *     videoUrl: string,
     *     memberName?: string,
     *     profileReference?: string,
     *     isFemaleMember?: boolean,
     *     isAdminReview?: boolean
     * }} options
     */
    function show(options) {
        const settings = options || {};

        const videoUrl = String(
            settings.videoUrl || ''
        ).trim();

        if (videoUrl === '') {
            return;
        }

        const memberName = String(
            settings.memberName || ''
        ).trim();

        const profileReference = String(
            settings.profileReference || ''
        ).trim();

        memberNameElement.textContent =
            memberName !== ''
                ? memberName
                : 'Video Introduction';

        profileReferenceElement.textContent =
            profileReference !== ''
                ? profileReference
                : '—';

        profileRowElement.classList.toggle(
            'd-none',
            profileReference === ''
        );

        femaleNoticeElement.classList.toggle(
            'd-none',
            settings.isFemaleMember !== true
        );

        messageElement.textContent =
            settings.isAdminReview === true
                ? 'Review the complete recording before '
                + 'saving a moderation decision.'
                : 'Do not copy, record, share or misuse '
                + 'this member\'s personal video.';

        video.src = videoUrl;
        video.load();

        modal.show();
    }

    function hide() {
        modal.hide();
    }

    video.addEventListener(
        'contextmenu',
        function (event) {
            event.preventDefault();
        }
    );

    modalElement.addEventListener(
        'hidden.bs.modal',
        function () {
            video.pause();
            video.removeAttribute('src');
            video.load();

            memberNameElement.textContent =
                'Video Introduction';

            profileReferenceElement.textContent =
                '—';

            femaleNoticeElement.classList.add(
                'd-none'
            );
        }
    );

    window.AppVideoIntroductionModal = {
        show: show,
        hide: hide
    };
})(window, document);