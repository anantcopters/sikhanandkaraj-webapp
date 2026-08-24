BEGIN;

CREATE TABLE member_partner_lifestyle_preferences (
    id BIGSERIAL PRIMARY KEY,

    user_id BIGINT NOT NULL,
    lifestyle_category_id BIGINT NOT NULL,

    is_compulsory BOOLEAN NOT NULL DEFAULT FALSE,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_partner_lifestyle_preference_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_partner_lifestyle_preference_category
        FOREIGN KEY (lifestyle_category_id)
        REFERENCES master_lifestyle_categories(id),

    CONSTRAINT uq_partner_lifestyle_preference_user_category
        UNIQUE (
            user_id,
            lifestyle_category_id
        )
);

CREATE INDEX idx_partner_lifestyle_preference_user
    ON member_partner_lifestyle_preferences(user_id);

CREATE TABLE member_partner_lifestyle_preference_options (
    id BIGSERIAL PRIMARY KEY,

    partner_lifestyle_preference_id BIGINT NOT NULL,
    lifestyle_option_id BIGINT NOT NULL,

    CONSTRAINT fk_partner_lifestyle_option_preference
        FOREIGN KEY (partner_lifestyle_preference_id)
        REFERENCES member_partner_lifestyle_preferences(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_partner_lifestyle_option_master
        FOREIGN KEY (lifestyle_option_id)
        REFERENCES master_lifestyle_options(id),

    CONSTRAINT uq_partner_lifestyle_preference_option
        UNIQUE (
            partner_lifestyle_preference_id,
            lifestyle_option_id
        )
);

CREATE INDEX idx_partner_lifestyle_option_preference
    ON member_partner_lifestyle_preference_options(
        partner_lifestyle_preference_id
    );

COMMIT;