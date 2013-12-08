<?php
/**
 * File adapted from Joomla's JLoader.
 *
 * PHP Version 5.3
 *
 * @package    Shmanic.Libraries
 * @author     Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright  Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Static class to handle loading of libraries.
 *
 * @package  Shmanic.Libraries
 * @since    2.0
 */
abstract class SHLoader
{
	/**
	 * Container for already imported library paths.
	 *
	 * @var    array
	 * @since  11.1
	 */
	protected static $classes = array();

	/**
	 * Method to get the list of registered classes and their respective file paths for the autoloader.
	 *
	 * @return  array  The array of class => path values for the autoloader.
	 *
	 * @since   11.1
	 */
	public static function getClassList()
	{
		return self::$classes;
	}

	/**
	 * Load the file for a class.
	 *
	 * @param   string  $class  The class to be loaded.
	 *
	 * @return  boolean  True on success
	 *
	 * @since   11.1
	 */
	public static function load($class)
	{
		// Sanitize class name.
		$class = strtolower($class);

		// If the class already exists do nothing.
		if (class_exists($class))
		{
			return true;
		}

		// If the class is registered include the file.
		if (isset(self::$classes[$class]))
		{
			include_once self::$classes[$class];

			return true;
		}

		return false;
	}

	/**
	 * Directly register a class to the autoload list.
	 *
	 * @param   string   $class  The class name to register.
	 * @param   string   $path   Full path to the file that holds the class to register.
	 * @param   boolean  $force  True to overwrite the autoload path value for the class if it already exists.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public static function register($class, $path, $force = true)
	{
		// Sanitize class name.
		$class = strtolower($class);

		// Only attempt to register the class if the name and file exist.
		if (!empty($class) && is_file($path))
		{
			// Register the class with the autoloader if not already registered or the force flag is set.
			if (empty(self::$classes[$class]) || $force)
			{
				self::$classes[$class] = $path;
			}
		}
	}

	/**
	 * Method to setup the autoloaders for the Joomla Platform.  Since the SPL autoloaders are
	 * called in a queue we will add our explicit, class-registration based loader first, then
	 * fall back on the autoloader based on conventions.  This will allow people to register a
	 * class in a specific location and override platform libraries as was previously possible.
	 *
	 * @return  void
	 *
	 * @since   11.3
	 */
	public static function setup()
	{
		spl_autoload_register(array('SHLoader', 'load'));
		spl_autoload_register(array('SHLoader', '_autoload'));
	}

	/**
	 * Autoload a Joomla Platform class based on name.
	 *
	 * @param   string  $class  The class to be loaded.
	 *
	 * @return  void
	 *
	 * @since   11.3
	 */
	private static function _autoload($class)
	{
		// Only attempt to autoload if the class does not already exist.
		if (class_exists($class))
		{
			return;
		}

		// Only attempt autoloading if we are dealing with a Joomla Platform class.
		if (substr($class, 0, 2) == 'SH')
		{
			// Split the class name (without the J) into parts separated by camelCase.
			$parts = preg_split('/(?<=[a-z])(?=[A-Z])/x', substr($class, 2));

			// If there is only one part we want to duplicate that part for generating the path.
			$parts = (count($parts) === 1) ? array($parts[0], $parts[0]) : $parts;

			// Generate the path based on the class name parts.
			$path = SHPATH_PLATFORM . '/' . implode('/', array_map('strtolower', $parts)) . '.php';

			// Load the file if it exists.
			if (file_exists($path))
			{
				include $path;
			}
		}
	}
}

/**
 * Executes a Shmanic importer.
 *
 * @param   string  $item  Item or product.
 *
 * @return  boolean  True on successful import or False on file not found.
 *
 * @since   2.0
 */
function shImport($item)
{
	// Remove non-alphanumeric characters (except underscore)
	$item = preg_replace('/\W/', null, $item);

	$file = SHPATH_PLATFORM . "/import/{$item}.php";

	if (file_exists($file))
	{
		// Include the import file
		return include_once $file;
	}

	// Failed to import
	return false;
}
