ALTER TABLE member_basic_details
ADD COLUMN IF NOT EXISTS about_me TEXT NULL;

COMMENT ON COLUMN member_basic_details.about_me IS
    'Member-written plain-text profile introduction, maximum 500 words.';