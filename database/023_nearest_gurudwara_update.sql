BEGIN;

ALTER TABLE prelaunch_profiles
ADD COLUMN nearest_gurudwara VARCHAR(300) NULL;

COMMIT;