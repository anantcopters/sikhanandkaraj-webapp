BEGIN;

ALTER TABLE member_notifications
DROP CONSTRAINT IF EXISTS chk_member_notifications_type;

ALTER TABLE member_notifications
ADD CONSTRAINT chk_member_notifications_type
CHECK (
    notification_type IN (
        'MESSAGE',

        'INTEREST_RECEIVED',
        'INTEREST_ACCEPTED',
        'INTEREST_DECLINED',

        'PROFILE_VIEWED',
        'PROFILE_SHORTLISTED',

        'PHOTO_APPROVED',
        'PHOTO_REJECTED',

        'AADHAAR_APPROVED',
        'AADHAAR_REJECTED',

        'VIDEO_APPROVED',
        'VIDEO_REJECTED',
        'VIDEO_RESUBMISSION_REQUESTED',

        'SUPPORT_RECEIVED',
        'SUPPORT_RESOLVED',

        'MEMBERSHIP_ACTIVATED',
        'MEMBERSHIP_EXPIRING_SOON',
        'MEMBERSHIP_EXPIRED',

        'SYSTEM'
    )
);

COMMIT;