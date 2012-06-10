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
class TCasesLdapclientSearchread extends TPluginsTestcasesTestcase implements TPluginsTestcasesItestcase
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
		
		// Test: Bind proxy user should work
		$a = new SHLdapClient(TCasesLdapclientHelper::getLdapConfig(1));
		$a->connect();
		$a->proxyBind();
		
		$result = $a->search('dc=home,dc=local', '(sAMAccountName=tuser1)');
		
		//print_r($result);
		
		/*
		 * COMPARE ASSERTION
		 */
		$result = $a->compare('HOME\tuser1', 'sn', 'tuser1');
		assert('$result===-1 /* This should be -1 as its invalid */');
		
		$result = $a->compare('CN=Test User1,OU=Tests,OU=Home,DC=HOME,DC=LOCAL', 'sn', 'tuser1');
		assert('$result===false /* This should be false as it doesnt match */');
		
		$result = $a->compare('CN=Test User1,OU=Tests,OU=Home,DC=HOME,DC=LOCAL', 'sn', 'user1');
		assert('$result===true /* This should be true as it does match */');
		
	
		
		$aa = new SHLdapExtended(TCasesLdapclientHelper::getLdapConfig(80));
		$aa->connect();
		$aa->proxyBind();
		$result = $aa->search('dc=home,dc=local', '(sAMAccountName=tuser1)');
		
		assert('$result instanceof SHLdapResult /* This should be true if the constant for result object is true */');
		
		//echo $aa->getUserDN('tuser1', 'tuser1', true); die();
		
		/*print_r($result->countEntries());
		//print_r($result->getResults());
		//print_r($result->getEntry(0));
		print_r($result->getAttribute(0, 'sn'));
		print_r($result->getValue(0, 'sn', 0)); echo '   ';
		echo $result->countAttributes(0);
		echo $result->countValues(0, 'sn');
		die();*/
		
		/*TPluginsTestcasesHelper::pushTestUnit(
				$results, 'GoodProxy', array($result, &$a)
		);*/
		
		
		TPluginsTestcasesHelper::pushTestUnit(
			$results, 'Test1', 'VALUE_TO_TEST'
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
	
	public function testTest1(TPluginsTestcasesUnitresult &$result)
	{
		$value = $result->getResult();

		if ($value != 'VALUE_TO_TEST') {
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('Value incorrect');
			return false;
		}
		
		$result->setStatus(TPluginsTestcasesUnitresult::STATUS_SUCCESS);
		return true;
	}
	
}