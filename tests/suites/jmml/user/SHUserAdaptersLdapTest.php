<?php

class SHUserAdaptersLdapTest extends TestCase
{
	const PLATFORM_CONFIG_FILE = 'platform_config.php';

	const LDAP_CONFIG_FILE = 'ldap_config.php';

	public function setUp()
	{
		// Clear the static config from factory
		SHFactory::$config = null;
		SHFactory::$ldap = null;

		// Create some files
		TestsHelper::createPlatformConfigFile(1, static::PLATFORM_CONFIG_FILE);
		TestsHelper::createLdapConfigFile(214, static::LDAP_CONFIG_FILE);

		parent::setUp();
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

		$this->assertEquals('LDAP', $adapter->name);
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

	public function testGetAttributesInvalidPasswordException()
	{
		$this->setExpectedException('SHExceptionInvaliduser', 'LIB_SHLDAP_ERR_10303', 10303);

		$ldap = TestsHelper::getLdapConfig(216);
		$user = TestsHelper::getUserCreds();
		$user['password'] .= '!"£%^&*()(&^$£"';

		$adapter = new SHUserAdaptersLdap($user, $ldap);

		try
		{
			$adapter->getId(true);
		}
		catch (Exception $e)
		{
			$adapter->getAttributes();
		}
	}

	public function testGetAttributesInvalidUserException()
	{
		$this->setExpectedException('SHExceptionInvaliduser', 'LIB_SHLDAP_ERR_10302', 10302);

		$ldap = TestsHelper::getLdapConfig(216);
		$user = TestsHelper::getUserCreds();
		$user['username'] .= '!"£%^&*()(&^$£"';

		$adapter = new SHUserAdaptersLdap($user, $ldap);

		$adapter->getAttributes();
	}

	public function testGetAttributesDoGetId()
	{
		$ldap = TestsHelper::getLdapConfig(216);
		$user = TestsHelper::getUserCreds();

		$adapter = new SHUserAdaptersLdap($user, $ldap);

		$attribute = $adapter->getAttributes($ldap['ldap_uid']);

		$this->assertEquals($user[$ldap['ldap_uid']][0], $attribute[$ldap['ldap_uid']][0]);
	}

	public function testGetAttributesChanges()
	{
		$ldap = TestsHelper::getLdapConfig(216);
		$user = TestsHelper::getUserCreds();

		$adapter = new SHUserAdaptersLdap($user, $ldap);

		// Standard first pass
		$attribute = $adapter->getAttributes(array($ldap['ldap_email'], $ldap['ldap_fullname']), false, false);
		$this->assertEquals($user[$ldap['ldap_email']][0], $attribute[$ldap['ldap_email']][0]);
		$this->assertEquals($user[$ldap['ldap_fullname']][0], $attribute[$ldap['ldap_fullname']][0]);

		// Stage a change
		$adapter->setAttributes(
			array(
				$ldap['ldap_email'] => array('lalalala@me.com'),
				'description' => array('Different'),
			)
		);

		// Check the staged change isn't returned
		$attribute = $adapter->getAttributes(array($ldap['ldap_email'], $ldap['ldap_fullname']), false, false);
		$this->assertEquals($user[$ldap['ldap_email']][0], $attribute[$ldap['ldap_email']][0]);
		$this->assertEquals($user[$ldap['ldap_fullname']][0], $attribute[$ldap['ldap_fullname']][0]);
		$this->assertArrayNotHasKey('description', $attribute);

		// Return staged changes now and test
		$attribute = $adapter->getAttributes(array($ldap['ldap_email'], $ldap['ldap_fullname']), false, true);
		$this->assertEquals('lalalala@me.com', $attribute[$ldap['ldap_email']][0]);
		$this->assertEquals($user[$ldap['ldap_fullname']][0], $attribute[$ldap['ldap_fullname']][0]);
		$this->assertArrayNotHasKey('description', $attribute);

		// Check a single attribute staged change
		$attribute = $adapter->getAttributes(array('description'), false, true);
		$this->assertEquals('Different', $attribute['description'][0]);
	}

	public function testSetAttributes1()
	{
		$ldap = TestsHelper::getLdapConfig(216);

		// Loop 50 times to test random users
		for ($i = 0; $i < 50; $i++)
		{
			$user = TestsHelper::getUserCreds();

			$adapter = new SHUserAdaptersLdap($user, $ldap);

			// Save the current phone
			$currentPhone = $adapter->getAttributes('telephoneNumber');

			// Generate new phone number
			$newPhone = rand(11111111, 99999999);

			// Save the new phone number to the user
			$changes = array('telephoneNumber' => array($newPhone));
			$adapter->setAttributes($changes);

			$commit = $adapter->commitChanges();

			$this->assertTrue($commit->status);
			$this->assertTrue($commit->changes);

			// Test to see if the adapter has updated it own internal attributes
			$this->assertEquals(array('telephoneNumber' => array($newPhone)), $adapter->getAttributes('telephoneNumber'));

			// Test the new attribute
			$testAdapter = new SHUserAdaptersLdap($user, $ldap);
			$this->assertEquals(array('telephoneNumber' => array($newPhone)), $testAdapter->getAttributes('telephoneNumber'));

			// Set back to the old attributes
			$adapter->setAttributes($currentPhone);
			$commit = $adapter->commitChanges();
			$this->assertTrue($commit->status);

			// Test its changeed back
			$testAdapter = new SHUserAdaptersLdap($user, $ldap);
			$this->assertEquals($currentPhone, $testAdapter->getAttributes('telephoneNumber'));
		}
	}

	public function testCreateDeleteUsers1()
	{
		$ldap = TestsHelper::getLdapConfig(216);

		// Loop 50 times to test random users
		for ($i = 0; $i < 50; $i++)
		{
			$user = TestsHelper::getUserCreds(null, 101);

			// Create the new user
			$adapter = new SHUserAdaptersLdap($user, $ldap, array('isNew' => 1));
			$adapter->setAttributes($user);
			$commit = $adapter->commitChanges();
			$this->assertTrue($commit->status);

			// Test the new user
			$testAdapter = new SHUserAdaptersLdap($user, $ldap);
			$this->assertEquals($user['dn'], $testAdapter->getId(true));
			$phone = JArrayHelper::getValue($testAdapter->getAttributes('telephoneNumber'), 'telephoneNumber');
			$this->assertEquals($user['telephoneNumber'], $phone);

			// Delete the new user
			$this->assertTrue($adapter->delete());
		}
	}
}
