--
-- JMML Unit Test DDL
--

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- --------------------------------------------------------

--
-- Table structure for table `jos_sh_ldap_config`
--

DROP TABLE IF EXISTS `jos_sh_ldap_config`;
CREATE TABLE IF NOT EXISTS `jos_sh_ldap_config` (
  `ldap_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `enabled` tinyint(3) NOT NULL DEFAULT '1',
  `ordering` int(11) DEFAULT '0',
  `params` text NOT NULL,
  PRIMARY KEY (`ldap_id`)
);
