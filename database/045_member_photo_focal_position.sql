BEGIN;

ALTER TABLE member_photos
    ADD COLUMN focal_x SMALLINT NOT NULL DEFAULT 50,
    ADD COLUMN focal_y SMALLINT NOT NULL DEFAULT 20;

ALTER TABLE member_photos
    ADD CONSTRAINT member_photos_focal_x_check
        CHECK (focal_x BETWEEN 0 AND 100),
    ADD CONSTRAINT member_photos_focal_y_check
        CHECK (focal_y BETWEEN 0 AND 100);

COMMIT;