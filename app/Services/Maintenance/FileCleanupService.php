<?php

declare(strict_types=1);

namespace App\Services\Maintenance;

use App\Models\Prelaunch\PrelaunchPhotoModel;
use App\Models\Prelaunch\PrelaunchProfileModel;
use App\Services\Prelaunch\PrelaunchPhotoService;
use CodeIgniter\Database\BaseConnection;
use Config\FileCleanup;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Executes explicitly registered filesystem cleanup jobs.
 *
 * The service does not accept arbitrary filesystem paths. It resolves every
 * cleanup root from trusted application configuration.
 */
final class FileCleanupService
{
    public function __construct(
        private readonly PrelaunchProfileModel $profileModel,
        private readonly PrelaunchPhotoModel $photoModel,
        private readonly PrelaunchPhotoService $photoService,
        private readonly BaseConnection $database,
        private readonly FileCleanup $configuration
    ) {}

    /**
     * Return registered filesystem cleanup job names.
     *
     * @return list<string>
     */
    public function getRegisteredJobs(): array
    {
        return array_keys(
            $this->configuration->jobs
        );
    }

    /**
     * Execute all registered filesystem cleanup jobs.
     *
     * @return list<FileCleanupResult>
     */
    public function runAll(): array
    {
        $results = [];

        foreach ($this->getRegisteredJobs() as $jobName) {
            $results[] = $this->run(
                $jobName
            );
        }

        return $results;
    }

    /**
     * Execute one registered filesystem cleanup job.
     */
    public function run(
        string $jobName
    ): FileCleanupResult {
        try {
            $job = $this->resolveJob(
                $jobName
            );

            return match ($jobName) {
                'prelaunch-approved-photos' =>
                $this->cleanupPrelaunchPhotos(
                    $jobName,
                    $job
                ),

                default =>
                throw new InvalidArgumentException(
                    sprintf(
                        'Unsupported file cleanup job "%s".',
                        $jobName
                    )
                ),
            };
        } catch (Throwable $exception) {
            log_message(
                'error',
                'File cleanup failed. '
                    . 'Job: {job}; error: {error}.',
                [
                    'job' =>
                    $jobName,
                    'error' =>
                    $exception->getMessage(),
                ]
            );

            return FileCleanupResult::failure(
                $jobName,
                $exception->getMessage()
            );
        }
    }

    /**
     * @param array{
     *     rootDirectory:string,
     *     retentionDays:int,
     *     batchSize:int,
     *     removeEmptyDirectories:bool
     * } $job
     */
    private function cleanupPrelaunchPhotos(
        string $jobName,
        array $job
    ): FileCleanupResult {
        $profiles = $this
            ->findProfilesDueForCleanup(
                $job['batchSize']
            );

        $processedProfiles = 0;
        $deletedFiles = 0;
        $deletedDirectories = 0;
        $failedProfiles = 0;

        foreach ($profiles as $profile) {
            $profileId = (int) (
                $profile['id']
                ?? 0
            );

            if ($profileId <= 0) {
                continue;
            }

            try {
                $result = $this->cleanupOneProfile(
                    $profile,
                    $job
                );

                $deletedFiles +=
                    $result['deletedFiles'];

                $deletedDirectories +=
                    $result['deletedDirectories'];

                $processedProfiles++;
            } catch (Throwable $exception) {
                $failedProfiles++;

                log_message(
                    'error',
                    'Prelaunch photo cleanup failed. '
                        . 'Profile: {profileId}; '
                        . 'reference: {reference}; '
                        . 'error: {error}.',
                    [
                        'profileId' =>
                        $profileId,
                        'reference' =>
                        (string) (
                            $profile['profile_reference']
                            ?? ''
                        ),
                        'error' =>
                        $exception->getMessage(),
                    ]
                );
            }
        }

        log_message(
            'info',
            'File cleanup completed. '
                . 'Job: {job}; '
                . 'profiles: {profiles}; '
                . 'files: {files}; '
                . 'directories: {directories}; '
                . 'failures: {failures}.',
            [
                'job' =>
                $jobName,
                'profiles' =>
                $processedProfiles,
                'files' =>
                $deletedFiles,
                'directories' =>
                $deletedDirectories,
                'failures' =>
                $failedProfiles,
            ]
        );

        return FileCleanupResult::success(
            jobName: $jobName,
            processedProfiles: $processedProfiles,
            deletedFiles: $deletedFiles,
            deletedDirectories: $deletedDirectories,
            failedProfiles: $failedProfiles
        );
    }

    /**
     * Find only successfully migrated profiles whose retention period ended.
     *
     * @return list<array<string, mixed>>
     */
    private function findProfilesDueForCleanup(
        int $batchSize
    ): array {
        return $this->database
            ->table(
                'prelaunch_profiles'
            )
            ->select([
                'id',
                'profile_reference',
                'status',
                'migrated_user_id',
                'migrated_at',
                'local_photos_cleanup_after',
                'local_photos_cleaned_at',
            ])
            ->where(
                'status',
                PrelaunchProfileModel::STATUS_APPROVED
            )
            ->where(
                'migrated_user_id IS NOT NULL',
                null,
                false
            )
            ->where(
                'migrated_at IS NOT NULL',
                null,
                false
            )
            ->where(
                'local_photos_cleanup_after <=',
                date('Y-m-d H:i:s')
            )
            ->where(
                'local_photos_cleaned_at',
                null
            )
            ->where(
                'deleted_at',
                null
            )
            ->orderBy(
                'local_photos_cleanup_after',
                'ASC'
            )
            ->limit($batchSize)
            ->get()
            ->getResultArray();
    }

    /**
     * @param array<string, mixed> $profile
     * @param array{
     *     rootDirectory:string,
     *     retentionDays:int,
     *     batchSize:int,
     *     removeEmptyDirectories:bool
     * } $job
     *
     * @return array{
     *     deletedFiles:int,
     *     deletedDirectories:int
     * }
     */
    private function cleanupOneProfile(
        array $profile,
        array $job
    ): array {
        $profileId = (int) (
            $profile['id']
            ?? 0
        );

        $profileReference = trim(
            (string) (
                $profile['profile_reference']
                ?? ''
            )
        );

        if (
            $profileId <= 0
            || $profileReference === ''
        ) {
            throw new RuntimeException(
                'The prelaunch profile cleanup data is incomplete.'
            );
        }

        $this->assertSafeReference(
            $profileReference
        );

        $profileDirectory = rtrim(
            $job['rootDirectory'],
            DIRECTORY_SEPARATOR
        )
            . DIRECTORY_SEPARATOR
            . $profileReference;

        $this->assertInsideWritePath(
            $profileDirectory
        );

        $photos = $this->photoModel
            ->findByProfile(
                $profileId
            );

        $deletedFiles = 0;
        $deletedDirectories = 0;

        foreach ($photos as $photo) {
            foreach (
                [
                    'original_path',
                    'medium_path',
                    'thumbnail_path',
                ] as $pathField
            ) {
                $relativePath = trim(
                    (string) (
                        $photo[$pathField]
                        ?? ''
                    )
                );

                if ($relativePath === '') {
                    continue;
                }

                $absolutePath = $this
                    ->photoService
                    ->absolutePath(
                        $relativePath
                    );

                $this->assertInsideWritePath(
                    $absolutePath
                );

                if (is_link($absolutePath)) {
                    throw new RuntimeException(
                        'A staged photograph path is a symbolic link.'
                    );
                }

                if (!is_file($absolutePath)) {
                    /*
                     * Missing files do not block cleanup. The filesystem may
                     * already have been partially cleaned during an earlier
                     * interrupted run.
                     */
                    continue;
                }

                if (!@unlink($absolutePath)) {
                    throw new RuntimeException(
                        'A staged photograph could not be deleted.'
                    );
                }

                $deletedFiles++;
            }
        }

        if (
            $job['removeEmptyDirectories']
            && is_dir($profileDirectory)
        ) {
            $deletedDirectories +=
                $this->removeEmptyTree(
                    $profileDirectory
                );
        }

        /*
         * Mark cleanup complete only after every existing file was removed.
         */
        if (
            !$this->profileModel->update(
                $profileId,
                [
                    'local_photos_cleaned_at' =>
                    date('Y-m-d H:i:s'),
                ]
            )
        ) {
            throw new RuntimeException(
                'The prelaunch cleanup status could not be saved.'
            );
        }

        return [
            'deletedFiles' =>
            $deletedFiles,
            'deletedDirectories' =>
            $deletedDirectories,
        ];
    }

    /**
     * Remove empty directories from deepest child to the supplied root.
     */
    private function removeEmptyTree(
        string $directory
    ): int {
        $this->assertInsideWritePath(
            $directory
        );

        if (
            !is_dir($directory)
            || is_link($directory)
        ) {
            return 0;
        }

        $items = scandir(
            $directory
        );

        if (!is_array($items)) {
            throw new RuntimeException(
                'The cleanup directory could not be read.'
            );
        }

        $deletedDirectories = 0;

        foreach ($items as $item) {
            if (
                $item === '.'
                || $item === '..'
            ) {
                continue;
            }

            $child = $directory
                . DIRECTORY_SEPARATOR
                . $item;

            if (is_link($child)) {
                throw new RuntimeException(
                    'Symbolic links are not allowed '
                        . 'inside the cleanup directory.'
                );
            }

            if (is_dir($child)) {
                $deletedDirectories +=
                    $this->removeEmptyTree(
                        $child
                    );
            }
        }

        $remainingItems = scandir(
            $directory
        );

        if (!is_array($remainingItems)) {
            return $deletedDirectories;
        }

        $remainingItems = array_values(
            array_diff(
                $remainingItems,
                ['.', '..']
            )
        );

        if (
            $remainingItems === []
            && @rmdir($directory)
        ) {
            $deletedDirectories++;
        }

        return $deletedDirectories;
    }

    /**
     * Resolve and validate one configured file-cleanup job.
     *
     * @return array{
     *     rootDirectory:string,
     *     retentionDays:int,
     *     batchSize:int,
     *     removeEmptyDirectories:bool
     * }
     */
    private function resolveJob(
        string $jobName
    ): array {
        $jobName = trim(
            $jobName
        );

        if (
            $jobName === ''
            || !isset(
                $this->configuration
                    ->jobs[$jobName]
            )
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown file cleanup job "%s".',
                    $jobName
                )
            );
        }

        $job = $this->configuration
            ->jobs[$jobName];

        if (
            trim($job['rootDirectory'])
            === ''
        ) {
            throw new InvalidArgumentException(
                'A file cleanup root directory is required.'
            );
        }

        if ($job['retentionDays'] < 1) {
            throw new InvalidArgumentException(
                'File cleanup retention must be at least one day.'
            );
        }

        if ($job['batchSize'] < 1) {
            throw new InvalidArgumentException(
                'File cleanup batch size must be positive.'
            );
        }

        $this->assertInsideWritePath(
            $job['rootDirectory']
        );

        return $job;
    }

    private function assertSafeReference(
        string $profileReference
    ): void {
        if (
            preg_match(
                '/^[A-Z0-9_-]+$/',
                $profileReference
            ) !== 1
        ) {
            throw new RuntimeException(
                'The profile storage reference is invalid.'
            );
        }
    }

    private function assertInsideWritePath(
        string $path
    ): void {
        $normalizedWritePath = rtrim(
            str_replace(
                ['/', '\\'],
                DIRECTORY_SEPARATOR,
                WRITEPATH
            ),
            DIRECTORY_SEPARATOR
        )
            . DIRECTORY_SEPARATOR;

        $normalizedPath = str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            $path
        );

        if (
            !str_starts_with(
                $normalizedPath,
                $normalizedWritePath
            )
        ) {
            throw new RuntimeException(
                'Cleanup attempted outside the writable directory.'
            );
        }
    }
}
