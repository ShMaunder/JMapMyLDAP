<?php
/**
 * PHP Version 5.3
 *
 * @package    Shmanic.Libraries
 * @author     Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright  Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.event.dispatcher');

/**
 * The SHPlatform main factory.
 *
 * @package  Shmanic.Libraries
 * @since    2.0
 */
abstract class SHFactory
{
	/**
	 * An array of dispatcher objects.
	 *
	 * @var    JDispatcher[]
	 * @since  2.0
	 */
	public static $dispatcher = array();

	/**
	 * Holds the platform configuration options.
	 *
	 * @var    JRegistry
	 * @since  2.0
	 */
	public static $config = null;

	/**
	 * An array of user adapters.
	 *
	 * @var    SHUserAdapter[]
	 * @since  2.0
	 */
	public static $adapters = array();

	/**
	 * An array of group adapters.
	 *
	 * @var    SHGroupAdapter[]
	 * @since  2.1
	 */
	public static $groups = array();

	/**
	 * An array of ldap clients.
	 *
	 * @var    SHLdap[]
	 * @since  2.0
	 */
	public static $ldap = array();

	/**
	 * Returns a specific dispatcher object. This dispatcher is independent
	 * of other dispatchers or the global dispatcher and therefore, will only
	 * call events that has been registered to it.
	 *
	 * @param   string  $name  Unique name of the dispatcher
	 *
	 * @return  JDispatcher  Dispatcher object
	 *
	 * @since   2.0
	 */
	public static function getDispatcher($name = 'SHFactory')
	{
		$name = strtolower($name);

		if (!isset(self::$dispatcher[$name]))
		{
			// Something is up with the deprecation of JDispatcher in newer Joomla Versions
			if (class_exists('JDispatcher'))
			{
				// J2.5
				self::$dispatcher[$name] = new JDispatcher;
			}
			else
			{
				// J3.0+ and Platform
				self::$dispatcher[$name] = new JEventDispatcher;
			}
		}

		return self::$dispatcher[$name];
	}

	/**
	 * Gets the configuration options for the platform.
	 *
	 * @param   string  $type     The type of configuration (i.e. sql, file).
	 * @param   array   $options  An array of options (handler=>[Database Object],
	 * 								table=>[Database Table], file=>[path to file], namespace=>[Namespace of Class]).
	 *
	 * @return  JRegistry  Registry of the configuration options.
	 *
	 * @since   2.0
	 */
	public static function getConfig($type = 'sql', $options = array())
	{
		if (!isset(self::$config))
		{
			if ($type === 'sql')
			{
				$options['handler'] = (isset($options['handler'])) ? $options['handler'] : null;

				if (!isset($options['table']))
				{
					// Uses the default table name
					$options['table'] = '#__sh_config';
				}

				// Retrieve the platform config via cached SQL
				$cache = JFactory::getCache('shplatform', 'callback');
				self::$config = $cache->get(
					array(__CLASS__, 'createDBConfig'),
					array($options['handler'], $options['table']),
					null
				);
			}
			elseif ($type === 'file')
			{
				if (!isset($options['file']))
				{
					// Attempt to use a default file
					$options['file'] = JPATH_ROOT . '/sh_configuration.php';
				}

				if (!isset($options['namespace']))
				{
					// Attempt to use a default file
					$options['namespace'] = null;
				}

				// Retrieve the platform config via PHP file
				self::$config = self::createFileConfig($options['file'], $options['namespace']);
			}
		}

		return self::$config;
	}

	/**
	 * Gets the user adapter for the user specified. Creates a new user
	 * adapter if one doesnt already exist for the user.
	 *
	 * @param   array|string  $user     Either a username string or array of credentials including JUser ID and domain.
	 * @param   string        $type     Type of adapter (e.g. ldap, xml, federated).
	 * @param   array         $options  An array of optional options including isNew.
	 *
	 * @return  SHUserAdapter  Object to user adapter.
	 *
	 * @since   2.0
	 * @throws  Exception
	 */
	public static function getUserAdapter($user, $type = null, $options = array())
	{
		if (is_array($user))
		{
			$username = strtolower(JArrayHelper::getValue($user, 'username', null, 'string'));
			$credentials = $user;
		}
		else
		{
			$username = strtolower((string) $user);
			$credentials = array('username' => $username);
		}

		if (empty($username))
		{
			throw new RuntimeException(JText::_('LIB_SHFACTORY_ERR_2121'), 2121);
		}

		if (!isset(self::$adapters[$username]))
		{
			$config = self::getConfig();

			// Check if this user is in the blacklist
			if ($blacklist = (array) json_decode($config->get('user.blacklist')))
			{
				if (in_array($username, $blacklist))
				{
					throw new RuntimeException(JText::sprintf('LIB_SHFACTORY_ERR_2125', $username), 2125);
				}
			}

			// Attempts to get the user linking entry to determine domain and type of user
			//TODO: allow multiple domains from links
			if ($links = SHAdapterMap::getUser($username))
			{
				if ((boolean) $config->get('user.usedomain', true))
				{
					if (!isset($credentials['domain']))
					{
						// Attempt to get the domain for this user
						$credentials['domain'] = $links[0]['domain'];
					}
				}
				else
				{
					unset($credentials['domain']);
				}

				if (!isset($credentials['type']) && is_null($type))
				{
					// Attempt to get the User Adapter name
					$type = $links[0]['adapter'];
				}
			}

			if (is_null($type))
			{
				// Get the default/primary user adapter type from the database
				$type = $config->get('user.type', 'Default');
			}

			// Camel case friendly for class name
			$type = ucfirst(strtolower($type));
			$class = "SHUserAdapters${type}";

			if (class_exists($class))
			{
				// Create the adapter (note: remember to unset if using multiple adapters!)
				self::$adapters[$username] = new $class($credentials, null, $options);
			}
			else
			{
				throw new RuntimeException(JText::sprintf('LIB_SHFACTORY_ERR_2123', $class), 2123);
			}
		}
		else
		{
			// Update credentials if required
			if ($password = JArrayHelper::getValue($user, 'password', false))
			{
				self::$adapters[$username]->updateCredential($password, $options);
			}
		}

		return self::$adapters[$username];
	}

	/**
	 * Gets a group adapter for the group specified. Creates a new group
	 * adapter if one doesnt already exist.
	 *
	 * @param   string  $group    Group name.
	 * @param   string  $type     Type of adapter (e.g. ldap, xml, federated).
	 * @param   array   $options  An array of optional options including isNew.
	 *
	 * @return  SHGroupAdapter  Object to group adapter.
	 *
	 * @since   2.0
	 * @throws  Exception
	 */
	public static function getGroupAdapter($group, $type = null, $options = array())
	{
		$groupname = strtolower((string) $group);

		if (!isset(self::$groups[$groupname]))
		{
			$config = self::getConfig();

			// Check if this group is in the blacklist
			if ($blacklist = (array) json_decode($config->get('group.blacklist')))
			{
				if (in_array($groupname, $blacklist))
				{
					//TODO: newID
					throw new RuntimeException(JText::sprintf('LIB_SHFACTORY_ERR_2125', $groupname), 2125);
				}
			}

			// Attempts to get the user linking entry to determine domain and type of user
			/*if (($link = SHAdapterMap::getUser($username)) && $link['adapter'])
			{
				if ((boolean) $config->get('user.usedomain', true))
				{
					if (!isset($credentials['domain']))
					{
						// Attempt to get the domain for this user
						$credentials['domain'] = $link['domain'];
					}
				}
				else
				{
					unset($credentials['domain']);
				}

				if (!isset($credentials['type']) && is_null($type))
				{
					// Attempt to get the User Adapter name
					$type = $link['adapter'];
				}
			}
			*/
			if (is_null($type))
			{
				// Get the default/primary user adapter type from the database
				$type = $config->get('user.type', 'Default');
			}

			// Camel case friendly for class name
			$type = ucfirst(strtolower($type));
			$class = "SHGroupAdapter${type}";

			if (class_exists($class))
			{
				// Create the adapter (note: remember to unset if using multiple adapters!)
				self::$groups[$groupname] = new $class($group, null, $options);
			}
			else
			{
				//TODO: new id
				throw new RuntimeException(JText::sprintf('LIB_SHFACTORY_ERR_2123', $class), 2123);
			}
		}

		return self::$groups[$groupname];
	}

	/**
	 * This method is similar to SHLdap::getInstance but also it stores
	 * all instantiated SHLdap objects in a static variable for later use.
	 *
	 * @param   integer|string  $domain    Optional domain or configuration record ID.
	 * @param   JRegistry       $config    Optional LDAP config (can also be single JRegistry without array).
	 * @param   JRegistry       $registry  Optional override for platform configuration registry.
	 *
	 * @return  SHLdap[]  An array of SHLdap objects.
	 *
	 * @since   2.1
	 * @throws  InvalidArgumentException  Invalid configurations
	 */
	public static function getLdapClient($domain = null, $config = array(), JRegistry $registry = null)
	{
		// Get the platform registry config from the factory $domain . d
		$registry = is_null($registry) ? self::getConfig() : $registry;

		$cache = JFactory::getCache('shldap', '');

		$domain = is_null($domain) ? $domain : strtolower($domain);

		// Generate a unique hash for this configuration depending on the domain requested
		$domainHash = empty($domain) ? '' : $domain;

		if (!empty($domain))
		{
			$hash = md5($domain . serialize($config) . serialize($registry));

			if (isset(self::$ldap[$hash]))
			{
				return array(self::$ldap[$hash]);
			}

			// Reconstruct the LDAP configuration object from cache and ensure it is valid
			if ($cachedConfig = $cache->get($hash) && ($cachedConfig instanceof SHLdap))
			{
				self::$ldap[$hash] = $cachedConfig;

				return array(self::$ldap[$hash]);
			}
		}
		else
		{
			$hash = md5('all_domains' . serialize($config) . serialize($registry));

			if (isset(self::$ldap[$hash]))
			{
				$valid = true;
				$configs = array();

				// Reconstruct all domains in order
				foreach (self::$ldap[$hash] as $hash)
				{
					if (isset(self::$ldap[$hash]))
					{
						$configs[] = self::$ldap[$hash];
					}
					else
					{
						// One of the configs are invalid and therefore we must run everything again
						$valid = false;
						break;
					}

					if ($valid)
					{
						return $configs;
					}
				}
			}
			else
			{
				// Check if we have done a "all domain" LDAP config retrieve
				if (($cachedConfigs = $cache->get($hash)) && is_array($cachedConfigs))
				{
					$valid = true;
					$configs = array();

					foreach ($cachedConfigs as $configHash)
					{
						// Reconstruct the "all domain" configurations from cache and check they are valid
						if (($cachedConfig = $cache->get($configHash)) && ($cachedConfig instanceof SHLdap))
						{
							$configs[] = $cachedConfig;
						}
						else
						{
							// One of the configs are invalid and therefore we must run everything again
							$valid = false;
							break;
						}
					}

					if ($valid)
					{
						return $configs;
					}
				}
			}
		}

		// Potentially we will need the original input config later
		$inputConfig = $config;

		if (empty($config))
		{
			/*
			 * Get all the Ldap configs that are enabled and available. An optional
			 * domain is passed - when this is passed, only one config will be returned.
			 */
			$configs = SHLdapHelper::getConfig($domain, $registry);
		}
		else
		{
			// Use the specified config
			$configs = ($config instanceof JRegistry) ? $config : new JRegistry($config);
		}

		if (!empty($configs))
		{
			// If only 1 config result, wrap around an array so we can use the same code
			$configs = ($configs instanceof JRegistry) ? array($configs) : $configs;

			// This will be all the LDAP clients that match the domain
			$clients = array();

			// Store all the hashes that we generate for caching purposes
			$hashes = array();

			// Loop around each of the Ldap configs until one authenticates
			foreach ($configs as $config)
			{
				// Validate the config from any registered listeners (this can also change the config)
				SHUtilValidate::getInstance()->validate($config);

				/*
				 * We won't catch exceptions now as it will mean either ldap
				 * extension missing OR a very bad problem with this LDAP configuraiton.
				 */
				$ldap = new SHLdap($config);

				$hash = md5(strtolower($ldap->domain) . serialize($inputConfig) . serialize($registry));

				if (!isset(self::$ldap[$hash]))
				{
					// We want to store this client for potential use later on
					self::$ldap[$hash] = $ldap;

					// Lets cache the LDAP client for future use
					$cache->store(self::$ldap[$hash], $hash);
				}

				$hashes[] = $hash;

				$clients[] = self::$ldap[$hash];

				unset($ldap);
			}

			if (count($clients))
			{
				if (empty($domain))
				{
					// Cache all domains in the correct order so we can use it for future use
					$hash = md5('all_domains' . serialize($inputConfig) . serialize($registry));
					$cache->store($hashes, $hash);

					self::$ldap[$hash] = $hashes;
				}

				// Found some LDAP configs - lets return them
				return $clients;
			}
		}

		// No errors happened, but unable to find suitable LDAP config for the domain
		throw new InvalidArgumentException(JText::_('LIB_SHFACTORY_ERR_2131'), 2131);
	}

	/**
	 * Setups the JCrypt object with default keys if not specified then returns it.
	 *
	 * @param   array  $options  Optional override options for keys.
	 *
	 * @return  JCrypt  The configured JCrypt object.
	 *
	 * @since   2.0
	 */
	public static function getCrypt($options = array())
	{
		$source = strtolower(JArrayHelper::getValue($options, 'source', 'jconfig', 'string'));

		if ($source === 'jconfig')
		{
			/*
			 * If JConfig has been included then lets check whether the keys
			 * have been imported and if not then use the secret value for now.
			 */
			if (class_exists('JConfig'))
			{
				$config = new JConfig;

				if (!isset($options['key']))
				{
					$options['key'] = $config->secret;
				}
			}
		}
		elseif ($source === 'file')
		{
			$file = JArrayHelper::getValue($options, 'file', '', 'string');

			if (file_exists($file))
			{
				$options['key'] = file_get_contents($file);
			}
		}

		$crypt = new JCrypt;

		// Create some default options
		$type = JArrayHelper::getValue($options, 'type', 'simple', 'string');
		$key = JArrayHelper::getValue($options, 'key', 'DEFAULTKEY', 'string');

		$crypt->setKey(
			new JCryptKey(
				$type,
				$key,
				$key
			)
		);

		return $crypt;
	}

	/**
	 * Returns a new configuration registry from a source configuration
	 * SQL table. This method uses the JDatabase object.
	 *
	 * @param   JDatabase  $handler  Connected and active JDatabase object.
	 * @param   string     $table    Database table to use.
	 *
	 * @return  JRegistry  Registry of configuration.
	 *
	 * @since   2.0
	 */
	public static function createDBConfig($handler, $table)
	{
		$handler = is_null($handler) ? JFactory::getDbo() : $handler;

		// Create the registry with a default namespace of config
		$registry = new JRegistry;

		$query = $handler->getQuery(true);

		// Do the SQL query ensuring only platform specific entries are returned
		$query->select($query->quoteName('name'))
			->select($query->quoteName('value'))
			->from($query->quoteName($table))
			->order($query->quoteName('id'));

		$handler->setQuery($query);

		// Load the SQL query into an associative array
		$array = $handler->loadAssocList('name', 'value');

		if (!is_null($array))
		{
			foreach ($array as $key => $value)
			{
				// Ensure compatibility between group.config and group:config
				$registry->set(str_replace(':', '.', $key), $value);
				$registry->set(str_replace('.', ':', $key), $value);
			}
		}

		return $registry;
	}

	/**
	 * Returns a new configuration registry from a source configuration
	 * file. Supports namespaced configs.
	 *
	 * @param   string  $file       Path to source configuration file.
	 * @param   string  $namespace  Namespace of configuration file.
	 *
	 * @return  JRegistry  Registry of configuration.
	 *
	 * @since   2.0
	 * @throws  RuntimeException
	 */
	protected static function createFileConfig($file, $namespace)
	{
		// Create the registry with a default namespace of config
		$registry = new JRegistry;

		// Sanitize the namespace.
		$namespace = ucfirst((string) preg_replace('/[^A-Z_]/i', '', $namespace));

		// Build the class with namespace support
		$name = 'SHConfig' . $namespace;

		if (!class_exists($name) || file_exists($file))
		{
			/*
			 * If the configuration is outside of the autoloader
			 * (i.e. /libraries/shmanic) then lets include it.
			 */
			include_once $file;
		}

		if (class_exists($name))
		{
			// Create the SHConfig object
			$config = new $name;

			// Load the configuration values into the registry
			foreach ($config as $key => $value)
			{
				// Ensure compatibility between group.config and group:config
				$registry->set(str_replace('__', '.', $key), $value);
				$registry->set(str_replace('__', ':', $key), $value);
			}

			return $registry;
		}

		// Not a valid file or namespace
		throw new RuntimeException(JText::sprintf('LIB_SHPLATFORM_ERR_1121', $file, $name), 1121);
	}
}
