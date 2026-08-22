<?php

declare(strict_types=1);

namespace App\Services\Profile;

use Config\ProfilePdf;
use RuntimeException;
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
        if (
            $this->config
            ->chromePath === ''
        ) {
            throw new RuntimeException(
                'Profile PDF Chrome path is not configured.'
            );
        }

        if (
            !is_file(
                $this->config
                    ->chromePath
            )
        ) {
            throw new RuntimeException(
                'Configured Chrome executable does not exist.'
            );
        }

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
                        $this->config
                            ->chromePath,

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

        if (
            PHP_OS_FAMILY
            === 'Windows'
        ) {
            return 'file:///'
                . str_replace(
                    ' ',
                    '%20',
                    $normalized
                );
        }

        return 'file://'
            . str_replace(
                ' ',
                '%20',
                $normalized
            );
    }
}
