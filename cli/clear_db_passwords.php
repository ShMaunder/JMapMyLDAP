<?php
/**
 * PHP Version 5.3
 *
 * @package    Shmanic.CLI
 * @author     Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright  Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// Make sure we're being called from the command line, not a web interface
(!array_key_exists('REQUEST_METHOD', $_SERVER)) or die;

define('_JEXEC', 1);

// Load system defines
if (file_exists(dirname(dirname(__FILE__)) . '/defines.php'))
{
	require_once dirname(dirname(__FILE__)) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(dirname(__FILE__)));
	require_once JPATH_BASE . '/includes/defines.php';
}

// Get the J! Framework and CMS libraries.
require_once JPATH_LIBRARIES . '/import.php';
require_once JPATH_LIBRARIES . '/cms.php';

// Load the configuration if one doesn't exist
(class_exists('JConfig')) or require_once JPATH_CONFIGURATION . '/configuration.php';

/**
 * This script will clear the Joomla user table of passwords!
 *
 * This is required to stop Joomla from using its own password to allow login
 * as well. For example, if the Joomla database had a different password from
 * Ldap, then both Joomla and Ldap passwords can be used to login if joomla
 * authentication is turned on - which is bad.
 * Probably best to run this in a cron job, or even better, use a SQL trigger
 * to do this on a row change on the users table (SQL below). Alternativly,
 * switch off the Joomla Authentication Plugin if this is a option.
 *
 * @package  Shmanic.CLI
 * @since    2.0
 */
class ClearDBPasswords extends JApplicationCli
{
	/**
	 * Entry point for the script.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function doExecute()
	{
		$this->out('Clearing Joomla passwords...');

		try
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);

			$authType = 'auth_type';

			// UPDATE jos_users SET password = '' WHERE params LIKE '%\"auth_type\":\"LDAP\"%';
			$query->update($query->quoteName('#__users'))
				->set($query->quoteName('password') . ' = ' . $db->quote(''))
				->where($query->quoteName('params') . ' LIKE ' . $db->quote("%\"{$authType}\":\"LDAP\"%"));

			$result = $db->setQuery($query)->execute();

			$count = $db->getAffectedRows($result);
		}
		catch (Exception $e)
		{
			$this->out("An error occurred {$e->getMessage}.");
			$this->close(1);
		}

		$this->out("Successfully cleared {$count} passwords.");
		$this->close(0);
	}
}

JApplicationCli::getInstance('ClearDBPasswords')->execute();
