<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Adapter.Event
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * This class observes the global JDispatcher. On Joomla event calls, it evaluates whether
 * the event should be passed onto the corresponding adapter event by checking the event's
 * context.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Adapter.Event
 * @since       2.1
 */
class SHAdapterEventBouncer extends JEvent
{
	/**
	 * Holds the user adapter name of the current session user.
	 *
	 * @var    null|string
	 * @since  2.0
	 */
	protected $adapterUser = null;

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The JDispatcher object to observe.
	 *
	 * @since   2.0
	 */
	public function __construct(&$subject)
	{
		// Gets the user adapter name for the current session user
		$session = JFactory::getSession();

		$user = $session->get('user');

		if (($user instanceof JUser) && $user->id > 0)
		{
			if (!($session->get('shuseradaptername', false)))
			{
				$userLink = SHAdapterMap::lookupFromJoomlaId(SHAdapterMap::TYPE_USER, $user->id);
				$session->set('shuseradaptername', $userLink['adapter']);
			}

			$this->adapterUser = $session->get('shuseradaptername');
		}

		parent::__construct($subject);
	}

	/**
	 * Proxy method for onAfterInitialise to fix potential ordering issue.
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
		if ($this->adapterUser)
		{
			SHAdapterEventHelper::triggerEvent($this->adapterUser, 'onAfterInitialise');
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
		if ($this->adapterUser)
		{
			SHAdapterEventHelper::triggerEvent($this->adapterUser, 'onAfterRoute');
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
		if ($this->adapterUser)
		{
			SHAdapterEventHelper::triggerEvent($this->adapterUser, 'onAfterDispatch');
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
		if ($this->adapterUser)
		{
			SHAdapterEventHelper::triggerEvent($this->adapterUser, 'onBeforeRender');
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
		if ($this->adapterUser)
		{
			SHAdapterEventHelper::triggerEvent($this->adapterUser, 'onAfterRender');
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
		if ($this->adapterUser)
		{
			SHAdapterEventHelper::triggerEvent($this->adapterUser, 'onBeforeCompileHead');
		}
	}

	/**
	 * Method prepares the data on a form.
	 * Note: This dispatches to all dispatchers without restrictions.
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
		return SHAdapterEventHelper::triggerEvent(null, 'onContentPrepareData', array($context, $data));
	}

	/**
	 * Method prepares a form in the way of fields.
	 * Note: This dispatches to all dispatchers without restrictions.
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
		return SHAdapterEventHelper::triggerEvent(null, 'onContentPrepareForm', array($form, $data));
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
		if (($userLink = SHAdapterMap::lookupFromJoomlaId(SHAdapterMap::TYPE_USER, $user['id'])) && $userLink['adapter'])
		{
			SHAdapterEventHelper::triggerEvent($userLink['adapter'], 'onUserBeforeDelete', array($user));
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
		if (($userLink = SHAdapterMap::lookupFromJoomlaId(SHAdapterMap::TYPE_USER, $user['id'])) && $userLink['adapter'])
		{
			SHAdapterEventHelper::triggerEvent($userLink['adapter'], 'onUserAfterDelete', array($user, $success, $msg));
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
		// Get the correct username where new username must be used when user isNew
		$username = $isNew ? $new['username'] : $user['username'];

		try
		{
			// We want to check if this user is an existing user in an Adapter
			$adapter = SHFactory::getUserAdapter($username);
			$adapter->getId(false);

			// We need to gather the adapter name to call the correct dispatcher
			$adapterName = $adapter::getName();
		}
		catch (Exception $e)
		{
			// We will assume this user doesnt exist in an Adapter
			$adapterName = false;
		}

		if ($adapterName)
		{
			$this->adapterUser = $adapterName;

			if (SHAdapterEventHelper::triggerEvent($adapterName, 'onUserBeforeSave', array($user, $isNew, $new)) !== false)
			{
				try
				{
					// Commit the changes to the Adapter if present
					SHAdapterHelper::commitChanges($adapter, true, true);
					SHLog::add(JText::sprintf('LIB_SHADAPTEREVENTBOUNCER_DEBUG_10986', $username), 10986, JLog::DEBUG, $adapterName);
				}
				catch (Excpetion $e)
				{
					SHLog::add($e, 10981, JLog::ERROR, $adapterName);
				}

				// For now lets NOT block the user from logging in even with a error
				return true;
			}

			return false;
		}
		elseif ($isNew)
		{
			// Use a default user adapter
			$name = SHFactory::getConfig()->get('user.type');

			// We must create and save the user as plugins may talk to adapter driver and expect a user object
			if (SHAdapterEventHelper::triggerEvent($name, 'onUserCreation', array($new)) === true)
			{
				$this->adapterUser = $name;

				JFactory::getSession()->set('created', $username, SHUserHelper::SESSION_KEY);

				if (SHAdapterEventHelper::triggerEvent($name, 'onUserBeforeSave', array($user, $isNew, $new)) !== false)
				{
					try
					{
						// Commit the changes to the Adapter if present
						$adapter = SHFactory::getUserAdapter($username);
						SHAdapterHelper::commitChanges($adapter, true, true);
					}
					catch (Exception $e)
					{
						SHLog::add($e, 10981, JLog::ERROR, $name);
					}

					// For now lets NOT block the user from logging in even with an error
					return true;
				}
			}

			// Something went wrong with the user creation
			return false;
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
		if ($this->adapterUser)
		{
			if ($isNew && $success)
			{
				// Ensure Joomla knows this is a new adapter user
				$adapter = SHFactory::getUserAdapter($user['username']);
				$options = array('adapter' => &$adapter);
				$instance = SHUserHelper::getUser($user, $options);

				// Silently resave the user without calling the onUserSave events
				SHUserHelper::save($instance, false);

				// Update the user map linker
				SHAdapterMap::setUser($adapter, $instance->id);
			}

			SHAdapterEventHelper::triggerEvent($this->adapterUser, 'onUserAfterSave', array($user, $isNew, $success, $msg));
			JFactory::getSession()->clear('created', SHUserHelper::SESSION_KEY);
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

		if (!(isset($user['type']) && $adapter::getName($user['type'])))
		{
			// Incorrect authentication type for this adapter
			return;
		}

		$adapterName = $adapter::getName();

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
			SHLog::add($e, 10991, JLog::ERROR, $adapterName);

			return false;
		}

		// Fire the adapter driver specific on login events
		$result = SHAdapterEventHelper::triggerEvent($adapterName, 'onUserLogin', array(&$instance, $options));

		if ($result === false)
		{
			// Due to Joomla's inbuilt User Plugin, we have to raise an exception to abort login
			throw new RuntimeException(JText::sprintf('LIB_SHADAPTEREVENTBOUNCER_ERR_10999', $user['username']), 10999);
		}

		// Check if any changes were made that need to be saved
		if ($result === true || isset($options['change']))
		{
			SHLog::add(JText::sprintf('LIB_SHADAPTEREVENTBOUNCER_DEBUG_10984', $user['username']), 10984, JLog::DEBUG, $adapterName);

			try
			{
				// Save the user back to the Joomla database
				if (!SHUserHelper::save($instance))
				{
					SHLog::add(JText::sprintf('LIB_SHADAPTEREVENTBOUNCER_ERR_10988', $user['username']), 10988, JLog::ERROR, $adapterName);
				}
			}
			catch (Exception $e)
			{
				SHLog::add($e, 10989, JLog::ERROR, $adapterName);
			}
		}

		// Update the user map linker
		SHAdapterMap::setUser($adapter, $instance->id);

		// Allow user adapter events to be called
		$this->adapterUser = $adapterName;

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
		if ($userLink = SHAdapterMap::getUserLink($user['username']) && $userLink['adapter'])
		{
			return SHAdapterEventHelper::triggerEvent($userLink['adapter'], 'onUserLogout', array($user, $options));
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
		if ($username = JArrayHelper::getValue($response, 'username', false, 'string'))
		{
			// Check if the user exists in the J! database
			if ($id = JUserHelper::getUserId($username))
			{
				// Check if the attempted login was an adapter user, if so then fire the event
				if ($userLink = SHAdapterMap::getUserLink($id) && $userLink['adapter'])
				{
					SHAdapterEventHelper::triggerEvent($userLink['adapter'], 'onUserLoginFailure', array($response));
				}
			}
		}
	}
}

/**
 * Deprecated class for SHAdapterEventBouncer.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Adapter.Event
 * @since       2.0
 *
 * @deprecated  [2.1] Use SHAdapterEventBouncer instead
 */
class SHLdapEventBouncer extends SHAdapterEventBouncer
{
}
