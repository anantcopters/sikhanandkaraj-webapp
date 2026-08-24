<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Services\Aws\S3Service;

use RuntimeException;
use Throwable;

final class MemberProfilePdfAssetService
{
    private const ICON_DIRECTORY =
    'assets/images/profile-pdf/icons/';

    private const PURPLE =
    '#310a57';

    private const RED =
    '#ce102c';

    public function __construct(
        private readonly S3Service $s3Service
    ) {}

    /**
     * @return array<string,string>
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

            /*
             * Decorative background is deliberately generated
             * locally. No external URL is exposed to Chrome.
             */
            'headerCorner' =>
            $this->dataUri(
                'image/svg+xml',
                $this->headerCornerSvg()
            ),

            'headerKnot' =>
            $this->dataUri(
                'image/svg+xml',
                $this->headerKnotSvg()
            ),
        ];
    }

    public function icon(
        string $name,
        string $colour = 'purple'
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

        $path =
            FCPATH
            . self::ICON_DIRECTORY
            . $safeName
            . '.svg';

        if (
            !is_file($path)
            || !is_readable($path)
        ) {
            return '';
        }

        $svg =
            file_get_contents(
                $path
            );

        if (
            $svg === false
            || trim($svg) === ''
        ) {
            return '';
        }

        $brandColour =
            strtolower($colour)
            === 'red'
            ? self::RED
            : self::PURPLE;

        /*
         * Existing PDF SVGs currently contain their own
         * black/current stroke values. Recolour the SVG before
         * embedding it rather than maintaining duplicate icons.
         */
        $svg = preg_replace(
            '/stroke="(?:#000000|#000|black|currentColor)"/i',
            'stroke="' . $brandColour . '"',
            $svg
        ) ?? $svg;

        $svg = preg_replace(
            '/fill="(?:#000000|#000|black|currentColor)"/i',
            'fill="' . $brandColour . '"',
            $svg
        ) ?? $svg;

        /*
         * Some icons rely on inherited CSS/currentColor.
         */
        $svg = str_replace(
            [
                'stroke="currentColor"',
                'fill="currentColor"',
            ],
            [
                'stroke="' . $brandColour . '"',
                'fill="' . $brandColour . '"',
            ],
            $svg
        );

        return $this->dataUri(
            'image/svg+xml',
            $svg
        );
    }

    /**
     * Convert an already-authorized private S3 image into
     * an embedded data URI.
     *
     * No S3 object key, S3 URL or CloudFront URL reaches
     * the PDF view or Chromium.
     */
    public function storedImage(
        string $objectKey
    ): string {
        $objectKey = ltrim(
            trim($objectKey),
            '/'
        );

        if ($objectKey === '') {
            return '';
        }

        try {
            $object =
                $this->s3Service
                ->read(
                    $objectKey
                );

            $body = (string) (
                $object['body']
                ?? ''
            );

            if ($body === '') {
                log_message(
                    'warning',
                    'Profile PDF S3 thumbnail returned an empty body.'
                );

                return '';
            }

            $contentType = strtolower(
                trim(
                    (string) (
                        $object['contentType']
                        ?? ''
                    )
                )
            );

            $allowedTypes = [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/webp',
            ];

            if (
                !in_array(
                    $contentType,
                    $allowedTypes,
                    true
                )
            ) {
                /*
             * Older S3 objects may contain a generic
             * application/octet-stream content type.
             *
             * Detect the actual image type from the bytes
             * instead of rejecting a valid image.
             */
                $detectedType =
                    $this->detectImageMime(
                        $body
                    );

                if ($detectedType === '') {
                    log_message(
                        'warning',
                        'Profile PDF S3 thumbnail has unsupported MIME type: {type}',
                        [
                            'type' =>
                            $contentType,
                        ]
                    );

                    return '';
                }

                $contentType =
                    $detectedType;
            }

            return $this->dataUri(
                $contentType,
                $body,
                true
            );
        } catch (Throwable $exception) {
            log_message(
                'warning',
                'Profile PDF S3 thumbnail embedding failed: {message}',
                [
                    'message' =>
                    $exception
                        ->getMessage(),
                ]
            );

            return '';
        }
    }

    /**
     * Return the locally installed Remix Icon font.
     *
     * @return array{
     *     uri:string,
     *     format:string
     * }
     */
    public function remixIconFont(): array
    {
        $candidates = [
            [
                'path' =>
                FCPATH
                    . 'assets/fonts/'
                    . 'remixicon.woff2',

                'mime' =>
                'font/woff2',

                'format' =>
                'woff2',
            ],

            [
                'path' =>
                FCPATH
                    . 'assets/fonts/'
                    . 'remixicon.woff',

                'mime' =>
                'font/woff',

                'format' =>
                'woff',
            ],
        ];

        foreach ($candidates as $candidate) {
            $path =
                $candidate['path'];

            if (
                !is_file($path)
                || !is_readable($path)
            ) {
                continue;
            }

            $contents =
                file_get_contents(
                    $path
                );

            if (
                $contents === false
                || $contents === ''
            ) {
                continue;
            }

            return [
                'uri' =>
                'data:'
                    . $candidate['mime']
                    . ';base64,'
                    . base64_encode(
                        $contents
                    ),

                'format' =>
                $candidate['format'],
            ];
        }

        log_message(
            'warning',
            'Profile PDF Remix Icon font is unavailable.'
        );

        return [
            'uri' => '',
            'format' => '',
        ];
    }

    public function remixIconCss(): string
    {
        $path =
            FCPATH
            . 'assets/fonts/'
            . 'remixicon.css';

        if (
            !is_file($path)
            || !is_readable($path)
        ) {
            log_message(
                'warning',
                'Profile PDF Remix Icon stylesheet is unavailable.'
            );

            return '';
        }

        $contents =
            file_get_contents(
                $path
            );

        if (
            $contents === false
            || trim($contents) === ''
        ) {
            return '';
        }

        /*
     * Pdf.php supplies its own embedded @font-face.
     *
     * Remove the library font-face so Chromium never attempts
     * to resolve relative Remix font URLs.
     */
        $contents = preg_replace(
            '/@font-face\s*\{.*?\}/is',
            '',
            $contents
        );

        if (!is_string($contents)) {
            return '';
        }

        return trim(
            $contents
        );
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

        return $this->dataUri(
            $mimeType,
            $contents,
            true
        );
    }

    private function dataUri(
        string $mimeType,
        string $contents,
        bool $base64 = false
    ): string {
        if ($base64) {
            return 'data:'
                . $mimeType
                . ';base64,'
                . base64_encode(
                    $contents
                );
        }

        return 'data:'
            . $mimeType
            . ';base64,'
            . base64_encode(
                $contents
            );
    }

    private function detectImageMime(
        string $contents
    ): string {
        if (
            strlen($contents) >= 3
            && substr(
                $contents,
                0,
                3
            ) === "\xFF\xD8\xFF"
        ) {
            return 'image/jpeg';
        }

        if (
            strlen($contents) >= 8
            && substr(
                $contents,
                0,
                8
            ) === "\x89PNG\r\n\x1A\n"
        ) {
            return 'image/png';
        }

        if (
            strlen($contents) >= 12
            && substr(
                $contents,
                0,
                4
            ) === 'RIFF'
            && substr(
                $contents,
                8,
                4
            ) === 'WEBP'
        ) {
            return 'image/webp';
        }

        return '';
    }

    private function headerCornerSvg(): string
    {
        return <<<'SVG'
                    <svg xmlns="http://www.w3.org/2000/svg"
                        width="260"
                        height="260"
                        viewBox="0 0 260 260">
                        <g fill="none"
                        stroke="#ce102c"
                        stroke-width="3"
                        opacity=".28">
                            <circle cx="225" cy="25" r="46"/>
                            <circle cx="225" cy="25" r="65"/>
                            <circle cx="225" cy="25" r="84"/>
                            <path d="M142 0 C160 48 208 82 260 88"/>
                            <path d="M165 0 C178 38 218 62 260 66"/>
                            <path d="M260 112 C215 116 180 146 170 190"/>
                            <path d="M260 136 C226 139 201 162 195 198"/>
                            <path d="M211 0 C213 30 232 49 260 52"/>
                            <path d="M260 170 C236 171 219 188 217 214"/>
                        </g>
                    </svg>
                    SVG;
    }

    private function headerKnotSvg(): string
    {
        return <<<'SVG'
                    <svg xmlns="http://www.w3.org/2000/svg"
                        width="120"
                        height="58"
                        viewBox="0 0 120 58">
                        <g fill="none"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="6">
                            <path
                                stroke="#310a57"
                                d="M58 28 C42 7 23 7 23 20 C23 34 43 40 59 50 C74 40 96 34 96 20 C96 7 76 7 61 28"/>
                            <path
                                stroke="#ce102c"
                                d="M60 28 C75 7 95 7 95 20 C95 34 74 40 59 50 C43 40 23 34 23 20 C23 7 43 7 58 28"/>
                        </g>
                    </svg>
                SVG;
    }
}
