<?php

declare(strict_types=1);

/**
 * Reusable Block Profile modal for member cards.
 *
 * Block is available to both Free and Paid members.
 *
 * @var string $modalId
 * @var string $profileReference
 * @var string $actionUrl
 * @var string $blockCaptcha
 */

$modalId = trim(
    (string) (
        $modalId
        ?? ''
    )
);

$profileReference = trim(
    (string) (
        $profileReference
        ?? ''
    )
);

$actionUrl = trim(
    (string) (
        $actionUrl
        ?? ''
    )
);

$blockCaptcha = trim(
    (string) (
        $blockCaptcha
        ?? ''
    )
);
?>

<div
    class="modal fade"
    id="<?= esc(
            $modalId,
            'attr'
        ) ?>"
    tabindex="-1"
    aria-hidden="true">

    <div
        class="modal-dialog
            modal-dialog-centered">

        <div
            class="modal-content
                border-0 shadow">

            <div class="modal-header bg-info-subtle py-2">

                <h2
                    class="modal-title
                        fs-18 fw-semibold">

                    Block Profile

                </h2>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>

            </div>

            <form
                method="post"
                action="<?= esc(
                            $actionUrl,
                            'attr'
                        ) ?>"
                data-validate
                data-submit-loader
                novalidate>

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="action_source"
                    value="card">

                <div class="modal-body">

                    <p class="text-muted fs-13">

                        Blocking
                        <strong>
                            <?= esc(
                                $profileReference
                            ) ?>
                        </strong>
                        removes the member from your matches
                        and member activity.

                    </p>

                    <div class="mb-3">

                        <label
                            class="form-label"
                            for="<?= esc(
                                        $modalId . 'Comment',
                                        'attr'
                                    ) ?>">

                            Reason for blocking

                        </label>

                        <textarea
                            id="<?= esc(
                                    $modalId . 'Comment',
                                    'attr'
                                ) ?>"
                            name="comment"
                            class="form-control"
                            rows="4"
                            maxlength="250"
                            required
                            data-error-required="Please enter a comment."
                            data-error-maxlength="The comment cannot exceed 250 characters."></textarea>

                    </div>

                    <?php if ($blockCaptcha !== ''): ?>

                        <div class="mb-0">

                            <label
                                class="form-label"
                                for="<?= esc(
                                            $modalId . 'Captcha',
                                            'attr'
                                        ) ?>">

                                Security Check

                            </label>

                            <div class="input-group">

                                <span
                                    class="input-group-text">

                                    <?= esc(
                                        $blockCaptcha
                                    ) ?>

                                </span>

                                <input
                                    type="text"
                                    id="<?= esc(
                                            $modalId . 'Captcha',
                                            'attr'
                                        ) ?>"
                                    name="captcha_answer"
                                    class="form-control"
                                    maxlength="20"
                                    autocomplete="off"
                                    required
                                    data-error-required="Please enter the security answer.">

                            </div>

                        </div>

                    <?php endif; ?>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button
                        type="submit"
                        class="btn
                            registration-form__submit
                            fs-14
                            fw-medium
                            text-uppercase"
                        data-submit-button>

                        <span data-submit-idle>

                            <i
                                class="ri-forbid-line
                                    me-1 fs-18"
                                aria-hidden="true">
                            </i>

                            Block Profile

                        </span>

                        <span
                            class="registration-submit__loading
                                d-none"
                            data-submit-loading>

                            <span
                                class="spinner-border
                                    spinner-border-sm"
                                aria-hidden="true">
                            </span>

                            Saving...

                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>