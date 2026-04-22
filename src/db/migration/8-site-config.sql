-- Migration 8: Site configuration table

CREATE TABLE IF NOT EXISTS `site_config` (
  `config_key` varchar(100) NOT NULL,
  `config_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default mail configuration (empty, to be filled by user)
INSERT IGNORE INTO `site_config` (`config_key`, `config_value`) VALUES
('mail_settings', '{"protocol": "smtp", "smtp_host": "localhost", "smtp_port": 25, "smtp_username": "", "smtp_password": "", "from_email": "no-reply@example.com", "from_name": "BandBinder", "mailgun_api_key": "", "mailgun_domain": ""}');