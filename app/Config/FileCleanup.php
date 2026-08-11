<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Defines application-controlled filesystem cleanup jobs.
 *
 * Security:
 *
 * - Cleanup roots are configured by the application.
 * - A CLI argument must never supply an arbitrary filesystem path.
 * - Every target must remain inside WRITEPATH.
 * - Symlinks are never followed.
 */
final class FileCleanup extends BaseConfig
{
    /**
     * Registered filesystem cleanup jobs.
     *
     * @var array<string, array{
     *     rootDirectory:string,
     *     retentionDays:int,
     *     batchSize:int,
     *     removeEmptyDirectories:bool
     * }>
     */
    public array $jobs = [
        'prelaunch-approved-photos' => [
            /*
             * This must match the root used by PrelaunchPhotoService.
             */
            'rootDirectory' =>
            WRITEPATH
                . 'uploads'
                . DIRECTORY_SEPARATOR
                . 'prelaunch',

            /*
             * Profile folders become eligible only after the database
             * records mark them for cleanup.
             *
             * This value remains a defensive minimum file-age check.
             */
            'retentionDays' => 7,

            /*
             * Limit work performed in one scheduled execution.
             */
            'batchSize' => 100,

            /*
             * Remove original/medium/thumbnail folders and finally the
             * profile-reference directory when they become empty.
             */
            'removeEmptyDirectories' => true,
        ],
    ];
}
