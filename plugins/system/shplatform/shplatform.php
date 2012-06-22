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
 * Boots the Shmanic platform and associated project libraries for the CMS.
 *
 * @package     Shmanic.Plugins
 * @subpackage  System
 * @since       2.0
 */
class PlgSystemSHPlatform extends JPlugin
{
	/**
	 * Initialises and boots the Shmanic platform and project libraries.
	 * This is fired on application initialise typically by the CMS.
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

		// Container to store project specific boot results
		$results = array();

		// Use the default SQL configuration
		$config = SHFactory::getConfig();

		// Get all the bootable projects
		if ($boot = json_decode($config->get('platform.boot')))
		{
			foreach ($boot as $project)
			{
				// Attempts to boot the specified project
				$results[] = shBoot(trim($project));
			}
		}

		if (in_array(false, $results, true))
		{
			// One of the specific projects failed to boot
			return false;
		}

		// Everything booted successfully
		return true;
	}
}
