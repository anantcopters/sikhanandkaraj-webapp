CREATE TABLE IF NOT EXISTS master_family_values
(
    id SMALLSERIAL PRIMARY KEY,

    code VARCHAR(40) NOT NULL,
    name VARCHAR(80) NOT NULL,

    display_order SMALLINT NOT NULL DEFAULT 0,

    is_active BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_master_family_values_code
        UNIQUE(code),

    CONSTRAINT uq_master_family_values_name
        UNIQUE(name)
);

CREATE INDEX IF NOT EXISTS idx_master_family_values_active_order
ON master_family_values
(
    is_active,
    display_order
);

INSERT INTO master_family_values
(
    code,
    name,
    display_order
)
VALUES
('ORTHODOX','Orthodox',10),
('TRADITIONAL','Traditional',20),
('MODERATE','Moderate',30),
('LIBERAL','Liberal',40)
ON CONFLICT (code)
DO NOTHING;

CREATE TABLE IF NOT EXISTS master_family_types
(
    id SMALLSERIAL PRIMARY KEY,

    code VARCHAR(40) NOT NULL,
    name VARCHAR(80) NOT NULL,

    display_order SMALLINT NOT NULL DEFAULT 0,

    is_active BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_master_family_types_code
        UNIQUE(code),

    CONSTRAINT uq_master_family_types_name
        UNIQUE(name)
);

CREATE INDEX IF NOT EXISTS idx_master_family_types_active_order
ON master_family_types
(
    is_active,
    display_order
);

INSERT INTO master_family_types
(
    code,
    name,
    display_order
)
VALUES
('JOINT_FAMILY','Joint Family',10),
('NUCLEAR_FAMILY','Nuclear Family',20),
('OTHERS','Others',30)
ON CONFLICT (code)
DO NOTHING;

CREATE TABLE IF NOT EXISTS master_family_statuses
(
    id SMALLSERIAL PRIMARY KEY,

    code VARCHAR(40) NOT NULL,
    name VARCHAR(80) NOT NULL,

    display_order SMALLINT NOT NULL DEFAULT 0,

    is_active BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_master_family_statuses_code
        UNIQUE(code),

    CONSTRAINT uq_master_family_statuses_name
        UNIQUE(name)
);

CREATE INDEX IF NOT EXISTS idx_master_family_statuses_active_order
ON master_family_statuses
(
    is_active,
    display_order
);

INSERT INTO master_family_statuses
(
    code,
    name,
    display_order
)
VALUES
('MIDDLE_CLASS','Middle Class',10),
('UPPER_MIDDLE_CLASS','Upper Middle Class',20),
('HIGH_CLASS','High Class',30),
('RICH_AFFLUENT','Rich / Affluent',40)
ON CONFLICT (code)
DO NOTHING;

ALTER TABLE member_family_details
ADD COLUMN IF NOT EXISTS family_value_id SMALLINT,
ADD COLUMN IF NOT EXISTS family_type_id SMALLINT,
ADD COLUMN IF NOT EXISTS family_status_id SMALLINT,
ADD COLUMN IF NOT EXISTS community_id INTEGER,
ADD COLUMN IF NOT EXISTS subcommunity_id INTEGER;

UPDATE member_family_details mf
SET family_value_id = mv.id
FROM master_family_values mv
WHERE mv.code = mf.family_value;

UPDATE member_family_details mf
SET family_type_id = mt.id
FROM master_family_types mt
WHERE mt.code = mf.family_type;

UPDATE member_family_details mf
SET family_status_id = ms.id
FROM master_family_statuses ms
WHERE ms.code = mf.family_status;

UPDATE member_family_details mf
SET
    community_id = sr.community_id,
    subcommunity_id = sr.subcommunity_id
FROM member_sikh_religious_details sr
WHERE sr.user_id = mf.user_id;

ALTER TABLE member_family_details
ADD CONSTRAINT fk_member_family_value
FOREIGN KEY (family_value_id)
REFERENCES master_family_values(id);

ALTER TABLE member_family_details
ADD CONSTRAINT fk_member_family_type
FOREIGN KEY (family_type_id)
REFERENCES master_family_types(id);

ALTER TABLE member_family_details
ADD CONSTRAINT fk_member_family_status
FOREIGN KEY (family_status_id)
REFERENCES master_family_statuses(id);

ALTER TABLE member_family_details
ADD CONSTRAINT fk_member_family_community
FOREIGN KEY (community_id)
REFERENCES master_sikh_communities(id);

ALTER TABLE member_family_details
ADD CONSTRAINT fk_member_family_subcommunity
FOREIGN KEY (subcommunity_id)
REFERENCES master_sikh_subcommunities(id);

CREATE INDEX IF NOT EXISTS idx_member_family_value
ON member_family_details(family_value_id);

CREATE INDEX IF NOT EXISTS idx_member_family_type
ON member_family_details(family_type_id);

CREATE INDEX IF NOT EXISTS idx_member_family_status
ON member_family_details(family_status_id);

CREATE INDEX IF NOT EXISTS idx_member_family_community
ON member_family_details(community_id);

CREATE INDEX IF NOT EXISTS idx_member_family_subcommunity
ON member_family_details(subcommunity_id);

ALTER TABLE member_family_details
DROP COLUMN family_value,
DROP COLUMN family_type,
DROP COLUMN family_status;