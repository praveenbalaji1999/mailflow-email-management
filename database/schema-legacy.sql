-- MailFlow Email Management System
-- Database: email_management

CREATE DATABASE IF NOT EXISTS email_management
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE email_management;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS email_history;
DROP TABLE IF EXISTS email_queue;
DROP TABLE IF EXISTS campaign_recipients;
DROP TABLE IF EXISTS campaigns;
DROP TABLE IF EXISTS group_members;
DROP TABLE IF EXISTS `groups`;
DROP TABLE IF EXISTS unsubscribes;
DROP TABLE IF EXISTS recipients;
DROP TABLE IF EXISTS smtp_settings;
DROP TABLE IF EXISTS admins;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recipients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  status ENUM('active', 'inactive', 'unsubscribed', 'bounced') NOT NULL DEFAULT 'active',
  unsubscribe_token VARCHAR(64) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_recipients_email (email),
  UNIQUE KEY uq_recipients_token (unsubscribe_token),
  KEY idx_recipients_status (status),
  KEY idx_recipients_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `groups` (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_groups_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE group_members (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id INT UNSIGNED NOT NULL,
  recipient_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_group_recipient (group_id, recipient_id),
  KEY idx_gm_recipient (recipient_id),
  CONSTRAINT fk_gm_group FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
  CONSTRAINT fk_gm_recipient FOREIGN KEY (recipient_id) REFERENCES recipients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE campaigns (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campaign_name VARCHAR(200) NOT NULL,
  subject VARCHAR(255) NOT NULL DEFAULT '',
  message MEDIUMTEXT NOT NULL,
  attachment_path VARCHAR(500) NULL,
  status ENUM('draft', 'pending', 'processing', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'draft',
  total_recipients INT UNSIGNED NOT NULL DEFAULT 0,
  total_sent INT UNSIGNED NOT NULL DEFAULT 0,
  total_failed INT UNSIGNED NOT NULL DEFAULT 0,
  total_pending INT UNSIGNED NOT NULL DEFAULT 0,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_campaigns_status (status),
  KEY idx_campaigns_created (created_at),
  CONSTRAINT fk_campaigns_admin FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE campaign_recipients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT UNSIGNED NOT NULL,
  recipient_id INT UNSIGNED NULL,
  email VARCHAR(190) NOT NULL,
  status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
  sent_at DATETIME NULL,
  error_message VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_cr_campaign (campaign_id),
  KEY idx_cr_status (status),
  KEY idx_cr_email (email),
  CONSTRAINT fk_cr_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
  CONSTRAINT fk_cr_recipient FOREIGN KEY (recipient_id) REFERENCES recipients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_queue (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT UNSIGNED NOT NULL,
  campaign_recipient_id INT UNSIGNED NOT NULL,
  recipient_email VARCHAR(190) NOT NULL,
  recipient_name VARCHAR(150) NOT NULL DEFAULT '',
  subject VARCHAR(255) NOT NULL,
  message MEDIUMTEXT NOT NULL,
  attachment_path VARCHAR(500) NULL,
  status ENUM('pending', 'processing', 'sent', 'failed') NOT NULL DEFAULT 'pending',
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  last_error VARCHAR(500) NULL,
  scheduled_at DATETIME NULL,
  processed_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_eq_status (status),
  KEY idx_eq_campaign (campaign_id),
  KEY idx_eq_scheduled (scheduled_at),
  CONSTRAINT fk_eq_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
  CONSTRAINT fk_eq_cr FOREIGN KEY (campaign_recipient_id) REFERENCES campaign_recipients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE unsubscribes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  token VARCHAR(64) NOT NULL,
  campaign_id INT UNSIGNED NULL,
  unsubscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_unsub_email (email),
  KEY idx_unsub_token (token),
  CONSTRAINT fk_unsub_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT UNSIGNED NULL,
  recipient_id INT UNSIGNED NULL,
  recipient_email VARCHAR(190) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  status ENUM('sent', 'pending', 'failed') NOT NULL,
  sent_at DATETIME NULL,
  error_message VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_eh_campaign (campaign_id),
  KEY idx_eh_status (status),
  KEY idx_eh_email (recipient_email),
  KEY idx_eh_sent (sent_at),
  CONSTRAINT fk_eh_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
  CONSTRAINT fk_eh_recipient FOREIGN KEY (recipient_id) REFERENCES recipients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE smtp_settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  host VARCHAR(255) NOT NULL,
  port INT UNSIGNED NOT NULL DEFAULT 587,
  encryption ENUM('tls', 'ssl', 'none') NOT NULL DEFAULT 'tls',
  username VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  from_name VARCHAR(150) NOT NULL,
  from_email VARCHAR(190) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin seeded by setup-db.sh / install.php
-- Login: admin@mailflow.local / Admin@123
INSERT INTO admins (name, email, password) VALUES
('Admin User', 'admin@mailflow.local', '$2y$10$4T8Ik4R8As5Liy6sf0K99e/K4Mmd27VmS0hE89GkGk0.82iEPsCu6');

INSERT INTO `groups` (name, description) VALUES
('All Customers', 'All customer contacts'),
('Employees', 'Internal employees'),
('HR Team', 'Human resources team'),
('Sales Team', 'Sales and account managers'),
('Premium Customers', 'Premium tier customers'),
('Marketing', 'Marketing distribution list');

INSERT INTO smtp_settings (host, port, encryption, username, password, from_name, from_email) VALUES
('smtp.mailflow.example.com', 587, 'tls', 'admin@example.com', '', 'MailFlow', 'admin@example.com');
