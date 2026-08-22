<?php

declare(strict_types=1);

namespace App\Services\Profile;

use Config\ProfilePdf;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

final class MemberProfilePdfService
{
    public function __construct(
        private readonly
        MemberProfilePdfDataService
        $dataService,

        private readonly
        ProfilePdf
        $config
    ) {}

    /**
     * @param array<string,mixed> $profile
     *
     * @return array{
     *     content:string,
     *     filename:string
     * }
     */
    public function generate(
        int $profileOwnerUserId,
        array $profile
    ): array {
        $chromePath =
            $this->resolveChromePath();

        $data =
            $this->dataService
            ->prepare(
                $profileOwnerUserId,
                $profile
            );

        $html = view(
            'Pages/Profile/Pdf',
            $data
        );

        $workingDirectory =
            WRITEPATH
            . 'cache'
            . DIRECTORY_SEPARATOR
            . 'profile-pdf';

        if (
            !is_dir($workingDirectory)
            && !mkdir(
                $workingDirectory,
                0700,
                true
            )
            && !is_dir(
                $workingDirectory
            )
        ) {
            throw new RuntimeException(
                'Unable to create profile PDF working directory.'
            );
        }

        $token =
            bin2hex(
                random_bytes(16)
            );

        $htmlPath =
            $workingDirectory
            . DIRECTORY_SEPARATOR
            . $token
            . '.html';

        $pdfPath =
            $workingDirectory
            . DIRECTORY_SEPARATOR
            . $token
            . '.pdf';

        try {
            if (
                file_put_contents(
                    $htmlPath,
                    $html,
                    LOCK_EX
                ) === false
            ) {
                throw new RuntimeException(
                    'Unable to create profile PDF HTML.'
                );
            }

            @chmod(
                $htmlPath,
                0600
            );

            $process =
                new Process(
                    [
                        $chromePath,

                        '--headless=new',

                        '--disable-gpu',

                        '--hide-scrollbars',

                        '--no-pdf-header-footer',

                        '--print-to-pdf='
                            . $pdfPath,

                        $this->fileUri(
                            $htmlPath
                        ),
                    ]
                );

            $process->setTimeout(
                $this->config
                    ->timeoutSeconds
            );

            $process->run();

            if (
                !$process
                    ->isSuccessful()
            ) {
                throw new RuntimeException(
                    'Chrome PDF render failed: '
                        . trim(
                            $process
                                ->getErrorOutput()
                        )
                );
            }

            if (
                !is_file($pdfPath)
                || filesize($pdfPath)
                <= 0
            ) {
                throw new RuntimeException(
                    'Chrome did not create a valid profile PDF.'
                );
            }

            $content =
                file_get_contents(
                    $pdfPath
                );

            if (
                $content === false
                || $content === ''
            ) {
                throw new RuntimeException(
                    'Generated profile PDF could not be read.'
                );
            }

            $reference = trim(
                (string) (
                    $data['profileReference']
                    ?? 'member'
                )
            );

            $safeReference =
                preg_replace(
                    '/[^A-Za-z0-9_-]+/',
                    '-',
                    $reference
                )
                ?? 'member';

            return [
                'content' =>
                $content,

                'filename' =>
                'sikhanandkaraj-profile-'
                    . strtolower(
                        $safeReference
                    )
                    . '.pdf',
            ];
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Profile PDF render failed: {message}',
                [
                    'message' =>
                    $exception
                        ->getMessage(),
                ]
            );

            throw $exception;
        } finally {
            if (
                is_file($htmlPath)
            ) {
                @unlink(
                    $htmlPath
                );
            }

            if (
                is_file($pdfPath)
            ) {
                @unlink(
                    $pdfPath
                );
            }
        }
    }

    /**
     * Resolve Chrome / Chromium for the current environment.
     *
     * Resolution order:
     *
     * 1. Explicit profilePdf.chromePath.
     * 2. Executable available through PATH.
     * 3. Common Windows installations.
     * 4. Common Linux installations.
     */
    private function resolveChromePath(): string
    {
        $configuredPath = trim(
            $this->config
                ->chromePath
        );

        if (
            $configuredPath !== ''
            && is_file(
                $configuredPath
            )
        ) {
            return $configuredPath;
        }

        /*
         * First allow the operating system PATH to
         * resolve Chrome without hard-coding an
         * environment-specific installation.
         */
        $finder =
            new ExecutableFinder();

        $executableNames =
            PHP_OS_FAMILY === 'Windows'
            ? [
                'chrome.exe',
                'chromium.exe',
                'msedge.exe',
            ]
            : [
                'google-chrome',
                'google-chrome-stable',
                'chromium',
                'chromium-browser',
            ];

        foreach (
            $executableNames
            as $executableName
        ) {
            $resolved =
                $finder->find(
                    $executableName
                );

            if (
                is_string($resolved)
                && $resolved !== ''
                && is_file($resolved)
            ) {
                return $resolved;
            }
        }

        /*
         * Chrome installed through the normal Windows
         * installer is usually not added to PATH.
         *
         * Build these locations from Windows environment
         * variables rather than assuming C:\Program Files.
         */
        if (
            PHP_OS_FAMILY
            === 'Windows'
        ) {
            $windowsCandidates = [];

            $programFiles =
                getenv(
                    'PROGRAMFILES'
                );

            $programFilesX86 =
                getenv(
                    'PROGRAMFILES(X86)'
                );

            $localAppData =
                getenv(
                    'LOCALAPPDATA'
                );

            if (
                is_string($programFiles)
                && $programFiles !== ''
            ) {
                $windowsCandidates[] =
                    $programFiles
                    . DIRECTORY_SEPARATOR
                    . 'Google'
                    . DIRECTORY_SEPARATOR
                    . 'Chrome'
                    . DIRECTORY_SEPARATOR
                    . 'Application'
                    . DIRECTORY_SEPARATOR
                    . 'chrome.exe';

                /*
                 * Edge uses the same Chromium headless
                 * print-to-PDF functionality and is
                 * available on modern Windows machines.
                 */
                $windowsCandidates[] =
                    $programFiles
                    . DIRECTORY_SEPARATOR
                    . 'Microsoft'
                    . DIRECTORY_SEPARATOR
                    . 'Edge'
                    . DIRECTORY_SEPARATOR
                    . 'Application'
                    . DIRECTORY_SEPARATOR
                    . 'msedge.exe';
            }

            if (
                is_string($programFilesX86)
                && $programFilesX86 !== ''
            ) {
                $windowsCandidates[] =
                    $programFilesX86
                    . DIRECTORY_SEPARATOR
                    . 'Google'
                    . DIRECTORY_SEPARATOR
                    . 'Chrome'
                    . DIRECTORY_SEPARATOR
                    . 'Application'
                    . DIRECTORY_SEPARATOR
                    . 'chrome.exe';

                $windowsCandidates[] =
                    $programFilesX86
                    . DIRECTORY_SEPARATOR
                    . 'Microsoft'
                    . DIRECTORY_SEPARATOR
                    . 'Edge'
                    . DIRECTORY_SEPARATOR
                    . 'Application'
                    . DIRECTORY_SEPARATOR
                    . 'msedge.exe';
            }

            if (
                is_string($localAppData)
                && $localAppData !== ''
            ) {
                $windowsCandidates[] =
                    $localAppData
                    . DIRECTORY_SEPARATOR
                    . 'Google'
                    . DIRECTORY_SEPARATOR
                    . 'Chrome'
                    . DIRECTORY_SEPARATOR
                    . 'Application'
                    . DIRECTORY_SEPARATOR
                    . 'chrome.exe';
            }

            foreach (
                $windowsCandidates
                as $candidate
            ) {
                if (
                    is_file($candidate)
                ) {
                    return $candidate;
                }
            }
        }

        /*
         * Common server installations.
         *
         * PATH remains preferred. These are fallback
         * locations for installations that do not expose
         * the executable to Apache/PHP's PATH.
         */
        if (
            PHP_OS_FAMILY
            !== 'Windows'
        ) {
            $linuxCandidates = [
                '/usr/bin/google-chrome',
                '/usr/bin/google-chrome-stable',
                '/usr/bin/chromium',
                '/usr/bin/chromium-browser',
                '/snap/bin/chromium',
            ];

            foreach (
                $linuxCandidates
                as $candidate
            ) {
                if (
                    is_file($candidate)
                    && is_executable(
                        $candidate
                    )
                ) {
                    return $candidate;
                }
            }
        }

        $message =
            'Chrome or Chromium executable could not be resolved.';

        if ($configuredPath !== '') {
            $message .=
                ' Configured profilePdf.chromePath was: '
                . $configuredPath
                . '.';
        }

        throw new RuntimeException(
            $message
        );
    }

    private function fileUri(
        string $path
    ): string {
        $realPath =
            realpath($path);

        if ($realPath === false) {
            throw new RuntimeException(
                'Profile PDF HTML path is invalid.'
            );
        }

        $normalized =
            str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                $realPath
            );

        /*
     * Chrome file URI.
     *
     * Encode only characters that are unsafe in the
     * local file URL while preserving drive letters
     * and directory separators.
     */
        $normalized =
            str_replace(
                [
                    '%',
                    ' ',
                    '#',
                    '?',
                ],
                [
                    '%25',
                    '%20',
                    '%23',
                    '%3F',
                ],
                $normalized
            );

        if (
            PHP_OS_FAMILY
            === 'Windows'
        ) {
            return 'file:///'
                . $normalized;
        }

        return 'file://'
            . $normalized;
    }
}
