BEGIN;

-- ============================================================
-- Highest Education master
-- ============================================================

CREATE TABLE IF NOT EXISTS master_educations (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(120) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_master_educations_code
        UNIQUE (code),

    CONSTRAINT uq_master_educations_name
        UNIQUE (name),

    CONSTRAINT chk_master_educations_code_not_blank
        CHECK (BTRIM(code) <> ''),

    CONSTRAINT chk_master_educations_name_not_blank
        CHECK (BTRIM(name) <> '')
);

CREATE INDEX IF NOT EXISTS idx_master_educations_active_order
    ON master_educations (
        is_active,
        display_order,
        name
    );


-- ============================================================
-- Occupation master
-- ============================================================

CREATE TABLE master_occupation_categories (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(60) NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_master_occupation_categories_code UNIQUE (code),
    CONSTRAINT uq_master_occupation_categories_name UNIQUE (name),
    CONSTRAINT chk_master_occupation_categories_code_not_blank CHECK (BTRIM(code) <> ''),
    CONSTRAINT chk_master_occupation_categories_name_not_blank CHECK (BTRIM(name) <> ''),
    CONSTRAINT chk_master_occupation_categories_display_order CHECK (display_order >= 0)
);

CREATE INDEX idx_master_occupation_categories_active_order ON master_occupation_categories (
    is_active,
    display_order,
    name
);

CREATE TABLE master_occupations (
    id SMALLSERIAL PRIMARY KEY,
    category_id SMALLINT NOT NULL,
    code VARCHAR(80) NOT NULL,
    name VARCHAR(120) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_master_occupations_category
        FOREIGN KEY (category_id)
        REFERENCES master_occupation_categories(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT uq_master_occupations_code
        UNIQUE (code),

/*
 * The same visible name may exist in different categories.
 * Example:
 *   Engineering -> Designer
 *   Media & Entertainment -> Designer
 */
CONSTRAINT uq_master_occupations_category_name
        UNIQUE (category_id, name),

    CONSTRAINT chk_master_occupations_code_not_blank
        CHECK (BTRIM(code) <> ''),

    CONSTRAINT chk_master_occupations_name_not_blank
        CHECK (BTRIM(name) <> ''),

    CONSTRAINT chk_master_occupations_display_order
        CHECK (display_order >= 0)
);

CREATE INDEX idx_master_occupations_category_active_order ON master_occupations (
    category_id,
    is_active,
    display_order,
    name
);

CREATE INDEX idx_master_occupations_active_order ON master_occupations (
    is_active,
    display_order,
    name
);


-- ============================================================
-- Annual Income master
--
-- min_amount and max_amount are stored in INR.
-- max_amount NULL means no upper limit.
-- ============================================================

CREATE TABLE IF NOT EXISTS master_annual_incomes (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    min_amount BIGINT NOT NULL DEFAULT 0,
    max_amount BIGINT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_master_annual_incomes_code
        UNIQUE (code),

    CONSTRAINT chk_master_annual_incomes_code_not_blank
        CHECK (BTRIM(code) <> ''),

    CONSTRAINT chk_master_annual_incomes_display_name_not_blank
        CHECK (BTRIM(display_name) <> ''),

    CONSTRAINT chk_master_annual_incomes_min_amount
        CHECK (min_amount >= 0),

    CONSTRAINT chk_master_annual_incomes_range
        CHECK (
            max_amount IS NULL
            OR max_amount > min_amount
        )
);

CREATE INDEX IF NOT EXISTS idx_master_annual_incomes_active_order
    ON master_annual_incomes (
        is_active,
        display_order
    );


-- ============================================================
-- Member Education & Profession details
-- ============================================================

CREATE TABLE IF NOT EXISTS member_education_profession_details (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,

    highest_education_id SMALLINT NOT NULL,
    education_detail VARCHAR(500) NULL,
    college_institution VARCHAR(200) NULL,

    employed_in VARCHAR(30) NOT NULL,
    occupation_id SMALLINT NOT NULL,
    occupation_detail VARCHAR(500) NULL,
    organization VARCHAR(200) NULL,

    annual_income_id SMALLINT NULL,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_member_education_profession_user
        UNIQUE (user_id),

    CONSTRAINT fk_member_education_profession_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,

    CONSTRAINT fk_member_education_profession_education
        FOREIGN KEY (highest_education_id)
        REFERENCES master_educations(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_education_profession_occupation
        FOREIGN KEY (occupation_id)
        REFERENCES master_occupations(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_education_profession_income
        FOREIGN KEY (annual_income_id)
        REFERENCES master_annual_incomes(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT chk_member_education_detail_not_blank
        CHECK (
            education_detail IS NULL
            OR BTRIM(education_detail) <> ''
        ),

    CONSTRAINT chk_member_college_not_blank
        CHECK (
            college_institution IS NULL
            OR BTRIM(college_institution) <> ''
        ),

    CONSTRAINT chk_member_occupation_detail_not_blank
        CHECK (
            occupation_detail IS NULL
            OR BTRIM(occupation_detail) <> ''
        ),

    CONSTRAINT chk_member_organization_not_blank
        CHECK (
            organization IS NULL
            OR BTRIM(organization) <> ''
        ),

    CONSTRAINT chk_member_employed_in
        CHECK (
            employed_in IN (
                'GOVERNMENT_PSU',
                'PRIVATE',
                'BUSINESS',
                'DEFENSE',
                'SELF_EMPLOYED',
                'NOT_WORKING'
            )
        )
);

CREATE INDEX IF NOT EXISTS idx_member_education_profession_education
    ON member_education_profession_details (
        highest_education_id
    );

CREATE INDEX IF NOT EXISTS idx_member_education_profession_occupation
    ON member_education_profession_details (
        occupation_id
    );

CREATE INDEX IF NOT EXISTS idx_member_education_profession_income
    ON member_education_profession_details (
        annual_income_id
    );

CREATE INDEX IF NOT EXISTS idx_member_education_profession_employed
    ON member_education_profession_details (
        employed_in
    );


-- ============================================================
-- Seed: Education
-- ============================================================

INSERT INTO master_educations (
    code,
    name,
    display_order
)
VALUES
    ('HIGH_SCHOOL', 'High School', 10),
    ('DIPLOMA', 'Diploma', 20),
    ('BACHELORS', 'Bachelor''s Degree', 30),
    ('MASTERS', 'Master''s Degree', 40),
    ('DOCTORATE', 'Doctorate / PhD', 50)
ON CONFLICT (code) DO UPDATE
SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

-- ============================================================
-- Seed occupation categories
-- ============================================================

INSERT INTO
    master_occupation_categories (id, code, name, display_order)
VALUES (
        1,
        'ADMINISTRATION',
        'Administration',
        10
    ),
    (
        2,
        'AGRICULTURE',
        'Agriculture',
        20
    ),
    (3, 'AIRLINE', 'Airline', 30),
    (
        4,
        'ARCHITECTURE_DESIGN',
        'Architecture & Design',
        40
    ),
    (
        5,
        'BANKING_FINANCE',
        'Banking & Finance',
        50
    ),
    (
        6,
        'BEAUTY_FASHION',
        'Beauty & Fashion',
        60
    ),
    (
        7,
        'BPO_CUSTOMER_SERVICE',
        'BPO & Customer Service',
        70
    ),
    (
        8,
        'CIVIL_SERVICES',
        'Civil Services',
        80
    ),
    (
        9,
        'CORPORATE_PROFESSIONALS',
        'Corporate Professionals',
        90
    ),
    (10, 'DEFENCE', 'Defence', 100),
    (
        11,
        'EDUCATION_TRAINING',
        'Education & Training',
        110
    ),
    (
        12,
        'ENGINEERING',
        'Engineering',
        120
    ),
    (
        13,
        'HOSPITALITY',
        'Hospitality',
        130
    ),
    (
        14,
        'IT_SOFTWARE',
        'IT & Software',
        140
    ),
    (15, 'LEGAL', 'Legal', 150),
    (
        16,
        'POLICE_LAW_ENFORCEMENT',
        'Police / Law Enforcement',
        160
    ),
    (
        17,
        'MEDICAL_HEALTHCARE',
        'Medical & Healthcare',
        170
    ),
    (
        18,
        'MEDIA_ENTERTAINMENT',
        'Media & Entertainment',
        180
    ),
    (
        19,
        'MERCHANT_NAVY',
        'Merchant Navy',
        190
    ),
    (
        20,
        'SCIENTIST',
        'Scientist',
        200
    ),
    (
        21,
        'SENIOR_MANAGEMENT',
        'Senior Management',
        210
    ),
    (22, 'OTHERS', 'Others', 220),
    (23, 'DOCTOR', 'Doctor', 230);


-- ============================================================
-- Seed occupations
-- ============================================================

INSERT INTO
    master_occupations (
        id,
        category_id,
        code,
        name,
        display_order
    )
VALUES

-- Administration
(
    49,
    1,
    'MANAGER',
    'Manager',
    10
),
(
    48,
    1,
    'SUPERVISOR',
    'Supervisor',
    20
),
(
    47,
    1,
    'OFFICER',
    'Officer',
    30
),
(
    39,
    1,
    'ADMINISTRATIVE_PROFESSIONAL',
    'Administrative Professional',
    40
),
(
    50,
    1,
    'EXECUTIVE',
    'Executive',
    50
),
(46, 1, 'CLERK', 'Clerk', 60),
(
    63,
    1,
    'HUMAN_RESOURCES_PROFESSIONAL',
    'Human Resources Professional',
    70
),
(
    78,
    1,
    'SECRETARY_FRONT_OFFICE',
    'Secretary / Front Office',
    80
),

-- Agriculture
(
    37,
    2,
    'AGRICULTURE_FARMING_PROFESSIONAL',
    'Agriculture & Farming Professional',
    10
),
(
    81,
    2,
    'HORTICULTURIST',
    'Horticulturist',
    20
),

-- Airline
(30, 3, 'PILOT', 'Pilot', 10),
(
    28,
    3,
    'AIR_HOSTESS_FLIGHT_ATTENDANT',
    'Air Hostess / Flight Attendant',
    20
),
(
    29,
    3,
    'AIRLINE_PROFESSIONAL',
    'Airline Professional',
    30
),

-- Architecture & Design
(
    19,
    4,
    'ARCHITECT',
    'Architect',
    10
),
(
    20,
    4,
    'INTERIOR_DESIGNER',
    'Interior Designer',
    20
),

-- Banking & Finance
(
    7,
    5,
    'CHARTERED_ACCOUNTANT',
    'Chartered Accountant',
    10
),
(
    10,
    5,
    'COMPANY_SECRETARY',
    'Company Secretary',
    20
),
(
    8,
    5,
    'ACCOUNTS_FINANCE_PROFESSIONAL',
    'Accounts / Finance Professional',
    30
),
(
    16,
    5,
    'BANKING_PROFESSIONAL',
    'Banking Professional',
    40
),
(
    9,
    5,
    'AUDITOR',
    'Auditor',
    50
),
(
    69,
    5,
    'FINANCIAL_ACCOUNTANT',
    'Financial Accountant',
    60
),
(
    64,
    5,
    'FINANCIAL_ANALYST_PLANNING',
    'Financial Analyst / Planning',
    70
),
(
    87,
    5,
    'INVESTMENT_PROFESSIONAL',
    'Investment Professional',
    80
),

-- Beauty & Fashion
(
    25,
    6,
    'FASHION_DESIGNER',
    'Fashion Designer',
    10
),
(
    33,
    6,
    'BEAUTICIAN',
    'Beautician',
    20
),
(
    82,
    6,
    'HAIR_STYLIST',
    'Hair Stylist',
    30
),
(
    83,
    6,
    'JEWELLERY_DESIGNER',
    'Jewellery Designer',
    40
),
(
    84,
    6,
    'DESIGNER_OTHERS',
    'Designer (Others)',
    50
),
(
    85,
    6,
    'MAKEUP_ARTIST',
    'Makeup Artist',
    60
),

-- BPO & Customer Service
(
    86,
    7,
    'BPO_KPO_ITES_PROFESSIONAL',
    'BPO / KPO / ITES Professional',
    10
),
(
    40,
    7,
    'CUSTOMER_SERVICE_PROFESSIONAL',
    'Customer Service Professional',
    20
),

-- Civil Services
(
    52,
    8,
    'CIVIL_SERVICES',
    'Civil Services (IAS / IPS / IRS / IES / IFS)',
    10
),

-- Corporate Professionals
(
    70,
    9,
    'ANALYST',
    'Analyst',
    10
),
(
    45,
    9,
    'CONSULTANT',
    'Consultant',
    20
),
(
    88,
    9,
    'CORPORATE_COMMUNICATION',
    'Corporate Communication',
    30
),
(
    89,
    9,
    'CORPORATE_PLANNING',
    'Corporate Planning',
    40
),
(
    42,
    9,
    'MARKETING_PROFESSIONAL',
    'Marketing Professional',
    50
),
(
    90,
    9,
    'OPERATIONS_MANAGEMENT',
    'Operations Management',
    60
),
(
    43,
    9,
    'SALES_PROFESSIONAL',
    'Sales Professional',
    70
),
(
    91,
    9,
    'SENIOR_MANAGER_MANAGER',
    'Senior Manager / Manager',
    80
),
(
    92,
    9,
    'SUBJECT_MATTER_EXPERT',
    'Subject Matter Expert',
    90
),
(
    93,
    9,
    'BUSINESS_DEVELOPMENT_PROFESSIONAL',
    'Business Development Professional',
    100
),
(
    94,
    9,
    'CONTENT_WRITER',
    'Content Writer',
    110
),

-- Defence
(53, 10, 'ARMY', 'Army', 10),
(54, 10, 'NAVY', 'Navy', 20),
(
    96,
    10,
    'DEFENCE_SERVICES_OTHERS',
    'Defence Services (Others)',
    30
),
(
    55,
    10,
    'AIR_FORCE',
    'Air Force',
    40
),
(
    97,
    10,
    'PARAMILITARY',
    'Paramilitary',
    50
),

-- Education & Training
(
    5,
    11,
    'PROFESSOR_LECTURER',
    'Professor / Lecturer',
    10
),
(
    4,
    11,
    'TEACHING_ACADEMICIAN',
    'Teaching / Academician',
    20
),
(
    6,
    11,
    'EDUCATION_PROFESSIONAL',
    'Education Professional',
    30
),
(
    111,
    11,
    'TRAINING_PROFESSIONAL',
    'Training Professional',
    40
),
(
    112,
    11,
    'RESEARCH_ASSISTANT',
    'Research Assistant',
    50
),
(
    113,
    11,
    'RESEARCH_SCHOLAR',
    'Research Scholar',
    60
),

-- Engineering
(
    114,
    12,
    'CIVIL_ENGINEER',
    'Civil Engineer',
    10
),
(
    115,
    12,
    'ELECTRONICS_TELECOM_ENGINEER',
    'Electronics / Telecom Engineer',
    20
),
(
    116,
    12,
    'MECHANICAL_PRODUCTION_ENGINEER',
    'Mechanical / Production Engineer',
    30
),
(
    117,
    12,
    'QA_ENGINEER_NON_IT',
    'Quality Assurance Engineer - Non IT',
    40
),
(
    3,
    12,
    'ENGINEER_NON_IT',
    'Engineer - Non IT',
    50
),
(
    65,
    12,
    'ENGINEERING_DESIGNER',
    'Designer',
    60
),
(
    118,
    12,
    'PRODUCT_MANAGER_NON_IT',
    'Product Manager - Non IT',
    70
),
(
    77,
    12,
    'PROJECT_MANAGER_NON_IT',
    'Project Manager - Non IT',
    80
),

-- Hospitality
(
    34,
    13,
    'HOTEL_HOSPITALITY_PROFESSIONAL',
    'Hotel / Hospitality Professional',
    10
),
(
    129,
    13,
    'RESTAURANT_CATERING_PROFESSIONAL',
    'Restaurant / Catering Professional',
    20
),
(
    130,
    13,
    'CHEF_COOK',
    'Chef / Cook',
    30
),

-- IT & Software
(
    1,
    14,
    'SOFTWARE_PROFESSIONAL',
    'Software Professional',
    10
),
(
    2,
    14,
    'HARDWARE_PROFESSIONAL',
    'Hardware Professional',
    20
),
(
    74,
    14,
    'PRODUCT_MANAGER',
    'Product Manager',
    30
),
(
    76,
    14,
    'PROJECT_MANAGER',
    'Project Manager',
    40
),
(
    75,
    14,
    'PROGRAM_MANAGER',
    'Program Manager',
    50
),
(
    119,
    14,
    'ANIMATOR',
    'Animator',
    60
),
(
    120,
    14,
    'CYBER_NETWORK_SECURITY',
    'Cyber / Network Security',
    70
),
(
    121,
    14,
    'UI_UX_DESIGNER',
    'UI / UX Designer',
    80
),
(
    122,
    14,
    'WEB_GRAPHIC_DESIGNER',
    'Web / Graphic Designer',
    90
),
(
    123,
    14,
    'SOFTWARE_CONSULTANT',
    'Software Consultant',
    100
),
(
    124,
    14,
    'DATA_ANALYST',
    'Data Analyst',
    110
),
(
    125,
    14,
    'DATA_SCIENTIST',
    'Data Scientist',
    120
),
(
    126,
    14,
    'NETWORK_ENGINEER',
    'Network Engineer',
    130
),
(
    128,
    14,
    'QUALITY_ASSURANCE_ENGINEER',
    'Quality Assurance Engineer',
    140
),

-- Legal
(
    17,
    15,
    'LAWYER_LEGAL_PROFESSIONAL',
    'Lawyer & Legal Professional',
    10
),
(
    131,
    15,
    'LEGAL_ASSISTANT',
    'Legal Assistant',
    20
),

-- Police / Law Enforcement
(
    18,
    16,
    'LAW_ENFORCEMENT_OFFICER',
    'Law Enforcement Officer',
    10
),
(
    95,
    16,
    'POLICE',
    'Police',
    20
),

-- Medical & Healthcare
(
    14,
    17,
    'HEALTHCARE_PROFESSIONAL',
    'Healthcare Professional',
    10
),
(
    15,
    17,
    'PARAMEDICAL_PROFESSIONAL',
    'Paramedical Professional',
    20
),
(13, 17, 'NURSE', 'Nurse', 30),
(
    98,
    17,
    'PHARMACIST',
    'Pharmacist',
    40
),
(
    100,
    17,
    'PHYSIOTHERAPIST',
    'Physiotherapist',
    50
),
(
    103,
    17,
    'PSYCHOLOGIST',
    'Psychologist',
    60
),
(
    107,
    17,
    'THERAPIST',
    'Therapist',
    70
),
(
    108,
    17,
    'MEDICAL_TRANSCRIPTIONIST',
    'Medical Transcriptionist',
    80
),
(
    109,
    17,
    'DIETICIAN_NUTRITIONIST',
    'Dietician / Nutritionist',
    90
),
(
    110,
    17,
    'LAB_TECHNICIAN',
    'Lab Technician',
    100
),
(
    150,
    17,
    'MEDICAL_REPRESENTATIVE',
    'Medical Representative',
    110
),

-- Media & Entertainment
(
    27,
    18,
    'JOURNALIST',
    'Journalist',
    10
),
(
    22,
    18,
    'MEDIA_PROFESSIONAL',
    'Media Professional',
    20
),
(
    24,
    18,
    'ENTERTAINMENT_PROFESSIONAL',
    'Entertainment Professional',
    30
),
(
    26,
    18,
    'EVENT_MANAGEMENT_PROFESSIONAL',
    'Event Management Professional',
    40
),
(
    21,
    18,
    'ADVERTISING_PR_PROFESSIONAL',
    'Advertising / PR Professional',
    50
),
(
    66,
    18,
    'MEDIA_DESIGNER',
    'Designer',
    60
),
(
    79,
    18,
    'ACTOR_MODEL',
    'Actor / Model',
    70
),
(
    80,
    18,
    'ARTIST',
    'Artist',
    80
),

-- Merchant Navy
(
    32,
    19,
    'MARINER_MERCHANT_NAVY',
    'Mariner / Merchant Navy',
    10
),
(
    133,
    19,
    'SAILOR',
    'Sailor',
    20
),

-- Scientist
(
    35,
    20,
    'SCIENTIST_RESEARCHER',
    'Scientist / Researcher',
    10
),

-- Senior Management
(
    41,
    21,
    'CXO_PRESIDENT_DIRECTOR_CHAIRMAN',
    'CXO / President, Director, Chairman',
    10
),
(
    134,
    21,
    'VP_AVP_GM_DGM_AGM',
    'VP / AVP / GM / DGM / AGM',
    20
),

-- Others
(
    44,
    22,
    'TECHNICIAN',
    'Technician',
    10
),
(
    38,
    22,
    'ARTS_CRAFTSMAN',
    'Arts & Craftsman',
    20
),
(
    68,
    22,
    'LIBRARIAN',
    'Librarian',
    30
),
(
    71,
    22,
    'BUSINESS_OWNER_ENTREPRENEUR',
    'Business Owner / Entrepreneur',
    40
),
(
    72,
    22,
    'RETIRED',
    'Retired',
    50
),
(
    73,
    22,
    'TRANSPORT_LOGISTICS_PROFESSIONAL',
    'Transportation / Logistics Professional',
    60
),
(
    135,
    22,
    'AGENT_BROKER_TRADER',
    'Agent / Broker / Trader',
    70
),
(
    136,
    22,
    'CONTRACTOR',
    'Contractor',
    80
),
(
    137,
    22,
    'FITNESS_PROFESSIONAL',
    'Fitness Professional',
    90
),
(
    138,
    22,
    'SECURITY_PROFESSIONAL',
    'Security Professional',
    100
),
(
    36,
    22,
    'SOCIAL_WORKER_VOLUNTEER_NGO',
    'Social Worker / Volunteer / NGO',
    110
),
(
    51,
    22,
    'SPORTSPERSON',
    'Sportsperson',
    120
),
(
    139,
    22,
    'TRAVEL_PROFESSIONAL',
    'Travel Professional',
    130
),
(
    140,
    22,
    'SINGER',
    'Singer',
    140
),
(
    141,
    22,
    'WRITER',
    'Writer',
    150
),
(
    158,
    22,
    'POLITICIAN',
    'Politician',
    160
),
(
    142,
    22,
    'ASSOCIATE',
    'Associate',
    170
),
(
    143,
    22,
    'BUILDER',
    'Builder',
    180
),
(
    144,
    22,
    'CHEMIST',
    'Chemist',
    190
),
(
    145,
    22,
    'CNC_OPERATOR',
    'CNC Operator',
    200
),
(
    146,
    22,
    'DISTRIBUTOR',
    'Distributor',
    210
),
(
    147,
    22,
    'DRIVER',
    'Driver',
    220
),
(
    148,
    22,
    'FREELANCER',
    'Freelancer',
    230
),
(
    149,
    22,
    'MECHANIC',
    'Mechanic',
    240
),
(
    151,
    22,
    'MUSICIAN',
    'Musician',
    250
),
(
    152,
    22,
    'PHOTO_VIDEOGRAPHER',
    'Photo / Videographer',
    260
),
(
    153,
    22,
    'SURVEYOR',
    'Surveyor',
    270
),
(
    154,
    22,
    'TAILOR',
    'Tailor',
    280
),
(
    99,
    22,
    'OTHERS',
    'Others',
    290
),

/*
 * Required by the current Not Working synchronization.
 * Keep this code unchanged.
 */
(
    159,
    22,
    'NOT_APPLICABLE',
    'Not Applicable',
    300
),

-- Doctor
(
    12,
    23,
    'DOCTOR',
    'Doctor',
    10
),
(
    105,
    23,
    'DENTIST',
    'Dentist',
    20
),
(
    106,
    23,
    'SURGEON',
    'Surgeon',
    30
),
(
    104,
    23,
    'VETERINARY_DOCTOR',
    'Veterinary Doctor',
    40
);

  
-- ============================================================
-- Seed: Annual Income
-- 1 lakh = 100000 INR
-- ============================================================

INSERT INTO master_annual_incomes (
    code,
    display_name,
    min_amount,
    max_amount,
    display_order
)
VALUES
    ('INR_0_1_LAKH', '₹0 – ₹1 Lakh', 0, 100000, 10),
    ('INR_1_2_LAKH', '₹1 – ₹2 Lakh', 100000, 200000, 20),
    ('INR_2_3_LAKH', '₹2 – ₹3 Lakh', 200000, 300000, 30),
    ('INR_3_4_LAKH', '₹3 – ₹4 Lakh', 300000, 400000, 40),
    ('INR_4_5_LAKH', '₹4 – ₹5 Lakh', 400000, 500000, 50),
    ('INR_5_6_LAKH', '₹5 – ₹6 Lakh', 500000, 600000, 60),
    ('INR_6_7_LAKH', '₹6 – ₹7 Lakh', 600000, 700000, 70),
    ('INR_7_8_LAKH', '₹7 – ₹8 Lakh', 700000, 800000, 80),
    ('INR_8_9_LAKH', '₹8 – ₹9 Lakh', 800000, 900000, 90),
    ('INR_9_10_LAKH', '₹9 – ₹10 Lakh', 900000, 1000000, 100),

    ('INR_10_15_LAKH', '₹10 – ₹15 Lakh', 1000000, 1500000, 110),
    ('INR_15_20_LAKH', '₹15 – ₹20 Lakh', 1500000, 2000000, 120),
    ('INR_20_25_LAKH', '₹20 – ₹25 Lakh', 2000000, 2500000, 130),
    ('INR_25_30_LAKH', '₹25 – ₹30 Lakh', 2500000, 3000000, 140),
    ('INR_30_35_LAKH', '₹30 – ₹35 Lakh', 3000000, 3500000, 150),
    ('INR_35_40_LAKH', '₹35 – ₹40 Lakh', 3500000, 4000000, 160),
    ('INR_40_45_LAKH', '₹40 – ₹45 Lakh', 4000000, 4500000, 170),
    ('INR_45_50_LAKH', '₹45 – ₹50 Lakh', 4500000, 5000000, 180),

    ('INR_50_60_LAKH', '₹50 – ₹60 Lakh', 5000000, 6000000, 190),
    ('INR_60_70_LAKH', '₹60 – ₹70 Lakh', 6000000, 7000000, 200),
    ('INR_70_80_LAKH', '₹70 – ₹80 Lakh', 7000000, 8000000, 210),
    ('INR_80_90_LAKH', '₹80 – ₹90 Lakh', 8000000, 9000000, 220),
    ('INR_90_100_LAKH', '₹90 Lakh – ₹1 Crore', 9000000, 10000000, 230),

    ('INR_ABOVE_1_CRORE', 'More than ₹1 Crore', 10000000, NULL, 240)
ON CONFLICT (code) DO UPDATE
SET
    display_name = EXCLUDED.display_name,
    min_amount = EXCLUDED.min_amount,
    max_amount = EXCLUDED.max_amount,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

COMMIT;