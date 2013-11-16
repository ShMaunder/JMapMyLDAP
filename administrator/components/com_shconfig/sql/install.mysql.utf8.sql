--
-- Table structure for table `jos_sh_config`
--

CREATE TABLE IF NOT EXISTS `#__sh_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
);

--
-- Default table data for `jos_sh_config`
--

REPLACE INTO `#__sh_config` 
  SET `name` = 'platform:version',
  `value` = '2.0.0.0';

REPLACE INTO `#__sh_config` 
  SET `name` = 'platform:import',
  `value` = '{}';

REPLACE INTO `#__sh_config` 
  SET `name` = 'user:autoregister',
  `value` = '2';

REPLACE INTO `#__sh_config` 
  SET `name` = 'user:defaultgroup',
  `value` = '2';

REPLACE INTO `#__sh_config` 
  SET `name` = 'user:type',
  `value` = '';

