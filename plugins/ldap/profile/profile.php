<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Plugin
 * @subpackage  Ldap.Profile
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.plugin.plugin');

/**
 * LDAP Profile Plugin
 *
 * @package     Shmanic.Plugin
 * @subpackage  Ldap.Profile
 * @since       2.0
 */
class PlgLdapProfile extends JPlugin
{
	/**
	 * The form field name to use for displaying the profile.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const FORM_FIELDS_NAME = 'ldap_profile';

	/**
	 * Holds the reference to the xml file.
	 *
	 * @var    SimpleXMLElement[]
	 * @since  2.0
	 */
	protected $xml = array();

	/**
	 * Holds the permitted forms the profile will render on.
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $permittedForms = array();

	/**
	 * Synchronise fullname with joomla database
	 * 0-No Sync | 1-Sync From LDAP | 2- Sync To and From LDAP
	 *
	 * @var    integer
	 * @since  2.0
	 */
	protected $sync_name = null;

	/**
	 * Synchronise email with joomla database
	 * 0-No Sync | 1-Sync From LDAP | 2- Sync To and From LDAP
	 *
	 * @var    integer
	 * @since  2.0
	 */
	protected $sync_email = null;

	/**
	 * Use profiles based on domain.
	 *
	 * @var    boolean
	 * @since  2.0
	 */
	protected $use_domain = false;

	/**
	 * Profile XML name to use.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $profile_name = null;

	/**
	 * Profile XML base to use.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $profile_base = null;

	/**
	 * Language base to use.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $lang_base = null;

	/**
	 * Use the XML profile.
	 *
	 * @var    boolean
	 * @since  2.0
	 */
	protected $use_profile = false;

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

		/*
		 * Setup some parameters and set defaults.
		 */
		$this->sync_name = (int) $this->params->get('sync_name', 1);
		$this->sync_email = (int) $this->params->get('sync_email', 1);

		$this->use_profile = (bool) $this->params->get('use_profile', false);
		$this->use_domain = (bool) $this->params->get('use_domain', false);
		$this->profile_name = $this->params->get('profile_name', 'default');
		$this->profile_base = $this->params->get('profile_base');

		if (empty($this->profile_base))
		{
			// Use default base path
			$this->profile_base = JPATH_PLUGINS . '/ldap/profile/profiles';
		}

		$this->lang_base = $this->params->get('lang_base');

		if (empty($this->lang_base))
		{
			// Use the profile base as the language base if one doesnt exist
			$this->lang_base = $this->profile_base;
		}

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
	 * Method to get certain otherwise inaccessible properties from the profile object.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   2.0
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'xmlFilePath':
				return $this->profile_base . '/' . $this->profile_name . '.xml';
				break;
		}

		return null;
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
			$result = $this->onLdapSync($instance, $options);

			if (!$this->params->get('abort_login') && $result === false)
			{
				// Abort login is disabled and the plugin failed - but lets not cancel login
				return;
			}

			return $result;
		}
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
		try
		{
			// Gather the user adapter
			$username = $instance->username;
			$adapter = SHFactory::getUserAdapter($username);

			// Mandatory Joomla field processing and saving
			$change = $this->updateMandatory($instance, $adapter);

			if ($this->use_profile)
			{
				// Save the profile as defined from the XML
				$result = $this->saveProfile($instance->id, $username, $adapter, $options);

				if ($result === false)
				{
					// There was an error
					return false;
				}
				elseif ($result === true)
				{
					// There was a change made
					return true;
				}
			}

			// No change at saveProfile but might be change at updateMandatory
			return $change;
		}
		catch (Exception $e)
		{
			SHLog::add($e, 12231, JLog::ERROR, 'ldap');

			return false;
		}
	}

	/**
	 * Get the profile data then merge it with the form so it can be displayed.
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
		if (!$this->use_profile)
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
				$records = $this->queryProfile($userId, true);

				// Merge the profile data
				$data->{self::FORM_FIELDS_NAME} = array();

				foreach ($records as $record)
				{
					$data->{self::FORM_FIELDS_NAME}[$record['profile_key']] = $record['profile_value'];
				}
			}
		}

		return true;
	}

	/**
	 * Loads the profile XML and passes it to the form to load the fields (excluding data).
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
		if (!$this->use_profile)
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

		$showForm = true;
		$domain = null;

		// Check if this user should have a profile
		if ($userId = isset($data->id) ? $data->id : 0)
		{
			if (SHLdapHelper::isUserLdap($userId))
			{
				$domain = SHUserHelper::getDomainParam($data);
			}
			else
			{
				$showForm = false;
			}
		}
		elseif (!JFactory::getUser()->guest)
		{
			/*
			 * Sometimes the $data variable is not populated even when an edit is required.
			 * This means we have to check the form post data directly for the user ID.
			 * We do not worry about frontend registrations as we check for guest.
			 * If there is no form posted then this could be a backend registration.
			 */
			if ($inForm = JFactory::getApplication()->input->get('jform', false, 'array'))
			{
				$id = SHUtilArrayhelper::getValue($inForm, 'id', 0, 'int');

				if ($id === 0)
				{
					// Ask all plugins if there is a plugin willing to deal with user creation for ldap
					if (count($results = SHFactory::getDispatcher('ldap')->trigger('askUserCreation')))
					{
						// Due to being unaware of the domain for this new user, we are forced to use the default domain
						$domain = SHFactory::getConfig()->get('ldap.defaultconfig');
					}
					else
					{
						// LDAP creation not enabled
						$showForm = false;
					}
				}
				else
				{
					if (SHLdapHelper::isUserLdap($id))
					{
						// Existing ldap user
						$domain = SHUserHelper::getDomainParam($id);
					}
					else
					{
						// Existing non-ldap user
						$showForm = false;
					}
				}
			}
		}

		if ($showForm)
		{
			// We have to launch the getxmlfields to correctly include languages
			$this->getXMLFields($domain);

			// Get the File and Path for the Profile XML
			$file 		= $this->getXMLFileName($domain);
			$xmlPath 	= $this->profile_base . '/' . $file . '.xml';

			// Load in the profile XML file to the form
			if (($xml = JFactory::getXML($xmlPath, true)) && ($form->load($xml, false, false)))
			{
				// Successfully loaded in the XML
				return true;
			}
		}
	}

	/**
	 *  Method is called after user data is deleted from the database.
	 *
	 *  Deletes the profile when a user is deleted.
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
		if (!$this->use_profile)
		{
			return;
		}

		if ($success)
		{
			try
			{
				$this->deleteProfile($user['id']);
				SHLog::add(JText::sprintf('PLG_LDAP_PROFILE_INFO_12211', $user['username']), 12211, JLog::INFO, 'ldap');
			}
			catch (Exception $e)
			{
				SHLog::add($e, 12234, JLog::ERROR, 'ldap');
			}
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

		try
		{
			// Get username for adapter
			$username = SHUtilArrayhelper::getValue($user, 'username', false, 'string');

			if (empty($username))
			{
				// The old username isn't present so use new username
				$username = SHUtilArrayhelper::getValue($new, 'username', false, 'string');
			}

			// Include the mandatory Joomla fields (fullname and email)
			$this->saveMandatoryToLdap($username, $new['name'], $new['email']);

			// Check there is a profile to save (i.e. this event may not have been called from the profile form)
			if ($this->use_profile && (isset($new[self::FORM_FIELDS_NAME]) && (count($new[self::FORM_FIELDS_NAME]))))
			{
				$xml = $this->getXMLFields(SHUserHelper::getDomainParam($new));

				// Only get profile data and enabled elements from the input
				$profileData = $this->cleanInput($xml, $new[self::FORM_FIELDS_NAME]);

				// Save the profile back to LDAP
				$result = $this->saveProfileToLdap($xml, $username, $profileData);
			}
		}
		catch (Exception $e)
		{
			SHLog::add($e, 12232, JLog::ERROR, 'ldap');

			return false;
		}

		return $result;
	}

	/**
	 * Method is called after user data is stored in the database.
	 *
	 * Saves the profile to the J! database.
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
		if (!$this->use_profile)
		{
			return;
		}

		if ($success)
		{
			try
			{
				$adapter = SHFactory::getUserAdapter($user['username']);
				$this->saveProfile($user['id'], $user['username'], $adapter);
			}
			catch (Exception $e)
			{
				SHLog::add($e, 12233, JLog::ERROR, 'ldap');
			}
		}
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
		if ($this->use_profile)
		{
			try
			{
				$xml = $this->getXMLFields($adapter->getDomain());

				// Process the profile XML (this only needs to be done once)
				return $this->getAttributes($xml);
			}
			catch (Exception $e)
			{
				SHLog::add($e, 12235, JLog::ERROR, 'ldap');
			}
		}
	}

	/**
	 * Retrieves the file name of the profile based on domain.
	 *
	 * @param   string  $domain  Optional domain to use.
	 *
	 * @return  string  File name of the XML file.
	 *
	 * @throws  RuntimeException
	 */
	protected function getXMLFileName($domain)
	{
		// Use the profile name as the default if a domain is not specified
		$file = (is_null($domain) || !$this->use_domain) ? $this->profile_name : $domain;

		if (empty($file))
		{
			throw new RuntimeException(JText::_('PLG_LDAP_PROFILE_ERR_12204'), 12204);
		}

		// Get the full XML file path
		$xmlPath = $this->profile_base . '/' . $file . '.xml';

		if (!file_exists($xmlPath))
		{
			if (!$this->use_domain || ($file == $domain && $file == $this->profile_name))
			{
				// XML file doesn't exist
				throw new RuntimeException(JText::sprintf('PLG_LDAP_PROFILE_ERR_12201', $file), 12201);
			}
			else
			{
				// We will try to use the profile_name as a default due to the users domain profile being unavailable
				$file = $this->profile_name;
				$xmlPath = $this->profile_base . '/' . $file . '.xml';

				if (!file_exists($xmlPath))
				{
					// XML file doesn't exist
					throw new RuntimeException(JText::sprintf('PLG_LDAP_PROFILE_ERR_12201', $file), 12201);
				}
			}
		}

		return $file;
	}

	/**
	 * Include the optional profile language and return the XML profile fields.
	 *
	 * @param   string  $domain  Optional domain to use.
	 *
	 * @return  SimpleXMLElement  Required XML profile fields
	 *
	 * @since   2.0
	 * @throws  RuntimeException
	 */
	protected function getXMLFields($domain = null)
	{
		// Get the File and Path for the Profile XML
		$file 		= $this->getXMLFileName($domain);
		$xmlPath 	= $this->profile_base . '/' . $file . '.xml';

		if (isset($this->xml[$file]))
		{
			// Xml has already been loaded
			return $this->xml[$file];
		}

		$fields = self::FORM_FIELDS_NAME;

		// Disable libxml errors and allow to fetch error information as needed
		libxml_use_internal_errors(true);

		// Attempt to load the XML file.
		if ($xml = simplexml_load_file($xmlPath))
		{
			// Get only the required header - i.e. ldap_profile
			if ($xml = $xml->xpath("/form/fields[@name='{$fields}']"))
			{
				SHLog::add(JText::_('PLG_LDAP_PROFILE_DEBUG_12202'), 12202, JLog::DEBUG, 'ldap');

				// Register the JForm rules classes
				if (file_exists($this->profile_base . '/rules'))
				{
					JForm::addRulePath($this->profile_base . '/rules');
				}

				// Register the JForm fields classes
				if (file_exists($this->profile_base . '/fields'))
				{
					JForm::addFieldPath($this->profile_base . '/fields');
				}

				// Attempt to load profile language
				$lang = JFactory::getLanguage();
				$lang->load($file, $this->lang_base);

				$this->xml[$file] = $xml[0];

				return $this->xml[$file];
			}
		}

		// Cannot load the XML file
		throw new RuntimeException(JText::sprintf('PLG_LDAP_PROFILE_ERR_12203', $xmlPath), 12203);
	}

	/**
	 * Initialise the synchronisation of the name and email fields from LDAP.
	 *
	 * @param   JUser          &$instance  Reference to the active joomla user.
	 * @param   SHUserAdapter  $adapter    User adapter of LDAP user.
	 *
	 * @return  boolean  True if a change occurred.
	 *
	 * @since   2.0
	 */
	protected function updateMandatory(&$instance, $adapter)
	{
		$change		= null;
		$fullname	= $adapter->getFullname();
		$email		= $adapter->getEmail();

		if ($this->sync_name && !empty($fullname) && $instance->name !== $fullname)
		{
			// Update the name of the JUser to the Ldap value
			$instance->name = $fullname;
			$change = true;
		}

		if ($this->sync_email && !empty($email) && $instance->email !== $email)
		{
			// Update the email of the JUser to the Ldap value
			$instance->email = $email;
			$change = true;
		}

		if ($change)
		{
			return true;
		}

		return;
	}

	/**
	 * Stage the mandatory data to LDAP ready for committing.
	 *
	 * @param   string  $username  Username of profile owner to change.
	 * @param   string  $name      Value of the new name.
	 * @param   string  $email     Value of the new email.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 * @throws  Exception
	 */
	protected function saveMandatoryToLdap($username, $name, $email)
	{
		$adapter = SHFactory::getUserAdapter($username);

		$attributes = array();

		// Do the Mandatory Joomla field saving for name/fullname
		if ((int) $this->sync_name === 2)
		{
			if (($key = $adapter->getFullname(true)) && $name)
			{
				$attributes[$key] = $name;
			}
		}

		// Do the Mandatory Joomla field saving for email
		if ((int) $this->sync_email === 2)
		{
			if (($key = $adapter->getEmail(true)) && $email)
			{
				$attributes[$key] = $email;
			}
		}

		$adapter->setAttributes($attributes);
	}

	/**
	 * Return the attributes required from the users LDAP account.
	 *
	 * @param   SimpleXMLElement  $xml  The XML profile to process.
	 *
	 * @return  array  An array of attributes
	 *
	 * @since   2.0
	 */
	protected function getAttributes($xml)
	{
		$attributes = array();

		foreach ($xml->fieldset as $fieldset)
		{
			foreach ($fieldset->field as $field)
			{
				$name = (string) $field['name'];
				$attributes[] = $name;
			}
		}

		return $attributes;
	}

	/**
	 * Save the users profile to the database.
	 *
	 * @param   integer        $userId    Joomla user ID to save.
	 * @param   string         $username  Joomla username to save.
	 * @param   SHUserAdapter  $adapter   User adapter of LDAP user.
	 * @param   array          $options   An optional set of options.
	 *
	 * @return  boolean  True on success
	 *
	 * @since   2.0
	 */
	protected function saveProfile($userId, $username, $adapter, $options = array())
	{
		$xml = $this->getXMLFields($adapter->getDomain());

		SHLog::add(JText::sprintf('PLG_LDAP_PROFILE_DEBUG_12221', $username), 12221, JLog::DEBUG, 'ldap');

		$addRecords		= array();
		$updateRecords 	= array();
		$deleteRecords	= array();

		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		// Lets get a list of current SQL entries
		if (is_null($current = $this->queryProfile($userId, true)))
		{
			return false;
		}

		/* We want to process each attribute in the XML
		* then find out if it exists in the LDAP directory.
		* If it does, then we compare that to the value
		* currently in the SQL database.
		*/
		$attributes = $this->getAttributes($xml);

		foreach ($attributes as $attribute)
		{
			// Lets check for a delimiter (this is the indicator that multiple values are supported)
			$delimiter = null;
			$xmlField = $xml->xpath("fieldset/field[@name='$attribute']");
			$value = null;

			if ($delimiter = (string) $xmlField[0]['delimiter'])
			{
				// These are potentially multiple values

				if (strToUpper($delimiter) == 'NEWLINE')
				{
					$delimiter = "\n";
				}

				$value = '';

				if ($v = $adapter->getAttributes($attribute))
				{
					if (is_array($v[$attribute]))
					{
						foreach ($v[$attribute] as $values)
						{
							$value .= $values . $delimiter;
						}
					}
				}
			}
			else
			{
				// These are single values
				if ($v = $adapter->getAttributes($attribute))
				{
					if (isset($v[$attribute][0]))
					{
						$value = $v[$attribute][0];
					}
				}
			}

			// Get the action status required against the SQL table
			$status = $this->checkSqlField($current, $attribute, $value);

			switch ($status)
			{
				case 1:
					$updateRecords[$attribute] = $value;
					break;

				case 2:
					$addRecords[$attribute] = $value;
					break;

				case 3:
					$deleteRecords[] = $attribute;
					break;
			}
		}

		/* Lets commit these differences to the database
		 * in steps (delete, add, update) and return the
		 * result.
		 */
		$results = array();

		if (count($deleteRecords))
		{
			$results[] = $this->deleteRecords($userId, $deleteRecords);
		}

		if (count($addRecords))
		{
			$results[] = $this->addRecords($userId, $addRecords, count($current) + 1);
		}

		if (count($updateRecords))
		{
			$results[] = $this->updateRecords($userId, $updateRecords);
		}

		$return = (!in_array(false, $results, true));

		if (count($results))
		{
			// Changes occurred so lets log it
			SHLog::add(
				JText::sprintf(
					'PLG_LDAP_PROFILE_DEBUG_12225',
					$username,
					$return == 1 ? JText::_('PLG_LDAP_PROFILE_SUCCESS') : JText::_('PLG_LDAP_PROFILE_FAIL')
				), 12225, JLog::DEBUG, 'ldap'
			);

			if (!$return)
			{
				// There was an error
				return false;
			}

			// Everything went well - we have updated both LDAP and the J! database.
			SHLog::add(JText::sprintf('PLG_LDAP_PROFILE_INFO_12224', $username), 12224, JLog::INFO, 'ldap');

			// Return this was successful and something was updated
			return true;
		}
		else
		{
			// No changes occurred so log that the profile was up to date
			SHLog::add(
				JText::sprintf('PLG_LDAP_PROFILE_DEBUG_12226', $username), 12226, JLog::DEBUG, 'ldap'
			);

			return;
		}
	}

	/**
	 * Delete the entire profile of the user.
	 *
	 * @param   integer  $userId  The user ID of the JUser
	 *
	 * @return  boolean  True on success
	 *
	 * @since   2.0
	 * @throws  JDatabaseException
	 */
	protected function deleteProfile($userId)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->delete($query->quoteName('#__user_profiles'))
			->where($query->quoteName('user_id') . ' = ' . $query->quote((int) $userId))
			->where($query->quoteName('profile_key') . ' LIKE \'ldap.%\'');

		$db->setQuery($query);

		return $db->query();
	}

	/**
	 * Return the records of a users profile.
	 *
	 * @param   integer  $userId  The user ID of the JUser
	 * @param   boolean  $clean   If true remove the profile prefix from the keys
	 *
	 * @return  array  Associated array of records (profile_key=>, profile_value=>)
	 *
	 * @since   2.0
	 */
	protected function queryProfile($userId, $clean = false)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		if ($clean)
		{
			$query->select('REPLACE(' . $query->quoteName('profile_key') .
				', \'ldap.\', \'\')' . $query->quoteName('profile_key')
			);
		}
		else
		{
			$query->select($query->quoteName('profile_key'));
		}

		$query->select($query->quoteName('profile_value'))
			->from($query->quoteName('#__user_profiles'))
			->where($query->quoteName('user_id') . ' = ' . $query->quote((int) $userId))
			->where($query->quoteName('profile_key') . ' LIKE \'ldap.%\'')
			->order($query->quoteName('ordering'));

		$db->setQuery($query);

		return $db->loadAssocList();
	}

	/**
	 * Add profile records (attributes) for a user to the database.
	 *
	 * @param   integer  $userId      The user ID of the JUser
	 * @param   array    $attributes  An array of associated attributes (profile_key=>profile_value)
	 * @param   integer  $order       The ordering number to use as a base
	 *
	 * @return  boolean  True on success
	 *
	 * @since   2.0
	 */
	protected function addRecords($userId, $attributes, $order)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->insert($query->quoteName('#__user_profiles'))
			->columns(
				array(
					$query->quoteName('user_id'),
					$query->quoteName('profile_key'),
					$query->quoteName('profile_value'),
					$query->quoteName('ordering')
				)
			);

		foreach ($attributes as $key => $value)
		{
			$key = 'ldap.' . $key;

			$query->values(
				$query->quote((int) $userId) . ', ' .
				$query->quote($key) . ', ' .
				$db->quote($value) . ', ' .
				$query->quote($order)
			);

			++$order;
		}

		$db->setQuery($query);

		return $db->query();
	}

	/**
	 * Update profile records (attributes) for a user to the database.
	 *
	 * @param   integer  $userId      The user ID of the JUser
	 * @param   array    $attributes  An array of associated attributes (profile_key=>profile_value)
	 *
	 * @return  boolean  True on success
	 *
	 * @since   2.0
	 */
	protected function updateRecords($userId, $attributes)
	{
		$result = true;

		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		foreach ($attributes as $key => $value)
		{
			$key = 'ldap.' . $key;
			$query->update($query->quoteName('#__user_profiles'))
				->set($query->quoteName('profile_value') . ' = ' . $db->quote($value))
				->where($query->quoteName('profile_key') . ' = ' . $query->quote($key))
				->where($query->quoteName('user_id') . ' = ' . $query->quote((int) $userId));

			$db->setQuery($query);

			if (!$db->query())
			{
				$result = false;
			}

			$query->clear();
		}

		return $result;
	}

	/**
	 * Delete profile records (attributes) for a user from the database.
	 *
	 * @param   integer  $userId      The user ID of the JUser
	 * @param   array    $attributes  An array of attribute/profile keys
	 *
	 * @return  boolean  True on success
	 *
	 * @since   2.0
	 */
	protected function deleteRecords($userId, $attributes)
	{
		$result = true;

		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		foreach ($attributes as $key)
		{
			$key = 'ldap.' . $key;
			$query->delete($query->quoteName('#__user_profiles'))
				->where($query->quoteName('user_id') . ' = ' . $query->quote((int) $userId))
				->where($query->quoteName('profile_key') . ' = ' . $query->quote($key));

			$db->setQuery($query);

			if (!$db->query())
			{
				$result = false;
			}

			$query->clear();
		}

		return $result;
	}

	/**
	 * Check the database (sql parameter) for the current status of a key and its
	 * value. This method will return with either a 0-match, 1-modify, 2-addition
	 * or 3-deletion flag for this key.
	 *
	 * @param   array   $sql    Associated array of records (profile_key=>, profile_value=>)
	 * @param   string  $key    The profile key to check
	 * @param   string  $value  The profile value to check against the key
	 *
	 * @return  integer  0-match | 1-modify | 2-addition | 3-delete
	 *
	 * @since   2.0
	 */
	protected function checkSqlField($sql, $key, $value)
	{
		if (is_null($value))
		{
			// LDAP value is null so lets default to SQL being correct
			$status = 0;
		}
		else
		{
			// LDAP value is populated so lets default to SQL addition
			$status = 2;
		}

		foreach ($sql as $record)
		{
			if ($record['profile_key'] == $key)
			{
				// The value exists in SQL but it may not be the correct value
				$status = 1;

				if (is_null($value))
				{
					// The value exists in SQL but not in LDAP
					$status = 3;
				}
				elseif ($record['profile_value'] == $value)
				{
					// The value exists in SQL and has the same value as LDAP
					$status = 0;
				}
			}
		}

		return $status;
	}

	/**
	 * Cleans the form fields to return only XML enabled form fields.
	 *
	 * @param   SimpleXMLElement  $xml     The XML profile to process.
	 * @param   array             $fields  An array of fields to be processed.
	 *
	 * @return  array  An array of fields that are enabled.
	 *
	 * @since   2.0
	 */
	protected function cleanInput($xml, $fields = array())
	{
		$clean = array();

		foreach ($fields as $key => $value)
		{
			if ($xmlField = $xml->xpath("fieldset/field[@name='$key']"))
			{
				$disabled = (string) $xmlField[0]['disabled'];

				if ($disabled != 'true' && $disabled != 1)
				{
					$clean[$key] = $value;
				}
			}
		}

		return $clean;
	}

	/**
	 * Stage the profile to LDAP ready for committing.
	 *
	 * @param   SimpleXMLElement  $xml       The XML profile to process.
	 * @param   string            $username  Username of profile owner to change.
	 * @param   array             $profile   Array of profile fields to save (key=>value).
	 *
	 * @return  boolean  True on success
	 *
	 * @since   2.0
	 * @throws  Exception
	 */
	protected function saveProfileToLdap($xml, $username, $profile = array())
	{
		// Setup the profile user in user adapter
		$adapter = SHFactory::getUserAdapter($username);

		$processed = array();

		// Loop around each profile field
		foreach ($profile as $key => $value)
		{
			$delimiter 	= null;
			$xmlField 	= $xml->xpath("fieldset/field[@name='$key']");

			if ($delimiter = (string) $xmlField[0]['delimiter'])
			{
				/* Multiple values - we will use a delimiter to represent
				 * the extra data in Joomla. We also use a newline override
				 * as this is probably going to be the most popular delimter.
				 */
				if (strToUpper($delimiter) == 'NEWLINE')
				{
					$delimiter = '\r\n|\r|\n';
				}

				// Split up the delimited profile field
				$newValues = preg_split("/$delimiter/", $value);

				// Resave the split profile field into a new array set
				for ($i = 0; $i < count($newValues); ++$i)
				{
					$processed[$key][$i] = $newValues[$i];
				}
			}
			else
			{
				// Single Value
				$processed[$key] = $value;
			}
		}

		if (count($processed))
		{
			// Lets save the new (current) fields to the LDAP DN
			$adapter->setAttributes($processed);
		}

		return true;
	}
}
