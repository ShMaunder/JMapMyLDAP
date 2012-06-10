<?php
/**
 * @package    Joomla.Platform
 *
 * @copyright  Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Static class to handle loading of libraries.
 *
 * @package  Joomla.Platform
 * @since    11.1
 */
abstract class TLoader
{

	/**
	 * Container for already imported library paths.
	 *
	 * @var    array
	 * @since  11.1
	 */
	protected static $classes = array();

	/**
	 * Method to discover classes of a given type in a given path.
	 *
	 * @param   string   $classPrefix  The class name prefix to use for discovery.
	 * @param   string   $parentPath   Full path to the parent folder for the classes to discover.
	 * @param   boolean  $force        True to overwrite the autoload path value for the class if it already exists.
	 * @param   boolean  $recurse      Recurse through all child directories as well as the parent path.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public static function discover($classPrefix, $parentPath, $force = true, $recurse = false)
	{
		try
		{
			if ($recurse)
			{
				$iterator = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($parentPath),
					RecursiveIteratorIterator::SELF_FIRST
				);
			}
			else
			{
				$iterator = new DirectoryIterator($parentPath);
			}

			foreach ($iterator as $file)
			{
				$fileName = $file->getFilename();

				// Only load for php files.
				// Note: DirectoryIterator::getExtension only available PHP >= 5.3.6
				if ($file->isFile() && substr($fileName, strrpos($fileName, '.') + 1) == 'php')
				{
					// Get the class name and full path for each file.
					$class = strtolower($classPrefix . preg_replace('#\.php$#', '', $fileName));

					// Register the class with the autoloader if not already registered or the force flag is set.
					if (empty(self::$classes[$class]) || $force)
					{
						JLoader::register($class, $file->getPath() . '/' . $fileName);
					}
				}
			}
		}
		catch (UnexpectedValueException $e)
		{
			// Exception will be thrown if the path is not a directory. Ignore it.
		}
	}

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
		spl_autoload_register(array('TLoader', 'load'));
		spl_autoload_register(array('TLoader', '_autoload'));
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

		// Only attempt autoloading if we are dealing with a Testing class.
		if ($class[0] == 'T')
		{
			// Split the class name (without the J) into parts separated by camelCase.
			$parts = preg_split('/(?<=[a-z])(?=[A-Z])/x', substr($class, 1));

			// If there is only one part we want to duplicate that part for generating the path.
			$parts = (count($parts) === 1) ? array($parts[0], $parts[0]) : $parts;

			// Generate the path based on the class name parts.
			$path = JPATH_TESTS . '/' . implode('/', array_map('strtolower', $parts)) . '.php';

			// Load the file if it exists.
			if (file_exists($path))
			{
				include $path;
			}
		}
	}
}
