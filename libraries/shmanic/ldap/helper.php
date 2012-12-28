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
 * An LDAP helper class.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap
 * @since       2.0
 */
abstract class SHLdapHelper
{
	/**
	 * Auto configuration.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	const CONFIG_AUTO = 1;

	/**
	 * SQL configuration.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	const CONFIG_SQL = 2;

	/**
	 * File configuration.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	const CONFIG_FILE = 3;

	/**
	 * Plugin configuration.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	const CONFIG_PLUGIN = 4;

	/**
	 * Loads the correct Ldap configuration based on the record ID specified. Then uses
	 * this configuration to instantiate an SHLdap client.
	 *
	 * @param   integer|string  $id        Configuration record ID. If blank, then returns all configs.
	 * @param   JRegistry       $registry  Platform configuration.
	 * @param   string          $source    Parameter source such as CONFIG_AUTO, CONFIG_SQL, CONFIG_FILE or CONFIG_PLUGIN.
	 *
	 * @return  false|JRegistry  Registry of parameters for Ldap or False on error.
	 *
	 * @since   2.0
	 */
	public static function getConfig($id = null, JRegistry $registry = null, $source = self::CONFIG_AUTO)
	{
		// Get the platform registry config from the factory if required
		$registry = is_null($registry) ? SHFactory::getConfig() : $registry;

		// If automatic configuration is specified; get the pre-configured source form registry
		if ($source === self::CONFIG_AUTO)
		{
			// Get the Ldap configuration source (e.g. sql | plugin | file)
			$source = (int) $registry->get('ldap.config', self::CONFIG_SQL);
		}

		if ($source === self::CONFIG_SQL)
		{
			// Get the database table using sh_ldap_config as default
			$table = $registry->get('ldap.table', '#__sh_ldap_config');

			// Get the global JDatabase object
			$db = JFactory::getDbo();

			$query = $db->getQuery(true);

			// Check if we should return an array of configs
			if (empty($id))
			{
				// Get all the enabled Ldap configurations from SQL
				$query->select('params')
					->from($query->qn($table))
					->where('enabled >= 1')
					->order('ordering');

				// Execute the query
				if ($params = $db->setQuery($query)->loadAssocList(null, 'params'))
				{
					$configs = array();

					// Push each found set of params into a registry
					foreach ($params as $param)
					{
						$newConfig = new JRegistry;
						$newConfig->loadString($param, 'JSON');

						// Push the Ldap config onto the return array
						$configs[] = $newConfig;
					}

					return $configs;
				}
				else
				{
					// No results found
					SHLog::add(JText::_('LIB_SHLDAPHELPER_ERR_10604'), 10604, JLog::ERROR, 'ldap');
				}
			}
			// Check if we need to get the config based on an ID
			elseif (is_numeric($id))
			{
				// Get the enabled configuration of the specified ID
				$query->select('params')
					->from($query->qn($table))
					->where('enabled >= 1')
					->where($query->qn('ldap_id') . '=' . $query->q((int) $id));

				// Execute the query
				if ($param = $db->setQuery($query)->loadResult())
				{
					$config = new JRegistry;
					$config->loadString($param, 'JSON');

					// Return our configuration result
					return $config;
				}
				else
				{
					// No result found
					SHLog::add(JText::sprintf('LIB_SHLDAPHELPER_ERR_10605', $id), 10605, JLog::ERROR, 'ldap');
				}

			}
			// Assume this is based on name (string)
			else
			{
				// Get the enabled configuration of the specified name
				$query->select('params')
					->from($query->qn($table))
					->where('enabled >= 1')
					->where($query->qn('name') . '=' . $query->q((string) $id));

				// Execute the query
				if ($param = $db->setQuery($query)->loadResult())
				{
					$config = new JRegistry;
					$config->loadString($param, 'JSON');

					// Return our configuration result
					return $config;
				}
				else
				{
					// No result found
					SHLog::add(JText::sprintf('LIB_SHLDAPHELPER_ERR_10606', $id), 10606, JLog::ERROR, 'ldap');
				}
			}

		}
		elseif ($source === self::CONFIG_PLUGIN)
		{
			// Grab the plug-in name from the registry
			$name = empty($id) ? $registry->get('ldap.plugin', 'ldap') : $id;

			if ($plugin = JPluginHelper::getPlugin('authentication', $name))
			{
				// Get the authentication LDAP plug-in parameters
				$params = new JRegistry;
				$params->loadString($plugin->params);

				/*
				 * We may have to convert the parameters from the plugin so they can be
				 * accepted into the SHLdap library. This is normally if we are using the
				 * JLDAP parameters.
				 */
				$converted = self::convertConfig($params, 'SHLdap');

				// Reload the converted parameters into a registry before returning
				$config = new JRegistry;
				$config->loadArray($converted);

				return $config;
			}
			else
			{
				// Invalid plugin
				SHLog::add(JText::sprintf('LIB_SHLDAPHELPER_ERR_10603', $name), 10603, JLog::ERROR, 'ldap');
			}
		}
		elseif ($source === self::CONFIG_FILE)
		{
			// Grab the plug-in name from the registry
			$file = $registry->get('ldap.file', JPATH_CONFIGURATION . '/ldap.php');

			// Check the file is valid and exists
			if (!empty($file) && file_exists($file))
			{
				// Include the file
				include_once $file;

				// Check if we need to treat the ID as a position for later
				$position = 0;
				if (is_numeric($id))
				{
					$position = (int) $id;
					$id = null;
				}

				// Generate the namesapce
				$namespaces = is_null($id) ? $registry->get('ldap.namespaces', '') : $id;

				// Split multiple namespaces
				$namespaces = explode(';', $namespaces);

				// Check if we are dealing with more than one namespace
				if (count($namespaces) === 1)
				{
					// Only one namespace; attempt to create it then return it
					if ($config = self::createFileConfig($namespaces[0]))
					{
						return $config;
					}
					else
					{
						// Unable to load the file namespace specified
						SHLog::add(JText::sprintf('LIB_SHLDAPHELPER_ERR_10607', $namespaces[0], $file), 10607, JLog::ERROR, 'ldap');
					}
				}
				// Check if we need a specific configuration ID (this would be from domain logins)
				elseif ($position > 0)
				{
					// Check it exists at the point
					if (isset($namespaces[$position]))
					{
						// Attempt to create it then return it
						if ($config = self::createFileConfig($namespaces[$position]))
						{
							return $config;
						}
					}
					else
					{
						// Unable to load the file namespace specified
						SHLog::add(JText::sprintf('LIB_SHLDAPHELPER_ERR_10609', $position, $file), 10609, JLog::ERROR, 'ldap');
					}
				}
				else
				{
					$configs = array();

					// Multiple namespaces so loop around each and attempt to create one
					foreach ($namespaces as $namespace)
					{
						// Create the namespace and add it to the configs array if it exists
						if ($config = self::createFileConfig($namespace))
						{
							$configs[] = $config;
						}
					}

					// Check we have some configs
					if (count($configs))
					{
						// Return the multiple configs
						return $configs;
					}
					else
					{
						// No file configurations found
						SHLog::add(JText::sprintf('LIB_SHLDAPHELPER_ERR_10608', $file), 10608, JLog::ERROR, 'ldap');
					}
				}

			}
			else
			{
				// Invalid file
				SHLog::add(JText::sprintf('LIB_SHLDAPHELPER_ERR_10602', $file), 10602, JLog::ERROR, 'ldap');
			}
		}
		else
		{
			// Invalid source
			SHLog::add(JText::_('LIB_SHLDAPHELPER_ERR_10601'), 10601, JLog::ERROR, 'ldap');
		}

		// Failed to find a valid config
		return false;
	}

	/**
	 * Creates and returns an object to the specified namespace class.
	 * This is used for LDAP configuration files.
	 *
	 * @param   string  $namespace  Name of namespace.
	 *
	 * @return  object  Object to configuration.
	 *
	 * @since   2.0
	 */
	protected static function createFileConfig($namespace)
	{
		// Sanitize the namespace
		$namespace = ucfirst((string) preg_replace('/[^A-Z_]/i', '', $namespace));

		// Build the config name
		$name = 'SHLdapConfig' . $namespace;

		// Handle the PHP configuration type.
		if (class_exists($name))
		{
			$config = new JRegistry;

			// Create the JConfig object
			$params = new $name;

			// Load the configuration values into the registry
			$config->loadObject($params);

			return $config;
		}

		// Failed to create config file
		return false;
	}

	/**
	 * Attempts to convert the Ldap configuration parameters to a specified
	 * library parameters. This is to aid backward compatibility between the
	 * two libraries. This method is not reliable and should be tested
	 * before using in a live environment.
	 *
	 * @param   array|JRegistry  $parameters  Parameters for conversion.
	 * @param   string           $convert     Library name conversion.
	 *
	 * @return  Array  Array of converted parameters
	 *
	 * @since   2.0
	 */
	public static function convertConfig($parameters, $convert = 'SHLdap')
	{
		$converted = array();

		$params = $parameters;

		// Convert the parameters to a registry if its an array
		if (is_array($parameters))
		{
			$param = new JRegistry;
			$param->loadArray($parameters);
		}

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
			$converted['use_referrals']		= $params->get('no_referrals');

			$converted['proxy_username']	= $params->get('username');
			$converted['proxy_password']	= $params->get('password');
			$converted['proxy_encryption']	= false;

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
			$converted['no_referrals']		= $params->get('use_referrals');

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
	 * Returns the ID of the SQL record for the specified Ldap name.
	 *
	 * @param   string  $name  Ldap name (i.e. domain).
	 *
	 * @return  integer  Record ID.
	 *
	 * @since   2.0
	 */
	public static function getConfigIDFromName($name)
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
	 * @param   JRegistry  $registry  Platform configuration.
	 *
	 * @return  Array  Array of configured IDs
	 *
	 * @since   2.0
	 */
	public static function getConfigIDs($registry = null)
	{
		// Get the Ldap configuration from the factory
		$registry = (is_null($registry)) ? SHFactory::getConfig() : $registry;

		// Get the Ldap configuration source (e.g. sql | plugin | file)
		$source = (int) $registry->get('ldap.config', self::CONFIG_SQL);

		if ($source === self::CONFIG_SQL)
		{
			// Get the database table using the sh_ldap_config as default
			$table = $registry->get('ldap.table', '#__sh_ldap_config');

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
		elseif ($source === self::CONFIG_FILE)
		{
			// Generate the namesapce
			if ($namespaces = $registry->get('ldap.namespaces', false))
			{
				// Split multiple namespaces
				$namespaces = explode(';', $namespaces);

				return $namespaces;
			}
		}
	}

	/**
	 * Returns if the current or specified user was authenticated
	 * via LDAP.
	 *
	 * @param   integer  $user  Optional user id (if null then uses current user).
	 *
	 * @return  boolean  True if user is Ldap authenticated or False otherwise.
	 *
	 * @since   2.0
	 */
	public static function isUserLdap($user = null)
	{
		if (is_null($user) || is_numeric($user))
		{
			// The input variable indicates we must load the user object
			if (JFactory::getUser($user)->getParam('authtype') == 'LDAP')
			{
				// This user has the LDAP auth type
				return true;
			}
		}
		elseif ($user instanceof JUser)
		{
			// Direct access of the object
			if ($user->getParam('authtype') == 'LDAP')
			{
				// This user has the LDAP auth type
				return true;
			}
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
		if ($ldap = SHLdap::getInstance(null, array('username' => $username)))
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
