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

    public int $maximumPhotoSizeKilobytes = 5120;
}
