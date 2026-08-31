<?php

declare(strict_types=1);

namespace App\Services\Email;

use InvalidArgumentException;

final class EmailRegistry
{
    public const MEMBER_EMAIL_VERIFICATION =
    'MEMBER_EMAIL_VERIFICATION';

    public const ADMIN_INVITATION =
    'ADMIN_INVITATION';

    public const CATEGORY_VERIFICATION =
    'VERIFICATION';

    public const CATEGORY_SECURITY =
    'SECURITY';

    public const MEMBER_INTEREST_RECEIVED =
    'MEMBER_INTEREST_RECEIVED';

    public const MEMBER_INTEREST_ACCEPTED =
    'MEMBER_INTEREST_ACCEPTED';

    public const MEMBER_INTEREST_DECLINED =
    'MEMBER_INTEREST_DECLINED';

    public const CATEGORY_MATRIMONIAL_ACTIVITY =
    'MATRIMONIAL_ACTIVITY';

    /**
     * @return array<string, EmailDefinition>
     */
    public function all(): array
    {
        return [
            self::MEMBER_EMAIL_VERIFICATION =>
            new EmailDefinition(
                key: self::MEMBER_EMAIL_VERIFICATION,

                name: 'Member Email Verification',

                category: self::CATEGORY_VERIFICATION,

                subject: 'Verify your Sikhanandkaraj email',

                viewName: 'Emails/Authentication/VerifyEmail',

                previewData: [
                    'userName' =>
                    'Harpreet Singh',

                    'emailAddress' =>
                    'harpreet@example.com',

                    'verificationUrl' =>
                    base_url(
                        'email/verify/'
                            . str_repeat('a', 64)
                    ),

                    'expiresInHours' =>
                    24,

                    'isReplacement' =>
                    false,
                ],

                priority: 10,

                maxAttempts: 3
            ),

            self::ADMIN_INVITATION =>
            new EmailDefinition(
                key: self::ADMIN_INVITATION,

                name: 'Administrator Invitation',

                category: self::CATEGORY_SECURITY,

                subject: 'Complete your Sikhanandkaraj administrator account',

                viewName: 'Emails/Admin/AdminInvitation',

                previewData: [
                    'adminName' =>
                    'Jaspreet Singh',

                    'invitationUrl' =>
                    base_url(
                        'admin/invitation/'
                            . str_repeat('b', 64)
                    ),

                    'expiresInHours' =>
                    24,
                ],

                priority: 5,

                maxAttempts: 3
            ),
        ];
    }

    public function get(
        string $key
    ): EmailDefinition {
        $key = strtoupper(
            trim($key)
        );

        $definitions =
            $this->all();

        if (
            $key === ''
            || !isset($definitions[$key])
        ) {
            throw new InvalidArgumentException(
                'Email definition could not be found.'
            );
        }

        return $definitions[$key];
    }
}
