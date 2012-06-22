<?php
/**
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @package     Shmanic
 * @subpackage  Ldap
 *
 * @copyright	Copyright (C) 2011 Shaun Maunder. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

jimport('shmanic.client.jldap2');
jimport('shmanic.ldap.event');
jimport('shmanic.log.ldaphelper');

/**
 * Ldap User Helper class.
 *
 * @package		Shmanic
 * @subpackage	Ldap.Helper
 * @since		2.0
 */
class LdapUserHelper extends JObject
{
	/**
	 * This method returns a user object. If options['autoregister'] is true,
	 * and if the user doesn't exist, then it'll be created.
	 *
	 * Dear Joomla, can you please put this into a library for everyone to use.
	 *
	 * @param  array  $user     Holds the user data.
	 * @param  array  $options  Array holding options (remember, autoregister, group).
	 *
	 * @return  JUser  A JUser object containing the user
	 * @since   1.0
	 */
	public static function &getUser($user, $options = array())
	{
		$instance = JUser::getInstance();
		if($id = intval(JUserHelper::getUserId($user['username'])))  {
			$instance->load($id);
			return $instance;
		}

		jimport('joomla.application.component.helper');
		$config	= JComponentHelper::getParams('com_users');
		// Default to Registered.
		$defaultUserGroup = $config->get('new_usertype', 2);

		$acl = JFactory::getACL();

		$instance->set('id'			, 0);
		$instance->set('name'		, $user['fullname']);
		$instance->set('username'	, $user['username']);
		$instance->set('password_clear'	, $user['password_clear']);
		$instance->set('email'		, $user['email']);	// Result should contain an email (check)
		$instance->set('usertype'	, 'depreciated');
		$instance->set('groups'		, array($defaultUserGroup));

		//If autoregister is set let's register the user
		$autoregister = isset($options['autoregister']) ? $options['autoregister'] : true;

		if($autoregister) {
			if(!$instance->save()) {
				JERROR::raiseWarning('SOME_ERROR_CODE', $instance->getError());
				$instance->set('error' , 1);
			}
		} else {
			//we don't want to proceed if autoregister is not enabled
			JERROR::raiseWarning('SOME_ERROR_CODE', JTEXT::_('JGLOBAL_AUTH_NO_USER'));
			$instance->set('error' , 1);
		}

		return $instance;
	}

	// get and return the user attributes (array) from LDAP
	/** @deprecated */
	public static function getAttributes($user)
	{
		if($ldap = LdapHelper::getConnection(false)) {

			$dn = $ldap->getUserDN($user['username'], null, false);
			if(JError::isError($dn)) {
				return false;
			}

			$attributes = $ldap->getUserDetails($dn);
			if(JError::isError($attributes)) {
				return false;
			}

			$ldap->close();

			return $attributes;
		}
	}

}

/**
* An LDAP helper class. All methods are static and don't require any
* new instance of the class.
*
* @package		Shmanic
* @subpackage	Ldap
* @since		2.0
*/
abstract class SHLdapHelper extends JObject
{

	/**
	 * Key to store user attributes in the authentication Response
	 *
	 * @var    string
	 * @since  2.0
	 */
	const ATTRIBUTE_KEY = 'attributes';

	/**
	 * Loads the correct Ldap configuration based on the record ID specified. Then uses
	 * this configuration to instantiate an LdapExtended client.
	 *
	 * @param   integer    $id      Configuration record ID.
	 * @param   JRegistry  $config  Optional override for platform configuration.
	 *
	 * @return  SHLdap  Ldap client with loaded configuration.
	 *
	 * @since   2.0
	 */
	public static function getClient($id = null, JRegistry $config = null)
	{
		if (is_null($config))
		{
			// Get the Ldap configuration from the factory
			$config = SHFactory::getConfig();
		}

		$params = array();

		// Get the Ldap configuration source (e.g. sql | plugin)
		$source = $config->get('ldap.config', 'sql');

		if ($source === 'sql')
		{
			// Get the number of servers configured in the database
			$servers = (int) $config->get('ldap.servers', 0);

			// Get the database table using the sh_ldap_config as default
			$table = $config->get('ldap.table', '#__sh_ldap_config');

			if (!$servers > 0 || is_null($id))
			{
				// No Ldap servers are configured!
				return null;
			}

			// Get the global JDatabase object
			$db = JFactory::getDbo();

			$query = $db->getQuery(true);

			// Do the SQL query
			$query->select($query->qn('name'))
				->select($query->qn('params'))
				->from($query->qn($table))
				->where($query->qn('enabled') . '>= 1')
				->where($query->qn('ldap_id') . '=' . $query->q($id));

			$db->setQuery($query);

			// Execute the query
			$results = $db->loadAssoc();

			if (is_null($results))
			{
				// Unable to find specified Ldap configuration
				return null;
			}

			if (isset($results['params']))
			{
				// Decode the JSON string direct from DB to an array
				$params = (array) json_decode($results['params']);
			}

		}
		elseif ($source === 'plugin')
		{
			// TODO: implement

			if ($plugin = JPluginHelper::getPlugin('authentication', $id))
			{
				// Get the authentication LDAP plug-in parameters
				$params = new JRegistry;
				$params->loadString($plugin->params);

				// We may have to convert if using the inbuilt JLDAP parameters
				return self::convert($params, 'SHLdap');
			}
		}

		return new SHLdap($params);

	}

	/**
	 * Returns the ID of the SQL record for the specified Ldap name.
	 *
	 * @param   string  $name  Ldap name (i.e. domain).
	 *
	 * @return  integer  Record ID.
	 *
	 * @since   2.0
	 */
	public static function getConfigId($name)
	{
		// Get the Ldap configuration from the factory
		$config = SHFactory::getConfig();

		// Get the database table using the sh_ldap_config as default
		$table = $config->get('ldap.table', '#__sh_ldap_config');

		// Get the global JDatabase object
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);

		// Do the SQL query
		$query->select($query->qn('ldap_id'))
			->from($query->qn($table))
			->where($query->qn('enabled') . '>= 1')
			->where($query->qn('name') . '=' . $query->q($name));

		$db->setQuery($query);

		// Execute the query
		$result = $db->loadResult();

		return $result;
	}

	/**
	 * Returns all the Ldap configured IDs and names in an associative array
	 * where [id] => [name].
	 *
	 * @return  Array  Array of configured IDs
	 *
	 * @since   2.0
	 */
	public static function getConfigIDs()
	{
		// Get the Ldap configuration from the factory
		$config = SHFactory::getConfig();

		// Get the database table using the sh_ldap_config as default
		$table = $config->get('ldap.table', '#__sh_ldap_config');

		// Get the global JDatabase object
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);

		// Do the SQL query
		$query->select($query->qn('ldap_id'))
			->select($query->qn('name'))
			->from($query->qn($table))
			->where($query->qn('enabled') . '>= 1');

		$db->setQuery($query);

		// Execute the query
		$results = $db->loadAssocList('ldap_id', 'name');

		return $results;
	}

	/**
	 * Returns if the current or specified user was authenticated
	 * via LDAP.
	 *
	 * @param   integer  $userId  Optional user id (if null then uses current user).
	 *
	 * @return  boolean  True if user is Ldap authenticated or False otherwise.
	 *
	 * @since   2.0
	 */
	public static function isUserLdap($userId = null)
	{
		if (JFactory::getUser($userId)->getParam('authtype') == 'LDAP')
		{
			// This user has the LDAP auth type
			return true;
		}

		return false;
	}

	/**
	 * Sets the flag for the specified user's parameters for
	 * LDAP authentication.
	 *
	 * @param   JUser|Integer  &$user  Specified user to set parameter
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public static function setUserLdap(&$user = null)
	{
		if (is_null($user) || is_int($user))
		{
			// The input variable indicates we must load the user object
			$obj = JFactory::getUser($user);
			$obj->setParam('authtype', 'LDAP');
		} elseif ($user instanceof JUser)
		{
			// Direct manipulation of the object
			$user->setParam('authtype', 'LDAP');
		}
	}

	/**
	 * Attempts to convert the Ldap configuration parameters to a specified
	 * library parameters. This method is not reliable and should not be used
	 * in a live environment.
	 *
	 * @param   JRegistry  $params   Parameters for conversion.
	 * @param   string     $convert  Library name conversion.
	 *
	 * @return  Array  Array of converted parameters
	 *
	 * @since   2.0
	 */
	public static function convert(JRegistry $params, $convert = 'SHLdap')
	{
		/*
		 * Attempt to convert inbuilt JLDAP library parameters
		* to the JLDAP2 parameters for backward compatibility.
		*/

		$converted = array();

		// Dodgy detection for JLDAP parameters
		if ($params->get('auth_method') && $params->get('search_string') || $params->get('users_dn'))
		{
			if ($convert === 'JLDAP')
			{
				// This appears to be converted already
				return $params->toArray();
			}

			// Convert all the parameters
			$converted['host'] 				= $params->get('host');
			$converted['port'] 				= $params->get('port');
			$converted['use_v3'] 			= $params->get('use_ldapV3');
			$converted['negotiate_tls']		= $params->get('negotiate_tls');
			$converted['use_referrals']		= (!$params->get('no_referrals'));

			$converted['proxy_username']	= $params->get('username');
			$converted['proxy_password']	= $params->get('password');

			$converted['ldap_uid'] 			= $params->get('ldap_uid');
			$converted['ldap_fullname']		= $params->get('ldap_fullname');
			$converted['ldap_email']		= $params->get('ldap_email');

			$converted['base_dn']			= $params->get('base_dn');

			$converted['use_search'] = ($params->get('auth_method') == 'search') ?
				true : false;

			if ($converted['use_search'])
			{
				// Build the search filter
				$tmp = trim($params->get('search_string'));
				$tmp = str_replace('[search]', '[username]', $tmp);
				$converted['user_qry'] = '(' . $tmp . ')';
			} else {
				// Build the direct user distinguished name
				$converted['user_qry'] = $params->get('users_dn');
			}

			return $converted;
		}

		// Dodgy detection for SHLdap parameters
		elseif ($params->get('user_qry') && $params->get('use_search'))
		{

			if ($convert === 'SHLdap')
			{
				// This appears to be converted already
				return $params->toArray();
			}

			// Convert all the parameters
			$converted['host'] 				= $params->get('host');
			$converted['port'] 				= $params->get('port');
			$converted['use_ldapV3'] 		= $params->get('use_v3');
			$converted['negotiate_tls']		= $params->get('negotiate_tls');
			$converted['no_referrals']		= (!$params->get('use_referrals'));

			$converted['username']			= $params->get('proxy_username');
			$converted['password']			= $params->get('proxy_password');

			$converted['ldap_uid'] 			= $params->get('ldap_uid');
			$converted['ldap_fullname']		= $params->get('ldap_fullname');
			$converted['ldap_email']		= $params->get('ldap_email');

			$converted['base_dn']			= $params->get('base_dn');

			$converted['auth_method'] = ($params->get('use_search')) ?
					'search' : 'bind';

			if ($converted['auth_method'] == 'search')
			{
				$tmp = trim($params->get('user_qry'));
				$tmp = str_replace('[username]', '[search]', $tmp);
				$converted['search_string'] = substr($tmp, 1, strlen($tmp) - 2);
			} else {
				$converted['users_dn'] = $params->get('user_qry');
			}

			return $converted;

		}

	}

	/**
	* Escape an input string based on the type of query (DN or Filter). This
	* method follows the RFC2254 guidelines.
	* Adapted from source: http://www.php.net/manual/en/function.ldap-search.php#90158
	*
	* @param   string   $str  Input string to escape.
	* @param   boolean  $dn   Set flag to true if escaping a distinguished name.
	*
	* @return  string  An escaped string.
	*
	* @since   1.0
	*/
	public static function escape($str, $dn = false)
	{
		// Characters to escpae depending whether if the dn flag is set
		$metaChars = $dn ? array(',', '=', '+', '<', '>', ';', '\\', '"', '#') :
			array('*', '(', ')', '\\', chr(0));

		$quotedMetaChars = array();
		foreach ($metaChars as $key => $value)
		{
			$quotedMetaChars[$key] = '\\' . str_pad(dechex(ord($value)), 2, '0');
		}

		return str_replace($metaChars, $quotedMetaChars, $str);
	}

	/**
	 * Escape the filter characters and build the filter with brackets
	 * using the operator specified.
	 *
	 * @param   array   $filters   An array of inner filters (i.e. array(uid=shaun, cn=uk)).
	 * @param   string  $operator  Set operator to carry out (null by default for no operator).
	 *
	 * @return  string  An escaped filter with filter operation.
	 *
	 * @since   1.0
	 */
	public static function buildFilter($filters, $operator = null)
	{
		$return = null;

		if (!count($filters))
		{
			return $return;
		}

		$string = null;
		foreach ($filters as $filter)
		{
			$filter = LdapHelper::escape($filter);
			$string .= '(' . $filter . ')';
		}

		$return = is_null($operator) ? $string : '(' . $operator . $string . ')';

		return $return;
	}

	/**
	 * Converts a dot notation IP address to net address (e.g. for Netware).
	 * Forked from the inbuilt Joomla LDAP (JLDAP 11.1) library.
	 *
	 * @param   string  $ip  An IP address to convert (e.g. xxx.xxx.xxx.xxx).
	 *
	 * @return  string  Net address.
	 *
	 * @since   1.0
	 */
	public static function ipToNetAddress($ip)
	{
		$parts = explode('.', $ip);
		$address = '1#';

		foreach ($parts as $int)
		{
			$tmp = dechex($int);
			if (strlen($tmp) != 2)
			{
				$tmp = '0' . $tmp;
			}
			$address .= '\\' . $tmp;
		}
		return $address;
	}


	// current - the current set of full ldap attributes
	// changes - array of changes to make
	// multiple - a boolean if this attribute in changes is multiple values
	// Return - Boolean to Success
	public static function makeChanges($dn, $current, $changes = array())
	{

		if(!count($changes)) {
			return false; // There is nothing to change
		}

		$deleteEntries 		= array();
		$addEntries 		= array();
		$replaceEntries		= array();

		foreach($changes as $key=>$value) {

			$return = 0;

			// Check this attribute for multiple values
			if(is_array($value)) {

				/* This is a multiple value attriute and to preserve
				 * order we must replace the whole thing if changes
				 * are required.
				 */
				$modification = false;
				$new = array();
				$count = 0;

				for($i=0; $i<count($value); $i++) {

					if($return = self::checkField($current, $key, $count, $value[$i])) {
						$modification = true;
					}

					if($return!=3 && $value[$i]) {
						$new[] = $value[$i]; //We don't want to save deletes
						$count++;
					}
				}

				if($modification) {
					$deleteEntries[$key] = array(); // We want to delete it first
					if(count($new)) {
						$addEntries[$key] = array_reverse($new); // Now lets re-add them
					}
				}


			} else {

				/* This is a single value attribute and we now need to
				 * determine if this needs to be ignored, added,
				 * modified or deleted.
				 */
				$return = self::checkField($current, $key, 0, $value);

				switch($return) {

					case 1:
						$replaceEntries[$key] = $value;
						break;

					case 2:
						$addEntries[$key] = $value;
						break;

					case 3:
						$deleteEntries[$key] = array();
						break;

				}
			}
		}

		/* We can now commit the changes to the
		 * LDAP server for this DN.
		 */
		$results 	= array();
		$ldap 		= JLDAP2::getInstance();

		if(count($deleteEntries)) {
			$results[] = $ldap->deleteAttributes($dn, $deleteEntries);
		}

		if(count($addEntries)) {
			$results[] = $ldap->addAttributes($dn, $addEntries);
		}

		if(count($replaceEntries)) {
			$results[] = $ldap->replaceAttributes($dn, $replaceEntries);
		}

		if(!in_array(false, $results, true)) {
			return true;
		}
	}

	// @RETURN: 0-same/ignore, 1-modify, 2-addition, 3-delete
	protected static function checkField($current, $key, $interval, $value)
	{

		// Check if the LDAP attribute exists
		if(array_key_exists($key, $current)) {

			if(isset($current[$key][$interval])) {
				if($current[$key][$interval] == $value) {
					return 0; // Same value - no need to update
				}
				if(is_null($value) || !$value) {
					return 3; // We don't want to include a blank or null value
				}
			}

			if(is_null($value) || !$value) {
				return 0; // We don't want to include a blank or null value
			}

			return 1;

		} else {
			if(!is_null($value) && $value) {
				return 2; // We need to create a new LDAP attribute
			} else {
				return 0; // We don't want to include a blank or null value
			}
		}
	}

	/**
	 * Calls any registered Ldap events associated with an event group.
	 *
	 * @param   string  $event  The event name.
	 * @param   array   $args   An array of arguments.
	 *
	 * @return  boolean  Result of all function calls.
	 *
	 * @since   2.0
	 */
	public static function triggerEvent($event, $args = null)
	{
		$result = SHFactory::getDispatcher('ldap')->trigger($event, $args);
		return(!in_array(false, $result, true));
	}

}
