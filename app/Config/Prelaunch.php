<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Controls the temporary pre-launch profile collection module.
 */
final class Prelaunch extends BaseConfig
{
    public bool $profileEntryEnabled = true;

    public int $maximumPhotos = 3;

    /**
     * Maximum allowed size of each prelaunch photograph.
     *
     * CI4's max_size validation rule expects the value in kilobytes.
     * 18 MB × 1024 = 18432 KB.
     */
    public int $maximumPhotoSizeKilobytes = 18432;
}
