<?php

declare(strict_types=1);

/**
 * @var array<string, string>|null $formAlert
 */

if (!is_array($formAlert ?? null)) {
    return;
}

$alertType = trim(
    (string) ($formAlert['type'] ?? 'info')
);

$alertTitle = trim(
    (string) ($formAlert['title'] ?? '')
);

$alertMessage = trim(
    (string) ($formAlert['message'] ?? '')
);
?>

<div
    class="alert alert-<?= esc($alertType, 'attr') ?>
        alert-dismissible fade show mb-3"
    role="alert">
    <?php if ($alertTitle !== ''): ?>
        <strong>
            <?= esc($alertTitle) ?>
        </strong>
    <?php endif; ?>

    <?= esc($alertMessage) ?>

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
        aria-label="Close"></button>
</div>