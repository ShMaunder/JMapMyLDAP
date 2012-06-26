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
class TCasesShplatformConfig extends TPluginsTestcasesTestcase implements TPluginsTestcasesItestcase
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
			$boot = JPATH_PLATFORM . '/shmanic/import.php';

			if (is_file($boot))
			{
				require_once($boot);
			}

			if (!class_exists('SHLdap'))
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


		// Test: Good SQL Config
		$a = SHFactory::getConfig('sql');

		TPluginsTestcasesHelper::pushTestUnit(
			$results, 'GoodSQL', array(&$a)
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

	public function testGoodSQL(TPluginsTestcasesUnitresult &$result)
	{
		$value = $result->getResult();

		/** @var JRegistry */
		$registry = $value[0];

		$version = $registry->get('platform.version');

		if (empty($version))
		{
			$result->setStatus(TPluginsTestcasesUnitresult::STATUS_FAILED);
			$result->addMessage('No version found!');
			return false;
		}

		$result->addMessage('Successfully loaded config');
		$result->setStatus(TPluginsTestcasesUnitresult::STATUS_SUCCESS);
		return true;
	}

}
