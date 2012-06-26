<?php
/**
 * Example Test Case.
 *
 * PHP Version 5.3
 *
 * @package     JMMLDAP.Tests
 * @subpackage  Cases
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @copyright   Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Example Test Case Class.
 *
 * @package     JMMLDAP.Tests
 * @subpackage  Cases
 * @since       2.0
 */
class TCasesLdapbaseSearchread extends TPluginsTestcasesTestcase implements TPluginsTestcasesItestcase
{

	/**
	 * (non-PHPdoc)
	 *
	 * @see     TPluginsTestcasesItestcase::initialise()
	 * @return  boolean
	 *
	 * @since  2.0
	 */
	public function initialise()
	{
		// Ensure the JMMLdap Factory & Autoloader have been registered
		if (!defined('SH_PLATFORM'))
		{
			if (!TCasesHelper::doBoot())
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see     TPluginsTestcasesItestcase::runTests()
	 * @return  TPluginsTestcasesUnitresult[]
	 *
	 * @since  2.0
	 */
	public function runTests()
	{
		$results = array();

		$a = new SHLdapBase(TCasesHelper::getLdapConfig(1));
		$a->connect();
		$a->proxyBind();

		// Test: whether we get TUser1 attributes back
		$result1 = $a->search('dc=home,dc=local', '(sAMAccountName=tuser1)');

		TPluginsTestcasesHelper::pushTestUnit(
			$results, 'TUser1', array($result1)
		);


		// Test: whether we get groups back
		$result2 = $a->search('dc=home,dc=local', '(objectClass=group)');

		TPluginsTestcasesHelper::pushTestUnit(
			$results, 'SearchGroups', array($result2)
		);

		// Test: do we get a blank result
		$result3 = $a->search('dc=home,dc=local', '(sAMAccountName=tnouser)');

		TPluginsTestcasesHelper::pushTestUnit(
			$results, 'SearchNoRes', array($result3)
		);

		// Ensure we only get specific attributes back
		$result4 = $a->search('dc=home,dc=local', '(sAMAccountName=tuser1)', array('name'));

		TPluginsTestcasesHelper::pushTestUnit(
			$results, 'OnlyAttributes', array($result4)
		);


		// Test: read TUser1 - Must specify default filter in base Ldap
		$result5 = $a->read('CN=Test User1,OU=Tests,OU=Home,DC=HOME,DC=LOCAL', '(objectclass=*)');

		TPluginsTestcasesHelper::pushTestUnit(
			$results, 'TUser1', array($result5)
		);

		// Test: try read a non-existent user - blank result
		$result6 = $a->read('CN=NoneExist,OU=blabla,DC=HOME,DC=LOCAL', '(objectclass=*)');

		TPluginsTestcasesHelper::pushTestUnit(
			$results, 'ReadNoRes', array($result6)
		);

		// Ensure we only get specific attributes back
		$result7 = $a->read('CN=Test User1,OU=Tests,OU=Home,DC=HOME,DC=LOCAL', '(sAMAccountName=*)', array('name'));

		TPluginsTestcasesHelper::pushTestUnit(
			$results, 'OnlyAttributes', array($result7)
		);

		/*
		 * LDAP Compare tests. TODO: test these out
		 */
		$result = $a->compare('HOME\tuser1', 'sn', 'tuser1');
		assert('$result===-1 /* This should be -1 as its invalid */');

		$result = $a->compare('CN=Test User1,OU=Tests,OU=Home,DC=HOME,DC=LOCAL', 'sn', 'tuser1');
		assert('$result===false /* This should be false as it doesnt match */');

		$result = $a->compare('CN=Test User1,OU=Tests,OU=Home,DC=HOME,DC=LOCAL', 'sn', 'user1');
		assert('$result===true /* This should be true as it does match */');


		/*TPluginsTestcasesHelper::pushTestUnit(
			$results, 'Test1', 'VALUE_TO_TEST'
		);*/


		return $results;
	}

	/*
	 * The following methods check the result and are passed a
	 * reference to a UnitResult. They return a boolean value
	 * where True means passed and False means failed.
	 *
	 * -- These will not use PHPdoc --
	 */

	public function testTUser1(TPluginsTestcasesUnitresult &$result)
	{
		$value = $result->getResult();

		$attributes = $value[0];

		if ($attributes === false)
		{
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Search/read failed');
			return false;
		}

		if (!isset($attributes[0]['dn']) || empty($attributes[0]['dn']))
		{
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Failed to get DN');
			return false;
		}

		if (!isset($attributes[0]['mail'][0]) || $attributes[0]['mail'][0] != 'tuser1@exa1mple.com1')
		{
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Incorrect email');
			return false;
		}

		$result->addMessage('Good attribute search');
		$result->setStatus(TPluginsTestcasesUnitresult::STATUS_SUCCESS);

		return true;
	}

	public function testSearchGroups(TPluginsTestcasesUnitresult &$result)
	{
		$value = $result->getResult();

		$attributes = $value[0];

		if ($attributes === false)
		{
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Search failed');
			return false;
		}

		// Assume 10 or more groups
		if (count($attributes) < 10)
		{
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Not enough groups');
			return false;
		}

		if (!isset($attributes[4]['dn']) || empty($attributes[4]['dn']))
		{
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Failed to get DN for group 4');
			return false;
		}

		$result->addMessage('Good attribute read/search');
		$result->setStatus(TPluginsTestcasesUnitresult::STATUS_SUCCESS);

		return true;
	}

	public function testSearchNoRes(TPluginsTestcasesUnitresult &$result)
	{
		$value = $result->getResult();

		$attributes = $value[0];

		if ($attributes === false)
		{
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Search failed');
			return false;
		}

		if (count($attributes))
		{
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Results found');
			return false;
		}

		$result->addMessage('No results found');
		$result->setStatus(TPluginsTestcasesUnitresult::STATUS_SUCCESS);

		return true;
	}

	public function testReadNoRes(TPluginsTestcasesUnitresult &$result)
	{
		$value = $result->getResult();

		$attributes = $value[0];

		if ($attributes !== false)
		{
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Read was success (bad)');
			return false;
		}

		$result->addMessage('Read failed (good)');
		$result->setStatus(TPluginsTestcasesUnitresult::STATUS_SUCCESS);

		return true;
	}

	public function testOnlyAttributes(TPluginsTestcasesUnitresult &$result)
	{
		$value = $result->getResult();

		$attributes = $value[0];

		if ($attributes === false)
		{
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Search/read failed');
			return false;
		}

		if (!isset($attributes[0]['dn']) || empty($attributes[0]['dn']))
		{
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Failed to get DN');
			return false;
		}

		if (!isset($attributes[0]['name'][0]) || $attributes[0]['name'][0] != 'Test User1')
		{
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Incorrect name');
			return false;
		}

		if (isset($attributes[0]['mail'][0]))
		{
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Mail present (bad)');
			return false;
		}

		$result->addMessage('Good attribute read/search');
		$result->setStatus(TPluginsTestcasesUnitresult::STATUS_SUCCESS);

		return true;
	}

}
