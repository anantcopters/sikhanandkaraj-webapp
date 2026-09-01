BEGIN;


CREATE TABLE short_urls (
    id BIGSERIAL PRIMARY KEY,

    short_code VARCHAR(6) NOT NULL,

    destination_url VARCHAR(2048) NOT NULL,

    /*
     * SHA-256 of the normalized destination URL.
     *
     * Used to guarantee that the same destination URL has only
     * one short URL without placing a UNIQUE constraint on the
     * full 2048-character URL.
     */
    destination_hash CHAR(64) NOT NULL,

    created_by_admin_id BIGINT NOT NULL,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_short_urls_created_by_admin
        FOREIGN KEY (created_by_admin_id)
        REFERENCES admin_users (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uq_short_urls_short_code
        UNIQUE (short_code),

    CONSTRAINT uq_short_urls_destination_hash
        UNIQUE (destination_hash),

    /*
     * Short URLs are exactly six uppercase alphanumeric characters.
     *
     * Examples:
     *
     * A7B2K9
     * 2F8KLM
     */
    CONSTRAINT chk_short_urls_short_code
        CHECK (
            short_code ~ '^[A-Z0-9]{6}$'
        ),

    /*
     * destination_hash must always contain a lowercase
     * hexadecimal SHA-256 value.
     */
    CONSTRAINT chk_short_urls_destination_hash
        CHECK (
            destination_hash ~ '^[0-9a-f]{64}$'
        ),

    /*
     * Do not allow blank destination URLs.
     *
     * Full URL validation and same-application-host validation
     * remain application responsibilities because the valid host
     * differs between local, QA and production environments.
     */
    CONSTRAINT chk_short_urls_destination_url
        CHECK (
            BTRIM(destination_url) <> ''
        )
);


CREATE INDEX idx_short_urls_created_by_admin
    ON short_urls (
        created_by_admin_id,
        created_at DESC,
        id DESC
    );


CREATE INDEX idx_short_urls_created_at
    ON short_urls (
        created_at DESC,
        id DESC
    );


COMMENT ON TABLE short_urls IS
    'Persistent SikhanandKaraj short URLs used for DLT, SMS and other application communication.';


COMMENT ON COLUMN short_urls.short_code IS
    'Six-character uppercase alphanumeric code used by the public /ISAK/{code} route.';


COMMENT ON COLUMN short_urls.destination_url IS
    'Normalized SikhanandKaraj application URL to which the public short URL redirects.';


COMMENT ON COLUMN short_urls.destination_hash IS
    'SHA-256 hash of the normalized destination URL used to prevent duplicate short URLs for the same destination.';


COMMENT ON COLUMN short_urls.created_by_admin_id IS
    'Super administrator who originally created the short URL.';


COMMIT;