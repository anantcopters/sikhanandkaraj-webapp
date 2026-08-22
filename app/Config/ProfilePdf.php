<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

final class ProfilePdf extends BaseConfig
{
    /**
     * Absolute Chrome/Chromium executable.
     *
     * Environment specific. Never hard-code this
     * into the PDF service.
     */
    public string $chromePath = '';

    /**
     * Maximum time allowed for one PDF render.
     */
    public int $timeoutSeconds = 60;

    /**
     * Help number displayed in the PDF.
     */
    public string $supportPhone = '+91 98877 11226';

    public function __construct()
    {
        parent::__construct();

        $this->chromePath = trim(
            (string) env(
                'profilePdf.chromePath',
                ''
            )
        );

        $this->timeoutSeconds = max(
            10,
            (int) env(
                'profilePdf.timeoutSeconds',
                60
            )
        );

        $this->supportPhone = trim(
            (string) env(
                'profilePdf.supportPhone',
                ''
            )
        );
    }
}
