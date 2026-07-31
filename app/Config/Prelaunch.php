<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Controls the temporary prelaunch profile collection module.
 */
final class Prelaunch extends BaseConfig
{
    /**
     * Allow prelaunch profile creation.
     */
    public bool $profileEntryEnabled = true;

    /**
     * Field Officer assigned to every prelaunch profile.
     *
     * This value must contain the primary-key ID of an ACTIVE,
     * non-deleted record from field_officers.
     *
     * Keep the default as zero so a missing environment configuration
     * fails closed rather than assigning an unintended officer.
     *
     * Environment override:
     *
     * prelaunch.profileFieldOfficerId = 12
     */
    public int $profileFieldOfficerId = 0;

    /**
     * Exact number of photographs required for each profile.
     */
    public int $maximumPhotos = 2;

    /**
     * Maximum uploaded photograph size.
     *
     * CI4 max_size expects kilobytes.
     *
     * 18 MB × 1024 = 18432 KB.
     */
    public int $maximumPhotoSizeKilobytes = 18432;

    /**
     * Maximum source-image width accepted during validation.
     */
    public int $maximumPhotoWidthPixels = 12000;

    /**
     * Maximum source-image height accepted during validation.
     */
    public int $maximumPhotoHeightPixels = 12000;

    /**
     * Maximum width of the locally stored optimized WebP.
     *
     * Aspect ratio is preserved and smaller images are not enlarged.
     */
    public int $optimizedOriginalWidth = 1920;

    /**
     * Maximum height of the locally stored optimized WebP.
     */
    public int $optimizedOriginalHeight = 1920;

    /**
     * Single WebP quality used for fast prelaunch processing.
     *
     * Avoid adaptive retry loops during the synchronous form request.
     */
    public int $optimizedWebpQuality = 78;
}
