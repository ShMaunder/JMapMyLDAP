<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Plugins
 * @subpackage  System
 * @author      Shaun Maunder <shaun@shmanic.com>
 * 
 * @copyright   Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Boots the Shmanic platform and Ldap libraries for the CMS. 
 *
 * @package     Shmanic.Plugins
 * @subpackage  System
 * @since       2.0
 */
class PlgSystemLDAPBoot extends JPlugin
{
	/**
	 * Initialises and boots the Shmanic platform and ldap libraries.
	 * This is fired on application initialise typically on the CMS.
	 * 
	 * @return  void
	 * 
	 * @since   2.0
	 */
	public function onAfterInitialise()
	{
		// Check if the Shmanic platform has already been booted
		if (!defined('SH_PLATFORM'))
		{
			$platform = JPATH_PLATFORM . '/shmanic/bootstrap.php';

			if (!file_exists($platform))
			{
				// Failed to find the Shmanic platform bootstrap
				return false;
			}

			// Shmanic Platform Boot
			if (!include_once $platform)
			{
				// Failed to boot the Shmanic platform
				return false;
			}
		}

		// Shmanic Ldap Boot
		if (!shBoot('ldap'))
		{
			// Failed to boot Ldap
			return false;
		}

		// Everything booted and ready to go
		return true;
	}
}
