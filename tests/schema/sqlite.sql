--
-- JMML Unit Test DDL
--

-- --------------------------------------------------------

--
-- Table structure for table `jos_sh_config`
--

CREATE TABLE `jos_sh_config` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT NOT NULL DEFAULT '',
  `value` TEXT NOT NULL DEFAULT '',
  CONSTRAINT `idx_sh_config_name` UNIQUE (`name`)
);

-- --------------------------------------------------------

--
-- Table structure for table `jos_sh_ldap_config`
--

CREATE TABLE `jos_sh_ldap_config` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT NOT NULL DEFAULT '',
  `enabled` INTEGER NOT NULL DEFAULT '0',
  `ordering` INTEGER NOT NULL DEFAULT '0',
  `params` TEXT NOT NULL DEFAULT '',
  CONSTRAINT `idx_sh_ldap_config_name` UNIQUE (`name`)
);

