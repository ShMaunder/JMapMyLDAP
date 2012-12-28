<?php
/**
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @package     Shmanic.Plugin
 * @subpackage  System.LdapProfile
 *
 * @copyright	Copyright (C) 2011 Shaun Maunder. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * LDAP Profile Plugin
 *
 * @package     Shmanic.Plugin
 * @subpackage  Ldap.Profile
 * @since       2.0
 */
class plgLdapProfile extends JPlugin
{
	/**
	* An object to a instance of LdapProfile
	*
	* @var    SHLdapProfile
	* @since  2.0
	*/
	protected $profile = null;

	/* holds the reference to the xml file */
	protected $xml = null;

	protected $permittedForms = array();

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

		$this->profile = new SHLdapProfile($this->params->toArray());

		// Split and trim the permitted forms
		$this->permittedForms = explode(';', $this->params->get('permitted_forms'));
		array_walk($this->permittedForms, 'self::_trimValue');

		// Process the profile XML (this only needs to be done once)
		$this->xml = $this->profile->getXMLFields();
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
	 * Called during an ldap login.
	 *
	 * Checks to ensure the onlogin parameter is true then calls the on sync method.
	 *
	 * @param   JUser  &$instance  A JUser object for the authenticating user.
	 * @param   array  $options    Array holding options.
	 *
	 * @return  boolean  False to cancel login
	 *
	 * @since   2.0
	 */
	public function onUserLogin(&$instance, $options = array())
	{
		if ($this->params->get('onlogin'))
		{
			$this->onLdapSync($instance, $options);
		}

		// Even if it did fail, we don't want to cancel the logon
		return true;
	}

	/**
	 * Called during a ldap synchronisation.
	 *
	 * Checks to ensure that required variables are set before calling the update
	 * field methods.
	 *
	 * @param   JUser  &$instance  A JUser object for the authenticating user.
	 * @param   array  $options    Array holding options.
	 *
	 * @return  boolean  True on success
	 *
	 * @since   2.0
	 */
	public function onLdapSync(&$instance, $options = array())
	{
		// Gather the user adapter
		$username = $instance->username;
		$adapter = SHFactory::getUserAdapter($username);

		// Mandatory Joomla field processing and saving
		$this->profile->updateMandatory($instance, $adapter);

		// Save the profile as defined from the XML
		return $this->profile->saveProfile($this->xml, $instance, $adapter, $options);
	}

	/**
	 * Get the profile data then merge it with the
	 * form so it can be displayed.
	 *
	 * @param   string  $context  The context for the data (i.e. the form name)
	 * @param   object  $data     Associated data for the form (this should be JUser in this context)
	 *
	 * @return  boolean
	 *
	 * @since   2.0
	 */
	public function onContentPrepareData($context, $data)
	{
		// Check if the profile parameter is enabled
		if (!$this->params->get('use_profile', 0))
		{
			return true;
		}

		// Check we are manipulating a valid form
		if (!in_array($context, $this->permittedForms))
		{
			return true;
		}

		// Check if this user should have a profile
		if ($userId = isset($data->id) ? $data->id : 0)
		{
			if (SHLdapHelper::isUserLdap($userId))
			{
				// Load the profile data from the database.
				$records = $this->profile->queryProfile($userId, true);

				// Merge the profile data
				$data->ldap_profile = array();
				foreach ($records as $record)
				{
					$data->ldap_profile[$record['profile_key']] = $record['profile_value'];
				}
			}
		}

		return true;
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
		// Check if the profile parameter is enabled
		if (!$this->params->get('use_profile', 0))
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
				// Load in the profile XML file to the form
				if (($xml = JFactory::getXML($this->profile->getXMLPath(), true)) && ($form->load($xml, false, false)))
				{
					// Successfully loaded in the XML
					return true;
				}
			}
		}

	}

	// Delete the profile
	public function onUserAfterDelete($user, $success, $msg)
	{
		if (!$success)
		{
			return false;
		}

		if ($userId = JArrayHelper::getValue($user, 'id', 0, 'int'))
		{
			$this->profile->deleteProfile($userId);
		}

	}

	/**
	 * Method is called before user data is stored in the database.
	 *
	 * Saves profile data to LDAP if a profile form is detected.
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
		if (!$this->params->get('allow_ldap_save', 1))
		{
			// Not allowed to save back to LDAP
			return;
		}

		// Default the return result to true
		$result = true;

		// Check there is a profile to save (i.e. this event may not have been called from the profile form)
		if (isset($new['ldap_profile']) && (count($new['ldap_profile'])))
		{
			// Get username and password to use for authenticating with Ldap
			$username 	= JArrayHelper::getValue($new, 'username', false, 'string');
			$password 	= JArrayHelper::getValue($new, 'password_clear', false, 'string');

			// Include the mandatory Joomla fields (fullname and email)
			$mandatoryData = array(
				'name' => JArrayHelper::getValue($new, 'name'),
				'email' => JArrayHelper::getValue($new, 'email')
			);

			// Only get profile data and enabled elements from the input
			$profileData = $this->profile->cleanInput($this->xml, $new['ldap_profile']);

			// Save the profile back to LDAP
			$result = $this->profile->saveToLDAP($this->xml, $username, $password, $profileData, $mandatoryData);
		}

		return $result;
	}

	/**
	 * Called before a user LDAP read to gather extra user ldap attribute keys
	 * required for this plugin to function correctly.
	 *
	 * @param   SHUserAdapter  $adapter  The current user adapter.
	 * @param   array          $options  Array holding options.
	 *
	 * @return  array  Array of attributes
	 *
	 * @since   2.0
	 */
	public function onLdapBeforeRead($adapter, $options = array())
	{
		if ($this->xml)
		{
			return $this->profile->getAttributes($this->xml);
		}
	}
}
