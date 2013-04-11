<?php

class SHLdapTest extends PHPUnit_Framework_TestCase
{
	const ENCRYPTION_KEY_FILE = 'ldap_encrypt_key.txt';

	const PLATFORM_CONFIG_FILE = 'platform_config.php';

	const LDAP_CONFIG_FILE = 'ldap_config.php';

	public function setUp()
	{
		// Clear the static config from factory
		SHFactory::$config = null;

		// Create some files
		fwrite(fopen(static::ENCRYPTION_KEY_FILE, 'w'), 'ym0ZBkTbDbYrQzjMM7COYnLYuArlq31UIfDyBj11gpeeVLlXeGYPQ7Qf71TPDlN8dVWQfsFbf5SteVXoNzQeiH3EHMFjQtyvmtDNv6kAqUa0Bc7r8QdN5H7VQXtARk1uYCwBqi4sYm1rRaUOJqDCRL64bj4ykeqyouPw8CscmK0hnikpQWSL9MKtJjNyathdSx3rVWE4YiIrgij8ELGjELwl7JQrztCSLAbRfQJafAQ6xGXUDRslRK4T4w2vtBMb');
		TestsHelper::createPlatformConfigFile(1, static::PLATFORM_CONFIG_FILE);
		TestsHelper::createLdapConfigFile(214, static::LDAP_CONFIG_FILE);
	}

	public function tearDown()
	{
		// Clean up LDAP config files
		unlink (static::ENCRYPTION_KEY_FILE);
		unlink (static::LDAP_CONFIG_FILE);
		unlink (static::PLATFORM_CONFIG_FILE);
	}

	/**
	 * @covers  SHLdap::connect
	 */
	public function testSlapdConnectSuccess()
	{
		$config = TestsHelper::getLdapConfig(214);

		$ldap = new SHLdap($config);

		$this->assertTrue($ldap->connect());
	}

	/**
	 * @covers  SHLdap::connect
	 */
	public function testSlapdConnectTLSFailure()
	{
		$this->setExpectedException('Exception', 'LIB_SHLDAP_ERR_10005', 10005);

		// Turn on TLS
		$config = TestsHelper::getLdapConfig(214);
		$config['negotiate_tls'] = 1;

		$ldap = new SHLdap($config);

		$ldap->connect();
	}

	/**
	 * @covers  SHLdap::connect
	 */
	public function testSlapdConnectNoHost()
	{
		$this->setExpectedException('Exception', 'LIB_SHLDAP_ERR_10001', 10001);

		// Blank the Host
		$config = TestsHelper::getLdapConfig(214);
		$config['host'] = '';

		$ldap = new SHLdap($config);

		$ldap->connect();
	}

	/**
	 * @covers  SHLdap::proxyBind
	 */
	public function testSlapdProxyBindUnencryptedSuccess()
	{
		$config = TestsHelper::getLdapConfig(203);

		$ldap = new SHLdap($config);

		$ldap->connect();

		$this->assertTrue($ldap->proxyBind());
	}

	/**
	 * @covers  SHLdap::proxyBind
	 * @covers  SHFactory::getCrypt
	 */
	public function testSlapdProxyBindDIEncyptedSuccess()
	{
		$config = TestsHelper::getLdapConfig(204);

		$ldap = new SHLdap($config);

		$ldap->connect();

		$this->assertTrue($ldap->proxyBind());
	}

	/**
	 * @covers  SHLdap::proxyBind
	 * @covers  SHFactory::getCrypt
	 */
	public function testSlapdProxyBindFileEncyptedSuccess()
	{
		$config = TestsHelper::getLdapConfig(205);

		$ldap = new SHLdap($config);

		$ldap->connect();

		$this->assertTrue($ldap->proxyBind());
	}

	/**
	 * @covers  SHLdap::proxyBind
	 */
	public function testSlapdProxyBindUnencyptedFailure()
	{
		// Mess up the proxy password
		$config = TestsHelper::getLdapConfig(214);
		$config['proxy_password'] = 'asdasdfsafgas';

		$ldap = new SHLdap($config);

		$ldap->connect();

		$this->assertFalse($ldap->proxyBind());
	}

	/**
	 * @covers  SHLdap::proxyBind
	 * @covers  SHFactory::getCrypt
	 */
	public function testSlapdProxyBindDIEncyptedFailure()
	{
		// Mess up proxy password
		$config = TestsHelper::getLdapConfig(204);
		$config['proxy_password'] = 'agsagagagdga';

		$ldap = new SHLdap($config);

		$ldap->connect();

		$this->assertFalse($ldap->proxyBind());
	}


	public function testSlapdSearchExceptionConnection()
	{
		$this->setExpectedException('RuntimeException', 'LIB_SHLDAP_ERR_10006', 10006);

		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));

		$ldap->search(null, '(uid=shaun.maunder)', array());
	}

	public function testSlapdSearchExceptionBind()
	{
		$this->setExpectedException('RuntimeException', 'LIB_SHLDAP_ERR_10007', 10007);

		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();

		$ldap->search(null, '(uid=shaun.maunder)', array());
	}

	/**
	 * @covers  SHLdap::allowAnonymous
	 * @covers  SHLdap::bind
	 */
	public function testSlapdAnonBind()
	{
		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();

		$this->assertFalse($ldap->bind());

		// Allow anonymous access
		$ldap->allowAnonymous();
		$this->assertTrue($ldap->bind());
	}

	/**
	 * @covers  SHLdap::bind
	 */
	public function testSlapdBind()
	{
		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();

		// Try 50 random users
		for ($i = 0; $i < 5; $i++)
		{
			$user = TestsHelper::getUserCreds();

			// No password (classed as anonymous)
			$this->assertFalse($ldap->bind($user['dn']));

			// Successful
			$this->assertTrue($ldap->bind($user['dn'], $user['password']));

			// Incorrect password
			$this->assertFalse($ldap->bind($user['dn'], ($user['password'] . 'asdhifoishg£$%^&*()%$££%^&*(')));
		}
	}

	public function testSlapdSearchSingleValid()
	{
		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		$user = TestsHelper::getUserCreds('shaun.maunder');

		// Restricted search to mail
		$result = $ldap->search(null, '(uid=shaun.maunder)', array('mail'));

		$this->assertInstanceOf('SHLdapResult', $result);
		$this->assertEquals(1, $result->countEntries());
		$this->assertEquals($user['dn'], $result->getDN(0));

		$this->assertEquals('shaun@shmanic.com', $result->getValue(0, 'mail', 0));
		$this->assertFalse($result->getValue(0, 'description', 0));

		// Unrestricted search
		$result = $ldap->search(null, '(uid=shaun.maunder)', array());

		$this->assertInstanceOf('SHLdapResult', $result);
		$this->assertEquals(1, $result->countEntries());
		$this->assertEquals($user['dn'], $result->getDN(0));

		$entry = $result->getEntry(0);

		$this->assertEquals('Shaun Maunder', $result->getValue(0, 'cn', 0));
		$this->assertEquals('Systems Admin Person', $result->getValue(0, 'description', 0));
		$this->assertEquals('/bin/bash', $result->getValue(0, 'loginShell', 0));
		$this->assertEquals('shaun@shmanic.com', $result->getValue(0, 'mail', 0));
	}

	public function testSlapdSearchMultipleValid()
	{
		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		// Restricted search to mail
		$result = $ldap->search(null, '(memberOf=cn=Artists,ou=Groups,dc=shmanic,dc=net)', array('dn'));

		$this->assertInstanceOf('SHLdapResult', $result);
		$this->assertGreaterThan(1, $result->countEntries());

		$o = array();

		foreach ($result->getResults() as $r)
		{
			$o[] = $r['dn'];
		}

		sort($o);

		$this->assertEquals(
			array(
				'uid=craig.david,ou=People,dc=shmanic,dc=net',
				'uid=justin.bieber,ou=People,dc=shmanic,dc=net',
				'uid=lister,ou=People,dc=shmanic,dc=net',
				'uid=rebecca.black,ou=People,dc=shmanic,dc=net'
			),
			$o
		);

		// Try with default filter
		$result = $ldap->search(null, null, array('dn'));

		$this->assertInstanceOf('SHLdapResult', $result);
		$this->assertGreaterThan(5, $result->countEntries());
	}

	public function testSlapdSearchInvalidFilter()
	{
		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		// Filter doesnt work
		$this->setExpectedException('SHLdapException', 'LIB_SHLDAP_ERR_10102', 10102);
		$result = $ldap->search(null, '(sada', array('mail'));
	}

	public function testSlapdSearchInvalidNoResults()
	{
		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		// No results
		$result = $ldap->search(null, '(uid=do.not.exist)', array('mail'));

		$this->assertInstanceOf('SHLdapResult', $result);
		$this->assertEquals(0, $result->countEntries());
	}

	public function testSlapdReadValid()
	{
		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		// Using DN without Filter
		$result = $ldap->read('uid=lister,ou=People,dc=shmanic,dc=net', null, array());

		$this->assertInstanceOf('SHLdapResult', $result);
		$this->assertEquals(1, $result->countEntries());
		$this->assertEquals('uid=lister,ou=People,dc=shmanic,dc=net', $result->getDN(0));

		$this->assertEquals('lister@shmanic.net', $result->getValue(0, 'mail', 0));
		$this->assertEquals('Dave Lister', $result->getValue(0, 'cn', 0));
	}

	public function testSlapdEntriesException()
	{
		$this->setExpectedException('InvalidArgumentException', 'LIB_SHLDAP_ERR_10121', 10121);

		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));

		$ldap->getEntries(array('this should be a resource'));
	}

	public function testSlapdSearchInvalidDn()
	{
		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		// Filter doesnt work
		$this->setExpectedException('SHLdapException', 'LIB_SHLDAP_ERR_10112', 10112);
		$result = $ldap->read('uid=lister,,,ou=People,dc=shmanic,dc=net', null, array());
	}

	public function testSlapdCompare()
	{
		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		$this->assertFalse($ldap->compare('uid=rimmer,ou=People,dc=shmanic,dc=net', 'cn', 'Ace Rimmer'));
		$this->assertTrue($ldap->compare('uid=rimmer,ou=People,dc=shmanic,dc=net', 'cn', 'Arnold Rimmer'));
	}

	public function testSlapdCompareException()
	{
		$this->setExpectedException('SHLdapException', 'LIB_SHLDAP_ERR_10131', 10131);

		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		$ldap->compare('uid=ace.rimmer,ou=People,dc=shmanic,dc=net', 'cn', 'Arnold Rimmer');
	}

	/**
	 * @covers  SHLdap::addAttributes
	 * @covers  SHLdap::deleteAttributes
	 */
	public function testSlapdAddCompareDeleteAttributes1()
	{
		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		$user = 'uid=morpheus,ou=Matrix,ou=People,dc=shmanic,dc=net';

		// Checks to make sure that attribute doesnt currently exist
		$result = $ldap->read($user, null, array('manager'));
		$this->assertEquals($user, $result->getDN(0));
		$this->assertEquals(0, $result->countValues(0, 'manager'));

		// Add two values to the manager attribute
		$this->assertTrue(
			$ldap->addAttributes(
				$user,
				array('manager' => array ('uid=oracle,ou=Matrix,ou=People,dc=shmanic,dc=net', 'uid=neo,ou=Matrix,ou=People,dc=shmanic,dc=net'))
			)
		);

		// Checks to make sure that attribute now exists
		$result = $ldap->read($user, null, array('manager'));
		$this->assertEquals($user, $result->getDN(0));
		$this->assertEquals(2, $result->countValues(0, 'manager'));

		// Delete the entire attribute
		$ldap->deleteAttributes($user, array('manager' => array()));

		// Checks to make sure that attribute doesnt exist again
		$result = $ldap->read($user, null, array('manager'));
		$this->assertEquals($user, $result->getDN(0));
		$this->assertEquals(0, $result->countValues(0, 'manager'));
	}

	/**
	 * @covers  SHLdap::replaceAttributes
	 */
	public function testSlapdReplaceCompareAttributes()
	{
		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		$user = TestsHelper::getUserCreds('neo');
		$key = 'description';
		$original = 'The MATRIX';
		$new = 'The One';

		// Checks to make sure that attribute doesnt currently exist
		$result = $ldap->read($user['dn'], null, array($key));
		$this->assertEquals($user['dn'], $result->getDN(0));
		$this->assertEquals($original, $result->getValue(0, $key, 0));

		$this->assertTrue(
			$ldap->replaceAttributes(
				$user['dn'],
				array($key => array ($new))
			)
		);

		// Checks to make sure that attribute now exists
		$result = $ldap->read($user['dn'], null, array($key));
		$this->assertEquals($user['dn'], $result->getDN(0));
		$this->assertEquals($new, $result->getValue(0, $key, 0));

		// Put it back again
		$ldap->replaceAttributes($user['dn'], array($key => array($original)));

		// Checks to make sure that attribute doesnt exist again
		$result = $ldap->read($user['dn'], null, array($key));
		$this->assertEquals($user['dn'], $result->getDN(0));
		$this->assertEquals($original, $result->getValue(0, $key, 0));
	}

	public function testSlapdAddAttributesException()
	{
		$this->setExpectedException('SHLdapException', 'LIB_SHLDAP_ERR_10171', 10171);

		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		$user = TestsHelper::getUserCreds('trinity');

		// Try to add an attribute that doesnt exist
		$this->assertTrue(
			$ldap->addAttributes(
				$user['dn'],
				array('attributedoesntexist' => array('asdasdas'))
			)
		);
	}

	public function testSlapdReplaceAttributesException()
	{
		$this->setExpectedException('SHLdapException', 'LIB_SHLDAP_ERR_10151', 10151);

		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		$user = TestsHelper::getUserCreds('trinity');

		// Try to replace an attribute that doesnt exist
		$this->assertTrue(
			$ldap->replaceAttributes(
				$user['dn'],
				array('attributedoesntexist' => array('asdasdas'))
			)
		);
	}

	public function testSlapdDeleteAttributesException()
	{
		$this->setExpectedException('SHLdapException', 'LIB_SHLDAP_ERR_10161', 10161);

		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		$user = TestsHelper::getUserCreds('trinity');

		// Try to delete an attribute that doesnt exist
		$this->assertTrue(
			$ldap->deleteAttributes(
				$user['dn'],
				array('attributedoesntexist' => array())
			)
		);
	}

	public function testSlapdGetUserDNSearch()
	{
		// Config uses a bracket in the user query
		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();

		$user = TestsHelper::getUserCreds('shaun.maunder');

		// Test Non-random one first
		$this->assertEquals(
			$user['dn'],
			JArrayHelper::getValue($ldap->getUserDnBySearch($user['username']), 0)
		);

		// Loop 50 times to test random users
		for ($i = 0; $i < 50; $i++)
		{
			// Get random user
			$user = TestsHelper::getUserCreds();

			$this->assertEquals(
				$user['dn'],
				JArrayHelper::getValue($ldap->getUserDnBySearch($user['username']), 0),
				"Failed to get User DN for {$user['dn']}"
			);
		}

		unset($ldap);

		// Config doesnt use a bracket in the user query
		$ldap = new SHLdap(TestsHelper::getLdapConfig(215));
		$ldap->connect();

		// Loop 50 times to test random users
		for ($i = 0; $i < 50; $i++)
		{
			// Get random user
			$user = TestsHelper::getUserCreds();

			$this->assertEquals(
				$user['dn'],
				JArrayHelper::getValue($ldap->getUserDnBySearch($user['username']), 0),
				"Failed to get User DN for {$user['dn']}"
			);
		}
	}

	public function testSlapdGetUserDNSearchProxyFail()
	{
		$this->setExpectedException('InvalidArgumentException', 'LIB_SHLDAP_ERR_10322', 10322);

		// Override the proxy user with something invalid
		$config = TestsHelper::getLdapConfig(214);
		$config['proxy_username'] = 'cn=donotexist,dc=shmanic,dc=net';

		$ldap = new SHLdap($config);
		$ldap->connect();

		$user = TestsHelper::getUserCreds();
		JArrayHelper::getValue($ldap->getUserDnBySearch($user['username']), 0);
	}

	public function testSlapdGetUserDNSearchBaseDnFail()
	{
		$this->setExpectedException('InvalidArgumentException', 'LIB_SHLDAP_ERR_10321', 10321);

		// Blank the base dn
		$config = TestsHelper::getLdapConfig(214);
		$config['base_dn'] = '';

		$ldap = new SHLdap($config);
		$ldap->connect();

		$user = TestsHelper::getUserCreds();
		JArrayHelper::getValue($ldap->getUserDnBySearch($user['username']), 0);
	}

	public function testSlapdGetUserDNDirectly()
	{
		$ldap = new SHLdap(TestsHelper::getLdapConfig(220));
		$ldap->connect();

		$user = TestsHelper::getUserCreds('shaun.maunder');

		$this->assertEquals(
			$user['dn'],
			JArrayHelper::getValue($ldap->getUserDnDirectly($user['username']), 0)
		);

		$user = TestsHelper::getUserCreds('kryten');

		$this->assertEquals(
			$user['dn'],
			JArrayHelper::getValue($ldap->getUserDnDirectly($user['username']), 0)
		);
	}

	public function testSlapdGetUserDNDirectlyInvalidQry()
	{
		$this->setExpectedException('InvalidArgumentException', 'LIB_SHLDAP_ERR_10331', 10331);

		// Use a filter instead of a DN
		$config = TestsHelper::getLdapConfig(220);
		$config['user_qry'] = '(uid=[username])';

		$ldap = new SHLdap($config);
		$ldap->connect();

		$user = TestsHelper::getUserCreds('shaun.maunder');

		JArrayHelper::getValue($ldap->getUserDnDirectly($user['username']), 0);
	}


	public function testSlapdGetUserDNNoUserQryFail()
	{
		$this->setExpectedException('InvalidArgumentException', 'LIB_SHLDAP_ERR_10301', 10301);

		// Blank the user query
		$config = TestsHelper::getLdapConfig(214);
		$config['user_qry'] = '';

		$ldap = new SHLdap($config);
		$ldap->connect();

		$user = TestsHelper::getUserCreds();
		$dn = $ldap->getUserDN($user['username'], $user['password'], true);
	}

	public function testSlapdGetUserDNSearchAuthFail()
	{
		$this->setExpectedException('SHExceptionInvalidUser', 'LIB_SHLDAP_ERR_10303', 10303);

		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();

		// We use a incorrect password here
		$user = TestsHelper::getUserCreds();
		$dn = $ldap->getUserDN($user['username'], ($user['password'] . 'kjfs!"£$%^&*()fkjsd'), true);
	}

	public function testSlapdGetUserDNSearchUsernameFail()
	{
		$this->setExpectedException('SHExceptionInvalidUser', 'LIB_SHLDAP_ERR_10302', 10302);

		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();

		// We use a incorrect username
		$user = TestsHelper::getUserCreds();
		$dn = $ldap->getUserDN($user['username'] . 'osjgo!"£$%^&*()', ($user['password']), true);
	}

	public function testSlapdGetUserDNSearchAuthSuccess()
	{
		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();

		// Loop 50 times to test random users
		for ($i = 0; $i < 50; $i++)
		{
			// Get random user
			$user = TestsHelper::getUserCreds();

			$this->assertEquals(
				$user['dn'],
				$ldap->getUserDN($user['username'], $user['password'], true),
				"Failed to get User DN for {$user['dn']}"
			);
		}
	}

	public function testSlapdGetUserDNSearchNoAuthSuccess()
	{
		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();

		// Loop 50 times to test random users
		for ($i = 0; $i < 50; $i++)
		{
			// Get random user
			$user = TestsHelper::getUserCreds();

			$this->assertEquals(
				$user['dn'],
				$ldap->getUserDN($user['username'], null, false),
				"Failed to get User DN for {$user['dn']}"
			);
		}
	}

	public function testSlapdGetUserDNSearchNoAuthFail()
	{
		$this->setExpectedException('SHExceptionInvalidUser', 'LIB_SHLDAP_ERR_10302', 10302);

		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();

		$user = TestsHelper::getUserCreds('shaun.maunder');

		$ldap->getUserDN($user['username'] . 'asdas', null, false);
	}

	public function testSlapdGetUserDNDirectlyAuthFail()
	{
		$this->setExpectedException('SHExceptionInvaliduser', 'LIB_SHLDAP_ERR_10304', 10304);

		$ldap = new SHLdap(TestsHelper::getLdapConfig(220));
		$ldap->connect();

		$user = TestsHelper::getUserCreds('shaun.maunder');

		$ldap->getUserDN($user['username'], ($user['password'] . 'kjfs!"£$%^&*()fkjsd'), true);
	}

	public function testSlapdGetUserDNDirectlyUsernameFail()
	{
		$this->setExpectedException('SHExceptionInvalidUser', 'LIB_SHLDAP_ERR_10304', 10304);

		$ldap = new SHLdap(TestsHelper::getLdapConfig(220));
		$ldap->connect();

		$user = TestsHelper::getUserCreds('shaun.maunder');

		$ldap->getUserDN($user['username'] . 'osjgo!"£$%^&*()', ($user['password']), true);
	}

	public function testSlapdGetUserDNDirectlyAuthSuccess()
	{
		$ldap = new SHLdap(TestsHelper::getLdapConfig(220));
		$ldap->connect();

		$user = TestsHelper::getUserCreds('shaun.maunder');

		$this->assertEquals(
			$user['dn'],
			$ldap->getUserDN($user['username'], $user['password'], true)
		);
	}

	public function testSlapdGetUserDNDirectlyNoAuthSuccess()
	{
		$ldap = new SHLdap(TestsHelper::getLdapConfig(220));
		$ldap->connect();

		$user = TestsHelper::getUserCreds('shaun.maunder');

		$this->assertEquals(
			$user['dn'],
			$ldap->getUserDN($user['username'], null, false)
		);
	}

	public function testSlapdGetUserDNDirectlyNoAuthFail()
	{
		$this->setExpectedException('SHExceptionInvaliduser', 'LIB_SHLDAP_ERR_10305', 10305);

		$ldap = new SHLdap(TestsHelper::getLdapConfig(220));
		$ldap->connect();

		$user = TestsHelper::getUserCreds('shaun.maunder');

		$ldap->getUserDN($user['username'] . 'sadhrtresa', null, false);
	}

	public function testSlapdGetUserDNDirectlyNoAuthNoProxy()
	{
		// Kill the proxy user
		$config = TestsHelper::getLdapConfig(220);
		$config['proxy_username'] = 'asdsadsadas';

		$ldap = new SHLdap($config);
		$ldap->connect();

		$user = TestsHelper::getUserCreds('shaun.maunder');

		// It should come back with the DN
		$this->assertEquals(
			$user['dn'],
			$ldap->getUserDN($user['username'], null, false)
		);
	}

	public function testSlapdErrorFunctions()
	{
		$config = TestsHelper::getLdapConfig(214);

		$ldap = new SHLdap($config);
		$ldap->connect();
		$ldap->proxyBind();

		try
		{
			$ldap->read('cn=doesntexist,dc=shmanic,dc=net');
		}
		catch (Exception $e)
		{
			if ($code = $ldap->getErrorCode() === 32)
			{
				if (!$ldap->getErrorMsg() === SHLdap::errorToString($code))
				{
					$this->fail('Incorrect response message');
				}
			}
		}
	}

	/**
	 * @covers  SHLdap::getInstance
	 */
	public function testSlapdGetInstanceNoAuth()
	{
		$platform = SHFactory::getConfig('file', array('file' => static::PLATFORM_CONFIG_FILE));

		$ldap = SHLdap::getInstance(null, array(), $platform);

		$ldap->connect();

		// Loop 50 times to test random users
		for ($i = 0; $i < 50; $i++)
		{
			// Get random user
			$user = TestsHelper::getUserCreds();

			$this->assertEquals(
				$user['dn'],
				$ldap->getUserDN($user['username'], null, false),
				"Failed to get User DN for {$user['dn']}"
			);
		}
	}

	/**
	 * @covers  SHLdap::getInstance
	 */
	public function testSlapdGetInstanceAuthSuccess()
	{
		$platform = SHFactory::getConfig('file', array('file' => static::PLATFORM_CONFIG_FILE));

		$user = TestsHelper::getUserCreds('shaun.maunder');

		$auth = array('authenticate' => SHLdap::AUTH_USER, 'username' => $user['username'], 'password' => $user['password']);

		$ldap = SHLdap::getInstance(null, $auth, $platform);

		$ldap->connect();

		// Loop 50 times to test random users
		for ($i = 0; $i < 50; $i++)
		{
			// Get random user
			$user = TestsHelper::getUserCreds();

			$this->assertEquals(
				$user['dn'],
				$ldap->getUserDN($user['username'], null, false),
				"Failed to get User DN for {$user['dn']}"
			);
		}
	}

	/**
	 * @covers  SHLdap::getInstance
	 */
	public function testSlapdGetInstanceAuthFailure()
	{
		$this->setExpectedException('SHExceptionStacked', 'LIB_SHLDAP_ERR_10411', 10411);

		$platform = SHFactory::getConfig('file', array('file' => static::PLATFORM_CONFIG_FILE));

		$user = TestsHelper::getUserCreds('shaun.maunder');

		$auth = array('authenticate' => SHLdap::AUTH_USER, 'username' => $user['username'], 'password' => $user['password'] . 'asdas');

		$ldap = SHLdap::getInstance(null, $auth, $platform);

		$ldap->connect();
	}

	/**
	 * @covers SHLdap::__get
	 */
	public function testMagicGetMethod()
	{
		$user = TestsHelper::getUserCreds('shaun.maunder');

		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();

		// Test Bind Status
		$this->assertEquals(SHLdap::AUTH_NONE, $ldap->bindStatus);
		$ldap->proxyBind();
		$this->assertEquals(SHLdap::AUTH_PROXY, $ldap->bindStatus);
		$ldap->bind('asdasdas', 'asdasdas');
		$this->assertEquals(SHLdap::AUTH_NONE, $ldap->bindStatus);
		$ldap->bind($user['dn'], $user['password']);
		$this->assertEquals(SHLdap::AUTH_USER, $ldap->bindStatus);

		// Rinse and Go
		$ldap = new SHLdap(TestsHelper::getLdapConfig(214));
		$ldap->connect();

		// Test Last User DN
		$this->assertNull($ldap->lastUserDn);
		$ldap->getUserDN($user['username'], $user['password']);
		$this->assertEquals($user['dn'], $ldap->lastUserDn);

		// Test Proxy Write
		$this->assertFalse($ldap->proxyWrite);

		// Test All user Filter
		$this->assertEquals('(objectclass=user)', $ldap->allUserFilter);

		// Rinse and Go
		$ldap = new SHLdap(TestsHelper::getLdapConfig(216));
		$ldap->connect();

		// Test Key for Name Attribute
		$this->assertEquals('cn', $ldap->keyName);
		$this->assertEquals('mail', $ldap->keyEmail);
		$this->assertEquals('uid', $ldap->keyUid);
		$this->assertEquals('uid', $ldap->ldap_uid);

		// Test Information
		$this->assertEquals('ldap1.shmanic.net:389', $ldap->info);

		// Test something that doesn't exist
		$this->assertNull($ldap->doesntexist);
	}

	public function testSlapdAuthenticateSuccess()
	{
		$user = TestsHelper::getUserCreds('shaun.maunder');

		$ldap = new SHLdap(TestsHelper::getLdapConfig(216));

		$this->assertTrue($ldap->authenticate(SHLdap::AUTH_USER, $user['username'], $user['password']));
		$this->assertTrue($ldap->authenticate(SHLdap::AUTH_PROXY));
		$this->assertTrue($ldap->authenticate(SHLdap::AUTH_NONE));
		$this->assertTrue($ldap->authenticate(SHLdap::AUTH_NONE, $user['username']));
	}

	public function testSlapdAuthenticateWrongUsername2Exception()
	{
		$this->setExpectedException('SHExceptionInvaliduser', 'LIB_SHLDAP_ERR_10302', 10302);

		$user = TestsHelper::getUserCreds('shaun.maunder');

		$ldap = new SHLdap(TestsHelper::getLdapConfig(216));

		$ldap->authenticate(SHLdap::AUTH_NONE, $user['username'] . ')(*&^%$£"!"£%^&*()');
	}

	public function testSlapdAuthenticateWrongUsernameException()
	{
		$this->setExpectedException('SHExceptionInvaliduser', 'LIB_SHLDAP_ERR_10302', 10302);

		$user = TestsHelper::getUserCreds('shaun.maunder');

		$ldap = new SHLdap(TestsHelper::getLdapConfig(216));

		$ldap->authenticate(SHLdap::AUTH_USER, $user['username'] . ')(*&^%$£"!"£%^&*()', $user['password']);
	}

	public function testSlapdAuthenticateWrongPasswordException()
	{
		$this->setExpectedException('SHExceptionInvaliduser', 'LIB_SHLDAP_ERR_10303', 10303);

		$user = TestsHelper::getUserCreds('shaun.maunder');

		$ldap = new SHLdap(TestsHelper::getLdapConfig(216));

		$ldap->authenticate(SHLdap::AUTH_USER, $user['username'], $user['password'] . ')(*&^%$£"!"£%^&*()');
	}

	/**
	 * TODO: move to a SHPlatform specific test in the future
	 */
	public function testSHPlatformFactoryBadConfig()
	{
		$this->setExpectedException('RuntimeException', 'LIB_SHPLATFORM_ERR_1121', 1121);

		$platform = SHFactory::getConfig('file', array('file' => static::PLATFORM_CONFIG_FILE, 'namespace' => 'asdas'));
	}
}
