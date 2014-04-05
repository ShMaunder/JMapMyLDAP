<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Adapter.Response
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * SHAdapter response class for commits.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Adapter.Response
 * @since       2.1
 */
class SHAdapterResponseCommits
{
	/**
	 * Array of commit objects.
	 *
	 * @var    SHAdapterResponseCommit[]
	 * @since  2.1
	 */
	public $commits;

	/**
	 * True if changes were made in the commits.
	 *
	 * @var    boolean
	 * @since  2.1
	 */
	public $changes;

	/**
	 * True if method completed without error.
	 *
	 * @var    boolean
	 * @since  2.1
	 */
	public $status;

	/**
	 * Class constructor.
	 *
	 * @param   SHAdapterResponseCommit[]  $commits  Array of commit objects.
	 *
	 * @since   2.1
	 */
	public function __construct(array $commits)
	{
		$this->commits = $commits;

		$this->changes = count($commits) ? true : false;

		$this->status = true;

		// Lets find if any of our commits encountered an error
		if ($this->changes)
		{
			foreach ($this->commits as $commit)
			{
				if ($commit->status === JLog::ERROR)
				{
					$this->status = false;
				}
			}
		}
	}

	/**
	 * Method to get certain otherwise inaccessible properties from the commits response.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   2.1
	 */
	public function __get($name)
	{
		if ($name === 'nochanges')
		{
			return !$this->changes;
		}

		return parent::__get($name);
	}
}
