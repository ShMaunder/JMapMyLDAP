<?php
/**
 * @version     $Id:$
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @package     Shmanic.Ldap
 * @subpackage  Profile
 *
 * @copyright	Copyright (C) 2011 Shaun Maunder. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Maps LDAP profile data to Joomla and vice-versa.
 *
 * @package		Shmanic.Ldap
 * @subpackage	Profile
 * @since		2.0
 */
class SHLdapProfile extends JObject
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
	* Profile XML name to use
	*
	* @var    string
	* @since  2.0
	*/
	protected $profile_name = null;

	/**
	 * Class constructor.
	 *
	 * @param   array  $parameters  The LDAP Profile parameters
	 *
	 * @since   2.0
	 */
	public function __construct($parameters)
	{
		$parameters = ($parameters instanceof JRegistry) ?
			$parameters->toArray() : $parameters;

		parent::__construct($parameters);

		// Load languages for errors
		$lang = JFactory::getLanguage();
		$lang->load('lib_ldap_profile', JPATH_SITE);
	}

	/**
	 * Initialise the synchronisation of the name and email
	 * fields from LDAP.
	 *
	 * @param   JUser   &$instance  Reference to the active joomla user
	 * @param   array   $user       Contains all the LDAP response data including attributes
	 * @param   string  $nameKey    Contains the ldap attribute key string for name
	 * @param   string  $emailKey   Contains the ldap attribute key string for email
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function doSync(&$instance, $user, $nameKey, $emailKey)
	{
		if ($this->sync_name && !is_null($nameKey) && isset($user['attributes'][$nameKey][0]))
		{
			if ($name = $user['attributes'][$nameKey][0])
			{
				$instance->set('name', $name);
			}
		}

		if ($this->sync_email && !is_null($emailKey) && isset($user['attributes'][$emailKey][0]))
		{
			if ($email = $user['attributes'][$emailKey][0])
			{
				$instance->set('email', $email);
			}
		}
	}

	/**
	* Builds and returns the full path to the profile XML.
	*
	* @param   string  $base  Optional base path to find the profile XML (default is ./profiles)
	*
	* @return  string  Full path to the profile XML
	*
	* @since   2.0
	*/
	public function getXMLPath($base = null)
	{

		if (is_null($this->profile_name))
		{
			return false;
		}

		if (is_null($base))
		{
			$base = JPATH_PLUGINS . '/ldap/profile/profiles';
		}

		$file = $base . '/' . $this->profile_name . '.xml';

		if (is_file($file))
		{
			return $file;
		}
		else
		{
			// XML file doesn't exist
			SHLog(JText::sprintf('LIB_LDAP_PROFILE_XML_NOT_EXISTS', $file), 0, JLog::ERROR, 'ldap');
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
	*/
	public function getXMLFields($xmlPath = null, $langPath = null, $fields = null)
	{
		$xmlPath = is_null($xmlPath) ? $this->getXMLPath() : $xmlPath;

		$langPath = is_null($langPath) ? JPATH_PLUGINS . '/ldap/profile/profiles' : $langPath;

		$fields = is_null($fields) ? 'ldap_profile' : $fields;

		// Attempt to load the XML file.
		if ($xml = JFactory::getXML($xmlPath, true))
		{
			// Get only the required header - i.e. ldap_profile
			if ($xml = $xml->xpath("/form/fields[@name='$fields']"))
			{
				SHLog::add(JText::_('LIB_LDAP_PROFILE_XML_LOADED_VALID'), 0, JLog::DEBUG, 'ldap');

				// Attempt to load profile language
				$lang = JFactory::getLanguage();
				$lang->load($this->profile_name, $langPath);
				return $xml[0];
			}
			else
			{
				// Invalid profile XML
				SHLog::add(JText::sprintf('LIB_LDAP_PROFILE_FAILED_LOAD_XML', $xmlPath), 0, JLog::ERROR, 'ldap');
			}
		}
		else
		{
			// Cannot load the XML file
			SHLog::add(JText::sprintf('LIB_LDAP_PROFILE_FAILED_LOAD_XML', $xmlPath), 0, JLog::ERROR, 'ldap');
		}
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
	*/
	public function deleteProfile($userId)
	{
		if (!$userId = (int) $userId)
		{
			return false;
		}

		SHLog::add(JText::sprintf('LIB_LDAP_PROFILE_DELETED_PROFILE', $userId), 0, JLog::INFO, 'ldap');

		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->delete($query->qn('#__user_profiles'))
			->where($query->qn('user_id') . ' = ' . $query->q($userId))
			->where($query->qn('profile_key') . ' LIKE \'ldap.%\'');

		$db->setQuery($query);

		if (!$db->query())
		{
			return false;
		}

		return true;
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
		if (!$userId = (int) $userId)
		{
			return false;
		}

		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		if ($clean)
		{
			$query->select('REPLACE(' . $query->qn('profile_key') .
				', \'ldap.\', \'\')' . $query->qn('profile_key')
			);

		}
		else
		{
			$query->select($query->qn('profile_key'));
		}

		$query->select($query->qn('profile_value'))
			->from($query->qn('#__user_profiles'))
			->where($query->qn('user_id') . ' = ' . $query->q($userId))
			->where($query->qn('profile_key') . ' LIKE \'ldap.%\'')
			->order($query->qn('ordering'));

		$db->setQuery($query);

		return $db->loadAssocList();

	}

	/**
	* Add profile records (attributes) for a user
	* to the database.
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
		if (!$userId = (int) $userId)
		{
			return false;
		}

		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->insert($query->qn('#__user_profiles'))
			->columns(array($query->qn('user_id'), $query->qn('profile_key'), $query->qn('profile_value'), $query->qn('ordering')));

		foreach ($attributes as $key => $value)
		{
			$key = 'ldap.' . $key;

			$query->values($query->q($userId) . ', ' . $query->q($key) . ', ' . $db->quote($value) . ', ' . $query->q($order));
			++$order;
		}

		$db->setQuery($query);

		if (!$db->query())
		{
			return false;
		}

		return true;
	}

	/**
	* Update profile records (attributes) for a user
	* to the database.
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
		if (!$userId = (int) $userId)
		{
			return false;
		}

		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		foreach ($attributes as $key => $value)
		{
			$key = 'ldap.' . $key;
			$query->update($query->qn('#__user_profiles'))
				->set($query->qn('profile_value') . ' = ' . $db->quote($value))
				->where($query->qn('profile_key') . ' = ' . $query->q($key))
				->where($query->qn('user_id') . ' = ' . $query->q($userId));

			$db->setQuery($query);

			if (!$db->query())
			{
				return false;
			}

			$query->clear();

		}

		return true;
	}

	/**
	* Delete profile records (attributes) for a user
	* from the database.
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

		if (!$userId = (int) $userId)
		{
			return false;
		}

		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		foreach ($attributes as $key)
		{
			$key = 'ldap.' . $key;
			$query->delete($query->qn('#__user_profiles'))
				->where($query->qn('user_id') . ' = ' . $query->q($userId))
				->where($query->qn('profile_key') . ' = ' . $query->q($key));

			$db->setQuery($query);

			if (!$db->query())
			{
				return false;
			}

			$query->clear();

		}

		return true;
	}

	/**
	* Save the users profile to the database.
	*
	* @param   JXMLElement  $xml       XML profile fields
	* @param   JUser        $instance  The Joomla user
	* @param   array        $user      Contains all the LDAP response data including attributes
	* @param   array        $options   An optional set of options
	*
	* @return  boolean  True on success
	*
	* @since   2.0
	*/
	public function saveProfile($xml, $instance, $user, $options = array())
	{
		if (!$userId = (int) $instance->get('id'))
		{
			return false;
		}

		SHLog::add(JText::sprintf('LIB_LDAP_PROFILE_ATTEMPT_TO_SYNC', $instance->username), 0, JLog::DEBUG, 'ldap');

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
				if (isset($user['attributes'][$attribute]))
				{
					foreach ($user['attributes'][$attribute] as $values)
					{
						$value .= $values . $delimiter;
					}
				}

			}
			else
			{
				// These are single values
				if (isset($user['attributes'][$attribute][0]))
				{
					$value = $user['attributes'][$attribute][0];
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
			SHLog::add(
				JText::sprintf(
					'LIB_LDAP_PROFILE_UPDATED_DATABASE_FIELDS',
					$instance->username,
					$return == 1 ? JText::_('LIB_LDAP_PROFILE_SUCCESS') : JText::_('LIB_LDAP_PROFILE_FAIL')
				),
				0, JLog::DEBUG, 'ldap'
			);
		}
		else
		{
			SHLog::add(JText::sprintf('LIB_LDAP_PROFILE_UP_TO_DATE', $instance->username), 0, JLog::DEBUG, 'ldap');
		}

		return $return;

	}

	/**
	* Check the database (sql parameter) for the
	* current status of a key and its value. This
	* method will return with either a 0-match, 1-modify,
	* 2-addition or 3-deletion flag for this key.
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
	* Return form fields that are enabled only in the
	* XML.
	*
	* @param   JXMLElement  $xml     XML profile fields
	* @param   array        $fields  An array of fields to be processed
	*
	* @return  array  An array of fields that are enabled
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
	* Save the profile to LDAP and then call for a
	* Joomla! database refresh so both data sources
	* have the same information.
	*
	* @param   JXMLElement  $xml        XML profile fields
	* @param   string       $username   The username of the profile to save
	* @param   array        $profile    Array of profile fields to save (key=>value)
	* @param   array        $mandatory  Array of mandatory joomla fields to save like name and email
	*
	* @return  boolean  True on success
	*
	* @since   2.0
	*/
	public function saveToLDAP($xml, $username, $profile = array(), $mandatory = array())
	{
return;
		/* Get a connection to ldap using the authentication
		 * username and password.
		 */
		if ($ldap = SHLdapHelper::getConnection(true))
		{

			if (!$dn = $ldap->getUserDN($username, null, false))
			{
				return false;
			}

			if (!$current = $ldap->getUserDetails($dn))
			{
				return false;
			}

			$processed = array();

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

					$newValues = preg_split("/$delimiter/", $value);

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

			// Do the Mandatory Joomla field saving
			if ($this->get('sync_name', 0) == 2)
			{
				if (($key = $ldap->ldap_fullname) && ($value = JArrayHelper::getValue($mandatory, 'name')))
				{
					$processed[$key] = $value;
				}
			}

			if ($this->get('sync_email', 0) == 2)
			{
				if (($key = $ldap->ldap_email) && ($value = JArrayHelper::getValue($mandatory, 'email')))
				{
					$processed[$key] = $value;
				}
			}

			if (count($processed))
			{
				// Lets save the new (current) fields to the LDAP DN
				LdapHelper::makeChanges($dn, $current, $processed);

				// Refresh profile for this user in J! database
				if (!$current = $ldap->getUserDetails($dn))
				{
					return false;
				}

				if ($userId = JUserHelper::getUserId($username))
				{

					SHLog::add(JText::sprintf('LIB_LDAP_PROFILE_UPDATED_PROFILE', $username), 0, JLog::INFO, 'ldap');

					$instance = new JUser($userId);
					$this->saveProfile($xml, $instance, array('attributes' => $current), array());

					return true;
				}

			}

		}
	}

}
