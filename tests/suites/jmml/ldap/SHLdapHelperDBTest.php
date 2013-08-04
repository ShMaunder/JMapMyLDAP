<?php

class SHLdapHelperDBTest extends TestCaseDatabase
{
	public function setUp()
	{
		parent::setUp();
		parent::setUpBeforeClass();

		$this->saveFactoryState();

		SHFactory::$config = null;
	}

	public function tearDown()
	{
		$this->restoreFactoryState();
	}

	public function testConfigSqlNoParams()
	{
		$config = SHLdapHelper::getConfig();

		// Check returned count
		$this->assertCount(2, $config);

		// Check JRegistry is returned
		$this->assertInstanceOf('JRegistry', $config[0]);
		$this->assertInstanceOf('JRegistry', $config[1]);

		// Check correct results
		$this->assertEquals($config[0]->get('domain'), 'slapd');
		$this->assertEquals($config[1]->get('domain'), 'ad');
	}

	public function testConfigSqlDeleteData()
	{
		// Delete all the records
		JFactory::getDbo()->setQuery('DELETE FROM #__sh_ldap_config')->execute();

		$this->setExpectedException('InvalidArgumentException', 'LIB_SHLDAPHELPER_ERR_10604', 10604);

		$config = SHLdapHelper::getConfig();
	}

	public function testConfigSqlNumericValid()
	{
		$config = SHLdapHelper::getConfig(2);

		// Check JRegistry is returned
		$this->assertInstanceOf('JRegistry', $config);

		// Check correct results
		$this->assertEquals($config->get('domain'), 'ad');
	}

	public function testConfigSqlNumericInvalid()
	{
		$this->setExpectedException('InvalidArgumentException', 'LIB_SHLDAPHELPER_ERR_10605', 10605);

		$config = SHLdapHelper::getConfig(4);
	}


	public function testConfigSqlStringValid()
	{
		$config = SHLdapHelper::getConfig('ad');

		// Check JRegistry is returned
		$this->assertInstanceOf('JRegistry', $config);

		// Check correct results
		$this->assertEquals($config->get('domain'), 'ad');
	}

	public function testConfigSqlStringInvalid()
	{
		$this->setExpectedException('InvalidArgumentException', 'LIB_SHLDAPHELPER_ERR_10606', 10606);

		$config = SHLdapHelper::getConfig('notexist');
	}
}
