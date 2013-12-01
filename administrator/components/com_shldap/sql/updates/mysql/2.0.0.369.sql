ALTER TABLE  `#__sh_ldap_config` 
	ADD  `checked_out` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0',
	ADD  `checked_out_time` DATETIME NOT NULL DEFAULT  '0000-00-00 00:00:00';

