
BEGIN;
-- ============================================================================
-- 037_membership_payments.sql
--
-- Membership payment/order ledger.
--
-- This table is deliberately separate from member_memberships.
--
-- member_payments:
--     commercial/payment lifecycle
--
-- member_memberships:
--     purchased entitlement lifecycle
--
-- Development currently simulates a successful provider response.
-- Future payment-gateway integration must reuse this ledger and replace only
-- provider order creation + authoritative webhook verification.
-- ============================================================================

CREATE TABLE IF NOT EXISTS member_payments (
    id BIGSERIAL PRIMARY KEY,

    user_id BIGINT NOT NULL,
    membership_plan_id BIGINT NOT NULL,

    member_membership_id BIGINT NULL,

    transaction_reference VARCHAR(40) NOT NULL,

    provider VARCHAR(40) NOT NULL,

    provider_order_id VARCHAR(120) NULL,
    provider_payment_id VARCHAR(120) NULL,
    provider_event_id VARCHAR(120) NULL,

    status VARCHAR(30) NOT NULL,

    plan_code_snapshot VARCHAR(30) NOT NULL,
    plan_name_snapshot VARCHAR(120) NOT NULL,
    amount_paise INTEGER NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'INR',

    purchase_action VARCHAR(30) NOT NULL,

    provider_response TEXT NULL,

    paid_at TIMESTAMP NULL,
    processed_at TIMESTAMP NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_member_payments_user
        FOREIGN KEY (user_id)
        REFERENCES users(id),

    CONSTRAINT fk_member_payments_plan
        FOREIGN KEY (membership_plan_id)
        REFERENCES membership_plans(id),

    CONSTRAINT fk_member_payments_membership
        FOREIGN KEY (member_membership_id)
        REFERENCES member_memberships(id),

    CONSTRAINT uq_member_payments_transaction_reference
        UNIQUE (transaction_reference),

    CONSTRAINT chk_member_payments_amount
        CHECK (amount_paise >= 0),

    CONSTRAINT chk_member_payments_status
        CHECK (
            status IN (
                'CREATED',
                'PAID',
                'PROCESSED',
                'FAILED'
            )
        ),

    CONSTRAINT chk_member_payments_purchase_action
        CHECK (
            purchase_action IN (
                'PURCHASE',
                'RENEWAL',
                'UPGRADE'
            )
        )
);

CREATE UNIQUE INDEX IF NOT EXISTS
    uq_member_payments_provider_payment
ON member_payments (
    provider,
    provider_payment_id
)
WHERE provider_payment_id IS NOT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS
    uq_member_payments_provider_event
ON member_payments (
    provider,
    provider_event_id
)
WHERE provider_event_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS
    idx_member_payments_user_created
ON member_payments (
    user_id,
    created_at DESC
);

CREATE INDEX IF NOT EXISTS
    idx_member_payments_membership
ON member_payments (
    member_membership_id
)
WHERE member_membership_id IS NOT NULL;

COMMIT;