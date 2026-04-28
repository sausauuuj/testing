CREATE DATABASE IF NOT EXISTS inventory_management_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE inventory_management_system;

CREATE TABLE IF NOT EXISTS divisions (
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
    ('DRD', 'DRD (Development Research Division)', 6),
    ('COA', 'COA (Commission on Audit)', 7)
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    sort_order = VALUES(sort_order);

CREATE TABLE IF NOT EXISTS funding_sources (
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

CREATE TABLE IF NOT EXISTS classifications (
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

CREATE TABLE IF NOT EXISTS accountable_officers (
    officer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    officer_code VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    position VARCHAR(120) NOT NULL DEFAULT '',
    unit VARCHAR(120) NOT NULL DEFAULT '',
    division_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_officers_division (division_id),
    UNIQUE KEY uniq_officer_name_division (name, division_id),
    CONSTRAINT fk_officers_division
        FOREIGN KEY (division_id)
        REFERENCES divisions(division_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS par (
    par_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    par_number VARCHAR(40) NOT NULL UNIQUE,
    accountable_officer_id INT UNSIGNED NOT NULL,
    par_date DATE NOT NULL,
    document_type VARCHAR(10) NOT NULL DEFAULT 'PAR',
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_par_accountable_officer (accountable_officer_id),
    UNIQUE KEY uniq_par_officer_date_type (accountable_officer_id, par_date, document_type),
    CONSTRAINT fk_par_officer
        FOREIGN KEY (accountable_officer_id)
        REFERENCES accountable_officers(officer_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id VARCHAR(60) NOT NULL UNIQUE,
    property_number VARCHAR(80) NULL,
    property_name VARCHAR(150) NOT NULL,
    property_type VARCHAR(100) NOT NULL,
    unit_cost DECIMAL(12, 2) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    description TEXT NULL,
    date_acquired DATE NOT NULL,
    estimated_useful_life VARCHAR(60) NULL,
    current_condition VARCHAR(80) NOT NULL,
    remarks TEXT NULL,
    par_id INT UNSIGNED NOT NULL,
    funding_source_id INT UNSIGNED NOT NULL,
    classification_id INT UNSIGNED NOT NULL,
    bulk_reference VARCHAR(60) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_assets_property_type (property_type),
    INDEX idx_assets_funding_source (funding_source_id),
    INDEX idx_assets_classification (classification_id),
    INDEX idx_assets_date_acquired (date_acquired),
    INDEX idx_assets_par (par_id),
    INDEX idx_assets_property_number (property_number),
    CONSTRAINT fk_assets_par
        FOREIGN KEY (par_id)
        REFERENCES par(par_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_assets_funding_source
        FOREIGN KEY (funding_source_id)
        REFERENCES funding_sources(funding_source_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_assets_classification
        FOREIGN KEY (classification_id)
        REFERENCES classifications(classification_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory_items (
    inventory_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(40) NOT NULL UNIQUE,
    request_type VARCHAR(20) NOT NULL DEFAULT 'RSMI',
    funding_source VARCHAR(120) NULL,
    category VARCHAR(180) NULL,
    ris_number VARCHAR(30) NULL,
    stock_number VARCHAR(30) NULL,
    item_name VARCHAR(180) NOT NULL,
    item_type VARCHAR(30) NOT NULL,
    unit VARCHAR(60) NOT NULL,
    division_id INT UNSIGNED NULL,
    officer_id INT UNSIGNED NULL,
    quantity_issued INT UNSIGNED NOT NULL DEFAULT 0,
    current_stock INT UNSIGNED NOT NULL DEFAULT 0,
    stock_limit INT UNSIGNED NOT NULL DEFAULT 0,
    low_stock_threshold INT UNSIGNED NOT NULL DEFAULT 0,
    unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    issued_at DATE NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_inventory_items_name (item_name),
    INDEX idx_inventory_items_type (item_type),
    INDEX idx_inventory_items_request_type (request_type),
    INDEX idx_inventory_items_ris_number (ris_number),
    INDEX idx_inventory_items_stock_number (stock_number),
    INDEX idx_inventory_items_division (division_id),
    INDEX idx_inventory_items_officer (officer_id),
    INDEX idx_inventory_items_issued_at (issued_at),
    INDEX idx_inventory_items_stock (current_stock)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory_movements (
    movement_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inventory_item_id INT UNSIGNED NOT NULL,
    movement_type VARCHAR(20) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    previous_stock INT UNSIGNED NOT NULL DEFAULT 0,
    current_stock INT UNSIGNED NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inventory_movements_item (inventory_item_id),
    INDEX idx_inventory_movements_type (movement_type),
    CONSTRAINT fk_inventory_movements_item
        FOREIGN KEY (inventory_item_id)
        REFERENCES inventory_items(inventory_item_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;
