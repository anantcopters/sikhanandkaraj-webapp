'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const photoModalElement = document.getElementById(
        'memberPhotoReviewModal'
    );

    if (
        !photoModalElement
        || typeof bootstrap === 'undefined'
    ) {
        return;
    }

    const photoModal = bootstrap.Modal.getOrCreateInstance(
        photoModalElement
    );

    const modalTitle = photoModalElement.querySelector(
        '[data-photo-modal-title]'
    );

    const modalSubtitle = photoModalElement.querySelector(
        '[data-photo-modal-subtitle]'
    );

    const loadingPanel = photoModalElement.querySelector(
        '[data-photo-loading]'
    );

    const errorPanel = photoModalElement.querySelector(
        '[data-photo-error]'
    );

    const contentPanel = photoModalElement.querySelector(
        '[data-photo-content]'
    );

    const emptyPanel = photoModalElement.querySelector(
        '[data-photo-empty]'
    );

    const carouselElement = document.getElementById(
        'memberPhotoCarousel'
    );

    const carouselInner = photoModalElement.querySelector(
        '[data-carousel-inner]'
    );

    const previousButton = photoModalElement.querySelector(
        '[data-carousel-previous]'
    );

    const nextButton = photoModalElement.querySelector(
        '[data-carousel-next]'
    );

    const positionLabel = photoModalElement.querySelector(
        '[data-photo-position]'
    );

    const actionPanel = photoModalElement.querySelector(
        '[data-photo-actions]'
    );

    const statusPanel = photoModalElement.querySelector(
        '[data-photo-status]'
    );

    const uploadedAtPanel = photoModalElement.querySelector(
        '[data-photo-uploaded-at]'
    );

    const carousel = bootstrap.Carousel.getOrCreateInstance(
        carouselElement,
        {
            interval: false,
            touch: true
        }
    );

    let currentPhotos = [];
    let currentIndex = 0;

    function escapeHtml(value) {
        const element = document.createElement('div');
        element.textContent = String(value ?? '');
        return element.innerHTML;
    }

    /**
     * Update existing forms and values used by dynamically created forms.
     *
     * @param {{name?: string, hash?: string}|null} csrf
     */
    function updateCsrf(csrf) {
        if (!csrf || !csrf.name || !csrf.hash) {
            return;
        }

        window.csrfTokenName = csrf.name;
        window.csrfTokenHash = csrf.hash;

        document.querySelectorAll(
            `input[name="${CSS.escape(csrf.name)}"]`
        ).forEach((field) => {
            field.value = csrf.hash;
        });
    }

    function setLoading(button, loading) {
        if (!button) {
            return;
        }

        const label = button.querySelector(
            '.registration-submit__label'
        );

        const loader = button.querySelector(
            '.registration-submit__loading'
        );

        button.disabled = loading;

        if (label) {
            label.classList.toggle(
                'd-none',
                loading
            );
        }

        if (loader) {
            loader.classList.toggle(
                'd-none',
                !loading
            );
        }
    }

    function resetPhotoModal() {
        currentPhotos = [];
        currentIndex = 0;

        loadingPanel.classList.remove('d-none');
        errorPanel.classList.add('d-none');
        contentPanel.classList.add('d-none');
        emptyPanel.classList.add('d-none');

        errorPanel.textContent = '';
        carouselInner.innerHTML = '';
        actionPanel.innerHTML = '';
        statusPanel.innerHTML = '';
        uploadedAtPanel.textContent = '';
        positionLabel.textContent = '0 of 0';

        previousButton.disabled = true;
        nextButton.disabled = true;
    }

    /**
     * Return the number of photos still awaiting moderation.
     *
     * @returns {number}
     */
    function pendingPhotoCount() {
        return currentPhotos.filter(
            (photo) =>
                String(photo.status || '').toUpperCase()
                === 'PENDING'
        ).length;
    }

    function statusBadge(status) {
        const normalized = String(status || '').toUpperCase();

        let badgeClass =
            'bg-warning-subtle text-warning';

        if (normalized === 'APPROVED') {
            badgeClass =
                'bg-success-subtle text-success';
        }

        if (normalized === 'REJECTED') {
            badgeClass =
                'bg-danger-subtle text-danger';
        }

        const label =
            normalized.charAt(0)
            + normalized.slice(1).toLowerCase();

        return `
            <span class="badge ${badgeClass} p-2">
                ${escapeHtml(label || 'Unknown')}
            </span>
        `;
    }

    function createModerationForm(
        photo,
        action,
        buttonClass,
        buttonText,
        iconClass
    ) {
        const actionUrl = action === 'approve'
            ? photo.approve_url
            : photo.reject_url;

        const isApprove = action === 'approve';

        const title = isApprove
            ? 'Approve photo?'
            : 'Reject photo?';

        const message = isApprove
            ? 'Approve this member photo?'
            : 'Reject this member photo?';

        const loadingText = isApprove
            ? 'Approving...'
            : 'Rejecting...';

        return `
        <form
            method="post"
            action="${escapeHtml(actionUrl)}"
            class="mb-0"
            data-confirm-form
            data-moderation-form
            data-action-type="${escapeHtml(action)}"
            data-photo-id="${escapeHtml(photo.id)}"
            data-confirm-title="${escapeHtml(title)}"
            data-confirm-message="${escapeHtml(message)}"
            data-confirm-button-text="${escapeHtml(buttonText)}"
            data-confirm-button-class="${escapeHtml(buttonClass)}"
            data-confirm-icon="${escapeHtml(iconClass)}"
            data-confirm-loading-text="${escapeHtml(loadingText)}">

            <input
                type="hidden"
                name="${escapeHtml(
            window.csrfTokenName || ''
        )}"
                value="${escapeHtml(
            window.csrfTokenHash || ''
        )}">

            <button
                type="submit"
                class="btn ${escapeHtml(buttonClass)}
                    btn-sm registration-form__submit">

                <span
                    class="registration-submit__label">

                    <i
                        class="${escapeHtml(iconClass)} me-1"
                        aria-hidden="true">
                    </i>

                    ${escapeHtml(buttonText)}
                </span>

                <span
                    class="registration-submit__loading d-none"
                    aria-hidden="true">

                    <span
                        class="spinner-border spinner-border-sm"
                        role="status"
                        aria-hidden="true">
                    </span>

                    <span>${escapeHtml(loadingText)}</span>
                </span>
            </button>
        </form>
    `;
    }

    function renderActions(photo) {
        actionPanel.innerHTML = '';

        if (
            String(photo.status).toUpperCase()
            !== 'PENDING'
        ) {
            return;
        }

        actionPanel.innerHTML =
            createModerationForm(
                photo,
                'approve',
                'btn-success',
                'Approve',
                'ri-check-line'
            )
            + createModerationForm(
                photo,
                'reject',
                'btn-danger',
                'Reject',
                'ri-close-line'
            );
    }

    function updateCurrentPhotoDetails() {
        if (currentPhotos.length === 0) {
            return;
        }

        const photo = currentPhotos[currentIndex];

        positionLabel.textContent =
            `${currentIndex + 1} of ${currentPhotos.length}`;

        statusPanel.innerHTML =
            statusBadge(photo.status)
            + (
                photo.is_primary
                    ? `
                        <span
                            class="badge
                                bg-primary-subtle
                                text-primary p-2 ms-1">
                            Main Photo
                        </span>
                    `
                    : ''
            );

        uploadedAtPanel.textContent =
            photo.created_at_label
                ? `Uploaded ${photo.created_at_label}`
                : '';

        renderActions(photo);

        previousButton.disabled =
            currentPhotos.length <= 1;

        nextButton.disabled =
            currentPhotos.length <= 1;
    }

    function renderPhotos(payload) {
        const member = payload.member || {};

        currentPhotos = Array.isArray(payload.photos)
            ? payload.photos
            : [];

        const memberId = String(
            member.member_id || ''
        );

        const reference = String(
            member.profile_ref_number || ''
        );

        const age = member.age
            ? String(member.age)
            : '';

        const gender = String(
            member.gender || ''
        );

        const location = String(
            member.location || ''
        );

        photoModalElement.dataset.memberId = memberId;

        modalTitle.textContent =
            member.full_name || 'Member photos';

        /*
         * Preserve member metadata when the carousel is re-rendered after
         * an approve or reject action.
         */
        modalSubtitle.dataset.reference = reference;
        modalSubtitle.dataset.age = age;
        modalSubtitle.dataset.gender = gender;
        modalSubtitle.dataset.location = location;

        const summaryParts = [
            reference,
            age !== '' ? `${age} years` : '',
            gender,
            location
        ].filter(Boolean);

        modalSubtitle.textContent =
            summaryParts.join(' • ');

        loadingPanel.classList.add('d-none');

        if (currentPhotos.length === 0) {
            emptyPanel.classList.remove('d-none');
            return;
        }

        carouselInner.innerHTML = currentPhotos
            .map((photo, index) => {
                const imageMarkup = photo.signed_url
                    ? `
                        <img
                            src="${escapeHtml(photo.signed_url)}"
                            class="d-block mx-auto img-fluid rounded"
                            style="max-height: 400px;"
                            alt="Member profile photo">
                    `
                    : `
                        <div class="text-center py-5">
                            <i
                                class="ri-image-line fs-24 text-muted"
                                aria-hidden="true">
                            </i>

                            <p class="text-muted mb-0 mt-2">
                                Photo preview is unavailable.
                            </p>
                        </div>
                    `;

                return `
                    <div
                        class="carousel-item
                            ${index === 0 ? 'active' : ''}"
                        data-photo-index="${index}">

                        <div
                            class="d-flex align-items-center
                                justify-content-center p-3"
                            style="min-height: 320px;">

                            ${imageMarkup}
                        </div>
                    </div>
                `;
            })
            .join('');

        currentIndex = 0;

        contentPanel.classList.remove('d-none');

        carousel.to(0);

        updateCurrentPhotoDetails();
    }

    async function loadMemberPhotos(button) {
        resetPhotoModal();

        modalTitle.textContent =
            button.dataset.memberName || 'Member photos';

        photoModal.show();

        try {
            const response = await fetch(
                button.dataset.photoUrl,
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }
            );

            const payload = await response.json();

            updateCsrf(payload.csrf);

            if (
                !response.ok
                || payload.status !== 'success'
            ) {
                throw new Error(
                    payload.message
                    || 'The photos could not be loaded.'
                );
            }

            renderPhotos(payload.data || {});
        } catch (error) {
            loadingPanel.classList.add('d-none');
            contentPanel.classList.add('d-none');
            emptyPanel.classList.add('d-none');

            errorPanel.textContent =
                error instanceof Error
                    ? error.message
                    : 'The photos could not be loaded.';

            errorPanel.classList.remove('d-none');
        }
    }

    async function submitModeration(form) {
        const formButton = form.querySelector(
            'button[type="submit"]'
        );

        setLoading(formButton, true);

        try {
            const response = await fetch(
                form.action,
                {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }
            );

            const payload = await response.json();

            updateCsrf(payload.csrf);

            if (
                !response.ok
                || payload.status !== 'success'
            ) {
                throw new Error(
                    payload.message
                    || 'The action could not be completed.'
                );
            }

            const actionType = form.dataset.actionType;

            if (actionType === 'approve-all') {
                const memberId = form.dataset.memberId;

                document.querySelector(
                    `[data-member-row="${CSS.escape(memberId)
                    }"]`
                )?.remove();

                photoModal.hide();
            } else {
                const photoId = Number(
                    form.dataset.photoId
                );

                currentPhotos = currentPhotos.filter(
                    (photo) =>
                        Number(photo.id) !== photoId
                );

                const currentMemberId =
                    photoModalElement.dataset.memberId || '';

                const remainingPendingCount =
                    pendingPhotoCount();

                if (remainingPendingCount === 0) {
                    if (currentMemberId !== '') {
                        document.querySelector(
                            `[data-member-row="${CSS.escape(currentMemberId)
                            }"]`
                        )?.remove();
                    }
                }

                if (currentPhotos.length === 0) {
                    contentPanel.classList.add('d-none');
                    emptyPanel.classList.remove('d-none');
                } else {
                    renderPhotos({
                        member: {
                            member_id: currentMemberId,
                            full_name:
                                modalTitle.textContent,
                            profile_ref_number:
                                modalSubtitle.dataset.reference
                                || '',
                            age:
                                modalSubtitle.dataset.age
                                || '',
                            gender:
                                modalSubtitle.dataset.gender
                                || '',
                            location:
                                modalSubtitle.dataset.location
                                || ''
                        },
                        photos: currentPhotos
                    });
                }
            }

            if (
                window.AppFeedbackModal
                && typeof window.AppFeedbackModal.show
                === 'function'
            ) {
                window.AppFeedbackModal.show({
                    type: 'success',
                    title:
                        payload.title
                        || 'Action completed',
                    message:
                        payload.message
                        || 'The action was completed.'
                });
            }
        } catch (error) {
            setLoading(formButton, false);

            if (
                window.AppFeedbackModal
                && typeof window.AppFeedbackModal.show
                === 'function'
            ) {
                window.AppFeedbackModal.show({
                    type: 'error',
                    title: 'Action not completed',
                    message:
                        error instanceof Error
                            ? error.message
                            : 'The action could not be completed.'
                });

                return;
            }

            errorPanel.textContent =
                error instanceof Error
                    ? error.message
                    : 'The action could not be completed.';

            errorPanel.classList.remove('d-none');
        }
    }

    document.addEventListener(
        'click',
        (event) => {
            const reviewButton = event.target.closest(
                '[data-photo-review]'
            );

            if (reviewButton) {
                loadMemberPhotos(reviewButton);
                return;
            }

            if (
                event.target.closest(
                    '[data-carousel-previous]'
                )
            ) {
                carousel.prev();
                return;
            }

            if (
                event.target.closest(
                    '[data-carousel-next]'
                )
            ) {
                carousel.next();
            }
        }
    );

    document.addEventListener(
        'submit',
        (event) => {
            const form = event.target;

            if (
                !(form instanceof HTMLFormElement)
                || !form.matches(
                    '[data-moderation-form]'
                )
            ) {
                return;
            }

            /*
             * The shared confirmation-modal.js runs first in capturing
             * mode. The first submit is stopped there. After confirmation,
             * it calls requestSubmit(), allowing this handler to perform
             * the AJAX request.
             */
            event.preventDefault();

            submitModeration(form);
        }
    );

    carouselElement.addEventListener(
        'slid.bs.carousel',
        (event) => {
            currentIndex =
                Number(event.to) || 0;

            updateCurrentPhotoDetails();
        }
    );
});