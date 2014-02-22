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
 * A global LDAP event debugger. This file can be easily edited for debugging events
 * witout worry of breaking stuff.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap.Event
 * @since       2.0
 */
class SHLdapEventDebug extends JEvent
{
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
		SHLog::add(
			JText::sprintf('LIB_SHLDAPEVENTDEBUG_CALLED', __METHOD__), 11901, JLog::DEBUG, 'ldap'
		);
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
		SHLog::add(
			JText::sprintf('LIB_SHLDAPEVENTDEBUG_CALLED', __METHOD__), 11902, JLog::DEBUG, 'ldap'
		);
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
		SHLog::add(
			JText::sprintf('LIB_SHLDAPEVENTDEBUG_CALLED', __METHOD__), 11903, JLog::DEBUG, 'ldap'
		);
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
		SHLog::add(
			JText::sprintf('LIB_SHLDAPEVENTDEBUG_CALLED', __METHOD__), 11904, JLog::DEBUG, 'ldap'
		);

		// Let us not insert clear passwords into a log file, accidents can happen
		if (isset($user['password']) && $user['password'])
		{
			$user['password'] = '__OBSCURED__';
		}

		if (isset($user['password_clear']) && $user['password_clear'])
		{
			$user['password_clear'] = '__OBSCURED__';
		}

		if (isset($new['password']) && $new['password'])
		{
			$new['password'] = '__OBSCURED__';
		}

		if (isset($new['password_clear']) && $new['password_clear'])
		{
			$new['password_clear'] = '__OBSCURED__';
		}

		unset($user['password1']);
		unset($user['password2']);

		unset($new['password1']);
		unset($new['password2']);

		SHLog::add(
			JText::sprintf(
				'LIB_SHLDAPEVENTDEBUG_DEBUG_11951',
				preg_replace('/\s+/', ' ', var_export($user, true)),
				preg_replace('/\s+/', ' ', var_export($new, true))
			), 11951, JLog::DEBUG, 'ldap'
		);
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
		SHLog::add(
			JText::sprintf('LIB_SHLDAPEVENTDEBUG_CALLED', __METHOD__), 11905, JLog::DEBUG, 'ldap'
		);
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
		SHLog::add(
			JText::sprintf('LIB_SHLDAPEVENTDEBUG_CALLED', __METHOD__), 11906, JLog::DEBUG, 'ldap'
		);
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
		SHLog::add(
			JText::sprintf('LIB_SHLDAPEVENTDEBUG_CALLED', __METHOD__), 11907, JLog::DEBUG, 'ldap'
		);
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
		SHLog::add(
			JText::sprintf('LIB_SHLDAPEVENTDEBUG_CALLED', __METHOD__), 11908, JLog::DEBUG, 'ldap'
		);
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
		SHLog::add(
			JText::sprintf('LIB_SHLDAPEVENTDEBUG_CALLED', __METHOD__), 11909, JLog::DEBUG, 'ldap'
		);
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
		SHLog::add(
			JText::sprintf('LIB_SHLDAPEVENTDEBUG_CALLED', __METHOD__), 11910, JLog::DEBUG, 'ldap'
		);
	}
}
