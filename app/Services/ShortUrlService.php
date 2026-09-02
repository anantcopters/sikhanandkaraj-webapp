<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ShortUrlModel;
use RuntimeException;

final class ShortUrlService
{
    private const CODE_LENGTH = 6;

    /*
     * Ambiguous characters are intentionally removed:
     *
     * 0 / O
     * 1 / I
     *
     * This makes manually copied SMS/DLT URLs easier to read.
     */
    private const CODE_ALPHABET =
    'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const MAX_GENERATION_ATTEMPTS = 20;

    public function __construct(
        private readonly ShortUrlModel $shortUrlModel =
        new ShortUrlModel()
    ) {}

    /**
     * @return array{
     *     valid: bool,
     *     url: string,
     *     message: string|null
     * }
     */
    public function validateDestination(
        string $destinationUrl
    ): array {
        $destinationUrl =
            $this->normalize(
                $destinationUrl
            );

        if ($destinationUrl === '') {
            return [
                'valid' =>
                false,

                'url' =>
                '',

                'message' =>
                'Destination URL is required.',
            ];
        }

        if (
            mb_strlen(
                $destinationUrl
            ) > 2048
        ) {
            return [
                'valid' =>
                false,

                'url' =>
                $destinationUrl,

                'message' =>
                'Destination URL cannot exceed 2048 characters.',
            ];
        }

        if (
            filter_var(
                $destinationUrl,
                FILTER_VALIDATE_URL
            ) === false
        ) {
            return [
                'valid' =>
                false,

                'url' =>
                $destinationUrl,

                'message' =>
                'Please enter a valid URL.',
            ];
        }

        $destinationParts =
            parse_url(
                $destinationUrl
            );

        $baseUrl =
            rtrim(
                (string) config('App')->baseURL,
                '/'
            );

        $baseParts =
            parse_url(
                $baseUrl
            );

        $destinationScheme =
            mb_strtolower(
                (string) (
                    $destinationParts['scheme']
                    ?? ''
                )
            );

        $destinationHost =
            mb_strtolower(
                (string) (
                    $destinationParts['host']
                    ?? ''
                )
            );

        $baseScheme =
            mb_strtolower(
                (string) (
                    $baseParts['scheme']
                    ?? ''
                )
            );

        $baseHost =
            mb_strtolower(
                (string) (
                    $baseParts['host']
                    ?? ''
                )
            );

        if (
            $destinationScheme
            !== $baseScheme
            || $destinationHost
            !== $baseHost
        ) {
            return [
                'valid' =>
                false,

                'url' =>
                $destinationUrl,

                'message' =>
                'Only SikhanandKaraj application URLs can be shortened.',
            ];
        }

        /*
         * Prevent a short URL from pointing to another short URL.
         *
         * This avoids unnecessary redirect chains and circular redirects.
         */
        $destinationPath =
            trim(
                (string) (
                    $destinationParts['path']
                    ?? ''
                ),
                '/'
            );

        if (
            preg_match(
                '/^ISAK\/[A-Za-z0-9]{6}$/',
                $destinationPath
            ) === 1
        ) {
            return [
                'valid' =>
                false,

                'url' =>
                $destinationUrl,

                'message' =>
                'A SikhanandKaraj short URL cannot be shortened again.',
            ];
        }

        return [
            'valid' =>
            true,

            'url' =>
            $destinationUrl,

            'message' =>
            null,
        ];
    }

    /**
     * @return array{
     *     record: array<string, mixed>,
     *     created: bool
     * }
     */
    public function createOrFind(
        string $destinationUrl,
        int $adminUserId
    ): array {
        $validation =
            $this->validateDestination(
                $destinationUrl
            );

        if (
            $validation['valid']
            !== true
        ) {
            throw new RuntimeException(
                (string) $validation['message']
            );
        }

        $destinationUrl =
            $validation['url'];

        $existing =
            $this
            ->shortUrlModel
            ->findByDestination(
                $destinationUrl
            );

        if ($existing !== null) {
            return [
                'record' =>
                $existing,

                'created' =>
                false,
            ];
        }

        $now =
            date(
                'Y-m-d H:i:s'
            );

        /*
         * The database UNIQUE constraint remains the final protection
         * against a generated-code collision.
         */
        for (
            $attempt = 0;
            $attempt < self::MAX_GENERATION_ATTEMPTS;
            $attempt++
        ) {
            $shortCode =
                $this->generateCode();

            if (
                $this
                ->shortUrlModel
                ->findByCode(
                    $shortCode
                )
                !== null
            ) {
                continue;
            }

            $inserted =
                $this
                ->shortUrlModel
                ->insert(
                    [
                        'short_code' =>
                        $shortCode,

                        'destination_url' =>
                        $destinationUrl,

                        'destination_hash' =>
                        hash(
                            'sha256',
                            $destinationUrl
                        ),

                        'created_by_admin_id' =>
                        $adminUserId,

                        'created_at' =>
                        $now,

                        'updated_at' =>
                        $now,
                    ],
                    true
                );

            if ($inserted !== false) {
                $record =
                    $this
                    ->shortUrlModel
                    ->find(
                        (int) $inserted
                    );

                if (is_array($record)) {
                    return [
                        'record' =>
                        $record,

                        'created' =>
                        true,
                    ];
                }
            }

            /*
             * Another request may have created the same destination
             * between our lookup and insert.
             */
            $existing =
                $this
                ->shortUrlModel
                ->findByDestination(
                    $destinationUrl
                );

            if ($existing !== null) {
                return [
                    'record' =>
                    $existing,

                    'created' =>
                    false,
                ];
            }
        }

        throw new RuntimeException(
            'Unable to generate a unique short URL. Please try again.'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolve(
        string $shortCode
    ): ?array {
        $shortCode =
            mb_strtoupper(
                trim(
                    $shortCode
                )
            );

        if (
            preg_match(
                '/^[A-Z0-9]{6}$/',
                $shortCode
            ) !== 1
        ) {
            return null;
        }

        return $this
            ->shortUrlModel
            ->findByCode(
                $shortCode
            );
    }

    public function shortUrl(
        string $shortCode
    ): string {
        return base_url(
            'ISAK/'
                . rawurlencode(
                    mb_strtoupper(
                        $shortCode
                    )
                )
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recent(): array
    {
        return $this
            ->shortUrlModel
            ->recent();
    }

    private function normalize(
        string $destinationUrl
    ): string {
        $destinationUrl =
            trim(
                $destinationUrl
            );

        /*
         * Remove a trailing slash only from a non-root destination.
         *
         * Query strings are retained because they may form part of the
         * actual application destination.
         */
        if (
            $destinationUrl !== ''
            && !str_contains(
                $destinationUrl,
                '?'
            )
            && !str_contains(
                $destinationUrl,
                '#'
            )
        ) {
            $destinationUrl =
                rtrim(
                    $destinationUrl,
                    '/'
                );
        }

        return $destinationUrl;
    }

    private function generateCode(): string
    {
        $alphabet =
            self::CODE_ALPHABET;

        $maximumIndex =
            strlen(
                $alphabet
            ) - 1;

        $code = '';

        for (
            $position = 0;
            $position < self::CODE_LENGTH;
            $position++
        ) {
            $code .=
                $alphabet[random_int(
                        0,
                        $maximumIndex
                    )];
        }

        return $code;
    }
}
