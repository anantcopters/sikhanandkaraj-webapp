BEGIN;

ALTER TABLE prelaunch_profiles
ADD COLUMN nearest_gurudwara VARCHAR(300) NULL;

ALTER TABLE prelaunch_photos
    ALTER COLUMN medium_path DROP NOT NULL;

ALTER TABLE prelaunch_photos
    ALTER COLUMN thumbnail_path DROP NOT NULL;

COMMIT;