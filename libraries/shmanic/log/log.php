<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Log
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Logging helper for SHPlatform.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Log
 * @since       2.0
 */
class SHLog
{
	/**
	 * List of IDs to ignore (not report).
	 *
	 * @var    array[integer]
	 * @since  2.0
	 */
	protected static $ignore = array();

	/**
	 * Add an Error ID to the ignore list.
	 *
	 * @param   integer  $id  Error ID.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public static function addIgnore($id)
	{
		self::$ignore[] = (int) $id;
	}

	/**
	 * Imports the logging plugin group; these are imported with the
	 * Joomla dispatcher.
	 *
	 * @param   string  $type  Log plugin group.
	 *
	 * @return  boolean  True on success or False on failure.
	 *
	 * @since   2.0
	 */
	public static function import($type = 'shlog')
	{
		$dispatcher = JDispatcher::getInstance();
		$result = JPluginHelper::importPlugin($type);
		$dispatcher->trigger('onLogInitialise');

		return $result;
	}

	/**
	 * Method to add an entry to the log (ID enabled).
	 *
	 * @param   mixed    $entry     The JLogEntry object to add to the log or the message for a new JLogEntry object.
	 * @param   integer  $id        ID of the entry.
	 * @param   integer  $priority  Message priority.
	 * @param   string   $category  Type of entry.
	 * @param   string   $date      Date of entry (defaults to now if not specified or blank).
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public static function add($entry, $id = 0, $priority = JLog::INFO, $category = '', $date = null)
	{
		// If the entry object isn't a JLogEntry object let's make one.
		if (!($entry instanceof JLogEntry))
		{
			// Check if the entry object is an exception
			if ($entry instanceof Exception)
			{
				$entry = new SHLogEntriesException($entry, $id, $priority, $category, $date);
			}
			else
			{
				$entry = new SHLogEntriesId($id, (string) $entry, $priority, $category, $date);
			}
		}

		// Check the Error ID is not listed as ignored
		if (!in_array((int) $id, self::$ignore))
		{
			// Something is up with the deprecation of JDispatcher in newer Joomla Versions
			if (class_exists('JDispatcher'))
			{
				// J2.5
				$dispatcher = JDispatcher::getInstance();
			}
			else
			{
				// J3.0+ and Platform
				$dispatcher = JEventDispatcher::getInstance();
			}

			// Inform any logging plugins that an entry has been produced
			$dispatcher->trigger('onLogEntry', array($entry));

			try
			{
				// Add the entry to all avilable loggers
				JLog::add($entry);
			}
			catch (Exception $e)
			{
				// Logging is broken - this isn't a reason though to
				error_log(JText::_('LIB_SHLOG_ERR_2200'));
			}
		}
	}
}
