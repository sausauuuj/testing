-- One-time migration for existing databases created with the older schema.
-- Run this after backing up your database.

USE inventory_management_system;

CREATE TABLE divisions (
    division_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    label VARCHAR(180) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO divisions (code, label, sort_order) VALUES
    ('ORD', 'ORD (Office of the Regional Director)', 1),
    ('FAD', 'FAD (Finance and Administrative Division)', 2),
    ('PDIPBD', 'PDIPBD (Project Development, Investment Programming, and Budgeting Division)', 3),
    ('PFPD', 'PFPD (Policy Formulation and Planning Division)', 4),
    ('PMED', 'PMED (Project Monitoring and Evaluation Division)', 5),
    ('DRD', 'DRD (Development Research Division)', 6)
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    sort_order = VALUES(sort_order);

INSERT INTO divisions (code, label, sort_order)
SELECT DISTINCT ao.division, ao.division, 99
FROM accountable_officers ao
LEFT JOIN divisions d ON d.code = ao.division
WHERE d.division_id IS NULL;

CREATE TABLE funding_sources (
    funding_source_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO funding_sources (name, sort_order) VALUES
    ('DEPDev', 1),
    ('RDC', 2)
ON DUPLICATE KEY UPDATE
    sort_order = VALUES(sort_order);

INSERT INTO funding_sources (name, sort_order)
SELECT DISTINCT a.funding_source, 99
FROM assets a
LEFT JOIN funding_sources fs ON fs.name = a.funding_source
WHERE fs.funding_source_id IS NULL;

CREATE TABLE classifications (
    classification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    label VARCHAR(120) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO classifications (code, label, sort_order) VALUES
    ('PPE', 'PPE', 1),
    ('SEMI', 'Semi-Expendable', 2)
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    sort_order = VALUES(sort_order);

INSERT INTO classifications (code, label, sort_order)
SELECT DISTINCT UPPER(a.classification), UPPER(a.classification), 99
FROM assets a
LEFT JOIN classifications c ON c.code = UPPER(a.classification)
WHERE c.classification_id IS NULL;

ALTER TABLE accountable_officers
    ADD COLUMN position VARCHAR(120) NOT NULL DEFAULT '' AFTER name,
    ADD COLUMN unit VARCHAR(120) NOT NULL DEFAULT '' AFTER position,
    ADD COLUMN division_id INT UNSIGNED NULL AFTER unit;

UPDATE accountable_officers ao
INNER JOIN divisions d ON d.code = ao.division
SET ao.division_id = d.division_id;

ALTER TABLE accountable_officers
    DROP INDEX uniq_officer_name_division,
    DROP COLUMN division,
    MODIFY COLUMN division_id INT UNSIGNED NOT NULL,
    ADD INDEX idx_officers_division (division_id),
    ADD UNIQUE KEY uniq_officer_name_division (name, division_id),
    ADD CONSTRAINT fk_officers_division
        FOREIGN KEY (division_id)
        REFERENCES divisions(division_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT;

ALTER TABLE assets
    ADD COLUMN property_number VARCHAR(80) NULL AFTER property_id,
    ADD COLUMN funding_source_id INT UNSIGNED NULL AFTER par_id,
    ADD COLUMN classification_id INT UNSIGNED NULL AFTER funding_source_id;

UPDATE assets a
INNER JOIN funding_sources fs ON fs.name = a.funding_source
SET a.funding_source_id = fs.funding_source_id;

UPDATE assets a
INNER JOIN classifications c ON c.code = UPPER(a.classification)
SET a.classification_id = c.classification_id;

ALTER TABLE assets
    DROP INDEX idx_assets_funding_source,
    DROP INDEX idx_assets_classification,
    DROP COLUMN funding_source,
    DROP COLUMN classification,
    MODIFY COLUMN funding_source_id INT UNSIGNED NOT NULL,
    MODIFY COLUMN classification_id INT UNSIGNED NOT NULL,
    ADD INDEX idx_assets_funding_source (funding_source_id),
    ADD INDEX idx_assets_classification (classification_id),
    ADD INDEX idx_assets_property_number (property_number),
    ADD CONSTRAINT fk_assets_funding_source
        FOREIGN KEY (funding_source_id)
        REFERENCES funding_sources(funding_source_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    ADD CONSTRAINT fk_assets_classification
        FOREIGN KEY (classification_id)
        REFERENCES classifications(classification_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT;
