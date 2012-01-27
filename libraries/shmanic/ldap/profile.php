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

jimport('joomla.version');
jimport('shmanic.client.jldap2');

/**
 * Maps LDAP profile data and Joomla profile data.
 *
 * @package		Shmanic.Ldap
 * @subpackage	Profile
 * @since		2.0
 */
class LdapProfile extends JObject 
{
	/**
	* Synchronise fullname with joomla database
	*
	* @var    boolean
	* @since  1.0
	*/
	protected $sync_name = false;
	
	/**
	 * Synchronise email with joomla database
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $sync_email = false;
	
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
	 * @param  array  $parameters  The LDAP Profile parameters
	 *
	 * @since   2.0
	 */
	function __construct($parameters) 
	{	
		if($parameters instanceof JRegistry) {
			$parameters = $parameters->toArray();
		}
		
		parent::__construct($parameters);
		
		$lang = JFactory::getLanguage();
		$lang->load('lib_ldapprofile', JPATH_SITE); //for errors
		
	}
	
	/**
	 * Initialise the synchronisation of the name and email
	 * fields from LDAP.
	 * 
	 * @param  JUser   &$instance  Reference to the active joomla user
	 * @param  array   $user       Contains all the LDAP response data including attributes
	 * @param  string  $nameKey    Contains the ldap attribute key string for name
	 * @param  string  $emailKey   Contains the ldap attribute key string for email
	 * 
	 * @return  void
	 * @since   2.0
	 */
	public function doSync(&$instance, $user, $nameKey, $emailKey)
	{
		if($this->sync_name && !is_null($nameKey) && isset($user['attributes'][$nameKey][0])) {
			if($name = $user['attributes'][$nameKey][0]) $instance->set('name', $name);
		}
		
		if($this->sync_email && !is_null($emailKey) && isset($user['attributes'][$emailKey][0])) {
			if($email = $user['attributes'][$emailKey][0]) $instance->set('email', $email);
		}
	}
	
	// get the full xml path
	public function getXMLPath($base = null) 
	{
		
		if(is_null($this->profile_name)) {
			return false;
		}
		
		if(is_null($base)) {
			$base = JPATH_PLUGINS . '/ldap/profile/profiles';
		}
		
		$file = $base . '/' . $this->profile_name . '.xml';
		
		if(is_file($file)) {
			return $file;
		}
		
	}
	
	/* get the xml fields and include the languages */
	public function getXMLFields($xmlPath = null, $langPath = null, $fields = null)
	{

		if(is_null($xmlPath)) {
			$xmlPath = $this->getXMLPath();
		}
		
		if(is_null($langPath)) {
			$langPath = JPATH_PLUGINS . '/ldap/profile/profiles';
		}
		
		if(is_null($fields)) {
			$fields = 'ldap_profile';
		}
		
		// Attempt to load the XML file.
		if($xml = JFactory::getXML($xmlPath, true)) {
			
			// Get only the required header - i.e. ldap_profile
			if($xml = $xml->xpath("/form/fields[@name='$fields']")) {
				
				// Attempt to load profile language
				$lang = JFactory::getLanguage();
				$lang->load($this->profile_name, $langPath);
				
				return $xml[0];
				
			}
		}
		
	}
	
	public function getAttributes($xml) 
	{
		$attributes = array();

		foreach($xml->fieldset as $fieldset) {
			foreach($fieldset->field as $field) {
				$name = (string)$field['name'];
				$attributes[] = $name;
			}
		}
		return $attributes;
	}
	
	
	public function deleteProfile($userId)
	{
		if(!$userId = (int)$userId) {
			return false;
		}

		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
			
		$query->delete($query->qn('#__user_profiles'))
			->where($query->qn('user_id') . ' = ' . $query->q($userId))
			->where($query->qn('profile_key') . ' LIKE \'ldap.%\'');
			
		$db->setQuery($query);
		
		if (!$db->query()) { 
			return false;
		}
		
		return true;
	}
	
	//$clean - remove the ldap. from the keys
	public function queryProfile($userId, $clean = false)
	{
		if(!$userId = (int)$userId) {
			return false;
		}
		
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		
		if($clean) {
			$query->select('REPLACE(' . $query->qn('profile_key') . 
				', \'ldap.\', \'\')' . $query->qn('profile_key'));
			
		} else {
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
	
	public function addRecords($userId, $attributes, $order)
	{
		if(!$userId = (int)$userId) {
			return false;
		}
		
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
			
		$query->insert($query->qn('#__user_profiles'))
			->columns(array($query->qn('user_id'), $query->qn('profile_key'), $query->qn('profile_value'), $query->qn('ordering')));
		
		foreach($attributes as $key=>$value) {
			$key = 'ldap.' . $key;
			
			$query->values($query->q($userId) . ', ' . $query->q($key) . ', ' . $db->quote($value) . ', ' . $query->q($order));
			$order++;
		}
		
		$db->setQuery($query);
		
		if (!$db->query()) {
			return false;
		}
		
		return true;
	}
	
	public function updateRecords($userId, $attributes)
	{
		if(!$userId = (int)$userId) {
			return false;
		}
		
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
			
		foreach($attributes as $key=>$value) {
			$key = 'ldap.' . $key;
			$query->update($query->qn('#__user_profiles'))
				->set($query->qn('profile_value') . ' = ' . $db->quote($value))
				->where($query->qn('profile_key') . ' = ' . $query->q($key))
				->where($query->qn('user_id') . ' = ' . $query->q($userId));
			
			$db->setQuery($query);
			
			if (!$db->query()) {
				return false;
			}
			
			$query->clear();
			
		}
		
		return true;
	}
	
	public function deleteRecords($userId, $attributes)
	{
		
		if(!$userId = (int)$userId) {
			return false;
		}
		
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
			
		foreach($attributes as $key) {
			$key = 'ldap.' . $key;
			$query->delete($query->qn('#__user_profiles'))
				->where($query->qn('user_id') . ' = ' . $query->q($userId))
				->where($query->qn('profile_key') . ' = ' . $query->q($key));
			
			$db->setQuery($query);
			
			if (!$db->query()) {
				return false;
			}
			
			$query->clear();
			
		}
		
		return true;

	}
	
	public function saveProfile($xml, $instance, $user, $options) 
	{
		if(!$userId = (int)$instance->get('id')) {
			return false;
		}
		
		$addRecords		= array();
		$updateRecords 	= array();
		$deleteRecords	= array();
		
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		
		// Lets get a list of current SQL entries
		if(is_null($current = $this->queryProfile($userId, true))) {
			return false;
		}

		/* We want to process each attribute in the XML
		* then find out if it exists in the LDAP directory.
		* If it does, then we compare that to the value
		* currently in the SQL database.
		*/
		$attributes = $this->getAttributes($xml);
		foreach($attributes as $attribute) {
			
			// Lets check for a delimiter (this is the indicator that multiple values are supported)
			$delimiter 	= null;
			$xmlField = $xml->xpath("fieldset/field[@name='$attribute']");
			$value = null;
			
			if($delimiter = (string)$xmlField[0]['delimiter']) {
				
				// These are potentially multiple values
				
				if(strToUpper($delimiter) == 'NEWLINE') $delimiter = "\n";
				
				$value = '';
				if(isset($user['attributes'][$attribute])) {
					foreach($user['attributes'][$attribute] as $values) {
						$value .= $values . $delimiter;
					}
				}
			
			
			} else {
				
				// These are single values
				
				if(isset($user['attributes'][$attribute][0])) {
					$value = $user['attributes'][$attribute][0];
				}
			}
			
			if(!is_null($value)) {
				$status = $this->checkSqlField($current, $attribute, $value);
			} else {
				$status = 3; // This record should be deleted
			}
				
			switch($status) {
					
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
		
		
		$results 	= array();
		
		if(count($deleteRecords)) {
			$results[] = $this->deleteRecords($userId, $deleteRecords);
		}
		
		if(count($addRecords)) {
			$results[] = $this->addRecords($userId, $addRecords, count($current)+1);
		}
		
		if(count($updateRecords)) {
			$results[] = $this->updateRecords($userId, $updateRecords);
		}
		
		if(!in_array(false, $results, true)) {
			return true;
		}
		
	}
	
	
	// @RETURN: 0-same/ignore, 1-modify, 2-addition, 3-delete
	// Check if the SQL database at $attribute has $value
	protected function checkSqlField($sql, $attribute, $value)
	{
		
		$status = 2;
		
		foreach($sql as $record) {
			
			if($record['profile_key']==$attribute) {
				$status = 1;
				if($record['profile_value']==$value) {
					$status = 0;
				}
			}
		}
	
		return $status;

	}
	
	
	/* clean up input fields to ensure that disabled options have been honoured */
	public function cleanInput($xml, $fields = array())
	{
		$clean = array();
		
		foreach($fields as $key=>$value) {
			if($xmlField = $xml->xpath("fieldset/field[@name='$key']")) {
				$disabled = (string)$xmlField[0]['disabled'];
				if($disabled != 'true' && $disabled != 1) {
					$clean[$key] = $value;
				}
			}
		}
		
		return $clean;
	}
	
	
	/* save the new profile attributes to ldap and J! database */
	public function saveToLDAP($xml, $username, $fields = array())
	{
		
		/* Get a connection to ldap using the authentication
		 * username and password.
		 */
		if($ldap = LdapHelper::getConnection(true)) {
			
			if(!$dn = $ldap->getUserDN($username, null, false)) {
				return false;
			}
			
			if(!$current = $ldap->getUserDetails($dn)) {
				return false;
			}
			
			$new = array();
			
			foreach($fields as $key=>$value) {
				
				$delimiter 	= null;
				$xmlField 	= $xml->xpath("fieldset/field[@name='$key']");
				
				if($delimiter = (string)$xmlField[0]['delimiter']) { 
					
					/* Multiple values - we will use a delimiter to represent
					 * the extra data in Joomla. We also use a newline override
					 * as this is probably going to be the most popular delimter.
					 */
					if(strToUpper($delimiter) == 'NEWLINE') $delimiter = '\r\n|\r|\n';
					
					$newValues = preg_split("/$delimiter/", $value);
					
					for($i=0; $i<count($newValues); $i++) {
						$new[$key][$i] = $newValues[$i];
					}
					
				} else {
					
					// Single Value
					$new[$key] = $value;

				}
			}

			
			if(count($new)) {
			
				// Lets save the new (current) fields to the LDAP DN
				LdapHelper::makeChanges($dn, $current, $new);

				// Refresh profile for this user in J! database
				if(!$current = $ldap->getUserDetails($dn)) {
					return false;
				}
				
				$instance = JFactory::getUser(); //THIS IS WRONG!! The logged on user may be editing somebody else's profile TODO TODO
				
				$this->saveProfile($xml, $instance, array('attributes'=>$current), array());
				
			}
			
		} 
	}
	
}
