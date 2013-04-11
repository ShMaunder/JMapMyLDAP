<?php

class SHLUserAdaptersLdapTest extends PHPUnit_Framework_TestCase
{
	const PLATFORM_CONFIG_FILE = 'platform_config.php';

	const LDAP_CONFIG_FILE = 'ldap_config.php';

	public function setUp()
	{
		// Clear the static config from factory
		SHFactory::$config = null;

		// Create some files
		TestsHelper::createPlatformConfigFile(1, static::PLATFORM_CONFIG_FILE);
		TestsHelper::createLdapConfigFile(214, static::LDAP_CONFIG_FILE);
	}

	public function tearDown()
	{
		// Clean up LDAP config files
		unlink (static::LDAP_CONFIG_FILE);
		unlink (static::PLATFORM_CONFIG_FILE);
	}

	public function testInitialiseObject()
	{
		$user = TestsHelper::getUserCreds('shaun.maunder');
		$ldap = TestsHelper::getLdapConfig(216);

		$adapter = new SHUserAdaptersLdap($user, $ldap);

		$this->assertEquals('ldap', $adapter->type);
		$this->assertTrue($adapter->isLdapCompatible);
	}

	public function testGetIdBasicSuccess()
	{
		$ldap = TestsHelper::getLdapConfig(216);

		// Loop 50 times to test random users
		for ($i = 0; $i < 50; $i++)
		{
			// Get random user
			$user = TestsHelper::getUserCreds();

			$adapter = new SHUserAdaptersLdap($user, $ldap);
			$this->assertEquals($user['dn'], $adapter->getId(false));
			$this->assertEquals($user['dn'], $adapter->getId(true));

			$adapter = new SHUserAdaptersLdap($user, $ldap);
			$this->assertEquals($user['dn'], $adapter->getId(true));
			$this->assertEquals($user['dn'], $adapter->getId(true));
		}
	}

	public function testGetIDUsernameFailureException()
	{
		$this->setExpectedException('SHExceptionInvaliduser', 'LIB_SHLDAP_ERR_10302', 10302);

		$user = TestsHelper::getUserCreds();
		$ldap = TestsHelper::getLdapConfig(216);

		$user['username'] .= 'asdasdas!"£%^&*()_(*&^%$£"!';

		$adapter = new SHUserAdaptersLdap($user, $ldap);
		$this->assertEquals($user['dn'], $adapter->getId(false));
	}

	public function testGetIdPasswordFailureException()
	{
		$this->setExpectedException('SHExceptionInvaliduser', 'LIB_SHLDAP_ERR_10303', 10303);

		$user = TestsHelper::getUserCreds();
		$ldap = TestsHelper::getLdapConfig(216);

		$user['password'] .= 'asdasdas!"£%^&*()_(*&^%$£"!';

		$adapter = new SHUserAdaptersLdap($user, $ldap);
		$this->assertEquals($user['dn'], $adapter->getId(true));
	}

	public function testGetIdUsernameFailureExceptionRetry()
	{
		$this->setExpectedException('SHExceptionInvaliduser', 'LIB_SHLDAP_ERR_10302', 10302);

		$user = TestsHelper::getUserCreds();
		$ldap = TestsHelper::getLdapConfig(216);

		$user['username'] .= 'asdasdas!"£%^&*()_(*&^%$£"!';

		$adapter = new SHUserAdaptersLdap($user, $ldap);

		try
		{
			$adapter->getId(false);
		}
		catch (Exception $e)
		{
			// Try it again to test retrying with exceptions
			$adapter->getId(false);
		}
	}

	public function testMagicGetMethod()
	{
		$ldap = TestsHelper::getLdapConfig(216);
		$user = TestsHelper::getUserCreds();

		$adapter = new SHUserAdaptersLdap($user, $ldap);
		$adapter->getId(false);

		$this->assertInstanceOf('shldap', $adapter->client);
		$this->assertInstanceOf('shldap', $adapter->driver);

		$this->assertNull($adapter->doesntexist);
	}

	public function testGetUidExists()
	{
		$ldap = TestsHelper::getLdapConfig(216);

		// Loop 50 times to test random users
		for ($i = 0; $i < 50; $i++)
		{
			// Get random user
			$user = TestsHelper::getUserCreds();

			$adapter = new SHUserAdaptersLdap($user, $ldap);

			$adapter->getId(true);

			$this->assertEquals($user[$adapter->getUid(true)][0], $adapter->getUid());
		}
	}

	public function testGetUidNotExists()
	{
		$ldap = TestsHelper::getLdapConfig(214);
		$user = TestsHelper::getUserCreds();

		$adapter = new SHUserAdaptersLdap($user, $ldap);
		$adapter->getId(true);

		// Test default
		$this->assertNull($adapter->getUid(false, null));
		$this->assertEquals('abc', $adapter->getUid(false, 'abc'));
	}

	public function testGetFullnameExists()
	{
		$ldap = TestsHelper::getLdapConfig(216);

		// Loop 50 times to test random users
		for ($i = 0; $i < 50; $i++)
		{
			// Get random user
			$user = TestsHelper::getUserCreds();

			$adapter = new SHUserAdaptersLdap($user, $ldap);

			$adapter->getId(true);

			$this->assertEquals($user[$adapter->getFullname(true)][0], $adapter->getFullname());
		}
	}

	public function testGetFullnameNotExists()
	{
		$ldap = TestsHelper::getLdapConfig(214);
		$user = TestsHelper::getUserCreds();

		$adapter = new SHUserAdaptersLdap($user, $ldap);
		$adapter->getId(true);

		// Test default
		$this->assertNull($adapter->getFullname(false, null));
		$this->assertEquals('abc', $adapter->getFullname(false, 'abc'));
	}

	public function testGetEmailExists()
	{
		$ldap = TestsHelper::getLdapConfig(216);

		// Loop 50 times to test random users
		for ($i = 0; $i < 50; $i++)
		{
			// Get random user
			$user = TestsHelper::getUserCreds();

			$adapter = new SHUserAdaptersLdap($user, $ldap);

			$adapter->getId(true);

			$this->assertEquals($user[$adapter->getEmail(true)][0], $adapter->getEmail());
		}
	}

	public function testGetEmailNotExists()
	{
		$ldap = TestsHelper::getLdapConfig(214);
		$user = TestsHelper::getUserCreds();

		$adapter = new SHUserAdaptersLdap($user, $ldap);
		$adapter->getId(true);

		// Test default
		$this->assertNull($adapter->getEmail(false, null));
		$this->assertEquals('abc', $adapter->getEmail(false, 'abc'));
	}

	public function testGetEmailFake()
	{
		$ldap = TestsHelper::getLdapConfig(216);
		$user = TestsHelper::getUserCreds();

		$ldap['ldap_email'] = '[username]@example.com';

		$adapter = new SHUserAdaptersLdap($user, $ldap);

		$adapter->getId(true);

		$this->assertEquals("${user['username']}@example.com", $adapter->getEmail(false));
	}

	public function testUpdateCredentials()
	{
		$ldap = TestsHelper::getLdapConfig(216);
		$user = TestsHelper::getUserCreds();
		$user['EXISTING_PASSWORD'] = $user['password'];
		$user['password'] .= '!"£%^&*()(&^$£"';

		$adapter = new SHUserAdaptersLdap($user, $ldap);

		try
		{
			$adapter->getId(true);
		}
		catch (Exception $e)
		{
			try
			{
				$adapter->getId(true);
			}
			catch (Exception $e)
			{
				$adapter->updateCredential($user['EXISTING_PASSWORD']);

				$this->assertEquals($user['dn'], $adapter->getId(true));
				return;
			}
		}

		$this->fail('Incorrect ordering of exceptions');
	}

}
