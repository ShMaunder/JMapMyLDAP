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
class TCasesLdapbaseBind extends TPluginsTestcasesTestcase implements TPluginsTestcasesItestcase
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

		// Test: Bind proxy user should work
		$a = new SHLdapBase(TCasesHelper::getLdapConfig(1));
		$a->connect();
		$result = $a->proxyBind();

		TPluginsTestcasesHelper::pushTestUnit(
			$results, 'GoodProxy', array($result, &$a)
		);

		// Test: Bind proxy user should NOT work
		$b = new SHLdapBase(TCasesHelper::getLdapConfig(3));
		$b->connect();
		$result = $b->proxyBind();

		TPluginsTestcasesHelper::pushTestUnit(
				$results, 'BadProxy', array($result, &$b)
		);

		// Test: Attempt anonymous bind (should fail)
		$c = new SHLdapBase(TCasesHelper::getLdapConfig(1));
		$c->connect();
		$c->allowAnonymous(true);
		$result = $c->bind();

		TPluginsTestcasesHelper::pushTestUnit(
				$results, 'GoodAnon', array($result, &$c)
		);

		// Test: Attempt anonymous bind (should fail)
		$d = new SHLdapBase(TCasesHelper::getLdapConfig(1));
		$d->connect();
		$result = $d->bind();

		TPluginsTestcasesHelper::pushTestUnit(
				$results, 'BadAnon', array($result, &$d)
		);

		// Test: Attempt good standard bind
		$config = TCasesHelper::getLdapConfig(6);
		$e = new SHLdapBase($config);
		$e->connect();
		$result = $e->bind($config['test_username'], $config['test_password']);

		TPluginsTestcasesHelper::pushTestUnit(
				$results, 'GoodBind', array($result, &$e)
		);

		// Test: Attempt bad standard bind
		$config = TCasesHelper::getLdapConfig(7);
		$f = new SHLdapBase($config);
		$f->connect();
		$result = $f->bind($config['test_username'], $config['test_password']);

		TPluginsTestcasesHelper::pushTestUnit(
				$results, 'BadBind', array($result, &$f)
		);


		return $results;
	}

	/*
	 * The following methods check the result and are passed a
	 * reference to a UnitResult. They return a boolean value
	 * where True means passed and False means failed.
	 *
	 * -- These will not use PHPdoc --
	 */

	public function testGoodProxy(TPluginsTestcasesUnitresult &$result)
	{
		$value = $result->getResult();

		$retVal = $value[0];
		$object = $value[1];

		if ($retVal !== true) {
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Function didnt return true');
			return false;
		}

		$result->addMessage('Binded with proxy user');
		$result->setStatus(TPluginsTestcasesUnitresult::STATUS_SUCCESS);
		return true;
	}

	public function testBadProxy(TPluginsTestcasesUnitresult &$result)
	{
		$value = $result->getResult();

		$retVal = $value[0];
		$object = $value[1];

		if ($retVal !== false) {
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Function didnt return false');
			return false;
		}

		$result->addMessage('Failed to bind proxy user');
		$result->setStatus(TPluginsTestcasesUnitresult::STATUS_SUCCESS);
		return true;
	}

	public function testGoodAnon(TPluginsTestcasesUnitresult &$result)
	{
		$value = $result->getResult();

		$retVal = $value[0];
		$object = $value[1];

		if ($retVal !== true) {
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Function didnt return true');
			return false;
		}

		$result->addMessage('Binded with anonymous user');
		$result->setStatus(TPluginsTestcasesUnitresult::STATUS_SUCCESS);
		return true;
	}

	public function testBadAnon(TPluginsTestcasesUnitresult &$result)
	{
		$value = $result->getResult();

		$retVal = $value[0];
		$object = $value[1];

		if ($retVal !== false) {
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Function didnt return false');
			return false;
		}

		$result->addMessage('Failed to bind anonymous user');
		$result->setStatus(TPluginsTestcasesUnitresult::STATUS_SUCCESS);
		return true;
	}

	public function testGoodBind(TPluginsTestcasesUnitresult &$result)
	{
		$value = $result->getResult();

		$retVal = $value[0];
		$object = $value[1];

		if ($retVal !== true) {
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Function didnt return true');
			return false;
		}

		$result->addMessage('Binded with user');
		$result->setStatus(TPluginsTestcasesUnitresult::STATUS_SUCCESS);
		return true;
	}

	public function testBadBind(TPluginsTestcasesUnitresult &$result)
	{
		$value = $result->getResult();

		$retVal = $value[0];
		$object = $value[1];

		if ($retVal !== false) {
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Function didnt return false');
			return false;
		}

		$result->addMessage('Failed to bind user');
		$result->setStatus(TPluginsTestcasesUnitresult::STATUS_SUCCESS);
		return true;
	}
}
