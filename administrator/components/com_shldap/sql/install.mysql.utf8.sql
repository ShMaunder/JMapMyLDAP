--
-- Table structure for table `jos_sh_ldap_config`
--
CREATE TABLE IF NOT EXISTS `#__sh_ldap_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `enabled` tinyint(3) NOT NULL DEFAULT '1',
  `ordering` int(11) DEFAULT '0',
  `params` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
);

--
-- LDAP default table data for `jos_sh_config`
--

REPLACE INTO `#__sh_config` 
  SET `name` = 'ldap:source',
  `value` = '1';

REPLACE INTO `#__sh_config` 
  SET `name` = 'ldap:plugin',
  `value` = 'ldap';

REPLACE INTO `#__sh_config` 
  SET `name` = 'user:type',
  `value` = 'ldap';

