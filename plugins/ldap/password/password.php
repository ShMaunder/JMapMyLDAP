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
	protected $permittedForms = array();

	public $successHashes = array();

	public $failHashes = array();

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

		// Split and trim the permitted forms
		$this->permittedForms = explode(';', $this->params->get('permitted_forms'));
		array_walk($this->permittedForms, 'self::_trimValue');
	}

	/**
	 * Trims an array's elements. Use with array_walk.
	 *
	 * @param   string  &$value  Value of element.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	private function _trimValue(&$value)
	{
		$value = trim($value);
	}

	/**
	 * Loads the profile XML and passes it to the form to
	 * load the fields (excluding data).
	 *
	 * @param   JForm  $form  The form to be altered.
	 * @param   array  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   2.0
	 */
	public function onContentPrepareForm($form, $data)
	{
		if (!$authenticate = $this->params->get('authenticate', 1))
		{
			return true;
		}

		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');
			return false;
		}

		// Check we are manipulating a valid form
		if (!in_array($form->getName(), $this->permittedForms))
		{
			return true;
		}

		// Check if this user should have a profile
		if ($userId = isset($data->id) ? $data->id : 0)
		{
			if (SHLdapHelper::isUserLdap($userId))
			{

				// Inject the current password field

				$field = <<<'EOT'
					<fieldset name="currentcreds" label="PLG_LDAP_PASSWORD_FIELDSET_LABEL">
						<field name="current-password"
							class="validate-password"
							type="password"
							label="PLG_LDAP_PASSWORD_FIELD_CURRENT_PASSWORD_LABEL"
							description="PLG_LDAP_PASSWORD_FIELD_CURRENT_PASSWORD_DESC"
							size="30"
							autocomplete="off"
							filter="raw"
						/>
					</fieldset>
EOT;

				// TODO: DO NOT SHOW ON RO-PROFILE OR IF ADMIN USER LOOKS AT PROFILE
				$form->setField(new SimpleXMLElement($field));
			}
		}
	}

	/**
	 * Attempts to authenticate the current user password by using the field
	 * that was requested earlier in the sequence from the form specified.
	 *
	 * @param   array  $form  Form with current password inside.
	 *
	 * @return  boolean  True if current password matches
	 *
	 * @since   2.0
	 */
	public function onUserFormAuthentication($form)
	{
		$password = JArrayHelper::getValue($form, 'current-password', null, 'string');

		// Only get the first result - Plugin order does matter but this shouldnt ever return more than one
		if (!is_null($password))
		{
			// Get username and password to use for authenticating with Ldap
			$auth = array(
				'username' => JArrayHelper::getValue($form, 'username', false, 'string'),
				'password' => $password
			);

			$hashed = md5(serialize($auth));

			// Do some caching of the result
			if (in_array($hashed, $this->successHashes))
			{
				return true;
			}
			elseif (in_array($hashed, $this->failHashes))
			{
				return false;
			}

			try
			{
				if (SHFactory::getUserAdapter($auth)->getId(true))
				{
					$this->successHashes[] = $hashed;
					return true;
				}
			}
			catch (Exception $e)
			{
				$this->failHashes[] = $hashed;
				return false;
			}
		}
	}

	/**
	 * Method is called before user data is stored in the database.
	 *
	 * Tried to authenticate the user with the "current password" if required.
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

		$password = JArrayHelper::getValue($new, 'password_clear', null, 'string');

		// We want to throw the form back if the current password is incorrect
		if ($this->params->get('authenticate', 1) && (!empty($password)) && (!SHLdapHelper::checkFormAuthentication($new)))
		{
			// Current password is incorrect
			SHLog::add('Incorrect current password', 100, JLog::ERROR, 'ldap');
			return false;
		}
	}

	/**
	 * Method is called after user data is stored in the database.
	 *
	 * Changes the password in LDAP if the user changed their password.
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
		if ($isNew)
		{
			// We dont want to deal with new users here
			return;
		}

		// Get username and password to use for authenticating with Ldap
		$username 	= JArrayHelper::getValue($user, 'username', false, 'string');
		$password 	= JArrayHelper::getValue($user, 'password_clear', null, 'string');

		if (!empty($password))
		{
			$auth = array(
				'authenticate' => SHLdap::AUTH_USER,
				'username' => $username,
				'password' => $password
			);

			try
			{
				// We will double check the password for double safety
				$authenticate = $this->params->get('authenticate', 1);

				// Get the user adapter then set the password on it
				$adapter = SHFactory::getUserAdapter($auth);
				return $adapter->setPassword(
					$new['password_clear'],
					JArrayHelper::getValue($user, 'current-password', null, 'string'),
					$authenticate
				);
			}
			catch (Exception $e)
			{
				// Log and Error out
				SHLog::add($e, 12401, JLog::ERROR, 'ldap');
				return false;
			}
		}
	}
}
