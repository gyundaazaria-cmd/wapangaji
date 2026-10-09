CREATE DATABASE IF NOT EXISTS gyunda_house
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE gyunda_house;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rooms (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_number VARCHAR(40) NOT NULL,
    monthly_rent DECIMAL(12,2) NOT NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rooms_room_number (room_number),
    CONSTRAINT chk_rooms_monthly_rent CHECK (monthly_rent >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tenants (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(160) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    alternate_phone VARCHAR(30) NULL,
    room_id BIGINT UNSIGNED NULL,
    monthly_rent DECIMAL(12,2) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenants_active (active),
    KEY idx_tenants_room (room_id),
    CONSTRAINT fk_tenants_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    CONSTRAINT chk_tenants_monthly_rent CHECK (monthly_rent >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('Cash', 'Mobile Money', 'Bank Transfer', 'Other') NOT NULL,
    covered_month DATE NOT NULL,
    reference VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_payments_tenant_month (tenant_id, covered_month),
    KEY idx_payments_date (payment_date),
    CONSTRAINT fk_payments_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE RESTRICT,
    CONSTRAINT chk_payments_amount CHECK (amount > 0),
    CONSTRAINT chk_payments_covered_month CHECK (DAYOFMONTH(covered_month) = 1)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tenant_portal_accounts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    username VARCHAR(80) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    photo_mime VARCHAR(40) NULL,
    photo_data MEDIUMBLOB NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tenant_portal_tenant (tenant_id),
    UNIQUE KEY uq_tenant_portal_username (username),
    UNIQUE KEY uq_tenant_portal_email (email),
    CONSTRAINT fk_tenant_portal_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tenant_portal_invites (
    token_hash BINARY(32) NOT NULL,
    tenant_id BIGINT UNSIGNED NOT NULL,
    issued_by BIGINT UNSIGNED NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (token_hash),
    KEY idx_tenant_portal_invites_tenant (tenant_id),
    CONSTRAINT fk_tenant_portal_invite_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE RESTRICT,
    CONSTRAINT fk_tenant_portal_invite_issuer FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tenant_contracts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    full_name VARCHAR(160) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    nida_number VARCHAR(40) NOT NULL,
    lease_start DATE NOT NULL,
    lease_duration_months SMALLINT UNSIGNED NOT NULL,
    next_of_kin_name VARCHAR(160) NOT NULL,
    next_of_kin_phone VARCHAR(30) NOT NULL,
    monthly_rent DECIMAL(12,2) NOT NULL,
    signature_data MEDIUMBLOB NOT NULL,
    signed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tenant_contract_tenant (tenant_id),
    KEY idx_tenant_contracts_tenant_signed (tenant_id, signed_at),
    CONSTRAINT fk_tenant_contracts_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE RESTRICT,
    CONSTRAINT chk_tenant_contracts_duration CHECK (lease_duration_months BETWEEN 1 AND 120),
    CONSTRAINT chk_tenant_contracts_rent CHECK (monthly_rent >= 0)
) ENGINE=InnoDB;
