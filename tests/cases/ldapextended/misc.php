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
class TCasesLdapextendedMisc extends TPluginsTestcasesTestcase implements TPluginsTestcasesItestcase
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

		$a = new SHLdap(TCasesHelper::getLdapConfig(80));
		$a->connect();
		$a->proxyBind();




		// Test: No host
		/*$a = new SHLdapBase(TCasesHelper::getLdapConfig(4));
		$result = $a->connect();

		TPluginsTestcasesHelper::pushTestUnit(
			$results, 'NoHost', array($result, &$a)
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


}
