<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\MemberBasicDetailModel;
use App\Models\UserModel;
use DomainException;
use RuntimeException;

/**
 * Reads and updates the member's plain-text profile introduction.
 */
final class AboutMeService
{
    private const MAX_WORDS = 500;

    private const MAX_CHARACTERS = 5000;

    public function __construct(
        private readonly UserModel $userModel,
        private readonly MemberBasicDetailModel $basicDetailModel
    ) {}

    /**
     * Return About Me data for one member.
     *
     * @return array{
     *     user: array<string, mixed>,
     *     aboutMe: string,
     *     completion: array{
     *         completed: int,
     *         total: int,
     *         percentage: int
     *     }
     * }
     */
    public function getForUser(int $userId): array
    {
        $user = $this->userModel->find($userId);

        if (!is_array($user)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $basicDetails = $this
            ->basicDetailModel
            ->findForUser($userId);

        $aboutMe = is_array($basicDetails)
            ? trim((string) ($basicDetails['about_me'] ?? ''))
            : '';

        return [
            'user' => $user,
            'aboutMe' => $aboutMe,
            'completion' => [
                'completed' => $aboutMe !== '' ? 1 : 0,
                'total' => 1,
                'percentage' => $aboutMe !== '' ? 100 : 0,
            ],
        ];
    }

    /**
     * Save a plain-text About Me introduction.
     */
    public function save(int $userId, string $submittedText): void
    {
        $basicDetails = $this
            ->basicDetailModel
            ->findForUser($userId);

        if (!is_array($basicDetails)) {
            throw new DomainException(
                'Please complete Basic Details before adding About Me.'
            );
        }

        $aboutMe = $this->normalizeText($submittedText);

        $this->assertValidText($aboutMe);

        $updated = $this->basicDetailModel->update(
            (int) $basicDetails['id'],
            [
                'about_me' => $aboutMe,
            ]
        );

        if ($updated === false) {
            throw new RuntimeException(
                'Your About Me information could not be saved.'
            );
        }
    }

    /**
     * Convert submitted content into normalized plain text.
     */
    private function normalizeText(string $text): string
    {
        /*
         * Remove all HTML before validation and storage.
         */
        $text = strip_tags($text);

        /*
         * Normalize Windows and legacy line endings.
         */
        $text = str_replace(
            ["\r\n", "\r"],
            "\n",
            $text
        );

        /*
         * Remove control characters except line breaks and tabs.
         */
        $text = preg_replace(
            '/[^\P{C}\n\t]+/u',
            '',
            $text
        ) ?? '';

        /*
         * Collapse repeated spaces and tabs.
         */
        $text = preg_replace(
            '/[ \t]+/u',
            ' ',
            $text
        ) ?? '';

        /*
         * Avoid excessive blank lines.
         */
        $text = preg_replace(
            "/\n{3,}/",
            "\n\n",
            $text
        ) ?? '';

        return trim($text);
    }

    private function assertValidText(string $aboutMe): void
    {
        if ($aboutMe === '') {
            throw new DomainException(
                'Please write a short introduction about yourself.'
            );
        }

        if (mb_strlen($aboutMe) > self::MAX_CHARACTERS) {
            throw new DomainException(
                'About Me is too long. Please keep it within 500 words.'
            );
        }

        if ($this->wordCount($aboutMe) > self::MAX_WORDS) {
            throw new DomainException(
                'About Me cannot contain more than 500 words.'
            );
        }

        if ($this->containsLink($aboutMe)) {
            throw new DomainException(
                'Links and website addresses are not allowed in About Me.'
            );
        }
    }

    /**
     * Count words using Unicode letters and numbers.
     */
    private function wordCount(string $text): int
    {
        preg_match_all(
            "/[\p{L}\p{N}]+(?:['’-][\p{L}\p{N}]+)*/u",
            $text,
            $matches
        );

        return count($matches[0] ?? []);
    }

    /**
     * Detect URLs and common domain-style website addresses.
     */
    private function containsLink(string $text): bool
    {
        $patterns = [
            '/\bhttps?:\/\/\S+/iu',
            '/\bftp:\/\/\S+/iu',
            '/\bwww\.\S+/iu',
            '/\b[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?'
                . '(?:\.[a-z]{2,})+(?:\/\S*)?\b/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }
}
