<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Controls the temporary pre-launch profile collection module.
 */
final class Prelaunch extends BaseConfig
{
    /**
     * Allow prelaunch profile creation.
     */
    public bool $profileEntryEnabled = true;

    /**
     * Exact number of photographs required for every prelaunch profile.
     */
    public int $maximumPhotos = 2;

    /**
     * Maximum allowed size of each uploaded prelaunch photograph.
     *
     * CI4's max_size validation rule expects kilobytes.
     *
     * 18 MB × 1024 = 18432 KB.
     */
    public int $maximumPhotoSizeKilobytes = 18432;

    /**
     * Maximum source-image width accepted during upload validation.
     */
    public int $maximumPhotoWidthPixels = 12000;

    /**
     * Maximum source-image height accepted during upload validation.
     */
    public int $maximumPhotoHeightPixels = 12000;

    /**
     * Maximum size of the optimized WebP original stored locally.
     *
     * 5 MB × 1024 × 1024 = 5242880 bytes.
     */
    public int $maximumStoredPhotoSizeBytes = 5242880;

    /**
     * Maximum width of the locally stored optimized original.
     *
     * The image service preserves aspect ratio and does not enlarge
     * photographs smaller than this boundary.
     */
    public int $optimizedOriginalWidth = 4000;

    /**
     * Maximum height of the locally stored optimized original.
     */
    public int $optimizedOriginalHeight = 4000;

    /**
     * WebP qualities attempted before reducing the output dimensions.
     *
     * The first result that satisfies maximumStoredPhotoSizeBytes is kept.
     *
     * @var list<int>
     */
    public array $optimizedWebpQualities = [
        88,
        84,
        80,
        76,
    ];

    /**
     * Width used for the prelaunch medium display variant.
     */
    //public int $mediumPhotoWidth = 900;

    /**
     * Height used for the prelaunch medium display variant.
     */
    //public int $mediumPhotoHeight = 1200;

    /**
     * WebP quality used for medium variants.
     */
    //public int $mediumWebpQuality = 82;

    /**
     * Width used for the prelaunch thumbnail variant.
     */
    //public int $thumbnailPhotoWidth = 300;

    /**
     * Height used for the prelaunch thumbnail variant.
     */
    //public int $thumbnailPhotoHeight = 400;

    /**
     * WebP quality used for thumbnail variants.
     */
    //public int $thumbnailWebpQuality = 78;
}
