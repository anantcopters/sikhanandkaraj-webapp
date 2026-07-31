CREATE TABLE member_basic_details (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    date_of_birth DATE NULL,
    marital_status VARCHAR(30) NULL,
    height_cm SMALLINT NULL,
    mother_tongue VARCHAR(50) NULL,
    current_city VARCHAR(100) NULL,
    current_state VARCHAR(100) NULL,
    country_code CHAR(2) NOT NULL DEFAULT 'IN',
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,

    CONSTRAINT uq_member_basic_details_user_id
        UNIQUE (user_id),

    CONSTRAINT fk_member_basic_details_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);