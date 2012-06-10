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
class TCasesLdapclientConnect extends TPluginsTestcasesTestcase implements TPluginsTestcasesItestcase
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
			if (!TCasesLdapclientHelper::doBoot())
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

		
		// Test: No host
		$a = new SHLdapClient(TCasesLdapclientHelper::getLdapConfig(4));
		$result = $a->connect();
		
		TPluginsTestcasesHelper::pushTestUnit(
			$results, 'NoHost', array($result, &$a)
		);
		
		// Test: Try to start TLS
		$b = new SHLdapClient(TCasesLdapclientHelper::getLdapConfig(5));
		$result = $b->connect();
		
		TPluginsTestcasesHelper::pushTestUnit(
			$results, 'StartTLS', array($result, &$b)
		);
		
		// Test: Should work (AD)
		$c = new SHLdapClient(TCasesLdapclientHelper::getLdapConfig(1));
		$result = $c->connect();
		
		TPluginsTestcasesHelper::pushTestUnit(
			$results, 'Good', array($result, &$c)
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
	
	public function testNoHost(TPluginsTestcasesUnitresult &$result)
	{
		$value = $result->getResult();

		$retVal = $value[0];
		$object = $value[1];

		if ($retVal !== false) {
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Function didnt return false');
			return false;
		}
		
		$error = $object->getError(null, false);
		if ($error instanceOf SHLdapException)
		{
			if ($error->getCode() != '10001')
			{
				$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
				$result->addMessage('Incorrect internal error code ' . $error->getCode() . ' should be 10001');
				return false;
			}
		}
		else
		{
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('No SHLdapExeception occurred');
			return false;
		}
		
		$result->addMessage('Successfully caught the error');
		$result->setStatus(TPluginsTestcasesUnitresult::STATUS_SUCCESS);
		return true;
	}
	
	public function testStartTLS(TPluginsTestcasesUnitresult &$result)
	{
		$value = $result->getResult();
	
		$retVal = $value[0];
		$object = $value[1];
	
		if ($retVal !== false) {
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Function didnt return false');
			return false;
		}
	
		$error = $object->getError(null, false);
		if ($error instanceOf SHLdapException)
		{
			if ($error->getCode() != '10005')
			{
				$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
				$result->addMessage("Incorrect internal error code {$error->getCode()} should be 10005");
				return false;
			}
			
			if ($error->getLdapCode() != '52')
			{
				$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
				$result->addMessage("Incorrect ldap error code {$error->getLdapCode()} should be 52");
				return false;
			}
			
			if ($error->getLdapMessage() != 'Server is unavailable')
			{
				$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
				$result->addMessage("Incorrect ldap message {$error->getLdapMessage()}");
				return false;
			}
			
		}
		else
		{
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('No SHLdapExeception occurred');
			return false;
		}
	
		$result->addMessage('Successfully caught the error');
		$result->setStatus(TPluginsTestcasesUnitresult::STATUS_SUCCESS);
		return true;
	}
	
	public function testGood(TPluginsTestcasesUnitresult &$result)
	{
		$value = $result->getResult();
	
		$retVal = $value[0];
		$object = $value[1];
	
		if ($retVal !== true) {
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Function did not succeed');
			return false;
		}
		
		if ($object->getError() !== false)
		{
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('An error has occurred, but shouldnt have');
			return false;
		}
		
		$result->addMessage('No error found');
		$result->setStatus(TPluginsTestcasesUnitresult::STATUS_SUCCESS);
		return true;
	}
}