<?php
/**
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @package     Shmanic
 * @subpackage  Ldap
 * 
 * @copyright	Copyright (C) 2011 Shaun Maunder. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('shmanic.client.jldap2');
jimport('shmanic.ldap.event');
jimport('shmanic.log.ldaphelper');

/**
 * Holds the parameter settings for the jmapmyldap class.
 *
 * @package		Shmanic
 * @subpackage	Ldap
 * @since		1.0
 */

/**
 * A Ldap group mapping class to initiate and commit group mappings.
 *
 * @package		Shmanic
 * @subpackage	Ldap
 * @since		1.0
 */
class LdapUserHelper extends JObject 
{
	/**
	 * This method returns a user object. If options['autoregister'] is true, 
	 * and if the user doesn't exist yet then it'll be created.
	 * 
	 * Dear Joomla, can you please put this into a library for people to use.
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
	
	/**
	 * Save the attributes of the specified JUser to the database. This
	 * method has been adapted from the JUser::save() method to bypass
	 * ACL checks for super users.
	 *
	 * @param  JUser  &$instance  The JUser object to save
	 *
	 * @return  mixed  True on success, otherwise either false or Exception on error
	 * @since   1.0
	 */
	public static function saveUser(&$instance) 
	{
		//we have to have a group if they're none
		$table			= $instance->getTable();
		$table->bind($instance->getProperties());

		// Check and store the object.
		if (!$table->check()) {
			return false;
		}
			
		$my = JFactory::getUser();

		//we aren't allowed to create new users return
		if(empty($instance->id)) {
			return true;
		}
			
		// Store the user data in the database
		if (!$table->store()) {
			throw new Exception($table->getError());
		}

		// Set the id for the JUser object in case we created a new user.
		if (empty($instance->id)) {
			$instance->id = $table->get('id');
		}

		if ($my->id == $table->id) {
			$registry = new JRegistry;
			$registry->loadString($table->params);
			$my->setParameters($registry);
		}

		return true;
	}
	
	// get and return the user attributes (array) from LDAP
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
class LdapHelper extends JObject
{
	
	/* get a connection and bind if specified */
	public static function getConnection($bind = false, $username = null, $password = null)
	{
		
		if(!$params = self::getParams()) {
			return false;
		}

		$ldap = JLDAP2::getInstance($params);
		
		if($ldap->isConnected() || $ldap->connect()) {
			
			if($bind) {
				
				if(is_null($username)) {
					$username = JArrayHelper::getValue($params, 'username');
					$password = JArrayHelper::getValue($params, 'password');
				}
				
				if(!$ldap->bind($username, $password)) return false;
				
			}
			
			return $ldap;
		}
	}
	
	/* get the LDAP plug-in parameters */
	public static function getParams($auth = null)
	{
		
		if(is_null($auth)) {
			//$auth = self::$auth_plugin;
			self::getGlobalParam('auth_plugin', 'jmapmyldap');
		}
		
		/*if($plugin = JPluginHelper::getPlugin('authentication', $options['authplugin'])) {*/
		if($plugin = JPluginHelper::getPlugin('authentication', $auth)) {
				
			$params = new JRegistry;
			$params->loadString($plugin->params);
				
			return self::convert($params, 'JLDAP2');
				
		}

	}
	
	
	// $params - Jregistry or Jobject of current ldap parameters
	// $convert - convert to either jldap2 or jldap
	// return - array of converted parameters
	public static function convert($params, $convert = 'JLDAP2')
	{
		/*
		 * Attempt to convert inbuilt JLDAP library parameters
		* to the JLDAP2 parameters for backward compatibility.
		*/
	
		$converted = array();
	
		// Detection for inbuilt JLDAP
		if($params->get('auth_method') && $params->get('search_string') || $params->get('users_dn')) {
				
			if($convert=='JLDAP') {
				return $params->toArray();
			}
				
			$converted['host'] 				= $params->get('host');
			$converted['port'] 				= $params->get('port');
			$converted['use_ldapV3'] 		= $params->get('use_ldapV3');
			$converted['negotiate_tls']		= $params->get('negotiate_tls');
			$converted['follow_referrals']	= $params->get('no_referrals');
				
			$converted['connect_username']	= $params->get('username');
			$converted['connect_password']	= $params->get('password');
				
			$converted['ldap_uid'] 			= $params->get('ldap_uid');
			$converted['ldap_fullname']		= $params->get('ldap_fullname');
			$converted['ldap_email']		= $params->get('ldap_email');
				
			$converted['base_dn']			= $params->get('base_dn');
				
			$converted['use_search'] = ($params->get('auth_method') == 'search') ?
			true : false;
	
			if($converted['use_search']) {
				$tmp = trim($params->get('search_string'));
				$tmp = str_replace('[search]', '[username]', $tmp);
				$converted['user_qry'] = '(' . $tmp . ')';
			} else {
				$converted['user_qry'] = $params->get('users_dn');
			}
	
			return $converted;
			
			// Detection for JLDAP2
		} elseif($params->get('user_qry') && $params->get('use_search')) {
				
			if($convert=='JLDAP2') {
				return $params->toArray();
			}
				
			$converted['host'] 				= $params->get('host');
			$converted['port'] 				= $params->get('port');
			$converted['use_ldapV3'] 		= $params->get('use_ldapV3');
			$converted['negotiate_tls']		= $params->get('negotiate_tls');
			$converted['no_referrals']		= $params->get('follow_referrals');
				
			$converted['username']			= $params->get('connect_username');
			$converted['password']			= $params->get('connect_password');
				
			$converted['ldap_uid'] 			= $params->get('ldap_uid');
			$converted['ldap_fullname']		= $params->get('ldap_fullname');
			$converted['ldap_email']		= $params->get('ldap_email');
				
			$converted['base_dn']			= $params->get('base_dn');
				
			$converted['auth_method'] = ($params->get('use_search')) ?
					'search' : 'bind';
				
			if($converted['auth_method'] == 'search') {
				$tmp = trim($params->get('user_qry'));
				$tmp = str_replace('[username]', '[search]', $tmp);
				$converted['search_string'] = substr($tmp, 1, strlen($tmp)-2);
			} else {
				$converted['users_dn'] = $params->get('user_qry');
			}
				
			return $converted;
			
		}
	
		//JLDAP libraryArray ( [host] => DC [port] => 389 [use_ldapV3] => 1 [negotiate_tls] => 0 [no_referrals] => 0 [auth_method] => search [base_dn] => DC=HOME,DC=LOCAL [search_string] => sAMAccountName=[search] [users_dn] => [username] => shaun@HOME.LOCAL [password] => password [ldap_fullname] => nam [ldap_email] => mail [ldap_uid] => sAMAccountName )
		//JLDAP2 libraryArray ( [use_ldapV3] => 1 [negotiate_tls] => 0 [follow_referrals] => 0 [host] => DC [port] => 389 [connect_username] => shaun@HOME.LOCAL [connect_password] => password [use_search] => 1 [base_dn] => DC=HOME,DC=LOCAL [user_qry] => (sAMAccountName=[username]) [ldap_uid] => sAMAccountName [ldap_fullname] => name [ldap_email] => mail )
	
	}
	
	/**
	* Escape characters based on the type of query (DN or Filter). This
	* method follows the RFC2254 guidelines.
	* Adapted from source: http://www.php.net/manual/en/function.ldap-search.php#90158
	*
	* @param  string   $inn   Input string to escape
	* @param  boolean  $isDn  Set the type of query; true for DN; false for filter (default false)
	*
	* @return  string  An escaped string
	* @since   1.0
	*/
	public static function escape($inn, $isDn = false)
	{
		$metaChars = $isDn ? array(',','=', '+', '<','>',';', '\\', '"', '#') :
		array('*', '(', ')', '\\', chr(0));
	
		$quotedMetaChars = array();
		foreach ($metaChars as $key => $value) {
			$quotedMetaChars[$key] = '\\'.str_pad(dechex(ord($value)), 2, '0');
		}
	
		return str_replace($metaChars, $quotedMetaChars, $inn);
	
	}
	
	/**
	 * Escape the filter characters and build the filter with brackets
	 * using the operator specified.
	 *
	 * @param  array   $filters   An array of inner filters (i.e. array(uid=shaun, cn=uk))
	 * @param  string  $operator  Set operator to carry out (null by default for no operator)
	 *
	 * @return  string  An escaped filter with filter operation
	 * @since   1.0
	 */
	public static function buildFilter($filters, $operator = null)
	{
		$return = null;
	
		if(!count($filters)) return $return;
	
		$string = null;
		foreach($filters as $filter) {
			$filter = JLDAPHelper::escape($filter);
			$string .= '(' . $filter . ')';
		}
	
		$return = is_null($operator) ? $string : '(' . $operator . $string . ')';
	
		return $return;
	}
	
	/**
	 * Converts a dot notation IP address to net address (e.g. for Netware).
	 * Taken from the inbuilt Joomla LDAP library.
	 *
	 * @param   string  $ip  An IP address to convert (e.g. xxx.xxx.xxx.xxx)
	 *
	 * @return  string  Net address
	 * @since   1.0
	 */
	public static function ipToNetAddress($ip)
	{
		$parts = explode('.', $ip);
		$address = '1#';
	
		foreach ($parts as $int) {
			$tmp = dechex($int);
			if (strlen($tmp) != 2) {
				$tmp = '0' . $tmp;
			}
			$address .= '\\' . $tmp;
		}
		return $address;
	}
	
	public static function getGlobalParam($field, $default = null)
	{
		
		$params = JComponentHelper::getParams('com_ldapadmin');
		
		
		return($params->get($field, $default));
		
		
	}

}

class LdapEventHelper extends JObject
{
	
	/*function __construct()
	{
		$lang = JFactory::getLanguage();
		$lang->load('lib_ldapcore', JPATH_SITE); //for errors
		
		// Load in plugins by type
		//$this->load('ldap');
		
		parent::__construct();
		
	}*/
	
	/*public static function getInstance()
	{
		static $instance;
		if(!is_object($instance)) {
			$instance = new self;
		}
		
		return $instance;
	}*/
	
	public static function loadEvents($dispatcher)
	{
		// Initialise logging
		JLogLdapHelper::addLoggers();
		
		// Creates a new instance of events binding it to the dispatcher
		return LdapEvent::getInstance($dispatcher);
	}
	
	public static function isLdapSession() 
	{
		$user 		= JFactory::getUser();
		$session 	= JFactory::getSession();

		// Return true only when this is a ldap session
		if($user->get('id') !=0 || $user->get('tmp_user')) {
			if($session->get('authtype') == 'LDAP') {
				return true;
			}
		}
		
		return false;
		
	}
	
	public static function loadPlugins($type = 'ldap')
	{
		$loaded = JPluginHelper::importPlugin($type);
		return $loaded;
	}
		
	/**
	 * Calls all handlers associated with an event group.
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

		$dispatcher = JDispatcher::getInstance();
		$result = $dispatcher->trigger($event, $args);
		
		return(!in_array(false, $result, true));

	}
	

	
	
}