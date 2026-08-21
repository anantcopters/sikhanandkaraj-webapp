<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

final class VideoIntroduction extends BaseConfig
{
    public int $minimumDurationSeconds;
    public int $maximumDurationSeconds;
    public int $maximumUploadSizeKb;
    public int $lockDays;
    public int $playbackUrlTtlSeconds;
    public int $maximumProcessingAttempts;
    public string $ffmpegBinary;
    public string $ffprobeBinary;
    public string $consentVersion;

    /** @var array<string, string> */
    public array $allowedMimeTypes = [
        'video/webm' => 'webm',
        'video/mp4' => 'mp4',
        'video/quicktime' => 'mov',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->minimumDurationSeconds = 15;
        $this->maximumDurationSeconds = 30;

        $this->maximumUploadSizeKb = max(
            1024,
            (int) env(
                'videoIntroduction.maximumUploadSizeKb',
                40960
            )
        );

        $this->lockDays = max(
            1,
            (int) env(
                'videoIntroduction.lockDays',
                7
            )
        );

        $this->playbackUrlTtlSeconds = max(
            60,
            (int) env(
                'videoIntroduction.playbackUrlTtlSeconds',
                300
            )
        );

        $this->maximumProcessingAttempts = max(
            1,
            (int) env(
                'videoIntroduction.maximumProcessingAttempts',
                3
            )
        );

        $this->ffmpegBinary = trim(
            (string) env(
                'videoIntroduction.ffmpegBinary',
                '/usr/bin/ffmpeg'
            )
        );

        $this->ffprobeBinary = trim(
            (string) env(
                'videoIntroduction.ffprobeBinary',
                '/usr/bin/ffprobe'
            )
        );

        $this->consentVersion = trim(
            (string) env(
                'videoIntroduction.consentVersion',
                '2026-08-19'
            )
        );
    }
}
