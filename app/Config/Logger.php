<?php

declare(strict_types=1);

namespace Config;

use App\Logging\DatabaseErrorHandler;
use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Log\Handlers\FileHandler;
use CodeIgniter\Log\Handlers\HandlerInterface;

/**
 * CodeIgniter application logging configuration.
 *
 * PostgreSQL stores operational errors while FileHandler remains the fallback
 * when the database or the database error table is unavailable.
 */
class Logger extends BaseConfig
{
    /**
     * Production logs runtime errors and more severe events.
     * Non-production environments retain all levels in file logs.
     *
     * @var int|list<int>
     */
    public $threshold =
    ENVIRONMENT === 'production'
        ? 4
        : 9;

    public string $dateFormat =
    'Y-m-d H:i:s';

    /**
     * @var array<
     *     class-string<HandlerInterface>,
     *     array<string, int|list<string>|string>
     * >
     */
    public array $handlers = [
        /*
         * Store only warning-and-higher events in PostgreSQL.
         *
         * The handler always returns true so FileHandler continues to execute.
         */
        DatabaseErrorHandler::class => [
            'handles' => [
                'emergency',
                'alert',
                'critical',
                'error',
                'warning',
            ],
        ],

        /*
         * File logging remains the mandatory emergency fallback.
         */
        FileHandler::class => [
            'handles' => [
                'critical',
                'alert',
                'emergency',
                'debug',
                'error',
                'info',
                'notice',
                'warning',
            ],

            'fileExtension' =>
            '',

            'filePermissions' =>
            0644,

            'path' =>
            '',
        ],
    ];
}
