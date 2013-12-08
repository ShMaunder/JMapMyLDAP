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

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Library language
$lang = JFactory::getLanguage();

// Try the Shmanic LDAP file in the current language (without allowing the loading of the file in the default language)
$lang->load('files_cli_shmanic_ldap', JPATH_SITE, null, false, false)
// Fallback to the Shmanic LDAP file in the default language
|| $lang->load('files_cli_shmanic_ldap', JPATH_SITE, null, true);

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

		$this->out(JText::_('CLI_SHMANIC_LDAP_INFO_13001'));

		// Get all the valid configurations
		if (!$configs = SHLdapHelper::getConfig())
		{
			// Failed to find any Ldap configs
			$this->out(JText::_('CLI_SHMANIC_LDAP_ERR_13003'));
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
		$this->out(JText::sprintf('CLI_SHMANIC_LDAP_INFO_13002', $count))->out();

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
					$errors[] = new Exception(JText::sprintf('CLI_SHMANIC_LDAP_ERR_13011', $ldap->info), 13011);
					unset($ldap);

					continue;
				}

				// Get all the Ldap users in the directory
				if (!$result = $ldap->search(null, $ldap->allUserFilter, array('dn', $ldap->keyUid)))
				{
					// Failed to search for all users in the directory
					$errors[] = new Exception(JText::sprintf('CLI_SHMANIC_LDAP_ERR_13012', $ldap->getErrorMsg()), 13012);
					unset($ldap);

					continue;
				}

				// Loop around each Ldap user
				for ($i = 0; $i < $result->countEntries(); ++$i)
				{
					// Get the Ldap username (case insensitive)
					if (!$username = strtolower($result->getValue($i, $ldap->keyUid, 0)))
					{
						continue;
					}

					try
					{
						// Check if this user is in the blacklist
						if ($blacklist = (array) json_decode(SHFactory::getConfig()->get('user.blacklist')))
						{
							if (in_array($username, $blacklist))
							{
								throw new RuntimeException(JText::_('CLI_SHMANIC_LDAP_ERR_13025'), 13025);
							}
						}

						// Create the new user adapter
						$adapter = new SHUserAdaptersLdap(array('username' => $username), $config);

						// Get the Ldap DN
						if (!$dn = $adapter->getId(false))
						{
							continue;
						}

						$this->out(JText::sprintf('CLI_SHMANIC_LDAP_INFO_13020', $username));

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
							throw new Exception(JText::_('CLI_SHMANIC_LDAP_ERR_13022'), 13022);
						}

						// Create the user array to enable creating a JUser object
						$user = array(
							'fullname' => $fullname,
							'username' => $username,
							'password_clear' => null,
							'email' => $email
						);

						// Create a JUser object from the Ldap user
						$options = array('adapter' => &$adapter);
						$instance = SHUserHelper::getUser($user, $options);

						if ($instance === false)
						{
							// Failed to get the user either due to save error or autoregister
							throw new Exception(JText::_('CLI_SHMANIC_LDAP_ERR_13024'), 13024);
						}

						// Fire the Ldap specific on Sync feature
						$sync = SHLdapHelper::triggerEvent('onLdapSync', array(&$instance, $options));

						// Check if the synchronise was successfully and report
						if ($sync !== false)
						{
							// Even if the sync does not need a save, do it anyway as Cron efficiency doesnt matter too much
							SHUserHelper::save($instance);

							// Above should throw an exception on error so therefore we can report success
							$this->out(JText::sprintf('CLI_SHMANIC_LDAP_INFO_13029', $username));
							++$success;
						}
						else
						{
							throw new Exception(JText::_('CLI_SHMANIC_LDAP_ERR_13026'), 13026);
						}

						unset($adapter);
					}
					catch (Exception $e)
					{
						unset($adapter);
						++$failed;
						$errors[] = new Exception(JText::sprintf('CLI_SHMANIC_LDAP_ERR_13028', $username, $e->getMessage()), $e->getCode());
					}
				}
			}
			catch (Exception $e)
			{
				$errors[] = new Exception(JText::_('CLI_SHMANIC_LDAP_ERR_13004'), 13004);
			}
		}

		// Print out some results and stats
		$this->out()->out()->out(JText::_('CLI_SHMANIC_LDAP_INFO_13032'))->out();

		$this->out(JText::_('CLI_SHMANIC_LDAP_INFO_13038'));

		foreach ($errors as $error)
		{
			if ($error instanceof Exception)
			{
				$this->out(' ' . $error->getCode() . ': ' . $error->getMessage());
			}
			else
			{
				$this->out(' ' . (string) $error);
			}
		}

		$this->out()->out(JText::sprintf('CLI_SHMANIC_LDAP_INFO_13034', $success));
		$this->out(JText::sprintf('CLI_SHMANIC_LDAP_INFO_13036', $failed));
		$this->out()->out('============================');
	}
}

JApplicationCli::getInstance('LdapCron')->execute();
