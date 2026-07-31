INSERT INTO master_sikh_communities (
    code,
    name,
    display_order,
    is_active
)
VALUES
    ('BRAHMIN_SIKH', 'Brahmin Sikh', 130, TRUE),
    ('RAJPUT_SIKH', 'Rajput Sikh', 140, TRUE),
    ('BANIA_SIKH', 'Bania Sikh', 150, TRUE),
    ('BHATRA_SINGH', 'Bhatra Sikh', 160, TRUE),
    ('TARKHAN_SIKH', 'Tarkhan Sikh', 170, TRUE),
    ('LOHAR_SIKH', 'Lohar Sikh', 180, TRUE),
    ('SUNIAR_SIKH', 'Suniar Sikh', 190, TRUE),
    ('CHHIMBA_SIKH', 'Chhimba Sikh', 200, TRUE),
    ('KASHYAP_SIKH', 'Kashyap Sikh', 210, TRUE),
    ('KUMHAR_SIKH', 'Kumhar Sikh', 220, TRUE),
    ('NAI_SIKH', 'Nai Sikh', 230, TRUE),
    ('DHOBI_SIKH', 'Dhobi Sikh', 240, TRUE),
    ('TELI_SIKH', 'Teli Sikh', 250, TRUE),
    ('KALAL_SIKH', 'Kalal Sikh', 260, TRUE),
    ('JULAHA_SIKH', 'Julaha Sikh', 270, TRUE),
    ('BAZIGAR_SIKH', 'Bazigar Sikh', 280, TRUE),
    ('SIKLIGAR_SIKH', 'Sikligar Sikh', 290, TRUE),
    ('SANSI_SIKH', 'Sansi Sikh', 300, TRUE),
    ('BAWARIA_SIKH', 'Bawaria Sikh', 310, TRUE),
    ('MAHTAM_SIKH', 'Mahtam Sikh', 320, TRUE),
    ('MIRASI_SIKH', 'Mirasi Sikh', 330, TRUE),
    ('MOCHI_SIKH', 'Mochi Sikh', 340, TRUE),
    ('MEGH_SIKH', 'Megh Sikh', 350, TRUE),
    ('AD_DHARMI_SIKH', 'Ad-Dharmi Sikh', 360, TRUE),
    ('BALMIKI_SIKH', 'Balmiki Sikh', 370, TRUE)
ON CONFLICT (code)
DO UPDATE SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;