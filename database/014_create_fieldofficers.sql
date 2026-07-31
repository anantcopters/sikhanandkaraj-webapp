BEGIN;

CREATE SEQUENCE IF NOT EXISTS field_officer_code_seq
    START WITH 1
    INCREMENT BY 1
    MINVALUE 1
    MAXVALUE 999999
    NO CYCLE;

CREATE TABLE IF NOT EXISTS field_officers
(
    id              BIGSERIAL PRIMARY KEY,
    officer_code    VARCHAR(11) NOT NULL,
    full_name       VARCHAR(150) NOT NULL,
    mobile_number   VARCHAR(15) NOT NULL,
    country_id      SMALLINT NOT NULL,
    state_id        INTEGER NOT NULL,
    city_id         INTEGER NOT NULL,
    address         VARCHAR(500) NULL,
    upi_id          VARCHAR(150) NULL,
    account_status  VARCHAR(20),
    created_by      BIGINT NOT NULL,
    activated_at    TIMESTAMP NULL,
    deactivated_at  TIMESTAMP NULL,
    updated_by      BIGINT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,

    CONSTRAINT uq_field_officers_code
        UNIQUE (officer_code),

    CONSTRAINT uq_field_officers_mobile
        UNIQUE (mobile_number),

    CONSTRAINT fk_field_officers_country
        FOREIGN KEY (country_id)
        REFERENCES master_countries(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_field_officers_state
        FOREIGN KEY (state_id)
        REFERENCES master_states(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_field_officers_city
        FOREIGN KEY (city_id)
        REFERENCES master_cities(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_field_officers_created_by
        FOREIGN KEY (created_by)
        REFERENCES admin_users(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_field_officers_updated_by
        FOREIGN KEY (updated_by)
        REFERENCES admin_users(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_field_officers_location
    ON field_officers (
        country_id,
        state_id,
        city_id
    )
    WHERE deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_field_officers_name
    ON field_officers (full_name)
    WHERE deleted_at IS NULL;

ALTER TABLE field_officers
ADD CONSTRAINT chk_field_officers_status
CHECK (
    account_status IN ('ACTIVE', 'INACTIVE')
);

CREATE INDEX IF NOT EXISTS idx_field_officers_status
ON field_officers (
    account_status,
    created_at DESC
)
WHERE deleted_at IS NULL;

ALTER TABLE field_officers
ADD CONSTRAINT uq_field_officers_officer_code
UNIQUE (officer_code);

COMMIT;