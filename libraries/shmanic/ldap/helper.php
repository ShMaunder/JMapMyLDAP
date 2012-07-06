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

	const CONFIG_AUTO = 1;

	const CONFIG_SQL = 2;

	const CONFIG_FILE = 4;

	const CONFIG_PLUGIN = 8;

	/**
	 * Loads the correct Ldap configuration based on the record ID specified. Then uses
	 * this configuration to instantiate an LdapExtended client.
	 *
	 * @param   integer    $id      Configuration record ID (use 1 if only one record is present).
	 * @param   JRegistry  $config  Platform configuration.
	 * @param   string     $source  Parameter source such as CONFIG_AUTO, CONFIG_SQL, CONFIG_FILE or CONFIG_PLUGIN.
	 *
	 * @return  false|JRegistry  Registry of parameters for Ldap or False on error.
	 *
	 * @since   2.0
	 */
	public static function getParams($id, JRegistry $config, $source = self::CONFIG_AUTO)
	{
		// Process the correct parameter source
		if ($source === self::CONFIG_AUTO)
		{
			// Get the Ldap configuration source (e.g. sql | plugin | file)
			$source = (int) $config->get('ldap.config', self::CONFIG_SQL);
		}

		if ($source === self::CONFIG_SQL)
		{
			// Get the number of servers configured in the database
			$servers = (int) $config->get('ldap.servers', 0);

			// Get the database table using the sh_ldap_config as default
			$table = $config->get('ldap.table', '#__sh_ldap_config');

			if (!$servers > 0 || is_null($id))
			{
				// No Ldap servers are configured!
				return false;
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
				return false;
			}

			if (isset($results['params']))
			{
				// Decode the JSON string direct from DB to an array
				$params = new JRegistry;
				$params->loadString($results['params']);
				return $params;
			}
		}
		elseif ($source === self::CONFIG_PLUGIN)
		{
			// TODO: implement

			if ($plugin = JPluginHelper::getPlugin('authentication', $id))
			{
				// Get the authentication LDAP plug-in parameters
				$params = new JRegistry;
				$params->loadString($plugin->params);

				// We may have to convert if using the inbuilt JLDAP parameters
				//return self::convert($params, 'SHLdap');
			}
		}
		elseif ($source === self::CONFIG_FILE)
		{
			// TODO: implement

		}
		else
		{
			// Invalid source
			return false;
		}

		return false;
	}

	/**
	 * Find the correct Ldap parameters based on the authorised and configuration
	 * specified. If found then return the successful Ldap object.
	 *
	 * Note: you can use SHLdap->getLastUserDN() for the user DN instead of rechecking again.
	 *
	 * @param   Array      $authorised  Optional authorisation/authentication options (authenticate, username, password).
	 * @param   JRegistry  $config      Optional override for platform configuration.
	 *
	 * @return  false|SHLdap  An Ldap object on successful authorisation or False on error.
	 *
	 * @since   2.0
	 */
	public static function getClient(array $authorised = array(), JRegistry $config = null)
	{
		// Get the optional authentication/authorisation options
		$authenticate = JArrayHelper::getValue($authorised, 'authenticate', false);
		$username = JArrayHelper::getValue($authorised, 'username', null);
		$password = JArrayHelper::getValue($authorised, 'password', null);

		// Get the Ldap configuration from the factory if required
		$config = is_null($config) ? SHFactory::getConfig() : $config;

		// Get the source from the config
		$source = (int) $config->get('ldap.config', self::CONFIG_SQL);

		/*
		 * SQL configuration may have multiple configurations, therefore
		 * we must loop round each until we can find the username if one
		 * has been specified otherwise, we just return the first known
		 * configuration.
		 */
		if ($source === self::CONFIG_SQL)
		{
			// Get all the Ldap configuration from the database
			$servers = self::getParamsIDs();

			if (!is_array($servers))
			{
				// No Ldap configuration host results found
				SHLog::add(JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12603'), 12603, JLog::ERROR, 'ldap');
				return false;
			}

			// Loop around all the Ldap configurations found
			foreach (array_keys($servers) as $id)
			{
				// Get the parameters for this Ldap configuration
				if ($params = self::getParams($id, $config, $source))
				{
					if ($ldap = self::authenticateLdap($params, $authenticate, $username, $password))
					{
						// This is the correct configuration so return the new client
						return $ldap;
					}
				}
			}

		}
		else
		{
			// We just get the Ldap parameters assuming there is only one configuration
			if ($params = self::getParams(1, $config, $source))
			{
				if ($ldap = self::authenticateLdap($params, $authenticate, $username, $password))
				{
					// This is the correct configuration so return the new client
					return $ldap;
				}
			}
		}

		return false;
	}

	/**
	 * Attempts to Ldap authorise/authenticate with the parameters specified.
	 *
	 * @param   mixed    $ldap          Either a JRegistry of parameters OR a SHLdap object.
	 * @param   boolean  $authenticate  Authenticate the username and password supplied with the Ldap object.
	 * @param   string   $username      Authorisation/authentication username.
	 * @param   string   $password      Authentication password.
	 *
	 * @return  false|SHLdap  An Ldap object on successful authorisation or False on error.
	 *
	 * @since   2.0
	 */
	public static function authenticateLdap($ldap, $authenticate, $username, $password)
	{
		try
		{
			// Check if we have an already instantiated Ldap object
			if (!$ldap instanceof SHLdap)
			{
				// Attempt to instantiate an Ldap client object with the configuration
				if (!$ldap = new SHLdap($ldap))
				{
					return false;
				}
			}

			// Start the LDAP connection procedure
			if ($ldap->connect() !== true)
			{
				// Failed to connect
				$exception = $ldap->getError(null, false);
				if ($exception instanceof SHLdapException)
				{
					// Processes an exception log
					SHLog::add($exception, 12605, JLog::ERROR, 'ldap');
				}
				else
				{
					// Process a error log
					SHLog::add(JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12605'), 12605, JLog::ERROR, 'ldap');
				}

				// Unset this Ldap client and try the next configuration
				unset($ldap);
				return false;
			}

			/*
			 * Check if a username has been specified. If not then assume this is the correct
			 * Ldap configuration and return it.
			 */
			if (!$authenticate && is_null($username))
			{
				return $ldap;
			}

			/* We will now get the authenticated user's dn.
			 * In this method we are also going to test the
			 * dn against the password. Therefore, if any dn
			 * is returned, it is a successfully authenticated
			 * user.
			 */
			if (!$dn = $ldap->getUserDN($username, $password, $authenticate))
			{
				// Failed to get users Ldap distinguished name
				$exception = $ldap->getError(null, false);
				if ($exception instanceof SHLdapException)
				{
					// Processes an exception log
					SHLog::add($exception, 12606, JLog::ERROR, 'ldap');
				}
				else
				{
					// Process a error log
					SHLog::add(JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12606'), 12606, JLog::ERROR, 'ldap');
				}

				// Unset this Ldap client and try the next configuration
				$ldap->close();
				unset($ldap);
				return false;
			}

			// Successfully authenticated and retrieved User DN.
			return $ldap;
		}
		catch (Exception $e)
		{
			unset($ldap);
			SHLog::add(JText::_('Something went very wrong with the Ldap client'), 0, JLog::ERROR, 'ldap');
		}

		return false;
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
	public static function getParamsIDFromName($name)
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
	public static function getParamsIDs()
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
			JFactory::getUser($user)->setParam('authtype', 'LDAP');
		}
		elseif ($user instanceof JUser)
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
			}
			else
			{
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
			}
			else
			{
				$converted['users_dn'] = $params->get('user_qry');
			}

			return $converted;

		}

	}

	/**
	 * Gets the user attributes from LDAP. This method will in fire the
	 * individual LDAP plugin onLdapBeforeRead and onLdapAfterRead methods.
	 *
	 * Note: this only needs to be used when authentication/authorisation through
	 * the SHLdap authentication plugin hasn't been fired.
	 *
	 * @param   string  $username  Specify username to return results on.
	 *
	 * @return  array|false  An array of attributes or False on error.
	 *
	 * @since   2.0
	 */
	public static function getUserDetails($username)
	{
		if ($ldap = self::getClient(array('username' => $username)))
		{
			$dn = $ldap->getLastUserDN();

			$attributes = $ldap->getUserDetails($dn);

			$ldap->close();

			return $attributes;
		}

		return false;
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
			$filter = self::escape($filter);
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


			}
			else
			{

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
