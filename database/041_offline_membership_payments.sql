BEGIN;

-- ============================================================================
-- 041_offline_membership_payments.sql
--
-- Extends the existing shared payment ledger with offline-payment metadata.
--
-- The same member_payments table continues to serve:
--   - offline payments;
--   - development simulator payments;
--   - future payment-gateway payments.
--
-- Membership activation continues through the existing
-- MembershipPaymentService -> MembershipPurchaseService pipeline.
-- ============================================================================

ALTER TABLE member_payments
    ADD COLUMN IF NOT EXISTS payment_method VARCHAR(30) NULL;

ALTER TABLE member_payments
    ADD COLUMN IF NOT EXISTS recorded_by_admin_user_id BIGINT NULL;

ALTER TABLE member_payments
    ADD COLUMN IF NOT EXISTS payment_note VARCHAR(500) NULL;

ALTER TABLE member_payments
    ADD CONSTRAINT fk_member_payments_recorded_by_admin
        FOREIGN KEY (recorded_by_admin_user_id)
        REFERENCES admin_users(id);

ALTER TABLE member_payments
    ADD CONSTRAINT chk_member_payments_payment_method
        CHECK (
            payment_method IS NULL
            OR payment_method IN (
                'BANK_TRANSFER',
                'UPI',
                'CASH',
                'OTHER'
            )
        );

CREATE INDEX IF NOT EXISTS
    idx_member_payments_recorded_by_admin
ON member_payments (
    recorded_by_admin_user_id
)
WHERE recorded_by_admin_user_id IS NOT NULL;

COMMIT;