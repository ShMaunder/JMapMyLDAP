<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap.Event
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * This class observes the global JDispatcher. On Joomla event calls, it evaluates whether
 * the event should be passed onto the corresponding Ldap event by checking the event's context.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap.Event
 * @since       2.0
 */
class SHLdapEventBouncer extends JEvent
{
	/**
	 * Holds if the current user/session is Ldap based.
	 *
	 * @var    boolean
	 * @since  2.0
	 */
	protected $isLdap = false;

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The JDispatcher object to observe.
	 *
	 * @since  2.0
	 */
	public function __construct(&$subject)
	{
		// Check if the current user is Ldap authenticated
		$this->isLdap = SHLdapHelper::isUserLdap();

		parent::__construct($subject);
	}

	/**
	 * Proxy method for onAfterInitialise to fix potential race conditions.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onSHPlaformInitialise()
	{
		$this->onAfterInitialise();
	}

	/**
	 * Method is called after initialise.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onAfterInitialise()
	{
		if ($this->isLdap)
		{
			SHLdapHelper::triggerEvent('onAfterInitialise');
		}
	}

	/**
	 * Method is called after route.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onAfterRoute()
	{
		if ($this->isLdap)
		{
			SHLdapHelper::triggerEvent('onAfterRoute');
		}
	}

	/**
	 * Method is called after dispatch.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onAfterDispatch()
	{
		if ($this->isLdap)
		{
			SHLdapHelper::triggerEvent('onAfterDispatch');
		}
	}

	/**
	 * Method is called before render.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onBeforeRender()
	{
		if ($this->isLdap)
		{
			SHLdapHelper::triggerEvent('onBeforeRender');
		}
	}

	/**
	 * Method is called after render.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onAfterRender()
	{
		if ($this->isLdap)
		{
			SHLdapHelper::triggerEvent('onAfterRender');
		}
	}

	/**
	 * Method is called before compile head.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onBeforeCompileHead()
	{
		if ($this->isLdap)
		{
			SHLdapHelper::triggerEvent('onBeforeCompileHead');
		}
	}

	/**
	 * Method prepares the data on a form.
	 * Note: Ldap user validation, if required, has to be done in plugin.
	 *
	 * @param   string  $context  Context / namespace of the form (i.e. form name).
	 * @param   object  $data     The associated data for the form.
	 *
	 * @return  boolean  True on success or False on error.
	 *
	 * @since   2.0
	 */
	public function onContentPrepareData($context, $data)
	{
		return SHLdapHelper::triggerEvent('onContentPrepareData', array($context, $data));
	}

	/**
	 * Method prepares a form in the way of fields.
	 * Note: Ldap user validation, if required, has to be done in plugin.
	 *
	 * @param   JForm   $form  The form to be alterted.
	 * @param   object  $data  The associated data for the form.
	 *
	 * @return  boolean  True on success or False on error.
	 *
	 * @since   2.0
	 */
	public function onContentPrepareForm($form, $data)
	{
		return SHLdapHelper::triggerEvent('onContentPrepareForm', array($form, $data));
	}

	/**
	 * Method is called before user data is deleted from the database.
	 *
	 * @param   array  $user  Holds the user data.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onUserBeforeDelete($user)
	{
		if (SHLdapHelper::isUserLdap($user))
		{
			SHLdapHelper::triggerEvent('onUserBeforeDelete', array($user));
		}
	}

	/**
	 *  Method is called after user data is deleted from the database.
	 *
	 * @param   array    $user     Holds the user data.
	 * @param   boolean  $success  True if user was successfully deleted from the database.
	 * @param   string   $msg      An error message.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onUserAfterDelete($user, $success, $msg)
	{
		if (SHLdapHelper::isUserLdap($user))
		{
			SHLdapHelper::triggerEvent('onUserAfterDelete', array($user, $success, $msg));
		}
	}

	/**
	 * Method is called before user data is stored in the database.
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
		$isAdapterExisting 	= true;
		$isLdapExisting 	= false;

		// Get the correct username where new username must be used when user isNew
		$username = $isNew ? $new['username'] : $user['username'];

		try
		{
			// We want to check if this user is an existing user in an Adapter
			$adapter = SHFactory::getUserAdapter($username);
			$adapter->getId(false);
		}
		catch (Exception $e)
		{
			// We will assume this user doesnt exist in an Adapter
			$isAdapterExisting = false;
		}

		if ($isAdapterExisting)
		{
			// We need to check the adapter is LDAP or not
			$isLdapExisting = $adapter->getType('LDAP');
		}

		if ($isLdapExisting)
		{
			$this->isLdap = true;

			if (SHLdapHelper::triggerEvent('onUserBeforeSave', array($user, $isNew, $new)) !== false)
			{
				try
				{
					// Commit the changes to the Adapter if present
					SHLdapHelper::commitChanges($adapter, true, true);
					SHLog::add(JText::sprintf('LIB_SHLDAPEVENTBOUNCER_DEBUG_10986', $username), 10986, JLog::DEBUG, 'ldap');
				}
				catch (Excpetion $e)
				{
					SHLog::add($e, 10981, JLog::ERROR, 'ldap');
				}

				// For now lets NOT block the user from logging in even with a error
				return true;
			}

			return false;
		}
		elseif ($isNew)
		{
			// Ask all plugins if there is a plugin willing to deal with user creation for ldap
			if (count($results = SHFactory::getDispatcher('ldap')->trigger('askUserCreation')))
			{
				// First, we must create and save the user as some plugins may talk to LDAP directly and cannot be delayed
				$result = SHLdapHelper::triggerEvent('onUserCreation', array($new));

				// Allow Ldap events to be called
				if ($this->isLdap = $result)
				{
					JFactory::getSession()->set('created', $username, 'ldap');

					if (SHLdapHelper::triggerEvent('onUserBeforeSave', array($user, $isNew, $new)) !== false)
					{
						try
						{
							// Commit the changes to the Adapter if present
							$adapter = SHFactory::getUserAdapter($username);
							SHLdapHelper::commitChanges($adapter, true, true);
						}
						catch (Exception $e)
						{
							SHLog::add($e, 10981, JLog::ERROR, 'ldap');
						}

						// For now lets NOT block the user from logging in even with a error
						return true;
					}
				}

				// Something went wrong with the user creation
				return false;
			}
		}
	}

	/**
	 * Method is called after user data is stored in the database.
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
		if ($this->isLdap)
		{
			if ($isNew && $success)
			{
				// Ensure Joomla knows this is a new Ldap user
				$adapter = SHFactory::getUserAdapter($user['username']);
				$options = array('adapter' => &$adapter);
				$instance = SHUserHelper::getUser($user, $options);

				// Silently resave the user without calling the onUserSave events
				SHUserHelper::save($instance, false);
			}

			SHLdapHelper::triggerEvent('onUserAfterSave', array($user, $isNew, $success, $msg));
			JFactory::getSession()->clear('created', 'ldap');
		}
	}

	/**
	 * Method handles login logic and report back to the subject.
	 *
	 * @param   array  $user     Holds the user data.
	 * @param   array  $options  Extra options such as autoregister.
	 *
	 * @return  boolean  Cancels login on False.
	 *
	 * @since   2.0
	 */
	public function onUserLogin($user, $options = array())
	{
		// Check if we have a user adapter already established for this user
		if (!isset(SHFactory::$adapters[strtolower($user['username'])]))
		{
			// SHAdapter did not log this in, get out now
			return;
		}

		// Get the processed user adapter directly from the static adapter holder
		$adapter = SHFactory::$adapters[strtolower($user['username'])];

		if (!(isset($user['type']) && $adapter::getType($user['type']) && $adapter::getType('LDAP')))
		{
			// Incorrect authentication type for this adapter OR is not compatible with LDAP
			return;
		}

		// Lets pass the getUser method the adapter so it can get extra values
		$options['adapter'] = $adapter;

		try
		{
			// Get a handle to the Joomla User object ready to be passed to the individual plugins
			$instance = SHUserHelper::getUser($user, $options);
		}
		catch (Exception $e)
		{
			// Failed to get the user either due to save error or autoregister
			SHLog::add($e, 10991, JLog::ERROR, 'ldap');

			return false;
		}

		// Fire the ldap specific on login events
		$result = SHLdapHelper::triggerEvent('onUserLogin', array(&$instance, $options));

		if ($result === false)
		{
			// Due to Joomla's inbuilt User Plugin, we have to raise an exception to abort login
			throw new RuntimeException(JText::sprintf('LIB_SHLDAPEVENTBOUNCER_ERR_10999', $user['username']), 10999);
		}

		// Check if any changes were made that need to be saved
		if ($result === true || isset($options['change']))
		{
			SHLog::add(JText::sprintf('LIB_SHLDAPEVENTBOUNCER_DEBUG_10984', $user['username']), 10984, JLog::DEBUG, 'ldap');

			try
			{
				// Save the user back to the Joomla database
				if (!SHUserHelper::save($instance))
				{
					SHLog::add(JText::sprintf('LIB_SHLDAPEVENTBOUNCER_ERR_10988', $user['username']), 10988, JLog::ERROR, 'ldap');
				}
			}
			catch (Exception $e)
			{
				SHLog::add($e, 10989, JLog::ERROR, 'ldap');
			}
		}

		// Allow Ldap events to be called
		$this->isLdap = true;

		return true;
	}

	/**
	 * Method handles logout logic and reports back to the subject.
	 *
	 * @param   array  $user     Holds the user data.
	 * @param   array  $options  Array holding options such as client.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.0
	 */
	public function onUserLogout($user, $options = array())
	{
		if ($this->isLdap)
		{
			return SHLdapHelper::triggerEvent('onUserLogout', array($user, $options));
		}
	}

	/**
	 * Method is called on user login failure.
	 *
	 * @param   array  $response  The authentication response.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onUserLoginFailure($response)
	{
		// Check if the attempted login was an Ldap user, if so then fire the event
		if ($username = SHUtilArrayhelper::getValue($response, 'username', false, 'string'))
		{
			// Check if the user exists in the J! database
			if ($id = JUserHelper::getUserId($username))
			{
				if (SHLdapHelper::isUserLdap($id))
				{
					SHLdapHelper::triggerEvent('onUserLoginFailure', array($response));
				}
			}
		}
	}
}
