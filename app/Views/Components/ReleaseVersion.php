<?php

declare(strict_types=1);

use App\Support\ReleaseVersion;

/**
 * Shared deployed-version display.
 *
 * The version comes from writable/release-version, which is generated
 * by the production deployment script from the deployed Git tag.
 */

$releaseVersion =
    ReleaseVersion::current();
?>

<?php if ($releaseVersion !== ''): ?>

    <span
        class="badge rounded-pill bg-light text-muted border"
        aria-label="<?= esc(
                        'Application version '
                            . $releaseVersion,
                        'attr'
                    ) ?>">

        Version <?= esc(
                    $releaseVersion
                ) ?>

    </span>

<?php endif; ?>