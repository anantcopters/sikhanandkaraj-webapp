<?php

declare(strict_types=1);

namespace App\Services\Profile;

use RuntimeException;

final class MemberProfilePdfAssetService
{
    private const ICON_DIRECTORY =
    'assets/images/profile-pdf/icons/';

    /**
     * Return common embedded PDF assets.
     *
     * @return array<string, string>
     */
    public function commonAssets(): array
    {
        return [
            'logo' =>
            $this->requiredDataUri(
                FCPATH
                    . 'assets/images/'
                    . 'logo_sak_bgremove_final.png'
            ),

            'marriageMotif' =>
            $this->requiredDataUri(
                FCPATH
                    . 'assets/images/profile-pdf/'
                    . 'sikh-marriage-motif.svg'
            ),

            'fontRegular' =>
            $this->requiredDataUri(
                FCPATH
                    . 'assets/fonts/inter/'
                    . 'Inter-Regular.ttf'
            ),

            'fontMedium' =>
            $this->requiredDataUri(
                FCPATH
                    . 'assets/fonts/inter/'
                    . 'Inter-Medium.ttf'
            ),

            'fontSemiBold' =>
            $this->requiredDataUri(
                FCPATH
                    . 'assets/fonts/inter/'
                    . 'Inter-SemiBold.ttf'
            ),

            'fontBold' =>
            $this->requiredDataUri(
                FCPATH
                    . 'assets/fonts/inter/'
                    . 'Inter-Bold.ttf'
            ),
        ];
    }

    /**
     * Return one embedded SVG icon.
     */
    public function icon(
        string $name
    ): string {
        $safeName = preg_replace(
            '/[^a-z0-9-]/',
            '',
            strtolower(
                trim($name)
            )
        ) ?? '';

        if ($safeName === '') {
            return '';
        }

        return $this->optionalDataUri(
            FCPATH
                . self::ICON_DIRECTORY
                . $safeName
                . '.svg'
        );
    }

    /**
     * Convert a remote, already-authorized member thumbnail
     * into an embedded image.
     *
     * The CloudFront URL therefore never appears in the HTML
     * passed to Chrome.
     */
    public function remoteImage(
        string $url
    ): string {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        try {
            $response = service(
                'curlrequest'
            )->get(
                $url,
                [
                    'timeout' => 10,
                    'http_errors' => false,
                ]
            );

            if (
                $response->getStatusCode()
                !== 200
            ) {
                return '';
            }

            $body =
                $response->getBody();

            if ($body === '') {
                return '';
            }

            $contentType = trim(
                explode(
                    ';',
                    $response->getHeaderLine(
                        'Content-Type'
                    )
                )[0]
            );

            if (
                !in_array(
                    $contentType,
                    [
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                    ],
                    true
                )
            ) {
                return '';
            }

            return 'data:'
                . $contentType
                . ';base64,'
                . base64_encode(
                    $body
                );
        } catch (\Throwable $exception) {
            log_message(
                'warning',
                'Profile PDF thumbnail embedding failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return '';
        }
    }

    private function requiredDataUri(
        string $path
    ): string {
        $uri =
            $this->optionalDataUri(
                $path
            );

        if ($uri === '') {
            throw new RuntimeException(
                'Required profile PDF asset is unavailable: '
                    . basename($path)
            );
        }

        return $uri;
    }

    private function optionalDataUri(
        string $path
    ): string {
        if (
            !is_file($path)
            || !is_readable($path)
        ) {
            return '';
        }

        $contents =
            file_get_contents(
                $path
            );

        if ($contents === false) {
            return '';
        }

        $extension = strtolower(
            pathinfo(
                $path,
                PATHINFO_EXTENSION
            )
        );

        $mimeType = match ($extension) {
            'svg' =>
            'image/svg+xml',

            'png' =>
            'image/png',

            'jpg',
            'jpeg' =>
            'image/jpeg',

            'webp' =>
            'image/webp',

            'ttf' =>
            'font/ttf',

            default =>
            '',
        };

        if ($mimeType === '') {
            return '';
        }

        return 'data:'
            . $mimeType
            . ';base64,'
            . base64_encode(
                $contents
            );
    }
}
