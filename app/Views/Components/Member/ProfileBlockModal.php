<?php

declare(strict_types=1);

/**
 * Shared Block Profile modal.
 *
 * Used by Full Profile and ProfileCard.
 *
 * @var string                $modalId
 * @var string                $profileReference
 * @var string                $actionUrl
 * @var string                $actionSource
 * @var array<string, string> $validationErrors
 * @var bool                  $reopenModal
 */

$modalId = trim(
    (string) (
        $modalId
        ?? 'memberBlockModal'
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

$actionSource = trim(
    (string) (
        $actionSource
        ?? 'profile'
    )
);

$errors =
    isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$reopenModal =
    ($reopenModal ?? false)
    === true;

$commentError = trim(
    (string) (
        $errors['comment']
        ?? ''
    )
);

$titleId =
    $modalId . 'Title';

$commentId =
    $modalId . 'Comment';
?>

<div
    class="modal fade"
    id="<?= esc(
            $modalId,
            'attr'
        ) ?>"
    tabindex="-1"
    aria-labelledby="<?= esc(
                            $titleId,
                            'attr'
                        ) ?>"
    aria-hidden="true"
    data-reopen-member-block="<?= $reopenModal
                                    ? '1'
                                    : '0' ?>">

    <div
        class="
            modal-dialog
            modal-dialog-centered
        ">

        <div class="modal-content">

            <form
                method="post"
                action="<?= esc(
                            $actionUrl,
                            'attr'
                        ) ?>"
                data-validate
                data-member-block-form
                novalidate>

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="action_source"
                    value="<?= esc(
                                $actionSource,
                                'attr'
                            ) ?>">

                <div
                    class="
                        modal-header
                        bg-info-subtle
                        py-2
                    ">

                    <h2
                        class="modal-title fs-18"
                        id="<?= esc(
                                $titleId,
                                'attr'
                            ) ?>">

                        Block the Member

                    </h2>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                    </button>

                </div>

                <div class="modal-body">

                    <p class="text-muted fs-13">

                        This member will no longer
                        appear in your matches,
                        interests, views or searches.

                    </p>

                    <label
                        for="<?= esc(
                                    $commentId,
                                    'attr'
                                ) ?>"
                        class="form-label">

                        Comment

                        <span class="text-danger">
                            *
                        </span>

                    </label>

                    <textarea
                        id="<?= esc(
                                $commentId,
                                'attr'
                            ) ?>"
                        name="comment"
                        class="form-control<?= $commentError !== ''
                                                ? ' is-invalid'
                                                : '' ?>"
                        rows="4"
                        maxlength="250"
                        required
                        data-error-required="Please enter a comment."
                        data-error-maxlength="The comment cannot exceed 250 characters."><?= esc(
                                                                                                old(
                                                                                                    'comment'
                                                                                                )
                                                                                            ) ?></textarea>

                    <div class="invalid-feedback">

                        <?= esc(
                            $commentError !== ''
                                ? $commentError
                                : 'Please enter a comment.'
                        ) ?>

                    </div>

                    <div class="form-text color-pink">
                        Maximum 250 characters.
                    </div>

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
                        class="
                            btn
                            btn-danger
                            d-inline-flex
                            align-items-center
                            justify-content-center
                            gap-2
                        "
                        data-member-block-submit>

                        <span data-member-block-label>
                            Block Member
                        </span>

                        <span
                            class="
                                d-none
                                align-items-center
                            "
                            data-member-block-loading>

                            <span
                                class="
                                    spinner-border
                                    spinner-border-sm
                                    me-1
                                "
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