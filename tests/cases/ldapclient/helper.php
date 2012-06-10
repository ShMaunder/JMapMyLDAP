<?php
/**
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
 * Helper for ldapclient. Deals with retrieving config values.
 * 
 * @package     JMMLDAP.Tests
 * @subpackage  Cases
 * @since       2.0
 */
abstract class TCasesLdapclientHelper
{
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
			$file = JPATH_TESTS . '/cases/ldapclient/config.xml';
		}
		
		if (!is_file($file)) {
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
	
	public static function doBoot()
	{
		
		$boot = JPATH_PLATFORM . '/shmanic/bootstrap.php';
		
		if (is_file($boot))
		{
			require_once($boot);
		}
		
		if (!class_exists('SHLdap'))
		{
			return false;
		}
		
		return true;
	}
}