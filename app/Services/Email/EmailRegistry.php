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

    public const MEMBER_PHOTO_REJECTED =
    'MEMBER_PHOTO_REJECTED';

    public const MEMBER_AADHAAR_APPROVED =
    'MEMBER_AADHAAR_APPROVED';

    public const MEMBER_AADHAAR_REJECTED =
    'MEMBER_AADHAAR_REJECTED';

    public const MEMBER_VIDEO_APPROVED =
    'MEMBER_VIDEO_APPROVED';

    public const MEMBER_VIDEO_REJECTED =
    'MEMBER_VIDEO_REJECTED';

    public const MEMBER_VIDEO_RESUBMISSION_REQUESTED =
    'MEMBER_VIDEO_RESUBMISSION_REQUESTED';

    public const CATEGORY_MODERATION =
    'MODERATION';

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

            self::MEMBER_PHOTO_REJECTED =>
            new EmailDefinition(
                key: self::MEMBER_PHOTO_REJECTED,

                name: 'Profile Photo Rejected',

                category: self::CATEGORY_MODERATION,

                subject: 'Your profile photo needs attention',

                viewName: 'Emails/Member/ModerationActivity',

                previewData: [
                    'userName' =>
                    'Harpreet Singh',

                    'heading' =>
                    'Your profile photo was not approved',

                    'message' =>
                    'One of your profile photos was not approved. '
                        . 'Please review the photo guidelines and upload '
                        . 'a suitable replacement.',

                    'reason' =>
                    'The photo does not meet the profile photo guidelines.',

                    'actionUrl' =>
                    base_url(
                        'profile/photos'
                    ),

                    'actionLabel' =>
                    'Review Profile Photos',
                ],

                priority: 20,

                maxAttempts: 3
            ),

            self::MEMBER_AADHAAR_APPROVED =>
            new EmailDefinition(
                key: self::MEMBER_AADHAAR_APPROVED,

                name: 'Aadhaar Approved',

                category: self::CATEGORY_VERIFICATION,

                subject: 'Your Aadhaar verification is approved',

                viewName: 'Emails/Member/ModerationActivity',

                previewData: [
                    'userName' =>
                    'Harpreet Singh',

                    'heading' =>
                    'Your Aadhaar verification is approved',

                    'message' =>
                    'Your Aadhaar verification has been reviewed '
                        . 'and approved.',

                    'reason' =>
                    '',

                    'actionUrl' =>
                    base_url(
                        'account-settings/aadhaar'
                    ),

                    'actionLabel' =>
                    'View Verification Status',
                ],

                priority: 20,

                maxAttempts: 3
            ),

            self::MEMBER_AADHAAR_REJECTED =>
            new EmailDefinition(
                key: self::MEMBER_AADHAAR_REJECTED,

                name: 'Aadhaar Rejected',

                category: self::CATEGORY_VERIFICATION,

                subject: 'Your Aadhaar verification needs attention',

                viewName: 'Emails/Member/ModerationActivity',

                previewData: [
                    'userName' =>
                    'Harpreet Singh',

                    'heading' =>
                    'Your Aadhaar verification was not approved',

                    'message' =>
                    'Your Aadhaar verification has been reviewed '
                        . 'and was not approved.',

                    'reason' =>
                    'The submitted document could not be verified.',

                    'actionUrl' =>
                    base_url(
                        'account-settings/aadhaar'
                    ),

                    'actionLabel' =>
                    'Review Aadhaar Verification',
                ],

                priority: 20,

                maxAttempts: 3
            ),

            self::MEMBER_VIDEO_APPROVED =>
            new EmailDefinition(
                key: self::MEMBER_VIDEO_APPROVED,

                name: 'Video Introduction Approved',

                category: self::CATEGORY_VERIFICATION,

                subject: 'Your Video Introduction is approved',

                viewName: 'Emails/Member/ModerationActivity',

                previewData: [
                    'userName' =>
                    'Harpreet Singh',

                    'heading' =>
                    'Your Video Introduction is approved',

                    'message' =>
                    'Your Video Introduction has been reviewed '
                        . 'and approved. It will follow the privacy '
                        . 'setting selected for your profile.',

                    'reason' =>
                    '',

                    'actionUrl' =>
                    base_url(
                        'account-settings/video-introduction'
                    ),

                    'actionLabel' =>
                    'View Video Introduction',
                ],

                priority: 20,

                maxAttempts: 3
            ),

            self::MEMBER_VIDEO_REJECTED =>
            new EmailDefinition(
                key: self::MEMBER_VIDEO_REJECTED,

                name: 'Video Introduction Rejected',

                category: self::CATEGORY_MODERATION,

                subject: 'Your Video Introduction needs attention',

                viewName: 'Emails/Member/ModerationActivity',

                previewData: [
                    'userName' =>
                    'Harpreet Singh',

                    'heading' =>
                    'Your Video Introduction was not approved',

                    'message' =>
                    'Your Video Introduction has been reviewed '
                        . 'and was not approved.',

                    'reason' =>
                    'Please record a clear introduction that follows '
                        . 'the Video Introduction guidelines.',

                    'actionUrl' =>
                    base_url(
                        'account-settings/video-introduction'
                    ),

                    'actionLabel' =>
                    'Review Video Introduction',
                ],

                priority: 20,

                maxAttempts: 3
            ),

            self::MEMBER_VIDEO_RESUBMISSION_REQUESTED =>
            new EmailDefinition(
                key: self::MEMBER_VIDEO_RESUBMISSION_REQUESTED,

                name: 'Video Introduction Resubmission Requested',

                category: self::CATEGORY_MODERATION,

                subject: 'Please resubmit your Video Introduction',

                viewName: 'Emails/Member/ModerationActivity',

                previewData: [
                    'userName' =>
                    'Harpreet Singh',

                    'heading' =>
                    'Please resubmit your Video Introduction',

                    'message' =>
                    'Our verification team has requested a new '
                        . 'Video Introduction from you.',

                    'reason' =>
                    'Please record the introduction again following '
                        . 'the Video Introduction guidelines.',

                    'actionUrl' =>
                    base_url(
                        'account-settings/video-introduction'
                    ),

                    'actionLabel' =>
                    'Record Video Introduction',
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
