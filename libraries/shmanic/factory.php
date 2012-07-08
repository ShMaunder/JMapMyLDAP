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
			self::$dispatcher[$name] = new JDispatcher;
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
			->order($query->qn('config_id'));

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
	 */
	protected static function createFileConfig($file, $namespace)
	{
		if (file_exists($file))
		{
			/*
			 * If the configuration is outside of the autoloader
			 * (i.e. /libraries/shmanic) then lets include it.
			 */
			include_once $file;
		}

		// Create the registry with a default namespace of config
		$registry = new JRegistry;

		// Sanitize the namespace.
		$namespace = ucfirst((string) preg_replace('/[^A-Z_]/i', '', $namespace));

		// Build the class with namespace support
		$name = 'SHConfig' . $namespace;

		if (class_exists($name))
		{
			// Create the SHConfig object
			$config = new $name;

			// Load the configuration values into the registry
			$registry->loadObject($config);
		}

		return $registry;
	}
}
