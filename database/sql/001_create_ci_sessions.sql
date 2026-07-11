CREATE TABLE IF NOT EXISTS ci_sessions
(
    id         VARCHAR(128) NOT NULL,
    ip_address VARCHAR(45)  NOT NULL,
    timestamp  BIGINT       NOT NULL DEFAULT 0,
    data       BYTEA        NOT NULL,

    CONSTRAINT pk_ci_sessions PRIMARY KEY (id)
);

CREATE INDEX IF NOT EXISTS idx_ci_sessions_timestamp
    ON ci_sessions (timestamp);