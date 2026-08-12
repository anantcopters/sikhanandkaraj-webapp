BEGIN;

ALTER TABLE field_officers
    ADD COLUMN aadhaar_document VARCHAR(255),
    ADD COLUMN pan_document VARCHAR(255),
    ADD COLUMN cancelled_cheque_document VARCHAR(255);

COMMENT ON COLUMN field_officers.aadhaar_document
    IS 'Random stored filename of the current Aadhaar document under writable/uploads/sak_volunteer_docs.';

COMMENT ON COLUMN field_officers.pan_document
    IS 'Random stored filename of the current PAN document under writable/uploads/sak_volunteer_docs.';

COMMENT ON COLUMN field_officers.cancelled_cheque_document
    IS 'Random stored filename of the current cancelled cheque under writable/uploads/sak_volunteer_docs.';

COMMIT;