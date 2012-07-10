<?php
/**
 * PHP Version 5.3
 *
 * @package    Shmanic.CLI
 * @author     Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright  Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
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

// Get the Shmanic platform and Ldap libraries.
require_once JPATH_PLATFORM . '/shmanic/import.php';
shImport('ldap');

/**
 * This script will fetch all the LDAP users in the specified
 * base DN and synchronise them to the Joomla! database.
 *
 * @package  Shmanic.CLI
 * @since    2.0
 */
class LdapCron extends JApplicationCli
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
		// Setup some stats
		$failed 	= 0;
		$success 	= 0;

		// Get the user filter
		$config = SHFactory::getConfig();
		$userFilter = $config->get('ldap.userfilter', '(objectclass=user)');

		// It appears we have to tell the system we are running with the site otherwise bad things happen
		JFactory::getApplication('site');

		// Bind with the proxy user. TODO allow multiple configs as well.
		$ldap = SHLdapHelper::getClient(array('authenticate' => SHLdapHelper::AUTH_PROXY));

		// Get all the Ldap users in the directory
		if (!$result = $ldap->search(null, $userFilter, array('dn')))
		{
			$this->out("Failed to connect to Ldap with error: {$ldap->getError()}");
			$this->close(1);
		}

		// Loop around each Ldap user
		for ($i = 0; $i < $result->countEntries(); ++$i)
		{
			// Get the Ldap User DN
			$dn = $result->getDN($i);

			// Get the Ldap user attributes
			$source = $ldap->getUserDetails($dn);

			// Get the core mandatory J! user fields
			$username = (isset($source[$ldap->getUid()][0])) ? $source[$ldap->getUid()][0] : null;
			$fullname = (isset($source[$ldap->getFullname()][0])) ? $source[$ldap->getFullname()][0] : null;
			$email = (isset($source[$ldap->getEmail()][0])) ? $source[$ldap->getEmail()][0] : null;

			$this->out()->out("Processing user {$username}");

			if (empty($fullname))
			{
				// Full name doesnt exist; use the username instead
				$fullname = $username;
			}

			if (empty($email))
			{
				// Email doesnt exist; cannot proceed
				$this->out("Empty email not allowed for user {$username}");
				++$failed;
				continue;
			}

			// Create the user array to enable creating a JUser object
			$user = array(
				'fullname' => $fullname,
				'username' => $username,
				'password_clear' => null,
				'email' => $email
			);

			// Create a JUser object from the Ldap user
			$options = array();
			$instance = SHUserHelper::getUser($user, $options);

			if ($instance === false)
			{
				// Failed to get the user either due to save error or autoregister
				$this->out("Failed to create a JUser object for {$username}");
				++$failed;
				continue;
			}

			// Set this user as an LDAP user
			SHLdapHelper::setUserLdap($instance);

			// Attach the attributes to the user
			$user[SHLdapHelper::ATTRIBUTE_KEY] = $source;

			// Fire the Ldap specific on Sync feature
			$sync = SHLdapHelper::triggerEvent('onLdapSync', array(&$instance, $user, $options));

			// Check if the synchronise was successfully and report
			if ($sync)
			{
				$this->out("Successfully synchronised user {$username}");
				++$success;
				$instance->save();
			}
			else
			{
				$this->out("Failed to synchronise user {$username}");
				++$failed;
			}
		}

		// Print out some results and stats
		$this->out()->out('=== LDAP Results ===');
		$this->out("Success: {$success}");
		$this->out("Failed:  {$failed}");

	}
}

JApplicationCli::getInstance('LdapCron')->execute();
