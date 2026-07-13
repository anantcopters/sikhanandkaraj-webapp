<?php

declare(strict_types=1);

/**
 * Reusable field-validation message.
 *
 * The component always renders the error container, even when there
 * is no server-side error. This allows the generic JavaScript validator
 * to reuse the same element instead of creating duplicate error blocks.
 *
 * @var string $field
 * @var string|null $errorId
 * @var array<string, string> $errors
 */

$message = $errors[$field] ?? '';

$hasError = is_string($message)
    && $message !== '';

$resolvedErrorId = isset($errorId)
    && is_string($errorId)
    && $errorId !== ''
        ? $errorId
        : $field . 'Error';
?>

<div
    id="<?= esc($resolvedErrorId, 'attr') ?>"
    class="invalid-feedback <?= $hasError ? 'd-block' : '' ?>"
    data-validation-error="<?= esc($field, 'attr') ?>">
    <?= $hasError ? esc($message) : '' ?>
</div>