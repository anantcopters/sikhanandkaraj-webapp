ALTER TABLE users
ADD COLUMN prelaunch_profile_id BIGINT NULL;

ALTER TABLE users
ADD CONSTRAINT fk_users_prelaunch_profile
FOREIGN KEY (prelaunch_profile_id)
REFERENCES prelaunch_profiles(id)
ON UPDATE RESTRICT
ON DELETE RESTRICT;

CREATE UNIQUE INDEX uq_users_prelaunch_profile_id
ON users(prelaunch_profile_id)
WHERE prelaunch_profile_id IS NOT NULL;

ALTER TABLE prelaunch_profiles
ADD COLUMN migrated_user_id BIGINT NULL,
ADD COLUMN migrated_at TIMESTAMP NULL,
ADD COLUMN local_photos_cleanup_after TIMESTAMP NULL,
ADD COLUMN local_photos_cleaned_at TIMESTAMP NULL,
ADD COLUMN migration_error TEXT NULL;


ALTER TABLE prelaunch_profiles
ADD CONSTRAINT fk_prelaunch_profiles_migrated_user
FOREIGN KEY (migrated_user_id)
REFERENCES users(id)
ON UPDATE RESTRICT
ON DELETE RESTRICT;

CREATE INDEX idx_prelaunch_profiles_cleanup
ON prelaunch_profiles
(
    local_photos_cleanup_after,
    local_photos_cleaned_at
)
WHERE migrated_user_id IS NOT NULL
AND local_photos_cleaned_at IS NULL;

ALTER TABLE member_photos
ADD COLUMN prelaunch_photo_id BIGINT NULL;

ALTER TABLE member_photos
ADD CONSTRAINT fk_member_photos_prelaunch_photo
FOREIGN KEY (prelaunch_photo_id)
REFERENCES prelaunch_photos(id)
ON UPDATE RESTRICT
ON DELETE RESTRICT;

CREATE UNIQUE INDEX uq_member_photos_prelaunch_photo
ON member_photos(prelaunch_photo_id)
WHERE prelaunch_photo_id IS NOT NULL;

CREATE INDEX idx_user_contacts_mobile
ON user_contacts
(
    contact_type,
    normalized_value
);

CREATE INDEX idx_user_contacts_email
ON user_contacts
(
    contact_type,
    normalized_value
);
