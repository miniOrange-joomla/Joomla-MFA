CREATE TABLE IF NOT EXISTS `#__miniorange_otp_custom_message` (
  `id` int(11) UNSIGNED NOT NULL,
  `mo_custom_email_success_message` VARCHAR(1048) NOT NULL,
  `mo_custom_email_error_message` VARCHAR(1048) NOT NULL,
  `mo_custom_email_invalid_format_message` VARCHAR(1048) NOT NULL,
  `mo_custom_email_blocked_message` VARCHAR(1048) NOT NULL,
  `mo_custom_phone_success_message` VARCHAR(1048) NOT NULL,
  `mo_custom_phone_error_message` VARCHAR(1048) NOT NULL,
  `mo_custom_phone_invalid_format_message` VARCHAR(1048) NOT NULL,
  `mo_custom_phone_blocked_message` VARCHAR(1048) NOT NULL,
  `mo_custom_invalid_otp_message` VARCHAR(1048) NOT NULL,
  `mo_block_country_code` VARCHAR(1048) NOT NULL,
  `mo_custom_both_message` VARCHAR(1048) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `#__miniorange_otp_transactions_report` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `verification_method` mediumtext NOT NULL,
  `user_email` mediumtext NOT NULL,
  `user_phone` mediumtext NOT NULL,
  `otp_sent` mediumtext NOT NULL,
  `otp_verified` mediumtext NOT NULL,
  `timestamp` int,
  PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;

ALTER TABLE `#__miniorange_tfa_customer_details`
  ADD COLUMN `mo_otp_allowed_email_domains` VARCHAR(255) NOT NULL,
  ADD COLUMN `reg_restriction` VARCHAR(255) NOT NULL,
  ADD COLUMN `white_or_black` VARCHAR(255) NOT NULL,
  ADD COLUMN `email_count` int(11),
  ADD COLUMN `sms_count` int(11),
  ADD COLUMN `registration_otp_type` int(1),
  ADD COLUMN `login_otp_type` int(1),
  ADD COLUMN `enable_during_registration` int(1),
  ADD COLUMN `uninstall_feedback` int(1) NOT NULL,
  ADD COLUMN `mo_default_country_code` int(5) NOT NULL,
  ADD COLUMN `mo_default_country` VARCHAR(255) NOT NULL,
  ADD COLUMN `rs_email_field` VARCHAR(255),
  ADD COLUMN `rs_contact_field` VARCHAR(255),
  ADD COLUMN `rs_form_count` int(2) NOT NULL,
  ADD COLUMN `rs_form_field_configuration` VARCHAR(255),
  ADD COLUMN `resend_otp_count` VARCHAR(10) NULL;

