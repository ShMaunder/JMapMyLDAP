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
 * Holds each Ldap entry with its associated Joomla group. This
 * class also contains methods for comparing entries.
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
	
	public function saveProfile($xml, $instance, $user, $options)
	{
		if($uid = $instance->get('id')) {
			
			try {
				
				$db = &JFactory::getDbo();
				$db->setQuery('DELETE FROM #__user_profiles WHERE user_id = '.$uid.' AND profile_key LIKE \'ldap.%\'');
				if (!$db->query()) {
					throw new Exception($db->getErrorMsg());
				}
				
				$order 	= 0;
				$tuples = array();
				$attributes = $this->getAttributes($xml);
				
				/* TODO: delimiting will NOT WORK here at the moment. URGENT FIX REQUIRED */
				foreach($attributes as $attribute) {
					if(isset($user['attributes'][$attribute][0]) && ($value = $user['attributes'][$attribute][0])) {
						$tuples[] = '(' . $uid . ', ' . $db->quote('ldap.'.$attribute) . ', ' . $db->quote($value)
						 . ', ' . $order++ . ')';
					}
				}
				
				if(count($tuples)) {

					$db->setQuery('INSERT INTO #__user_profiles VALUES '.implode(', ', $tuples));
					
					if (!$db->query()) {
						throw new Exception($db->getErrorMsg());
					}
				}
				
			} catch (JException $e) {
				$this->setError($e->getError());
				return false;
			}
		
		}

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

			$dn = $ldap->getUserDN($username, null, false);
			if(JError::isError($dn)) {
				return false;
			}
			
			$user = $ldap->getUserDetails($dn);
			if(JError::isError($user)) {
				return false;
			}

			$modify 	= array();
			$addition	= array();
			
			foreach($fields as $key=>$value) {
				
				$delimiter 	= null;
				
				$xmlField = $xml->xpath("fieldset/field[@name='$key']");
				
				if($delimiter = (string)$xmlField[0]['delimiter']) { 
					
					// use the extra optional characters for windows newlines
					if(strToUpper($delimiter) == 'NEWLINE') $delimiter = '\r\n|\r|\n';
					
					$newValues = preg_split("/$delimiter/", $value);
					
					for($i=0; $i<count($newValues); $i++) {
						
						$result = ($this->checkField($user, $key, $i, $newValues[$i]));
						
						if($result == 1) 		$modify[$key][$i] = $newValues[$i];
						elseif ($result ==2)	$addition[$key][$i] = $newValues[$i];
						
					}
					
				} else {
					
					$result = ($this->checkField($user, $key, 0, $value));
					
					if($result == 1) 		$modify[$key][0] = $value;
					elseif ($result ==2)	$addition[$key][0] = $value;
					
				}
			}
			
			if(count($modify)) {
				$ldap->modify($dn, $modify);
			}
			
			if(count($addition)) {
				$ldap->add($dn, $addition);
			}
			
			if(count($modify) || count($addition)) {
				// Refresh profile for this user in J! database
				
				$user = $ldap->getUserDetails($dn);
				if(JError::isError($user)) {
					return false;
				}
				
				$instance = JFactory::getUser();
				
				$this->saveProfile($xml, $instance, array('attributes'=>$user), array());
				
			}	
			
		}
	}
	
	// @RETURN: 0-discard, 1-modify, 2-addition
	protected function checkField($user, $key, $interval, $value) 
	{
		
		if(array_key_exists($key, $user)) {
			// LDAP attribute exists, we can use modify
			
			if(isset($user[$key][$interval])) {
				if($user[$key][$interval] == $value) {
					return 0; // Same value - no need to update
				}
			}
		
			return 1;

		} else {
			// We need to create a new LDAP attribute
			return 2;
		}
	}
	
}
