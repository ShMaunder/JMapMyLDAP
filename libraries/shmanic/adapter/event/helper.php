<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Adapter
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Helper class for SHAdapter for events.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Adapter
 * @since       2.1
 */
abstract class SHAdapterEventHelper
{
	/**
	 * Calls any registered adapter events associated with an event group.
	 *
	 * @param   string  $dispatch  Dispatcher name OR null to dispatch to all.
	 * @param   string  $event     The event name.
	 * @param   array   $args      An array of arguments.
	 *
	 * @return  boolean  Result of all function calls.
	 *
	 * @since   2.1
	 */
	public static function triggerEvent($dispatch, $event, $args = null)
	{
		if (is_null($dispatch))
		{
			$results = array();

			// Loop through all dispatchers and trigger the event
			foreach (SHFactory::$dispatcher as $dispatcher)
			{
				array_push($results, $dispatcher->trigger($event, $args));
			}
		}
		else
		{
			// Single dispatcher trigger
			$results = SHFactory::getDispatcher($dispatch)->trigger($event, $args);
		}

		// We want to return the actual result (false, true or blank)
		if (in_array(false, $results, true))
		{
			return false;
		}
		elseif (in_array(true, $results, true))
		{
			return true;
		}
		else
		{
			return;
		}
	}
}
