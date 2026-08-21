<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * Administrator Aadhaar and Video Introduction details.
 *
 * @var array<string, mixed> $aadhaarDetails
 * @var array<string, mixed> $videoIntroductionDetails
 * @var string               $fullName
 * @var string               $profileReference
 */

$resolvedAadhaar = isset($aadhaarDetails) && is_array($aadhaarDetails)
    ? $aadhaarDetails
    : [];
$aadhaarLatest = is_array($resolvedAadhaar['latest'] ?? null)
    ? $resolvedAadhaar['latest']
    : null;
$aadhaarHistory = is_array($resolvedAadhaar['history'] ?? null)
    ? $resolvedAadhaar['history']
    : [];

$resolvedVideo = isset($videoIntroductionDetails) && is_array($videoIntroductionDetails)
    ? $videoIntroductionDetails
    : [];
$currentVideo = is_array($resolvedVideo['current'] ?? null)
    ? $resolvedVideo['current']
    : null;
$videoHistory = is_array($resolvedVideo['history'] ?? null)
    ? $resolvedVideo['history']
    : [];
$playbackUrl = trim((string) ($resolvedVideo['playbackUrl'] ?? ''));
$resolvedFullName = trim((string) ($fullName ?? ''));
$resolvedFullName = $resolvedFullName !== '' ? $resolvedFullName : 'Member';
$resolvedProfileReference = trim((string) ($profileReference ?? ''));

$statusBadge = static function (string $status): string {
    return match (mb_strtoupper(trim($status))) {
        'APPROVED' => 'bg-success-subtle text-success',
        'UNDER_REVIEW', 'PENDING_REVIEW', 'PROCESSING' => 'bg-warning-subtle text-warning',
        'REJECTED', 'PROCESSING_FAILED' => 'bg-danger-subtle text-danger',
        default => 'bg-secondary-subtle text-secondary',
    };
};

$displayDateTime = static function (string $value): string {
    $value = trim($value);

    return $value !== ''
        ? DateDisplay::formatUtcDateTime($value)
        : '—';
};

$displayFileSize = static function (int $bytes): string {
    if ($bytes <= 0) {
        return '—';
    }

    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }

    return number_format($bytes / 1024, 2) . ' KB';
};
?>

<div class="row g-4 mb-4">
    <div class="col-12 col-xl-6">
        <section class="card border border-danger border-opacity-25 h-100 mb-0">
            <div class="card-header">
                <h2 class="card-title fs-16 mb-0">
                    <i class="ri-fingerprint-line me-1" aria-hidden="true"></i>
                    Aadhaar Details
                </h2>
            </div>

            <div class="card-body">
                <?php if ($aadhaarLatest === null): ?>
                    <p class="text-muted mb-0">Aadhaar has not been submitted.</p>
                <?php else: ?>
                    <?php $aadhaarStatus = (string) ($aadhaarLatest['status'] ?? 'NOT_ADDED'); ?>
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                        <span class="text-muted">Current status</span>
                        <span class="badge <?= esc($statusBadge($aadhaarStatus), 'attr') ?>">
                            <?= esc(str_replace('_', ' ', $aadhaarStatus)) ?>
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Aadhaar Name</div>
                            <div class="fw-medium"><?= esc((string) ($aadhaarLatest['aadhaarName'] ?: '—')) ?></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Aadhaar Date of Birth</div>
                            <div class="fw-medium"><?= esc((string) ($aadhaarLatest['aadhaarDateOfBirth'] ?: '—')) ?></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Upload Reference</div>
                            <div class="fw-medium text-break"><?= esc((string) ($aadhaarLatest['uploadReference'] ?: '—')) ?></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Uploaded</div>
                            <div class="fw-medium"><?= esc($displayDateTime((string) ($aadhaarLatest['uploadedAt'] ?? ''))) ?></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Document Type</div>
                            <div class="fw-medium"><?= esc((string) ($aadhaarLatest['mimeType'] ?: '—')) ?></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Document Size</div>
                            <div class="fw-medium"><?= esc($displayFileSize((int) ($aadhaarLatest['fileSizeBytes'] ?? 0))) ?></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Reviewed</div>
                            <div class="fw-medium"><?= esc($displayDateTime((string) ($aadhaarLatest['reviewedAt'] ?? ''))) ?></div>
                        </div>
                    </div>

                    <?php if (trim((string) ($aadhaarLatest['rejectionReason'] ?? '')) !== ''): ?>
                        <div class="alert alert-danger mt-3 mb-0">
                            <?= esc((string) $aadhaarLatest['rejectionReason']) ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if ($aadhaarHistory !== []): ?>
                <div class="card-footer bg-transparent">
                    <div class="text-muted fs-12 mb-2">Submission History</div>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($aadhaarHistory as $submission): ?>
                            <?php if (!is_array($submission)) {
                                continue;
                            } ?>
                            <div class="d-flex align-items-center justify-content-between gap-3 border-bottom pb-2">
                                <div>
                                    <div class="fw-medium"><?= esc((string) ($submission['uploadReference'] ?: 'Aadhaar submission')) ?></div>
                                    <div class="text-muted fs-12"><?= esc($displayDateTime((string) ($submission['uploadedAt'] ?? ''))) ?></div>
                                </div>
                                <span class="badge <?= esc($statusBadge((string) ($submission['status'] ?? '')), 'attr') ?>">
                                    <?= esc(str_replace('_', ' ', (string) ($submission['status'] ?? 'UNKNOWN'))) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-12 col-xl-6">
        <section class="card border border-danger border-opacity-25 h-100 mb-0">
            <div class="card-header">
                <h2 class="card-title fs-16 mb-0">
                    <i class="ri-video-line me-1" aria-hidden="true"></i>
                    Live Introduction
                </h2>
            </div>

            <div class="card-body">
                <?php if ($currentVideo === null): ?>
                    <p class="text-muted mb-0">Live Introduction has not been submitted.</p>
                <?php else: ?>
                    <?php $videoStatus = (string) ($currentVideo['status'] ?? 'NOT_SUBMITTED'); ?>
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                        <span class="text-muted">Current status</span>
                        <span class="badge <?= esc($statusBadge($videoStatus), 'attr') ?>">
                            <?= esc(str_replace('_', ' ', $videoStatus)) ?>
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Visibility</div>
                            <div class="fw-medium"><?= esc(str_replace('_', ' ', (string) ($currentVideo['visibility'] ?: '—'))) ?></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Duration</div>
                            <div class="fw-medium"><?= esc(is_numeric($currentVideo['durationSeconds'] ?? null) ? number_format((float) $currentVideo['durationSeconds'], 1) . ' seconds' : '—') ?></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Submitted</div>
                            <div class="fw-medium"><?= esc($displayDateTime((string) ($currentVideo['submittedAt'] ?? ''))) ?></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Resolution</div>
                            <div class="fw-medium"><?= esc(($currentVideo['width'] ?? 0) > 0 && ($currentVideo['height'] ?? 0) > 0 ? $currentVideo['width'] . ' × ' . $currentVideo['height'] : '—') ?></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Source Type</div>
                            <div class="fw-medium"><?= esc((string) ($currentVideo['sourceMimeType'] ?: '—')) ?></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Source Size</div>
                            <div class="fw-medium"><?= esc($displayFileSize((int) ($currentVideo['sourceSizeBytes'] ?? 0))) ?></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Video Codec</div>
                            <div class="fw-medium"><?= esc((string) ($currentVideo['videoCodec'] ?: '—')) ?></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Audio Codec</div>
                            <div class="fw-medium"><?= esc((string) ($currentVideo['audioCodec'] ?: '—')) ?></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Processed</div>
                            <div class="fw-medium"><?= esc($displayDateTime((string) ($currentVideo['processedAt'] ?? ''))) ?></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Moderated</div>
                            <div class="fw-medium"><?= esc($displayDateTime((string) ($currentVideo['moderatedAt'] ?? ''))) ?></div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-muted fs-12 mb-1">Approved</div>
                            <div class="fw-medium"><?= esc($displayDateTime((string) ($currentVideo['approvedAt'] ?? ''))) ?></div>
                        </div>
                    </div>

                    <?php if ($playbackUrl !== ''): ?>
                        <button
                            type="button"
                            class="btn btn-primary mt-3"
                            data-admin-video-open
                            data-video-url="<?= esc($playbackUrl, 'attr') ?>"
                            data-member-name="<?= esc($resolvedFullName, 'attr') ?>"
                            data-profile-reference="<?= esc($resolvedProfileReference, 'attr') ?>">
                            <i class="ri-play-circle-line me-1" aria-hidden="true"></i>
                            Play Live Introduction
                        </button>
                    <?php endif; ?>

                    <?php if (trim((string) ($currentVideo['rejectionReason'] ?? '')) !== ''): ?>
                        <div class="alert alert-danger mt-3 mb-0">
                            <?= esc((string) $currentVideo['rejectionReason']) ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if ($videoHistory !== []): ?>
                <div class="card-footer bg-transparent">
                    <div class="text-muted fs-12 mb-2">Version History</div>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($videoHistory as $video): ?>
                            <?php if (!is_array($video)) {
                                continue;
                            } ?>
                            <div class="d-flex align-items-center justify-content-between gap-3 border-bottom pb-2">
                                <div>
                                    <div class="fw-medium">Version <?= esc((string) ($video['versionNumber'] ?? 0)) ?></div>
                                    <div class="text-muted fs-12"><?= esc($displayDateTime((string) ($video['submittedAt'] ?? ''))) ?></div>
                                </div>
                                <span class="badge <?= esc($statusBadge((string) ($video['status'] ?? '')), 'attr') ?>">
                                    <?= esc(str_replace('_', ' ', (string) ($video['status'] ?? 'UNKNOWN'))) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>