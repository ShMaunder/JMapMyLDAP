<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
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
	 * LDAP configuration file prefix.
	 *
	 * @var    string
	 * @since  2.1
	 */
	const CONFIG_PREFIX = 'SHLdapConfig';

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
	 * @throws  InvalidArgumentException
	 * @throws  RuntimeException
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
				$query->select($db->quoteName('name'))
					->select($db->quoteName('params'))
					->select($db->quoteName('user_params'))
					->select($db->quoteName('group_params'))
					->from($db->quoteName($table))
					->where($db->quoteName('enabled') . ' >= ' . $db->quote('1'))
					->order($db->quoteName('ordering'));

				// Execute the query
				if ($rows = $db->setQuery($query)->loadAssocList())
				{
					$configs = array();

					// Push each found set of params into a registry
					foreach ($rows as $row)
					{
						$newConfig = new JRegistry;
						$newConfig->loadString($row['params'], 'JSON');

						// Inject the domain ID into the config
						$newConfig->set('domain', $row['name']);

						// Inject the user_params and group_params
						$newConfig->set('user_params', $row['user_params']);
						$newConfig->set('group_params', $row['group_params']);

						// Push the Ldap config onto the return array
						$configs[] = $newConfig;
					}

					return $configs;
				}
				else
				{
					// No results found
					throw new InvalidArgumentException(JText::_('LIB_SHLDAPHELPER_ERR_10604'), 10604);
				}
			}

			// Check if we need to get the config based on an ID
			elseif (is_numeric($id))
			{
				// Get the enabled configuration of the specified ID
				$query->select($db->quoteName('name'))
					->select($db->quoteName('params'))
					->select($db->quoteName('user_params'))
					->select($db->quoteName('group_params'))
					->from($db->quoteName($table))
					->where($db->quoteName('enabled') . ' >= ' . $db->quote('1'))
					->where($db->quoteName('id') . ' = ' . $db->quote((int) $id));

				// Execute the query
				if ($row = $db->setQuery($query)->loadAssoc())
				{
					$config = new JRegistry;
					$config->loadString($row['params'], 'JSON');

					// Inject the domain ID into the config
					$config->set('domain', $row['name']);

					// Inject the user_params and group_params
					$config->set('user_params', $row['user_params']);
					$config->set('group_params', $row['group_params']);

					// Return our configuration result
					return $config;
				}
				else
				{
					// No result found
					throw new InvalidArgumentException(JText::sprintf('LIB_SHLDAPHELPER_ERR_10605', $id), 10605);
				}
			}

			// Assume this is based on name (string)
			else
			{
				// Get the enabled configuration of the specified name
				$query->select($db->quoteName('params'))
					->select($db->quoteName('user_params'))
					->select($db->quoteName('group_params'))
					->from($db->quoteName($table))
					->where($db->quoteName('enabled') . ' >= ' . $db->quote('1'))
					->where($db->quoteName('name') . ' = ' . $db->quote((string) $id));

				// Execute the query
				if ($row = $db->setQuery($query)->loadAssoc())
				{
					$config = new JRegistry;
					$config->loadString($row['params'], 'JSON');

					// Inject the domain ID into the config
					$config->set('domain', (string) $id);

					// Inject the user_params and group_params
					$config->set('user_params', $row['user_params']);
					$config->set('group_params', $row['group_params']);

					// Return our configuration result
					return $config;
				}
				else
				{
					// No result found
					throw new InvalidArgumentException(JText::sprintf('LIB_SHLDAPHELPER_ERR_10606', $id), 10606);
				}
			}
		}
		// WARNING: pulling attributes from a plugin is deprecated since 2.0 and is now broken
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
				throw new InvalidArgumentException(JText::sprintf('LIB_SHLDAPHELPER_ERR_10603', $name), 10603);
			}
		}
		elseif ($source === self::CONFIG_FILE)
		{
			// Define some default variables
			$namespace = '';
			$position = 0;
			$fileInc = false;

			// Grab the LDAP configuration file path from the registry and include it
			if ($file = $registry->get('ldap.file', JPATH_CONFIGURATION . '/ldap.php'))
			{
				$fileInc = @include_once $file;
			}

			// Lets find all classes in the LDAP configuration file
			$classes = array_values(preg_grep('/(' . self::CONFIG_PREFIX . '){1}\w*/i', get_declared_classes()));

			if (empty($classes))
			{
				$fileInc = ($fileInc) ? JText::_('LIB_SHLDAPHELPER_INFO_10624') : JText::_('LIB_SHLDAPHELPER_INFO_10625');

				// Either file not lopaded or file has no valid classes
				throw new RuntimeException(
					JText::sprintf('LIB_SHLDAPHELPER_ERR_10623', $id, $fileInc),
					10623
				);
			}

			$namespaces = $classes;

			// Retrieve the namespaces from the classes
			foreach ($namespaces as &$namespace)
			{
				$namespace = strtolower(str_ireplace(self::CONFIG_PREFIX, '', $namespace));
			}

			if (is_null($id))
			{
				if (count($namespaces) === 1)
				{
					// We will use a single namespace here
					$nsKeys = array(0);
				}
				elseif (count($namespaces) > 1)
				{
					// We will return an array of configs
					$nsKeys = array_keys($namespaces);
				}
			}
			else
			{
				if (is_numeric($id))
				{
					// Need to treat the ID as a position - check if it exists
					if (isset($namespaces[$id]))
					{
						$nsKeys = array($id);
					}
					else
					{
						// Unable to load the file namespace specified
						throw new InvalidArgumentException(JText::sprintf('LIB_SHLDAPHELPER_ERR_10609', $id), 10609);
					}
				}
				elseif (is_string($id))
				{
					$key = array_search(strtolower($id), $namespaces, false);

					if ($key !== false)
					{
						$nsKeys = array($key);
					}
					else
					{
						// Unable to load the file namespace specified
						throw new InvalidArgumentException(JText::sprintf('LIB_SHLDAPHELPER_ERR_10609', $id), 10609);
					}
				}
				else
				{
					// Invalid id argument
					throw new InvalidArgumentException(JText::_('LIB_SHLDAPHELPER_ERR_10610'), 10610);
				}
			}

			$configs = array();

			// We need to instantiate the config classes from the file and store them in the configs
			foreach ($nsKeys as $nsKey)
			{
				$class = $classes[$nsKey];

				$config = new JRegistry;

				// Create the JConfig object
				$params = new $class;

				// Load the configuration values into the registry
				$config->loadObject($params);

				// Inject the domain ID into the config
				$config->def('domain', empty($namespaces[$nsKey]) ? 'default' : $namespaces[$nsKey]);

				$hostParams = array();
				$userParams = array();
				$groupParams = array();

				foreach ($config->toArray() as $key => $value)
				{
					if (substr($key, 0, 5) == 'user_')
					{
						$userParams[$key] = $value;
					}
					elseif (substr($key, 0, 6) == 'group_')
					{
						$groupParams[$key] = $value;
					}
					else
					{
						$hostParams[$key] = $value;
					}
				}

				$config = new JRegistry($hostParams);

				// Inject the user_params and group_params
				$config->set('user_params', json_encode($userParams));
				$config->set('group_params', json_encode($groupParams));

				if (isset($userParams['user_qry']))
				{
					// Backwards compatibility with 2.0
					$config->set('user_qry', $userParams['user_qry']);
				}

				$configs[] = $config;
			}

			// We only return an array when the ID is null
			return (!is_null($id)) ? $configs[0] : $configs;
		}
		else
		{
			// Invalid source
			throw new InvalidArgumentException(JText::_('LIB_SHLDAPHELPER_ERR_10601'), 10601);
		}

		// Failed to find a valid config
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
	 * @deprecated  Due to the unreliability of the converted config, it really should not get used.
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

			if ($params->get('auth_method') == 'search')
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
		elseif ($params->get('user_qry'))
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

			$converted['auth_method'] = (preg_match('/(?<!\S)[\(]([\S]+)[\)](?!\S)/', $params->get('user_qry'))) ?
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
		$query->select($db->quoteName('id'))
			->from($db->quoteName($table))
			->where($db->quoteName('enabled') . ' >= ' . $db->quote('1'))
			->where($db->quoteName('name') . ' = ' . $db->quote($name));

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
			$query->select($db->quoteName('id'))
				->select($db->quoteName('name'))
				->from($db->quoteName($table))
				->where($db->quoteName('enabled') . ' >= ' . $db->quote('1'));

			$db->setQuery($query);

			// Execute the query
			$results = $db->loadAssocList('id', 'name');

			return $results;
		}
		elseif ($source === self::CONFIG_FILE)
		{
			// Grab the LDAP configuration file path from the registry and include it
			if ($file = $registry->get('ldap.file', JPATH_CONFIGURATION . '/ldap.php'))
			{
				@include_once $file;
			}

			// Lets find all classes in the LDAP configuration file
			$classes = array_values(preg_grep('/(' . self::CONFIG_PREFIX . '){1}\w*/i', get_declared_classes()));

			if (!empty($classes))
			{
				$namespaces = $classes;

				// Retrieve the namespaces from the classes
				foreach ($namespaces as &$namespace)
				{
					$namespace = str_ireplace(self::CONFIG_PREFIX, '', $namespace);
				}

				return $namespaces;
			}

			// There are no namespaces, there return an array with one null element
			return array(null);
		}
	}

	/**
	 * Returns if the current or specified user was authenticated
	 * via LDAP.
	 *
	 * @param   JUser|integer|array  $user  Optional user id (if null then uses current user).
	 *
	 * @return  boolean  True if user is Ldap authenticated or False otherwise.
	 *
	 * @since   2.0
	 * @deprecated  [2.1] Use SHAdapterMap::getUser instead.
	 */
	public static function isUserLdap($user = null)
	{
		// This is inefficient but has to be done to prevent issues with numeric usernames
		if (!($id = JUserHelper::getUserId($user)))
		{
			$id = $user;
		}

		if ($links = SHAdapterMap::getUser($id, true))
		{
			if (strtolower($links[0]['adapter']) === 'ldap')
			{
				return true;
			}
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
	 *
	 * @deprecated  Use SHAdapterEventHelper::triggerEvent() instead
	 */
	public static function triggerEvent($event, $args = null)
	{
		return SHAdapterEventHelper::triggerEvent('ldap', $event, $args);
	}

	/**
	 * Commits the changes to the LDAP user adapter and parses the result.
	 * If any errors occurred then optionally log them and throw an exception.
	 *
	 * @param   SHUserAdaptersLdap  $adapter  LDAP user adapter.
	 * @param   boolean             $log      Log any errors directly to SHLog.
	 * @param   boolean             $throw    Throws an exception on error OR return array on error.
	 *
	 * @return  true|array
	 *
	 * @deprecated  Use SHAdapterHelper::commitChanges() instead
	 */
	public static function commitChanges($adapter, $log = false, $throw = true)
	{
		return SHAdapterHelper::commitChanges($adapter, $log, $throw);
	}
}
