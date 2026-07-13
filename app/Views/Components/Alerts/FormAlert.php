<?php

declare(strict_types=1);

/**
 * @var array{
 *     type?: string,
 *     title?: string,
 *     message?: string
 * }|null $alert
 */

if (!isset($alert) || !is_array($alert)) {
    return;
}

$allowedTypes = [
    'success',
    'danger',
    'warning',
    'info',
];

$type = in_array(
    $alert['type'] ?? '',
    $allowedTypes,
    true
) ? $alert['type'] : 'info';

$title = trim((string) ($alert['title'] ?? ''));
$message = trim((string) ($alert['message'] ?? ''));

$icons = [
    'success' => 'mdi mdi-check-circle-outline',
    'danger' => 'mdi mdi-alert-circle-outline',
    'warning' => 'mdi mdi-alert-outline',
    'info' => 'mdi mdi-information-outline',
];

if ($message === '') {
    return;
}
?>

<div
    class="alert alert-<?= esc($type, 'attr') ?>
           alert-top-border alert-dismissible shadow fade show mb-3"
    role="<?= $type === 'danger' ? 'alert' : 'status' ?>"
    aria-live="<?= $type === 'danger' ? 'assertive' : 'polite' ?>">

    <span
        class="<?= esc($icons[$type], 'attr') ?>
               me-2 align-middle fs-18"
        aria-hidden="true"></span>

    <?php if ($title !== ''): ?>
        <strong><?= esc($title) ?></strong>
        <span aria-hidden="true"> — </span>
    <?php endif; ?>

    <?= esc($message) ?>

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
        aria-label="Close">
    </button>
</div>