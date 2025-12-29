CREATE TABLE IF NOT EXISTS `#__miniorange_tfa_customer_details` (
`id` int(11) UNSIGNED NOT NULL ,
`email` VARCHAR(255)  NOT NULL default '',
`password` VARCHAR(255)  NOT NULL default '' ,
`admin_phone` VARCHAR(255)  NOT NULL default '' ,
`customer_key` VARCHAR(255)  NOT NULL default '' ,
`customer_token` VARCHAR(255) NOT NULL default '',
`api_key` VARCHAR(255)  NOT NULL default '',
`app_secret` VARCHAR(255)  NOT NULL default '',
`login_status` tinyint NOT NULL default 0,
`registration_status` VARCHAR(255) NOT NULL default '',
`new_registration` int(11) NOT NULL default 0,
`transaction_id` VARCHAR(255) NOT NULL default '',
`license_type`   VARCHAR(255) NOT NULL default '',
`license_plan`   VARCHAR(255) NOT NULL default '',
`no_of_users`    int(11) UNSIGNED NOT NULL default 0, 
`jid`		int(11) UNSIGNED NOT NULL default 0,
`smsRemaining`  int(11) UNSIGNED NOT NULL default 0, 
`emailRemaining` int(11) UNSIGNED NOT NULL default 0,
`supportExpiry`  VARCHAR(255) NOT NULL default '',
`licenseExpiry`  VARCHAR(255) NOT NULL default '',
`fid`           int(11) UNSIGNED NOT NULL default 0,
`miniorange_fifteen_days_before_lexp` tinyint default 0,
`miniorange_five_days_before_lexp` tinyint default 0,
`miniorange_after_lexp` tinyint default 0,
`miniorange_after_five_days_lexp` tinyint default 0,
`miniorange_lexp_notification_sent` tinyint default 0,
`auto_send_email_time` TEXT,
`mo_otp_allowed_email_domains` VARCHAR(255) NOT NULL,
`reg_restriction` VARCHAR(255) NOT NULL,
`white_or_black` VARCHAR(255) NOT NULL,
`email_count` int(11),
`sms_count` int(11),
`registration_otp_type` int(1),
`login_otp_type` int(1),
`enable_during_registration` int(1),
`uninstall_feedback` int(1) NOT NULL,
`mo_default_country_code` int(5) NOT NULL,
`mo_default_country` VARCHAR(255) NOT NULL,
`rs_email_field` VARCHAR(255) ,
`rs_contact_field` VARCHAR(255) ,
`rs_form_count`  int(2) NOT NULL ,
`rs_form_field_configuration` VARCHAR(255) ,
`resend_otp_count` VARCHAR(10) NULL,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci; 

CREATE TABLE IF NOT EXISTS `#__miniorange_tfa_proxy_setup` (
`id` INT(11) UNSIGNED NOT NULL ,
`password` VARCHAR(255) NOT NULL ,
`proxy_host_name` VARCHAR(255) NOT NULL ,
`port_number` VARCHAR(255) NOT NULL,
`username` VARCHAR(255) NOT NULL ,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `#__miniorange_tfa_settings` (
`id` INT(11) UNSIGNED NOT NULL ,
`tfa_enabled` tinyint NOT NULL default 1,
`tfa_enabled_type` VARCHAR(255) NOT NULL DEFAULT 'site',
`tfa_halt` tinyint NOT NULL default 0,
`skip_tfa_for_users` tinyint NOT NULL default 0,
`enable_tfa_passwordless_login` tinyint NOT NULL default 0,
`enable_change_2fa_method` tinyint NOT NULL default 0,
`remember_device` tinyint NOT NULL default 0,
`enable_backup_method` tinyint NOT NULL default 0,
`enable_backup_method_type` VARCHAR(255) NOT NULL DEFAULT 'none',
`tfa_kba_set1` MEDIUMTEXT NULL,
`tfa_kba_set2` MEDIUMTEXT NULL,
`login_with_second_factor_only` tinyint NOT NULL default 0,
`mo_tfa_for_roles` VARCHAR(2048) NOT NULL DEFAULT '["ALL"]',
`activeMethods` varchar(2048) NOT NULL DEFAULT '["ALL"]',
`enableIpWhiteList` tinyint NOT NULL DEFAULT 0,
`enableIpBlackList` tinyint NOT NULL DEFAULT 0,
`whiteListedIps` varchar(4092) NOT NULL DEFAULT '[]',
`blackListedIPs` varchar(4092) NOT NULL DEFAULT '[]',
`afterLoginRedirectUrl` varchar(1024) NOT NULL default '',
`googleAuthAppName` varchar(1024) NOT NULL DEFAULT 'miniOrangeAuth',
`branding_name` varchar(1024)  NOT NULL DEFAULT 'login',
`customFormCss` VARCHAR(255)  NOT NULL default '',
`primarybtnCss` VARCHAR(255)  NOT NULL default '',
`enableTfaRegistration` TINYINT(1) DEFAULT 0,
`enableTfaDomain` TINYINT(1) DEFAULT 0,
`tfaDomainList` TEXT NOT NULL,
`enableEmailFunctionality` TINYINT(1) DEFAULT 0,
`feedback_uninstall` int(1) NOT NULL,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `#__miniorange_tfa_users` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`jUsername` VARCHAR(255)  NOT NULL default '' ,
`username` VARCHAR(255)  NOT NULL default '' ,
`email` VARCHAR(255)  NOT NULL default '',
`phone` VARCHAR(255)  NOT NULL default '',
`user_group` VARCHAR(255)  NOT NULL default '',
`configured_methods` VARCHAR(255)  NOT NULL default '',
`active_method` VARCHAR(255) NOT NULL default '',
`status_of_motfa` VARCHAR(255)  NOT NULL default '',
`force_reset` VARCHAR(255)  NOT NULL default '',
`backup_method` varchar(255) NULL default '',
`disable_motfa` tinyint NOT NULL default 0,
`transactionId`VARCHAR(255) NOT NULL default '',
`mo_backup_codes` VARCHAR(512) NOT NULL default '',
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `#__miniorange_rba_device` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`user_id` int(11) UNSIGNED NOT NULL default 0,
`mo_rba_device` VARCHAR(10000) NOT NULL default '',
PRIMARY KEY (`id`)
)DEFAULT COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `#__miniorange_email_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email_method` VARCHAR(50) NOT NULL,
    `smtp_host` VARCHAR(255) NOT NULL,
    `smtp_port` INT NOT NULL,
    `smtp_username` VARCHAR(255) NOT NULL,
    `smtp_password` VARCHAR(255) NOT NULL,
    `recipients` TEXT NOT NULL
) DEFAULT COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `#__miniorange_otp_transactions_report` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`verification_method` mediumtext NOT NULL ,
`user_email` mediumtext NOT NULL ,
`user_phone` mediumtext NOT NULL ,
`otp_sent` mediumtext NOT NULL ,
`otp_verified` mediumtext NOT NULL ,
`timestamp` int,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;

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
`mo_block_country_code` VARCHAR (1048) NOT NULL,
`mo_custom_both_message` VARCHAR (1048) NOT NULL,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;


INSERT IGNORE INTO `#__miniorange_tfa_customer_details`(`id`,`login_status`,`registration_status`) values (1,0,'not-started') ;
INSERT IGNORE INTO `#__miniorange_tfa_settings`(`id`,`tfa_enabled`, `tfa_kba_set1`,`tfa_kba_set2`) values (1,1,'What is your first company name?What was your childhood nickname?In what city did you meet your spouse/significant other?What is the name of your favorite childhood friend?What school did you attend for sixth grade?','What was the name of the city or town where you first worked? Which sport is your favourite? Who is your favourite athlete? What is the maiden name of your grandmother? What was the registration number of your first car?') ;
INSERT IGNORE INTO `#__miniorange_otp_custom_message`(`id`) values (1);