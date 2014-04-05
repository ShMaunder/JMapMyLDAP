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
 * Helper class for SHAdapter.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Adapter
 * @since       2.1
 */
abstract class SHAdapterHelper
{
	/**
	 * Commits the changes to the adapter and parses the result.
	 * If any errors occurred then optionally log them and throw an exception.
	 *
	 * @param   SHAdapter  $adapter  Adapter.
	 * @param   boolean    $log      Log any errors directly to SHLog.
	 * @param   boolean    $throw    Throws an exception on error OR return array on error.
	 *
	 * @return  true|array
	 *
	 * @since   2.1
	 */
	public static function commitChanges($adapter, $log = false, $throw = true)
	{
		$results = $adapter->commitChanges();
		$adapterName = $adapter->getName();

		// Only if there is an array can we actually do anything here
		if (is_array($results))
		{
			if ($log)
			{
				// Lets log all the commits
				foreach ($results['commits'] as $commit)
				{
					if ($commit['status'] === JLog::INFO)
					{
						SHLog::add($commit['info'], 10634, JLog::INFO, $adapterName);
					}
					else
					{
						SHLog::add($commit['info'], 10636, JLog::ERROR, $adapterName);
						SHLog::add($commit['exception'], 10637, JLog::ERROR, $adapterName);
					}
				}
			}

			// Check if any of the commits failed
			if (!$results['status'])
			{
				if ($throw)
				{
					throw new RuntimeException(JText::_('LIB_SHADAPTERHELPER_ERR_10638'), 10638);
				}
				else
				{
					return $results;
				}
			}
		}
		else
		{
			return $results;
		}

		return true;
	}
}
