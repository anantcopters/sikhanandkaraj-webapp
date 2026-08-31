<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Services\Communication\CommunicationCategory;
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

    /*
    * --------------------------------------------------------------------------
    * Support communications
    * --------------------------------------------------------------------------
    *
    * These emails acknowledge member-created Contact Us requests and notify
    * the member when an administrator resolves the request.
    */
    public const MEMBER_SUPPORT_REQUEST_RECEIVED =
    'MEMBER_SUPPORT_REQUEST_RECEIVED';

    public const MEMBER_SUPPORT_REQUEST_RESOLVED =
    'MEMBER_SUPPORT_REQUEST_RESOLVED';

    /*
    * --------------------------------------------------------------------------
    * Membership communications
    * --------------------------------------------------------------------------
    *
    * Membership activation is generated only after the authoritative payment
    * processor has successfully activated the membership.
    *
    * Expiry is generated only when MembershipLifecycleService successfully
    * transitions an ACTIVE membership to EXPIRED.
    */
    public const MEMBER_MEMBERSHIP_ACTIVATED =
    'MEMBER_MEMBERSHIP_ACTIVATED';

    /**
     * One lifecycle reminder sent three calendar days before
     * an ACTIVE membership reaches expires_at.
     */
    public const MEMBER_MEMBERSHIP_EXPIRING_SOON =
    'MEMBER_MEMBERSHIP_EXPIRING_SOON';

    public const MEMBER_MEMBERSHIP_EXPIRED =
    'MEMBER_MEMBERSHIP_EXPIRED';

    /*
    * --------------------------------------------------------------------------
    * Communication categories
    * --------------------------------------------------------------------------
    *
    * Categories are channel-independent and are owned by
    * CommunicationCategory.
    *
    * These aliases are retained so existing EmailRegistry consumers remain
    * backward compatible while the communication architecture moves toward
    * channel-independent orchestration.
    */
    public const CATEGORY_MODERATION =
    CommunicationCategory::MODERATION;

    public const CATEGORY_VERIFICATION =
    CommunicationCategory::VERIFICATION;

    public const CATEGORY_SECURITY =
    CommunicationCategory::SECURITY;

    public const CATEGORY_MATRIMONIAL_ACTIVITY =
    CommunicationCategory::MATRIMONIAL_ACTIVITY;

    public const CATEGORY_TRANSACTIONAL =
    CommunicationCategory::TRANSACTIONAL;

    public const CATEGORY_ENGAGEMENT =
    CommunicationCategory::ENGAGEMENT;

    public const CATEGORY_SUPPORT =
    CommunicationCategory::SUPPORT;

    public const CATEGORY_MEMBERSHIP =
    CommunicationCategory::MEMBERSHIP;

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
            /*
 * --------------------------------------------------------------------------
 * Support Request Received
 * --------------------------------------------------------------------------
 *
 * This is a transactional acknowledgement. The member has just submitted
 * the request, therefore the email confirms the public support reference
 * without copying the member's potentially sensitive support message.
 */
            self::MEMBER_SUPPORT_REQUEST_RECEIVED =>
            new EmailDefinition(
                key: self::MEMBER_SUPPORT_REQUEST_RECEIVED,

                name: 'Support Request Received',

                category: self::CATEGORY_SUPPORT,

                subject: 'We received your Sikhanandkaraj support request',

                viewName: 'Emails/Member/SupportActivity',

                previewData: [
                    'userName' =>
                    'Harpreet Singh',

                    'heading' =>
                    'We received your support request',

                    'message' =>
                    'Your request has been received by the '
                        . 'Sikhanandkaraj support team.',

                    'requestReference' =>
                    'SAKSUPP-123456',

                    'responseNote' =>
                    '',

                    'actionUrl' =>
                    base_url(
                        'account-settings/contact'
                    ),

                    'actionLabel' =>
                    'View Support Request',
                ],

                priority: 20,

                maxAttempts: 3
            ),

            /*
 * --------------------------------------------------------------------------
 * Support Request Resolved
 * --------------------------------------------------------------------------
 *
 * The administrator's member-facing response may be included because it is
 * explicitly written for the member. Internal administration information
 * and administrator identity are deliberately excluded.
 */
            self::MEMBER_SUPPORT_REQUEST_RESOLVED =>
            new EmailDefinition(
                key: self::MEMBER_SUPPORT_REQUEST_RESOLVED,

                name: 'Support Request Resolved',

                category: self::CATEGORY_SUPPORT,

                subject: 'Your Sikhanandkaraj support request has been resolved',

                viewName: 'Emails/Member/SupportActivity',

                previewData: [
                    'userName' =>
                    'Harpreet Singh',

                    'heading' =>
                    'Your support request has been resolved',

                    'message' =>
                    'The Sikhanandkaraj support team has '
                        . 'reviewed and resolved your request.',

                    'requestReference' =>
                    'SAKSUPP-123456',

                    'responseNote' =>
                    'Your request has been reviewed and the '
                        . 'required update has been completed.',

                    'actionUrl' =>
                    base_url(
                        'account-settings/contact'
                    ),

                    'actionLabel' =>
                    'View Support History',
                ],

                priority: 20,

                maxAttempts: 3
            ),

            /*
 * --------------------------------------------------------------------------
 * Membership Activated
 * --------------------------------------------------------------------------
 *
 * This email is sent only after successful payment processing and
 * authoritative membership activation.
 *
 * Provider payment IDs and raw gateway responses are deliberately excluded.
 */
            self::MEMBER_MEMBERSHIP_ACTIVATED =>
            new EmailDefinition(
                key: self::MEMBER_MEMBERSHIP_ACTIVATED,

                name: 'Membership Activated',

                category: self::CATEGORY_MEMBERSHIP,

                subject: 'Your Sikhanandkaraj membership is active',

                viewName: 'Emails/Member/MembershipActivity',

                previewData: [
                    'userName' =>
                    'Harpreet Singh',

                    'heading' =>
                    'Your membership is active',

                    'message' =>
                    'Your payment was successful and your '
                        . 'Sikhanandkaraj membership is now active.',

                    'planName' =>
                    'Sikhanandkaraj Pro',

                    'amount' =>
                    '₹2,999',

                    'transactionReference' =>
                    'SAKPAY-1234567890ABCDEF',

                    'expiresAt' =>
                    '31 August 2027',

                    'isExpired' =>
                    false,

                    'actionUrl' =>
                    base_url(
                        'account-settings/membership'
                    ),

                    'actionLabel' =>
                    'View Membership',
                ],

                priority: 15,

                maxAttempts: 3
            ),

            /*
 * --------------------------------------------------------------------------
 * Membership Expiring Soon
 * --------------------------------------------------------------------------
 *
 * One transactional lifecycle reminder is sent three calendar days before
 * the purchased membership period expires.
 *
 * The reminder uses the same MembershipActivity template as activation and
 * expiry. No separate email UI is required.
 */
            self::MEMBER_MEMBERSHIP_EXPIRING_SOON =>
            new EmailDefinition(
                key: self::MEMBER_MEMBERSHIP_EXPIRING_SOON,

                name: 'Membership Expiring Soon',

                category: self::CATEGORY_MEMBERSHIP,

                subject: 'Your Sikhanandkaraj membership expires in 3 days',

                viewName: 'Emails/Member/MembershipActivity',

                previewData: [
                    'userName' =>
                    'Harpreet Singh',

                    'heading' =>
                    'Your membership expires in 3 days',

                    'message' =>
                    'Your Sikhanandkaraj membership is approaching '
                        . 'its expiry date. Renew your membership to '
                        . 'continue using paid membership features.',

                    'planName' =>
                    'Sikhanandkaraj Pro',

                    /*
         * Amount and transaction reference are intentionally empty.
         *
         * This is a renewal reminder, not a payment receipt.
         */
                    'amount' =>
                    '',

                    'transactionReference' =>
                    '',

                    'expiresAt' =>
                    '3rd Sep 2026',

                    /*
         * Existing MembershipActivity.php will therefore display
         * "Valid Until" rather than "Expired On".
         */
                    'isExpired' =>
                    false,

                    'actionUrl' =>
                    base_url(
                        'account-settings/plans'
                    ),

                    'actionLabel' =>
                    'View Membership Plans',
                ],

                priority: 25,

                maxAttempts: 3
            ),

            /*
 * --------------------------------------------------------------------------
 * Membership Expired
 * --------------------------------------------------------------------------
 *
 * This is a lifecycle notification, not a marketing email. It tells the
 * member that the previously purchased commercial period has ended.
 */
            self::MEMBER_MEMBERSHIP_EXPIRED =>
            new EmailDefinition(
                key: self::MEMBER_MEMBERSHIP_EXPIRED,

                name: 'Membership Expired',

                category: self::CATEGORY_MEMBERSHIP,

                subject: 'Your Sikhanandkaraj membership has expired',

                viewName: 'Emails/Member/MembershipActivity',

                previewData: [
                    'userName' =>
                    'Harpreet Singh',

                    'heading' =>
                    'Your membership has expired',

                    'message' =>
                    'Your Sikhanandkaraj membership period has ended. '
                        . 'Your profile remains available, but paid membership '
                        . 'features now follow the current account entitlement.',

                    'planName' =>
                    'Sikhanandkaraj Pro',

                    'amount' =>
                    '',

                    'transactionReference' =>
                    '',

                    'expiresAt' =>
                    '31 August 2026',

                    'isExpired' =>
                    true,

                    'actionUrl' =>
                    base_url(
                        'account-settings/plans'
                    ),

                    'actionLabel' =>
                    'View Membership Plans',
                ],

                priority: 30,

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
