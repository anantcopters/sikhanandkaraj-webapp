<?php

declare(strict_types=1);

/**
 * @var array<string, mixed>  $user
 * @var array<string, mixed>  $basicDetails
 * @var array<string, string> $validationErrors
 */

$details = is_array($basicDetails ?? null)
    ? $basicDetails
    : [];

$errors = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

$shouldOpenEditor = $errors !== []
    || session('openProfileSection')
    === 'basic-details';

$fieldValue = static function (
    string $field,
    mixed $storedValue = ''
): string {
    $oldValue = old($field);

    return $oldValue !== null
        ? (string) $oldValue
        : (string) $storedValue;
};

$isSelected = static function (
    string $field,
    string $option,
    mixed $storedValue = ''
) use ($fieldValue): string {
    return strtoupper(
        trim($fieldValue($field, $storedValue))
    ) === strtoupper($option)
        ? 'selected'
        : '';
};

$maximumDateOfBirth = date(
    'Y-m-d',
    strtotime('-18 years')
);
