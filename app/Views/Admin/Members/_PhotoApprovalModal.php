<?php

declare(strict_types=1);

/**
 * Member photo approval modal.
 *
 * @var array<string, mixed>       $member
 * @var list<array<string, mixed>> $photos
 * @var string                     $modalId
 */

$member = isset($member) && is_array($member)
    ? $member
    : [];

$photos = isset($photos) && is_array($photos)
    ? $photos
    : [];

$memberName = trim(
    (string) ($member['full_name'] ?? '')
);

$carouselId = $modalId . 'Carousel';
?>

<div
    class="modal fade"
    id="<?= esc($modalId, 'attr') ?>"
    tabindex="-1"
    aria-labelledby="<?= esc(
                            $modalId . 'Label',
                            'attr'
                        ) ?>"
    aria-hidden="true">

    <div
        class="modal-dialog
            modal-xl modal-dialog-centered
            modal-dialog-scrollable">

        <div class="modal-content">

            <div class="modal-header bg-info-subtle py-2">

                <div>
                    <h5
                        class="modal-title"
                        id="<?= esc(
                                $modalId . 'Label',
                                'attr'
                            ) ?>">

                        <?= esc($memberName) ?>
                    </h5>

                    <p class="text-muted fs-13 mb-0">
                        Reference:
                        <?= esc(
                            (string) (
                                $member['profile_ref_number']
                                ?? ''
                            )
                        ) ?>
                    </p>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>
            </div>

            <div class="modal-body p-0">

                <?php if ($photos === []): ?>

                    <div class="text-center py-5">
                        <i
                            class="ri-image-line fs-24
                                text-muted"
                            aria-hidden="true">
                        </i>

                        <p class="text-muted mb-0 mt-2">
                            No active photos are available.
                        </p>
                    </div>

                <?php else: ?>

                    <div
                        id="<?= esc(
                                $carouselId,
                                'attr'
                            ) ?>"
                        class="carousel slide">

                        <div class="carousel-indicators">

                            <?php foreach (
                                $photos as $index => $photo
                            ): ?>

                                <button
                                    type="button"
                                    data-bs-target="#<?= esc(
                                                            $carouselId,
                                                            'attr'
                                                        ) ?>"
                                    data-bs-slide-to="<?= esc(
                                                            (string) $index,
                                                            'attr'
                                                        ) ?>"
                                    class="<?= $index === 0
                                                ? 'active'
                                                : '' ?>"
                                    aria-current="<?= $index === 0
                                                        ? 'true'
                                                        : 'false' ?>"
                                    aria-label="Photo <?= esc(
                                                            (string) ($index + 1),
                                                            'attr'
                                                        ) ?>">
                                </button>

                            <?php endforeach; ?>

                        </div>

                        <div class="carousel-inner bg-light">

                            <?php foreach (
                                $photos as $index => $photo
                            ): ?>
                                <?php
                                $photoId = (int) (
                                    $photo['id'] ?? 0
                                );

                                $photoStatus = strtoupper(
                                    trim(
                                        (string) (
                                            $photo['status']
                                            ?? ''
                                        )
                                    )
                                );

                                $isPending =
                                    $photoStatus === 'PENDING';

                                $signedUrl = trim(
                                    (string) (
                                        $photo['signed_url']
                                        ?? ''
                                    )
                                );

                                $statusClass = match ($photoStatus) {
                                    'APPROVED' =>
                                    'bg-success-subtle text-success',

                                    'REJECTED' =>
                                    'bg-danger-subtle text-danger',

                                    default =>
                                    'bg-warning-subtle text-warning',
                                };
                                ?>

                                <div
                                    class="carousel-item
                                        <?= $index === 0
                                            ? 'active'
                                            : '' ?>">

                                    <div
                                        class="d-flex
                                            align-items-center
                                            justify-content-center
                                            p-3 p-md-4"
                                        style="min-height: 420px;">

                                        <?php if ($signedUrl !== ''): ?>

                                            <img
                                                src="<?= esc(
                                                            $signedUrl,
                                                            'attr'
                                                        ) ?>"
                                                class="img-fluid
                                                    rounded"
                                                style="max-height: 520px;"
                                                alt="Profile photo of <?= esc(
                                                                            $memberName,
                                                                            'attr'
                                                                        ) ?>">

                                        <?php else: ?>

                                            <div class="text-center">
                                                <i
                                                    class="ri-image-line
                                                        fs-24 text-muted"
                                                    aria-hidden="true">
                                                </i>

                                                <p
                                                    class="text-muted
                                                        mb-0 mt-2">
                                                    Photo preview is
                                                    unavailable.
                                                </p>
                                            </div>

                                        <?php endif; ?>

                                    </div>

                                    <div class="border-top p-3">

                                        <div
                                            class="d-flex
                                                flex-column flex-md-row
                                                align-items-md-center
                                                justify-content-between
                                                gap-3">

                                            <div>
                                                <span
                                                    class="badge p-2
                                                        <?= esc(
                                                            $statusClass,
                                                            'attr'
                                                        ) ?>">

                                                    <?= esc(
                                                        ucwords(
                                                            strtolower(
                                                                $photoStatus
                                                            )
                                                        )
                                                    ) ?>
                                                </span>

                                                <?php if (
                                                    (
                                                        $photo['is_primary']
                                                        ?? false
                                                    ) === true
                                                ): ?>

                                                    <span
                                                        class="badge
                                                            bg-primary-subtle
                                                            text-primary p-2">

                                                        Main Photo
                                                    </span>

                                                <?php endif; ?>
                                            </div>

                                            <?php if ($isPending): ?>

                                                <div
                                                    class="d-flex
                                                        flex-column
                                                        flex-sm-row
                                                        gap-2">

                                                    <form
                                                        method="post"
                                                        action="<?= route_to(
                                                                    'admin.members'
                                                                        . '.photos'
                                                                        . '.approve',
                                                                    $photoId
                                                                ) ?>"
                                                        data-confirm-form
                                                        data-confirm-message="Approve this photo?">

                                                        <?= csrf_field() ?>

                                                        <button
                                                            type="submit"
                                                            class="btn
                                                                btn-success
                                                                w-100">

                                                            <i
                                                                class="ri-check-line
                                                                    me-1"
                                                                aria-hidden="true">
                                                            </i>

                                                            Approve
                                                        </button>
                                                    </form>

                                                    <form
                                                        method="post"
                                                        action="<?= route_to(
                                                                    'admin.members'
                                                                        . '.photos'
                                                                        . '.reject',
                                                                    $photoId
                                                                ) ?>"
                                                        data-confirm-form
                                                        data-confirm-message="Reject this photo?">

                                                        <?= csrf_field() ?>

                                                        <div
                                                            class="input-group">

                                                            <input
                                                                type="text"
                                                                name="rejection_reason"
                                                                class="form-control"
                                                                maxlength="500"
                                                                placeholder="Reason (optional)"
                                                                aria-label="Rejection reason">

                                                            <button
                                                                type="submit"
                                                                class="btn
                                                                    btn-danger">

                                                                <i
                                                                    class="ri-close-line
                                                                        me-1"
                                                                    aria-hidden="true">
                                                                </i>

                                                                Reject
                                                            </button>
                                                        </div>
                                                    </form>

                                                </div>

                                            <?php endif; ?>

                                        </div>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <?php if (count($photos) > 1): ?>

                            <button
                                class="carousel-control-prev"
                                type="button"
                                data-bs-target="#<?= esc(
                                                        $carouselId,
                                                        'attr'
                                                    ) ?>"
                                data-bs-slide="prev">

                                <span
                                    class="carousel-control-prev-icon"
                                    aria-hidden="true">
                                </span>

                                <span class="visually-hidden">
                                    Previous
                                </span>
                            </button>

                            <button
                                class="carousel-control-next"
                                type="button"
                                data-bs-target="#<?= esc(
                                                        $carouselId,
                                                        'attr'
                                                    ) ?>"
                                data-bs-slide="next">

                                <span
                                    class="carousel-control-next-icon"
                                    aria-hidden="true">
                                </span>

                                <span class="visually-hidden">
                                    Next
                                </span>
                            </button>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>