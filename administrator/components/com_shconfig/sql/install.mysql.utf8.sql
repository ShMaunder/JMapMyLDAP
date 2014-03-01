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
-- Table structure for table `jos_sh_config`
--

CREATE TABLE IF NOT EXISTS `#__sh_adapter_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(3) NOT NULL,
  `adapter_name` varchar(255) NOT NULL,
  `domain` varchar(45) NOT NULL,
  `adapter_id` varchar(255) NOT NULL,
  `joomla_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_entry` (`type`, `adapter_name`, `domain`, `adapter_id`)
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

