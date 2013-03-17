<?php

class SHLdapTest extends PHPUnit_Framework_TestCase
{

	const ENCRYPTION_KEY_FILE = 'ldap_encrypt_key.txt';

	public function setUp()
	{
		$this->configs = static::getLdapConfig(80);

		fwrite(fopen(static::ENCRYPTION_KEY_FILE, 'w'), 'ym0ZBkTbDbYrQzjMM7COYnLYuArlq31UIfDyBj11gpeeVLlXeGYPQ7Qf71TPDlN8dVWQfsFbf5SteVXoNzQeiH3EHMFjQtyvmtDNv6kAqUa0Bc7r8QdN5H7VQXtARk1uYCwBqi4sYm1rRaUOJqDCRL64bj4ykeqyouPw8CscmK0hnikpQWSL9MKtJjNyathdSx3rVWE4YiIrgij8ELGjELwl7JQrztCSLAbRfQJafAQ6xGXUDRslRK4T4w2vtBMb');
	}

	public function tearDown()
	{
		unlink (static::ENCRYPTION_KEY_FILE);
	}

	/**
	 * @covers  SHLdap::connect
	 */
	public function testSlapdConnectSuccess()
	{
		$config = static::getLdapConfig(201);

		$ldap = new SHLdap($config);

		$this->assertTrue($ldap->connect());
	}

	/**
	 * @covers  SHLdap::connect
	 */
	public function testSlapdConnectFailure()
	{
		$config = static::getLdapConfig(202);

		$ldap = new SHLdap($config);

		try
		{
			$ldap->connect();
		}
		catch (Exception $e)
		{
			if ($e->getCode() === 10005)
			{
				return;
			}
			else
			{
				$this->fail('Incorrect error code ' . $e->getCode());
			}
		}

		$this->fail('No exception on TLS connection failure');
	}

	/**
	 * @covers  SHLdap::proxyBind
	 */
	public function testSlapdProxyBindUnencryptedSuccess()
	{
		$config = static::getLdapConfig(203);

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
		$config = static::getLdapConfig(204);

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
		$config = static::getLdapConfig(205);

		$ldap = new SHLdap($config);

		$ldap->connect();

		$this->assertTrue($ldap->proxyBind());
	}

	/**
	 * @covers  SHLdap::proxyBind
	 */
	public function testSlapdProxyBindFileEncyptedFailure()
	{
		$config = static::getLdapConfig(208);

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
		$config = static::getLdapConfig(209);

		$ldap = new SHLdap($config);

		$ldap->connect();

		$this->assertFalse($ldap->proxyBind());
	}


	public function testSlapdSearchExceptionConnection()
	{
		$this->setExpectedException('RuntimeException', 'LIB_SHLDAP_ERR_10006', 10006);

		$ldap = new SHLdap(static::getLdapConfig(214));

		$ldap->search(null, '(uid=shaun.maunder)', array());
	}

	public function testSlapdSearchExceptionBind()
	{
		$this->setExpectedException('RuntimeException', 'LIB_SHLDAP_ERR_10007', 10007);

		$ldap = new SHLdap(static::getLdapConfig(214));
		$ldap->connect();

		$ldap->search(null, '(uid=shaun.maunder)', array());
	}

	/**
	 * @covers  SHLdap::allowAnonymous
	 * @covers  SHLdap::bind
	 */
	public function testSlapdAnonBind()
	{
		$ldap = new SHLdap(static::getLdapConfig(214));
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
		$ldap = new SHLdap(static::getLdapConfig(214));
		$ldap->connect();

		$username = 'uid=shaun.maunder,ou=People,dc=shmanic,dc=net';

		// No password (classed as anonymous)
		$this->assertFalse($ldap->bind($username));

		// Successful
		$this->assertTrue($ldap->bind($username, 'password'));

		// Incorrect password
		$this->assertFalse($ldap->bind($username, 'wrongpassword'));
	}

	public function testSlapdSearchSingleValid()
	{
		$ldap = new SHLdap(static::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		// Restricted search to mail
		$result = $ldap->search(null, '(uid=shaun.maunder)', array('mail'));

		$this->assertInstanceOf('SHLdapResult', $result);
		$this->assertEquals(1, $result->countEntries());
		$this->assertEquals('uid=shaun.maunder,ou=People,dc=shmanic,dc=net', $result->getDN(0));

		$this->assertEquals('shaun@shmanic.com', $result->getValue(0, 'mail', 0));
		$this->assertFalse($result->getValue(0, 'description', 0));

		// Unrestricted search
		$result = $ldap->search(null, '(uid=shaun.maunder)', array());

		$this->assertInstanceOf('SHLdapResult', $result);
		$this->assertEquals(1, $result->countEntries());
		$this->assertEquals('uid=shaun.maunder,ou=People,dc=shmanic,dc=net', $result->getDN(0));

		$entry = $result->getEntry(0);

		$this->assertEquals('Shaun Maunder', $result->getValue(0, 'cn', 0));
		$this->assertEquals('Systems Admin Person', $result->getValue(0, 'description', 0));
		$this->assertEquals('/bin/bash', $result->getValue(0, 'loginShell', 0));
		$this->assertEquals('shaun@shmanic.com', $result->getValue(0, 'mail', 0));
	}

	public function testSlapdSearchMultipleValid()
	{
		$ldap = new SHLdap(static::getLdapConfig(214));
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
		$ldap = new SHLdap(static::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		// Filter doesnt work
		$this->setExpectedException('SHLdapException', 'LIB_SHLDAP_ERR_10102', 10102);
		$result = $ldap->search(null, '(sada', array('mail'));
	}

	public function testSlapdSearchInvalidNoResults()
	{
		$ldap = new SHLdap(static::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		// No results
		$result = $ldap->search(null, '(uid=do.not.exist)', array('mail'));

		$this->assertInstanceOf('SHLdapResult', $result);
		$this->assertEquals(0, $result->countEntries());
	}

	public function testSlapdReadValid()
	{
		$ldap = new SHLdap(static::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		// Using DN without Filter
		$result = $ldap->read('uid=lister,ou=People,dc=shmanic,dc=net', null, array());

		$this->assertInstanceOf('SHLdapResult', $result);
		$this->assertEquals(1, $result->countEntries());
		$this->assertEquals('uid=lister,ou=People,dc=shmanic,dc=net', $result->getDN(0));

		$this->assertEquals('lister@shmanic.net', $result->getValue(0, 'mail', 0));
		$this->assertEquals('Dave Lister', $result->getValue(0, 'cn', 0));

		// Using Filter without Base DN - would this ever work?
		//$result = $ldap->read(null, '(cn=admin)', array());
	}

	public function testSlapdEntriesException()
	{
		$this->setExpectedException('InvalidArgumentException', 'LIB_SHLDAP_ERR_10121', 10121);

		$ldap = new SHLdap(static::getLdapConfig(214));

		$ldap->getEntries(array('this should be a resource'));
	}

	public function testSlapdSearchInvalidDn()
	{
		$ldap = new SHLdap(static::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		// Filter doesnt work
		$this->setExpectedException('SHLdapException', 'LIB_SHLDAP_ERR_10112', 10112);
		$result = $ldap->read('uid=lister,,,ou=People,dc=shmanic,dc=net', null, array());
	}

	public function testSlapdCompare()
	{
		$ldap = new SHLdap(static::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		$this->assertFalse($ldap->compare('uid=rimmer,ou=People,dc=shmanic,dc=net', 'cn', 'Ace Rimmer'));
		$this->assertTrue($ldap->compare('uid=rimmer,ou=People,dc=shmanic,dc=net', 'cn', 'Arnold Rimmer'));
	}

	public function testSlapdCompareException()
	{
		$this->setExpectedException('SHLdapException', 'LIB_SHLDAP_ERR_10131', 10131);

		$ldap = new SHLdap(static::getLdapConfig(214));
		$ldap->connect();
		$ldap->proxyBind();

		$ldap->compare('uid=ace.rimmer,ou=People,dc=shmanic,dc=net', 'cn', 'Arnold Rimmer');
	}

	/**
	 * @covers  SHLdap::addAttributes
	 * @covers  SHLdap::deleteAttributes
	 */
	public function testSlapdAddCompareDeleteAttributes()
	{
		$ldap = new SHLdap(static::getLdapConfig(214));
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
	 * Read in the case XML file and parse it to an
	 * array in the form array(category=>case).
	 *
	 * @param string $file Path to the XML file
	 *
	 * @return array Array of cases
	 * @since  1.0
	 */
	public static function getLdapConfig($id, $file = null)
	{
		if (is_null($file))
		{
			$file = __DIR__ . '/configs.xml';
		}

		if (!is_file($file))
		{
			return false;
		}

		$result = array();

		// Load the XML file
		$xml = \simplexml_load_file($file, 'SimpleXMLElement');

		// Get all the category tags ignoring anything else in the file
		$configs = $xml->xpath("/configs/config[@id={$id}]");

		foreach ($configs as $config)
		{
			foreach($config as $key=>$value)
			{

				if (!is_array($value))
				{
					$result[$key] = (string) $value[0];
				}

			}
		}

		return $result;
	}
}
