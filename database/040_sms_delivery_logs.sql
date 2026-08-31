BEGIN;

/*
|--------------------------------------------------------------------------
| SMS Delivery Logs
|--------------------------------------------------------------------------
|
| Operational audit of application SMS delivery attempts.
|
| IMPORTANT:
|
| 1. OTP values and complete SMS message bodies are deliberately NOT stored.
| 2. recipient_mobile stores the normalized destination because operations
|    and abuse analysis require a stable recipient key. The Admin UI masks it.
| 3. SENT means accepted by the configured application SMS provider.
|    It does NOT mean handset delivery. Provider DLR belongs to Phase 4F.
| 4. This table is channel-delivery state. It does not replace
|    contact_verifications or other business-domain records.
|
*/

CREATE TABLE IF NOT EXISTS sms_delivery_logs
(
    id BIGSERIAL PRIMARY KEY,

    message_type VARCHAR(50) NOT NULL,

    recipient_mobile VARCHAR(20) NOT NULL,

    provider VARCHAR(30) NOT NULL,

    provider_message_id VARCHAR(255) NULL,

    status VARCHAR(20) NOT NULL,

    error_message VARCHAR(500) NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    sent_at TIMESTAMP NULL,

    failed_at TIMESTAMP NULL,

    CONSTRAINT chk_sms_delivery_log_status
        CHECK
        (
            status IN
            (
                'SENT',
                'FAILED'
            )
        )
);


/*
|--------------------------------------------------------------------------
| Operational listing
|--------------------------------------------------------------------------
*/

CREATE INDEX IF NOT EXISTS
    idx_sms_delivery_logs_status_created
ON
    sms_delivery_logs
    (
        status,
        created_at DESC
    );


/*
|--------------------------------------------------------------------------
| Recipient history
|--------------------------------------------------------------------------
|
| Useful for:
|
| - operational investigation;
| - SMS volume review;
| - future abuse correlation.
|
*/

CREATE INDEX IF NOT EXISTS
    idx_sms_delivery_logs_recipient_created
ON
    sms_delivery_logs
    (
        recipient_mobile,
        created_at DESC
    );


/*
|--------------------------------------------------------------------------
| Message type reporting
|--------------------------------------------------------------------------
*/

CREATE INDEX IF NOT EXISTS
    idx_sms_delivery_logs_type_created
ON
    sms_delivery_logs
    (
        message_type,
        created_at DESC
    );

COMMIT;