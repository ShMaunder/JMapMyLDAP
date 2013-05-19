--
-- Table structure for table `jos_sh_config`
--

CREATE TABLE IF NOT EXISTS `#__sh_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type` (`name`)
);
