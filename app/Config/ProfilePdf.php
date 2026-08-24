<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

final class ProfilePdf extends BaseConfig
{
    /**
     * Optional absolute Chrome / Chromium executable.
     *
     * Environment configuration takes precedence.
     * When empty, MemberProfilePdfService attempts
     * to resolve a supported local installation.
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

        /*
         * Keep the configured project default when
         * no environment override is supplied.
         */
        $configuredSupportPhone = trim(
            (string) env(
                'profilePdf.supportPhone',
                ''
            )
        );

        if ($configuredSupportPhone !== '') {
            $this->supportPhone =
                $configuredSupportPhone;
        }
    }
}
