ALTER TABLE  `#__sh_ldap_config` 
	ADD  `user_params` TEXT NOT NULL AFTER `params`,
	ADD  `group_params` TEXT NOT NULL AFTER `user_params`;

