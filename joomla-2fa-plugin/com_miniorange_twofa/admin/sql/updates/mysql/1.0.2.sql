ALTER TABLE `#__miniorange_tfa_settings` ADD COLUMN  `activeMethods` varchar(2048)  NOT NULL DEFAULT '["ALL"]';
ALTER TABLE `#__miniorange_tfa_settings` ADD COLUMN  `enableIpWhiteList` tinyint  NOT NULL DEFAULT 0;
ALTER TABLE `#__miniorange_tfa_settings` ADD COLUMN  `whiteListedIps` varchar(8192)  NOT NULL DEFAULT '[]';
ALTER TABLE `#__miniorange_tfa_settings` ADD COLUMN  `afterLoginRedirectUrl` varchar(1024)  NOT NULL default '';
ALTER TABLE `#__miniorange_tfa_settings` ADD COLUMN  `googleAuthAppName` varchar(1024)  NOT NULL DEFAULT 'miniOrangeAuth';

