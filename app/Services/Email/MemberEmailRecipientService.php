<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Models\UserContactModel;
use App\Support\BooleanValue;

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

        if (
            !BooleanValue::fromDatabase(
                $contact['is_verified']
                    ?? false
            )
        ) {
            return null;
        }

        $email = trim(
            (string) (
                $contact['contact_value']
                ?? ''
            )
        );

        if (
            $email === ''
            || filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            return null;
        }

        return [
            'email' =>
            $email,

            'name' =>
            trim(
                $recipientName
            ),
        ];
    }
}
