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
     * SAK Volunteer assigned to every prelaunch profile.
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

    /**
     * Hard minimum source dimensions accepted for prelaunch photos.
     *
     * Keep these aligned with MemberMedia so a photograph accepted during
     * prelaunch cannot later fail when migrated through the member-media
     * processing pipeline.
     */
    public int $minimumPhotoWidthPixels = 300;

    public int $minimumPhotoHeightPixels = 300;

    /**
     * Recommended upload dimensions displayed to the user.
     *
     * These values are guidance only and are not rejection thresholds.
     */
    public int $recommendedPhotoWidthPixels = 600;

    public int $recommendedPhotoHeightPixels = 600;

    /**
     * Require explicit SAK Volunteer verification on the
     * public prelaunch form.
     *
     * Production:
     * Member/user must enter and verify an active SAK Volunteer.
     *
     * QA/Development:
     * Continue using profileFieldOfficerId from configuration.
     */
    public bool $requiresFieldOfficerVerification = false;

    public function __construct()
    {
        parent::__construct();

        $deployment = trim(
            (string) env(
                'APP_DEPLOYMENT',
                'development'
            )
        );

        $this->requiresFieldOfficerVerification =
            in_array(
                $deployment,
                [
                    'development',
                    'production',
                ],
                true
            );
    }
}
