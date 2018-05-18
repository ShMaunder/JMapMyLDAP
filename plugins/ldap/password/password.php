<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Plugin
 * @subpackage  Ldap.Password
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.plugin.plugin');

/**
 * LDAP User Password Plugin
 *
 * @package     Shmanic.Plugin
 * @subpackage  Ldap.Password
 * @since       2.0
 */
class PlgLdapPassword extends JPlugin
{
	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 *
	 * @since  2.0
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	/**
	 * Method is called before user data is stored in the database.
	 *
	 * Changes the password in LDAP if the user changed their password.
	 *
	 * @param   array    $user   Holds the old user data.
	 * @param   boolean  $isNew  True if a new user is stored.
	 * @param   array    $new    Holds the new user data.
	 *
	 * @return  boolean  Cancels the save if False.
	 *
	 * @since   2.0
	 */
	public function onUserBeforeSave($user, $isNew, $new)
	{
		if ($isNew)
		{
			// We dont want to deal with new users here
			return;
		}

		// Get username and password to use for authenticating with Ldap
		$username 	= JArrayHelper::getValue($user, 'username', false, 'string');
		$password 	= JArrayHelper::getValue($new, 'password_clear', null, 'string');

		if (!empty($password))
		{
			$auth = array(
				'authenticate' => SHLdap::AUTH_USER,
				'username' => $username,
				'password' => $password
			);

			try
			{
				// We will double check the password for double safety (breaks password reset if on)
				$authenticate = $this->params->get('authenticate', 0);

				// Get the user adapter then set the password on it
				$adapter = SHFactory::getUserAdapter($auth);

				$adapter->setPassword(
					$password,
					JArrayHelper::getValue($new, 'current-password', null, 'string'),
					$authenticate
				);

				SHLog::add(JText::sprintf('PLG_LDAP_PASSWORD_INFO_12411', $username), 12411, JLog::INFO, 'ldap');
			}
			catch (Exception $e)
			{
				// Log and Error out
				SHLog::add($e, 12401, JLog::ERROR, 'ldap');

				return false;
			}
		}
	}
	
	/**
	 * Method is called after user data is stored in the database.
	 *
	 * Clears the password field in J!'s user table if nullpasword option is set
	 *
	 * @param   array    $user     Holds the new user data.
	 * @param   boolean  $isNew    True if a new user has been stored.
	 * @param   boolean  $success  True if user was successfully stored in the database.
	 * @param   string   $msg      An error message.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onUserAfterSave($user, $isNew, $success, $msg)
	{
		if ($isNew || !$success)
		{
			// We dont want to deal with new users here, or if user wasnn't saved
			return;
		}

		if (SHFactory::getConfig()->get('user.nullpassword') && $this->changed)
		{
			// Get the processed user adapter directly from the static adapter holder
			$adapter = SHFactory::$adapters[strtolower($user['username'])];

			// Lets pass the getUser method the adapter so it can get extra values
			$options['adapter'] = $adapter;

			try
			{
				// Get a handle to the Joomla User object ready to be passed to the individual plugins
				$instance = SHUserHelper::getUser($user, $options);
				// Clear the password and silently resave the user without calling the onUserSave events
				$instance->password       = '';
				$instance->password_clear = '';
				SHUserHelper::save($instance, false);
			}
			catch (Exception $e)
			{
				// Failed to get the user either due to save error or autoregister
				SHLog::add($e, 10991, JLog::ERROR, 'ldap');

				return false;
			}

		}
	}

}
