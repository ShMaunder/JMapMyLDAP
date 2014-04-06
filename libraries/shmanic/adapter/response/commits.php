<?php
/**
 * Backported from 2.1!
 *
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
	protected $commits;

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
	 * @since   2.1
	 */
	public function __construct()
	{
		$this->commits = array();
		$this->update();
	}

	/**
	 * Method to get certain otherwise inaccessible class properties.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   2.1
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'nochanges':
				return !$this->changes;
				break;

			case 'commits':
				return $this->commits;
				break;
		}

		return $this->$name;
	}

	/**
	 * Method to set class properties.
	 *
	 * @param   string  $name   The property name.
	 * @param   string  $value  The property value.
	 *
	 * @return  void
	 *
	 * @since   2.1
	 */
	public function __set($name, $value)
	{
		$this->$name = $value;

		if ($name === 'commits')
		{
			$this->update();
		}
	}

	/**
	 * Gets commits.
	 *
	 * @return  SHAdapterResponseCommit[]  Array of commits.
	 *
	 * @since   2.1
	 */
	public function getCommits()
	{
		return $this->commits;
	}

	/**
	 * Adds a commit to the commits stack.
	 *
	 * @param   string     $operation  The commit operation name (e.g. add, replace, delete).
	 * @param   string     $message    Describe the changes made in the commit.
	 * @param   integer    $status     @see JLog constants.
	 * @param   Exception  $exception  The exception object if something went wrong.
	 *
	 * @return  void
	 *
	 * @since   2.1
	 */
	public function addCommit($operation, $message, $status = JLog::INFO, $exception = null)
	{
		$this->commits[] = new SHAdapterResponseCommit($operation, $message, $status, $exception);
		$this->update();
	}

	/**
	 * Updates the changes and status by inspecting the commits.
	 *
	 * @return  void
	 *
	 * @since   2.1
	 */
	protected function update()
	{
		$this->changes = count($this->commits) ? true : false;

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
}
