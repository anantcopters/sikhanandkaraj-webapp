BEGIN;

ALTER TABLE public.member_family_details
    ADD COLUMN IF NOT EXISTS nearest_gurudwara VARCHAR(200) NULL,
    ADD COLUMN IF NOT EXISTS reference_person_1 VARCHAR(200) NULL,
    ADD COLUMN IF NOT EXISTS reference_person_2 VARCHAR(200) NULL;

COMMENT ON COLUMN public.member_family_details.nearest_gurudwara
    IS 'Optional name and/or location of the nearest Gurudwara.';

COMMENT ON COLUMN public.member_family_details.reference_person_1
    IS 'Optional name and contact details of the first reference person.';

COMMENT ON COLUMN public.member_family_details.reference_person_2
    IS 'Optional name and contact details of the second reference person.';

COMMIT;