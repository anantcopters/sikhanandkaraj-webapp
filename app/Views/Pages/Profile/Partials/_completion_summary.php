<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $overallProfileSummary
 */

$summary = is_array($overallProfileSummary ?? null)
    ? $overallProfileSummary
    : [];

$percentage = max(
    0,
    min(
        100,
        (int) ($summary['percentage'] ?? 0)
    )
);

$completedSections = max(
    0,
    (int) ($summary['completedSteps'] ?? 0)
);

$totalSections = max(
    0,
    (int) ($summary['totalSteps'] ?? 0)
);

$pendingSections = max(
    0,
    (int) ($summary['pendingSections'] ?? 0)
);

$hasProfilePhoto = (bool) (
    $summary['hasProfilePhoto'] ?? false
);

$visibilityLabel = trim(
    (string) ($summary['visibilityLabel'] ?? 'Low')
);

$visibilityClass = trim(
    (string) ($summary['visibilityClass'] ?? 'danger')
);
