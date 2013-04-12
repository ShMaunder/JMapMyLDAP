--
-- JMML Unit Test DDL
--

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- --------------------------------------------------------

--
-- Table structure for table `jos_sh_config`
--

DROP TABLE IF EXISTS `jos_sh_config`;
CREATE TABLE IF NOT EXISTS `jos_sh_config` (
  `config_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`config_id`),
  UNIQUE KEY `type` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=17 ;


