<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Models\UserContactModel;

/**
 * Resolves the only email address that normal member communications may use.
 *
 * Email Verification is intentionally excluded from this policy because its
 * purpose is to establish verified-email state in the first place.
 */
final class MemberEmailRecipientService
{
    public function __construct(
        private readonly UserContactModel $contactModel
    ) {}

    /**
     * @return array{email:string,name:string}|null
     */
    public function verifiedPrimaryEmail(
        int $userId,
        string $recipientName = ''
    ): ?array {
        if ($userId <= 0) {
            return null;
        }

        $contact = $this->contactModel
            ->findPrimaryForUser(
                $userId,
                UserContactModel::TYPE_EMAIL
            );

        if (!is_array($contact)) {
            return null;
        }

        if (($contact['is_verified'] ?? false) !== true
            && (int) ($contact['is_verified'] ?? 0) !== 1) {
            return null;
        }

        $email = trim((string) ($contact['contact_value'] ?? ''));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return [
            'email' => $email,
            'name' => trim($recipientName),
        ];
    }
}
