BEGIN;

CREATE TABLE IF NOT EXISTS master_lifestyle_categories (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    name VARCHAR(100) NOT NULL,
    icon_class VARCHAR(60) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_master_lifestyle_categories_code
        UNIQUE (code),

    CONSTRAINT uq_master_lifestyle_categories_name
        UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS
    idx_master_lifestyle_categories_active_order
ON master_lifestyle_categories (
    is_active,
    display_order,
    id
);

CREATE TABLE IF NOT EXISTS master_lifestyle_options (
    id SERIAL PRIMARY KEY,
    lifestyle_category_id SMALLINT NOT NULL,
    code VARCHAR(60) NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_master_lifestyle_options_category
        FOREIGN KEY (lifestyle_category_id)
        REFERENCES master_lifestyle_categories(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT uq_master_lifestyle_options_category_code
        UNIQUE (lifestyle_category_id, code),

    CONSTRAINT uq_master_lifestyle_options_category_name
        UNIQUE (lifestyle_category_id, name)
);

CREATE INDEX IF NOT EXISTS
    idx_master_lifestyle_options_category_active_order
ON master_lifestyle_options (
    lifestyle_category_id,
    is_active,
    display_order,
    id
);

CREATE TABLE IF NOT EXISTS member_lifestyle_options (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    lifestyle_option_id INTEGER NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_member_lifestyle_options_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,

    CONSTRAINT fk_member_lifestyle_options_option
        FOREIGN KEY (lifestyle_option_id)
        REFERENCES master_lifestyle_options(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT uq_member_lifestyle_user_option
        UNIQUE (user_id, lifestyle_option_id)
);

CREATE INDEX IF NOT EXISTS idx_member_lifestyle_options_user
ON member_lifestyle_options (user_id);

CREATE INDEX IF NOT EXISTS idx_member_lifestyle_options_option
ON member_lifestyle_options (lifestyle_option_id);

COMMIT;

BEGIN;

INSERT INTO master_lifestyle_categories (
    code,
    name,
    icon_class,
    display_order
)
VALUES
    ('HOBBIES_INTERESTS', 'Hobbies & Interests', 'ri-palette-line', 10),
    ('MUSIC', 'Music', 'ri-music-2-line', 20),
    ('READING', 'Reading', 'ri-book-open-line', 30),
    ('MOVIES_TV', 'Movies & TV Shows', 'ri-movie-2-line', 40),
    ('SPORTS_FITNESS', 'Sports & Fitness', 'ri-run-line', 50),
    ('FOOD', 'Food', 'ri-restaurant-line', 60)
ON CONFLICT (code)
DO UPDATE SET
    name = EXCLUDED.name,
    icon_class = EXCLUDED.icon_class,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO master_lifestyle_options (
    lifestyle_category_id,
    code,
    name,
    display_order
)
SELECT
    category.id,
    seed.code,
    seed.name,
    seed.display_order
FROM master_lifestyle_categories AS category
JOIN (
    VALUES
        ('HOBBIES_INTERESTS', 'TRAVELLING', 'Travelling', 10),
        ('HOBBIES_INTERESTS', 'PHOTOGRAPHY', 'Photography', 20),
        ('HOBBIES_INTERESTS', 'COOKING', 'Cooking', 30),
        ('HOBBIES_INTERESTS', 'GARDENING', 'Gardening', 40),
        ('HOBBIES_INTERESTS', 'PAINTING', 'Painting', 50),
        ('HOBBIES_INTERESTS', 'DANCING', 'Dancing', 60),
        ('HOBBIES_INTERESTS', 'VOLUNTEERING', 'Volunteering', 70),
        ('HOBBIES_INTERESTS', 'PET_LOVER', 'Pet Lover', 80),
        ('HOBBIES_INTERESTS', 'TECHNOLOGY', 'Technology', 90),
        ('HOBBIES_INTERESTS', 'NATURE', 'Nature', 100),

        ('MUSIC', 'PUNJABI', 'Punjabi', 10),
        ('MUSIC', 'GURBANI_KIRTAN', 'Gurbani & Kirtan', 20),
        ('MUSIC', 'BOLLYWOOD', 'Bollywood', 30),
        ('MUSIC', 'CLASSICAL', 'Classical', 40),
        ('MUSIC', 'SUFI', 'Sufi', 50),
        ('MUSIC', 'POP', 'Pop', 60),
        ('MUSIC', 'ROCK', 'Rock', 70),
        ('MUSIC', 'INSTRUMENTAL', 'Instrumental', 80),
        ('MUSIC', 'FOLK', 'Folk', 90),
        ('MUSIC', 'JAZZ', 'Jazz', 100),

        ('READING', 'FICTION', 'Fiction', 10),
        ('READING', 'NON_FICTION', 'Non-fiction', 20),
        ('READING', 'BIOGRAPHIES', 'Biographies', 30),
        ('READING', 'HISTORY', 'History', 40),
        ('READING', 'SPIRITUAL', 'Spiritual', 50),
        ('READING', 'POETRY', 'Poetry', 60),
        ('READING', 'BUSINESS', 'Business', 70),
        ('READING', 'SCIENCE_TECHNOLOGY', 'Science & Technology', 80),
        ('READING', 'NEWSPAPERS', 'Newspapers', 90),
        ('READING', 'COMICS', 'Comics', 100),

        ('MOVIES_TV', 'BOLLYWOOD', 'Bollywood', 10),
        ('MOVIES_TV', 'HOLLYWOOD', 'Hollywood', 20),
        ('MOVIES_TV', 'PUNJABI_CINEMA', 'Punjabi Cinema', 30),
        ('MOVIES_TV', 'COMEDY', 'Comedy', 40),
        ('MOVIES_TV', 'ACTION', 'Action', 50),
        ('MOVIES_TV', 'ROMANCE', 'Romance', 60),
        ('MOVIES_TV', 'THRILLER', 'Thriller', 70),
        ('MOVIES_TV', 'DOCUMENTARIES', 'Documentaries', 80),
        ('MOVIES_TV', 'WEB_SERIES', 'Web Series', 90),
        ('MOVIES_TV', 'SPORTS_SHOWS', 'Sports Shows', 100),

        ('SPORTS_FITNESS', 'GYM', 'Gym', 10),
        ('SPORTS_FITNESS', 'YOGA', 'Yoga', 20),
        ('SPORTS_FITNESS', 'WALKING', 'Walking', 30),
        ('SPORTS_FITNESS', 'RUNNING', 'Running', 40),
        ('SPORTS_FITNESS', 'CRICKET', 'Cricket', 50),
        ('SPORTS_FITNESS', 'BADMINTON', 'Badminton', 60),
        ('SPORTS_FITNESS', 'FOOTBALL', 'Football', 70),
        ('SPORTS_FITNESS', 'CYCLING', 'Cycling', 80),
        ('SPORTS_FITNESS', 'SWIMMING', 'Swimming', 90),
        ('SPORTS_FITNESS', 'MEDITATION', 'Meditation', 100),

        ('FOOD', 'PUNJABI', 'Punjabi', 10),
        ('FOOD', 'NORTH_INDIAN', 'North Indian', 20),
        ('FOOD', 'SOUTH_INDIAN', 'South Indian', 30),
        ('FOOD', 'CHINESE', 'Chinese', 40),
        ('FOOD', 'ITALIAN', 'Italian', 50),
        ('FOOD', 'CONTINENTAL', 'Continental', 60),
        ('FOOD', 'STREET_FOOD', 'Street Food', 70),
        ('FOOD', 'HOME_COOKED', 'Home-cooked Food', 80),
        ('FOOD', 'VEGETARIAN', 'Vegetarian', 90),
        ('FOOD', 'NON_VEGETARIAN', 'Non-vegetarian', 100)
) AS seed(category_code, code, name, display_order)
    ON category.code = seed.category_code
ON CONFLICT (lifestyle_category_id, code)
DO UPDATE SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

COMMIT;