<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Plugins
 * @subpackage  System
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
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
	 * Initialises and imports the Shmanic platform and project libraries.
	 * This is fired on application initialise typically by the CMS.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onAfterInitialise()
	{
		// Check if the Shmanic platform has already been imported
		if (!defined('SHPATH_PLATFORM'))
		{
			$platform = JPATH_PLATFORM . '/shmanic/import.php';

			if (!file_exists($platform))
			{
				// Failed to find the import file
				return false;
			}

			// Shmanic Platform import
			if (!include_once $platform)
			{
				// Failed to import the Shmanic platform
				return false;
			}
		}

		// Import the logging method
		SHLog::import($this->params->get('log_group', 'shlog'));

		// Container to store project specific import results
		$results = array();

		// Use the default SQL configuration
		$config = SHFactory::getConfig();

		// Get all the importable projects
		if ($imports = json_decode($config->get('platform.import')))
		{
			foreach ($imports as $project)
			{
				// Attempts to import the specified project
				$results[] = shImport(trim($project));
			}
		}

		// Fire the onAfterInitialise for all the registered imports/projects
		JDispatcher::getInstance()->trigger('onSHPlaformInitialise');

		if (in_array(false, $results, true))
		{
			// One of the specific projects failed to import
			return false;
		}

		// Everything imported successfully
		return true;
	}
}
