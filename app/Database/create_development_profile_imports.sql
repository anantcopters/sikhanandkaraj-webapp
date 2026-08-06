BEGIN;

CREATE TABLE IF NOT EXISTS development_profile_imports (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    source_key VARCHAR(150) NOT NULL,
    batch_key VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_development_profile_imports_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT uq_development_profile_imports_user UNIQUE (user_id),
    CONSTRAINT uq_development_profile_imports_source UNIQUE (source_key)
);

CREATE INDEX IF NOT EXISTS idx_development_profile_imports_batch ON development_profile_imports (batch_key, created_at);

COMMENT ON TABLE development_profile_imports IS 'Development-only ownership marker for generated member profiles.';

COMMENT ON COLUMN development_profile_imports.source_key IS 'Stable source such as development-profile:male:1.';

COMMIT;