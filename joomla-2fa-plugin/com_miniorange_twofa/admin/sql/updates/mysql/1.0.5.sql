ALTER TABLE `#__miniorange_tfa_settings` ADD COLUMN  `enable_change_2fa_method` tinyint NOT NULL default 0;
ALTER TABLE `#__miniorange_tfa_settings` ADD COLUMN  `enable_backup_method` tinyint NOT NULL DEFAULT 0;
ALTER TABLE `#__miniorange_tfa_customer_details` ADD COLUMN `licenseExpiry`  VARCHAR(255) NOT NULL default '';