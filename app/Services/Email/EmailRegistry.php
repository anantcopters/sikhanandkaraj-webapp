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

    public const MEMBER_INTEREST_RECEIVED =
    'MEMBER_INTEREST_RECEIVED';

    public const MEMBER_INTEREST_ACCEPTED =
    'MEMBER_INTEREST_ACCEPTED';

    public const MEMBER_INTEREST_DECLINED =
    'MEMBER_INTEREST_DECLINED';

    public const CATEGORY_VERIFICATION =
    'VERIFICATION';

    public const CATEGORY_SECURITY =
    'SECURITY';

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

            self::MEMBER_INTEREST_RECEIVED =>
            new EmailDefinition(
                key: self::MEMBER_INTEREST_RECEIVED,

                name: 'Interest Received',

                category: self::CATEGORY_MATRIMONIAL_ACTIVITY,

                subject: 'You received a new Interest on Sikhanandkaraj',

                viewName: 'Emails/Member/InterestActivity',

                previewData: [
                    'userName' =>
                    'Harpreet Singh',

                    'heading' =>
                    'You received a new Interest',

                    'message' =>
                    'A member has shown Interest in your profile.',

                    'actionUrl' =>
                    base_url(
                        'members/interests'
                    ),

                    'actionLabel' =>
                    'View Interest',
                ],

                priority: 20,

                maxAttempts: 3
            ),

            self::MEMBER_INTEREST_ACCEPTED =>
            new EmailDefinition(
                key: self::MEMBER_INTEREST_ACCEPTED,

                name: 'Interest Accepted',

                category: self::CATEGORY_MATRIMONIAL_ACTIVITY,

                subject: 'Your Interest was accepted on Sikhanandkaraj',

                viewName: 'Emails/Member/InterestActivity',

                previewData: [
                    'userName' =>
                    'Harpreet Singh',

                    'heading' =>
                    'Your Interest was accepted',

                    'message' =>
                    'A member has accepted your Interest.',

                    'actionUrl' =>
                    base_url(
                        'members/interests'
                    ),

                    'actionLabel' =>
                    'View Interest',
                ],

                priority: 20,

                maxAttempts: 3
            ),

            self::MEMBER_INTEREST_DECLINED =>
            new EmailDefinition(
                key: self::MEMBER_INTEREST_DECLINED,

                name: 'Interest Declined',

                category: self::CATEGORY_MATRIMONIAL_ACTIVITY,

                subject: 'Update on your Sikhanandkaraj Interest',

                viewName: 'Emails/Member/InterestActivity',

                previewData: [
                    'userName' =>
                    'Harpreet Singh',

                    'heading' =>
                    'An Interest has been updated',

                    'message' =>
                    'A member has declined your Interest.',

                    'actionUrl' =>
                    base_url(
                        'members/interests'
                    ),

                    'actionLabel' =>
                    'View Interests',
                ],

                priority: 20,

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
            || !isset(
                $definitions[$key]
            )
        ) {
            throw new InvalidArgumentException(
                'Email definition could not be found.'
            );
        }

        return $definitions[$key];
    }
}
