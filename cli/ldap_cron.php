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
		$errors		= array();

		// It appears we have to tell the system we are running with the site otherwise bad things happen
		JFactory::getApplication('site');

		$this->out('Started LDAP Cron Script for Joomla!');

		// Get all the valid configurations
		if (!$configs = SHLdapHelper::getConfig())
		{
			// Failed to find any Ldap configs
			$this->out('Failed to find any LDAP configurations');
			$this->close(1);
		}

		// Check if only a single config was found
		if ($configs instanceof JRegistry)
		{
			/*
			 * To make things easier, we pretend we returned multiple Ldap configs
			 * by casting the single entry into an array.
			 */
			$configs = array($configs);
		}

		$count = count($configs);
		$this->out("Found $count LDAP configurations")->out();

		// Loop around each LDAP configuration
		foreach ($configs as $config)
		{
			try
			{
				// Get a new Ldap object
				$ldap = new SHLdap($config);

				// Bind with the proxy user
				if (!$ldap->authenticate(SHLdap::AUTH_PROXY))
				{
					// Something is wrong with this LDAP configuration - cannot bind to proxy user
					$errors[] = "Failed to bind with the proxy user for LDAP configuration: {$ldap->getInfo()}";
					unset($ldap);

					continue;
				}

				// Get all the Ldap users in the directory
				if (!$result = $ldap->search(null, $ldap->allUserFilter, array('dn', $ldap->keyUid)))
				{
					// Failed to search for all users in the directory
					$errors[] = "Failed to search all users in the directory with error: {$ldap->getError()}";
					unset($ldap);

					continue;
				}

				// Loop around each Ldap user
				for ($i = 0; $i < $result->countEntries(); ++$i)
				{
					// Get the Ldap username
					if (!$username = $result->getValue($i, $ldap->keyUid, 0))
					{
						continue;
					}

					try
					{
						// Create the new user adapter
						$adapter = new SHUserAdaptersLdap(array('username' => $username));

						// Get the Ldap DN
						if (!$dn = $adapter->getId(false))
						{
							continue;
						}

						$this->out("Attempting to synchronise user: {$username}");

						// Get the Ldap user attributes
						$source = $adapter->getAttributes();

						// Get the core mandatory J! user fields
						$username = $adapter->getUid();
						$fullname = $adapter->getFullname();
						$email = $adapter->getEmail();

						if (empty($fullname))
						{
							// Full name doesnt exist; use the username instead
							$fullname = $username;
						}

						if (empty($email))
						{
							// Email doesnt exist; cannot proceed
							$errors[] = ("Empty email not allowed for user: {$username}");
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
							$errors[] = ("Failed to create a JUser object for user: {$username}");
							++$failed;
							continue;
						}

						// Set this user as an LDAP user
						SHLdapHelper::setUserLdap($instance);

						// Fire the Ldap specific on Sync feature
						$sync = SHLdapHelper::triggerEvent('onLdapSync', array(&$instance, $options));

						// Check if the synchronise was successfully and report
						if ($sync)
						{
							$this->out("Successfully synchronised user: {$username}");
							++$success;
							$instance->save();
						}
						else
						{
							$errors[] = ("Failed to synchronise user: {$username}");
							++$failed;
						}

						unset($adapter);
					}
					catch (Exception $e)
					{
						unset($adapter);
						$errors[] = ("Fatal error ({$e->getMessage()}) for user: {$username}");
					}
				}
			}
			catch (Exception $e)
			{
				$errors[] = "Invalid LDAP configuration";
			}
		}

		// Print out some results and stats
		$this->out()->out()->out('======= LDAP Results =======')->out();

		$this->out("Errors: ");
		foreach ($errors as $error)
		{
			$this->out(' ' . (string) $error);
		}

		$this->out()->out("Users Success: {$success}");
		$this->out("Users Failed:  {$failed}");
		$this->out()->out('============================');

	}
}

JApplicationCli::getInstance('LdapCron')->execute();
