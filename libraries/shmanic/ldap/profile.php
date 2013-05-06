<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Maps LDAP profile data to Joomla and vice-versa.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap
 * @since       2.0
 */
class SHLdapProfile
{
	/**
	* Synchronise fullname with joomla database
	* 0-No Sync | 1-Sync From LDAP | 2- Sync To and From LDAP
	*
	* @var    integer
	* @since  1.0
	*/
	protected $sync_name = null;

	/**
	 * Synchronise email with joomla database
	 * 0-No Sync | 1-Sync From LDAP | 2- Sync To and From LDAP
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $sync_email = null;

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
	 * Class constructor.
	 *
	 * @param   object  $configObj  An object of configuration variables.
	 *
	 * @since   2.0
	 */
	public function __construct($configObj)
	{
		if (is_null($configObj))
		{
			// Parameters will need setting later
			$configArr = array();
		}
		elseif ($configObj instanceof JRegistry)
		{
			// JRegistry object needs to be converted to an array
			$configArr = $configObj->toArray();
		}
		elseif (is_array($configObj))
		{
			// The parameter was an array already
			$configArr = $configObj;
		}
		else
		{
			// Unknown format
			throw new InvalidArgumentException(JText::_('LIB_LDAP_PROFILE_ERR_12211'), 12211);
		}

		// Assign the configuration to their respected class properties only if they exist
		foreach ($configArr as $k => $v)
		{
			if (property_exists($this, $k))
			{
				$this->$k = $v;
			}
		}

		if (empty($this->profile_base))
		{
			// Use default base path
			$this->profile_base = JPATH_PLUGINS . '/ldap/profile/profiles';
		}

		// Load languages for errors
		JFactory::getLanguage()->load('lib_ldap_profile', JPATH_BASE);
	}

	/**
	 * Initialise the synchronisation of the name and email fields from LDAP.
	 *
	 * @param   JUser          &$instance  Reference to the active joomla user.
	 * @param   SHUserAdapter  $adapter    User adapter of LDAP user.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function updateMandatory(&$instance, $adapter)
	{
		$fullname = $adapter->getFullname();
		$email = $adapter->getEmail();

		if ($this->sync_name && !empty($fullname))
		{
			// Update the name of the JUser to the Ldap value
			$instance->name = $fullname;
		}

		if ($this->sync_email && !empty($email))
		{
			// Update the email of the JUser to the Ldap value
			$instance->email = $email;
		}
	}

	/**
	* Include the optional profile language and return the XML profile fields.
	*
	* @param   string  $xmlPath   Optional full path to the profile XML
	* @param   string  $langPath  Optional base path to the profile languages
	* @param   string  $fields    Optional name of the XML field to use
	*
	* @return  JXMLElement  Required XML profile fields
	*
	* @since   2.0
	* @throws  RuntimeException
	*/
	public function getXMLFields($xmlPath = null, $langPath = null, $fields = null)
	{
		if (is_null($xmlPath))
		{
			if (is_null($this->profile_name))
			{
				throw new RuntimeException(JText::_('LIB_LDAP_PROFILE_ERR_12214'), 12214);
			}

			$xmlPath = $this->xmlFilePath;
		}

		if (!file_exists($xmlPath))
		{
			// XML file doesn't exist
			throw new RuntimeException(JText::sprintf('LIB_LDAP_PROFILE_ERR_12201', $file), 12201);
		}

		$langPath = is_null($langPath) ? $this->profile_base : $langPath;

		$fields = is_null($fields) ? 'ldap_profile' : $fields;

		// Attempt to load the XML file.
		if ($xml = JFactory::getXML($xmlPath, true))
		{
			// Get only the required header - i.e. ldap_profile
			if ($xml = $xml->xpath("/form/fields[@name='$fields']"))
			{
				SHLog::add(JText::_('LIB_LDAP_PROFILE_DEBUG_12202'), 12202, JLog::DEBUG, 'ldap');

				// Attempt to load profile language
				$lang = JFactory::getLanguage();
				$lang->load($this->profile_name, $langPath);
				return $xml[0];
			}
		}

		// Cannot load the XML file
		throw new RuntimeException(JText::sprintf('LIB_LDAP_PROFILE_ERR_12203', $xmlPath), 12203);
	}

	/**
	* Return the attributes required from the users LDAP account.
	*
	* @param   JXMLElement  $xml  XML profile fields
	*
	* @return  array  An array of attributes
	*
	* @since   2.0
	*/
	public function getAttributes($xml)
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
	* Delete the entire profile of the user.
	*
	* @param   integer  $userId  The user ID of the JUser
	*
	* @return  boolean  True on success
	*
	* @since   2.0
	* @throws  JDatabaseException
	*/
	public function deleteProfile($userId)
	{
		SHLog::add(JText::sprintf('LIB_LDAP_PROFILE_INFO_12211', $userId), 12211, JLog::INFO, 'ldap');

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
	public function queryProfile($userId, $clean = false)
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
	public function addRecords($userId, $attributes, $order)
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
	public function updateRecords($userId, $attributes)
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
	public function deleteRecords($userId, $attributes)
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
	* Save the users profile to the database.
	*
	* @param   JXMLElement    $xml       XML profile fields.
	* @param   JUser          $instance  The Joomla user.
	* @param   SHUserAdapter  $adapter   User adapter of LDAP user.
	* @param   array          $options   An optional set of options.
	*
	* @return  boolean  True on success
	*
	* @since   2.0
	*/
	public function saveProfile(JXMLElement $xml, JUser $instance, $adapter, $options = array())
	{
		if (!$userId = (int) $instance->get('id'))
		{
			return false;
		}

		SHLog::add(JText::sprintf('LIB_LDAP_PROFILE_DEBUG_12221', $instance->username), 12221, JLog::DEBUG, 'ldap');

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
			$delimiter 	= null;
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

			if (!is_null($value))
			{
				$status = $this->checkSqlField($current, $attribute, $value);
			}
			else
			{
				// This record should be deleted
				$status = 3;
			}

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
		$results 	= array();

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
					'LIB_LDAP_PROFILE_DEBUG_12225',
					$instance->username,
					$return == 1 ? JText::_('LIB_LDAP_PROFILE_SUCCESS') : JText::_('LIB_LDAP_PROFILE_FAIL')
				), 12225, JLog::DEBUG, 'ldap'
			);
		}
		else
		{
			// No changes occurred so log that the profile was up to date
			SHLog::add(
				JText::sprintf('LIB_LDAP_PROFILE_DEBUG_12226', $instance->username), 12226, JLog::DEBUG, 'ldap'
			);
		}

		return $return;
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
		$status = 2;

		foreach ($sql as $record)
		{
			if ($record['profile_key'] == $key)
			{
				$status = 1;
				if ($record['profile_value'] == $value)
				{
					$status = 0;
				}
			}
		}

		return $status;
	}

	/**
	* Cleans the form fields to return only XML enabled form fields.
	*
	* @param   JXMLElement  $xml     XML profile fields.
	* @param   array        $fields  An array of fields to be processed.
	*
	* @return  array  An array of fields that are enabled.
	*
	* @since   2.0
	*/
	public function cleanInput($xml, $fields = array())
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
	* @param   JXMLElement  $xml        XML profile fields.
	* @param   string       $username   Username of profile owner to change.
	* @param   array        $profile    Array of profile fields to save (key=>value).
	* @param   array        $mandatory  Array of mandatory joomla fields to save like name and email.
	*
	* @return  boolean  True on success
	*
	* @since   2.0
	* @throws  Exception
	*/
	public function saveToLDAP(JXMLElement $xml, $username, $profile = array(), $mandatory = array())
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

		// Do the Mandatory Joomla field saving for name/fullname
		if ((int) $this->sync_name === 2)
		{
			if (($key = $adapter->getFullname(true)) && ($value = JArrayHelper::getValue($mandatory, 'name')))
			{
				$processed[$key] = $value;
			}
		}

		// Do the Mandatory Joomla field saving for email
		if ((int) $this->sync_email === 2)
		{
			if (($key = $adapter->getEmail(true)) && ($value = JArrayHelper::getValue($mandatory, 'email')))
			{
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
