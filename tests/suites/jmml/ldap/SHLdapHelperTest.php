<?php

class SHLdapHelperTest extends TestCaseDatabase
{
	const PLATFORM_CONFIG_FILE = 'platform_config_multi_ns.php';

	const LDAP_CONFIG_FILE = 'ldap_config.php';

	const LDAP_BAD_CONFIG_FILE = 'ldap_bad_config.php';

	public function setUp()
	{
		parent::setUp();
		parent::setUpBeforeClass();

		$this->saveFactoryState();

		$this->setUpLdapConfigFiles();

		SHFactory::$config = null;
	}

	public function tearDown()
	{
		// Clean up LDAP config files
		unlink (static::LDAP_CONFIG_FILE);
		unlink (static::PLATFORM_CONFIG_FILE);
		unlink (static::LDAP_BAD_CONFIG_FILE);
		$this->restoreFactoryState();
	}

	protected function setUpLdapConfigFiles()
	{
		$platform = "<?php \nclass SHConfigSingle \n{\n" .
			"\t" . 'public $ldap__config = 3;' . "\n" .
			"\t" . 'public $ldap__file = \'' . static::LDAP_CONFIG_FILE . '\';' . "\n" .
			"}\n";


		$platform .= "\nclass SHConfigMulti \n{\n" .
			"\t" . 'public $ldap__config = 3;' . "\n" .
			"\t" . 'public $ldap__file = \'' . static::LDAP_CONFIG_FILE . '\';' . "\n" .
			"\t" . 'public $ldap__namespaces = \'search;bind\';' . "\n" .
			"}\n";

		fwrite(fopen(static::PLATFORM_CONFIG_FILE, 'w'), $platform);

		/*
		 * Create the LDAP Config file with multiple classes (aka namespaces)
		 */
		$ldapWrite = null;
		$ldapExport = null;
		$ldapConfig = TestsHelper::getLdapConfig(214);

		foreach ($ldapConfig as $k => $v)
		{
			$ldapExport .= "\t" . 'public $' . $k . ' = \'' . str_replace('\'', '\\\'', $v) . "'; \n";
		}

		$ldapWrite = "<?php \nclass SHLdapConfig \n{\n{$ldapExport}}\n";

		$ldapWrite .= "\nclass SHLdapConfigSearch \n{\n{$ldapExport}}\n";

		$ldapExport = null;
		$ldapConfig = TestsHelper::getLdapConfig(220);

		foreach ($ldapConfig as $k => $v)
		{
			$ldapExport .= "\t" . 'public $' . $k . ' = \'' . str_replace('\'', '\\\'', $v) . "'; \n";
		}

		$ldapWrite .= "\nclass SHLdapConfigBind \n{\n{$ldapExport}}\n";

		fwrite(fopen(static::LDAP_CONFIG_FILE, 'w'), $ldapWrite);



		fwrite(fopen(static::LDAP_BAD_CONFIG_FILE, 'w'), '<?php class SHLdapBadConfig {} ');
	}

	public function testConfigInvalidFileBad()
	{
		$this->setExpectedException('RuntimeException', 'LIB_SHLDAPHELPER_ERR_10621', 10621);

		// Change it to a bad file source
		$platform = clone(SHFactory::getConfig('file', array('file' => static::PLATFORM_CONFIG_FILE, 'namespace' => 'single')));
		$platform->set('ldap.file', static::LDAP_BAD_CONFIG_FILE);

		SHLdapHelper::getConfig(null, $platform);
	}

	public function testConfigSuccessMultiNSFile()
	{
		$platform = SHFactory::getConfig('file', array('file' => static::PLATFORM_CONFIG_FILE, 'namespace' => 'multi'));

		$ldapSearch = TestsHelper::getLdapConfig(214);
		$ldapBind = TestsHelper::getLdapConfig(220);

		$registrySearchByString = SHLdapHelper::getConfig('search', $platform);
		$registryBindByString = SHLdapHelper::getConfig('bind', $platform);
		$registrySearchByPosition = SHLdapHelper::getConfig(0, $platform);
		$registryBindByPosition = SHLdapHelper::getConfig(1, $platform);

		$registryAll = SHLdapHelper::getConfig(null, $platform);

		$this->assertEquals($ldapSearch['user_qry'], $registrySearchByString->get('user_qry'));
		$this->assertEquals($ldapSearch['user_qry'], $registrySearchByPosition->get('user_qry'));
		$this->assertEquals($ldapSearch['user_qry'], $registryAll[0]->get('user_qry'));
		$this->assertEquals($ldapBind['user_qry'], $registryBindByString->get('user_qry'));
		$this->assertEquals($ldapBind['user_qry'], $registryBindByPosition->get('user_qry'));
		$this->assertEquals($ldapBind['user_qry'], $registryAll[1]->get('user_qry'));
	}

	public function testConfigSuccessSingleNSFile()
	{
		$platform = SHFactory::getConfig('file', array('file' => static::PLATFORM_CONFIG_FILE, 'namespace' => 'single'));

		$registry = SHLdapHelper::getConfig(null, $platform);

		$ldapConfig = TestsHelper::getLdapConfig(214);

		$this->assertEquals($ldapConfig['user_qry'], $registry->get('user_qry'));
		$this->assertEquals($ldapConfig['host'], $registry->get('host'));
	}

	public function testConfigInvalidFilePath()
	{
		$this->setExpectedException('InvalidArgumentException', 'LIB_SHLDAPHELPER_ERR_10622', 10622);

		// Change it to a invalid file source path
		$platform = SHFactory::getConfig('file', array('file' => static::PLATFORM_CONFIG_FILE, 'namespace' => 'single'));
		$platform->set('ldap.file', 'thisdoesntexist.php');
		$platform->set('ldap.namespaces', 'nonexist');

		SHLdapHelper::getConfig(null, $platform);
	}

	public function testConfigInvalidSource()
	{
		$this->setExpectedException('InvalidArgumentException', 'LIB_SHLDAPHELPER_ERR_10601', 10601);

		// Change it to a invalid LDAP config source
		$platform = SHFactory::getConfig('file', array('file' => static::PLATFORM_CONFIG_FILE, 'namespace' => 'single'));
		$platform->set('ldap.config', 84);

		SHLdapHelper::getConfig(null, $platform);
	}

	public function testConfigSql()
	{
		// TODO: incomplete - just for testing database connection etc.
		SHLdapHelper::getConfig();
	}
}
