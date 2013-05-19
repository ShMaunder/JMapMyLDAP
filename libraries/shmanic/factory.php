<?php
/**
 * PHP Version 5.3
 *
 * @package    Shmanic.Libraries
 * @author     Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright  Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
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
	 * @var    Array[JDispatcher]
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
	 * @var    Array[SHUserAdapter]
	 * @since  2.0
	 */
	public static $adapters = array();

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
				if (!isset($options['handler']))
				{
					// Uses the default Joomla database object
					$options['handler'] = & JFactory::getDbo();
				}

				if (!isset($options['table']))
				{
					// Uses the default table name
					$options['table'] = '#__sh_config';
				}

				// Retrieve the platform config via SQL
				self::$config = self::createDBConfig($options['handler'], $options['table']);
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
	 * @param   array|string  $user     Either a username string or array of credentials.
	 * @param   string        $type     Type of adapter (e.g. ldap, xml, federated).
	 * @param   array         $options  An array of optional options.
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
			throw new Exception('Invalid username to instanitate a user adapter.');
		}

		if (!isset(self::$adapters[$username]))
		{
			if (is_null($type))
			{
				// Get the default user adpater type from the database
				$type = self::getConfig()->get('user.type');
			}

			// Camel case friendly for class name
			$type = ucfirst(strtolower($type));
			$class = "SHUserAdapters${type}";

			// Create the adapter (note: remember to unset if using multiple adapters!)
			self::$adapters[$username] = new $class($credentials, null, $options);
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
	protected static function createDBConfig(JDatabase $handler, $table)
	{
		// Create the registry with a default namespace of config
		$registry = new JRegistry;

		$query = $handler->getQuery(true);

		// Do the SQL query ensuring only platform specific entries are returned
		$query->select($query->qn('name'))
			->select($query->qn('value'))
			->from($query->qn($table))
			->order($query->qn('id'));

		$handler->setQuery($query);

		// Load the SQL query into an associative array
		$array = $handler->loadAssocList('name', 'value');

		if (!is_null($array))
		{
			foreach ($array as $key => $value)
			{
				// Is there a better way to do this?
				$registry->set($key, $value);
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
				$registry->set(str_replace('__', '.', $key), $value);
			}

			return $registry;
		}

		// Not a valid file or namespace
		throw new RuntimeException(JText::sprintf('LIB_SHPLATFORM_ERR_1121', $file, $name), 1121);

	}
}
