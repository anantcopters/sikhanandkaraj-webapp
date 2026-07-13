<?php

declare(strict_types=1);

/**
 * @var string $field
 * @var array<string, string> $errors
 */

$message = $errors[$field] ?? '';

if (!is_string($message) || $message === '') {
    return;
}
?>

<div
    id="<?= esc($field, 'attr') ?>Error"
    class="invalid-feedback d-block"
    data-validation-error="<?= esc($field, 'attr') ?>">
    <?= esc($message) ?>
</div>