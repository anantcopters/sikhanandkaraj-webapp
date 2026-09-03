<?php

declare(strict_types=1);
?>

<div
    class="modal fade"
    id="memberPhotoReviewModal"
    tabindex="-1"
    aria-labelledby="memberPhotoReviewTitle"
    aria-hidden="true">

    <div
        class="modal-dialog
            modal-lg modal-dialog-centered
            modal-dialog-scrollable">

        <div class="modal-content">

            <div class="modal-header bg-info-subtle py-2">

                <div>
                    <h5
                        class="modal-title"
                        id="memberPhotoReviewTitle"
                        data-photo-modal-title>

                        Member photos
                    </h5>

                    <p
                        class="text-muted fs-13 mb-0"
                        data-photo-modal-subtitle>
                    </p>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>
            </div>

            <div class="modal-body pt-2">

                <div
                    class="text-center py-5"
                    data-photo-loading>

                    <div
                        class="spinner-border
                            text-primary"
                        role="status">

                        <span class="visually-hidden">
                            Loading photos
                        </span>
                    </div>

                    <p class="text-muted mb-0 mt-3">
                        Loading member photos...
                    </p>
                </div>

                <div
                    class="alert alert-danger d-none"
                    role="alert"
                    data-photo-error>
                </div>

                <div
                    class="d-none"
                    data-photo-content>

                    <!-- Controls above image -->
                    <div
                        class="d-flex flex-column
                            flex-sm-row align-items-sm-center
                            justify-content-between gap-2 mb-3">

                        <div
                            class="d-flex flex-wrap gap-2"
                            data-photo-actions>
                        </div>

                        <div
                            class="d-flex align-items-center
                                justify-content-between
                                justify-content-sm-end gap-2">

                            <button
                                type="button"
                                class="btn btn-dark btn-sm"
                                data-carousel-previous>

                                <i
                                    class="ri-arrow-left-line
                                        me-1"
                                    aria-hidden="true">
                                </i>

                                Previous
                            </button>

                            <span
                                class="badge
                                    bg-light text-body
                                    border p-2"
                                data-photo-position>

                                0 of 0
                            </span>

                            <button
                                type="button"
                                class="btn btn-dark btn-sm"
                                data-carousel-next>

                                Next

                                <i
                                    class="ri-arrow-right-line
                                        ms-1"
                                    aria-hidden="true">
                                </i>
                            </button>
                        </div>
                    </div>

                    <div
                        id="memberPhotoCarousel"
                        class="carousel slide carousel-dark"
                        data-bs-interval="false">

                        <div
                            class="carousel-inner
                                bg-light rounded"
                            data-carousel-inner>
                        </div>
                    </div>

                    <div
                        class="d-flex align-items-center
                            justify-content-between
                            gap-3 mt-3">

                        <div data-photo-status></div>

                        <span
                            class="text-muted fs-13"
                            data-photo-uploaded-at>
                        </span>
                    </div>
                </div>

                <div
                    class="text-center py-5 d-none"
                    data-photo-empty>

                    <i
                        class="ri-image-line
                            fs-24 text-muted"
                        aria-hidden="true">
                    </i>

                    <p class="text-muted mb-0 mt-2">
                        No active photos are available.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>