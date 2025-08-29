-- Core additions
ALTER TABLE user_companies
    ADD COLUMN visibility VARCHAR(20) DEFAULT 'all',
    ADD COLUMN status VARCHAR(20) DEFAULT 'active';

CREATE TABLE user_profiles (
    user_id BIGINT PRIMARY KEY,
    full_name VARCHAR(100),
    phone VARCHAR(50)
);

CREATE TABLE signup_intents (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(120),
    password_hash VARCHAR(255),
    business_name VARCHAR(120),
    plan_slug VARCHAR(50),
    status VARCHAR(20) DEFAULT 'draft'
);

CREATE TABLE modules (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) UNIQUE,
    name VARCHAR(100),
    description TEXT,
    icon VARCHAR(50),
    badge_text VARCHAR(50),
    tier VARCHAR(20),
    sort_order INT DEFAULT 0,
    is_core TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
);

CREATE TABLE user_module_favorites (
    user_id BIGINT,
    module_slug VARCHAR(50)
);

CREATE TABLE menu_shortcuts (
    user_id BIGINT,
    module_slug VARCHAR(50)
);

CREATE TABLE user_company_module_roles (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_company_id BIGINT,
    module_slug VARCHAR(50),
    role VARCHAR(20),
    skill_level INT DEFAULT 0
);

CREATE TABLE user_company_scopes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_company_id BIGINT
);

CREATE TABLE user_company_scope_units (
    scope_id BIGINT,
    unit_id BIGINT
);

CREATE TABLE user_company_scope_businesses (
    scope_id BIGINT,
    business_id BIGINT
);

CREATE TABLE invitations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT,
    email VARCHAR(120),
    token VARCHAR(64),
    seat_reserved TINYINT(1),
    modules TEXT,
    proposed_role VARCHAR(20),
    proposed_visibility VARCHAR(20),
    status VARCHAR(20) DEFAULT 'pending'
);
