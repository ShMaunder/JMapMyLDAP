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
 * Builds the SHAdapter log entry.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Log
 * @since       2.1
 */
class SHLogEntriesAdapter extends SHLogEntriesId
{
	/**
	 * Type of adapter.
	 *
	 * @var    string
	 * @since  2.1
	 */
	public $adapterType;

	/**
	 * Domain of adapter.
	 *
	 * @var    string
	 * @since  2.1
	 */
	public $adapterDomain;

	/**
	 * ID of adapter.
	 *
	 * @var    string
	 * @since  2.1
	 */
	public $adapterId;

	/**
	 * Log entry in one string including adapter.
	 *
	 * @var    string
	 * @since  2.1
	 */
	public $full;

	/**
	 * Constructor
	 *
	 * @param   SHAdapter  $adapter   Adapter for log.
	 * @param   string     $message   The message to log.
	 * @param   integer    $id        Internal ID code of entry.
	 * @param   string     $priority  Message priority based on {$this->priorities}.
	 * @param   string     $category  Type of entry.
	 * @param   string     $date      Date of entry (defaults to now if not specified or blank).
	 *
	 * @since   2.1
	 */
	public function __construct(SHAdapter $adapter, $message, $id, $priority = JLog::INFO, $category = null, $date = null)
	{
		// Get the adapter type and transform it to human readable
		$type = $adapter::TYPE;

		if ($type === SHAdapter::TYPE_USER)
		{
			$this->adapterType = 'User';
			$this->adapterId = $adapter->loginUser;
		}
		elseif ($type === SHAdapter::TYPE_GROUP)
		{
			$this->adapterType = 'Group';
			//$this->adapterId = $adapter->getId();
		}
		else
		{
			$this->adapterType = 'Generic';
			$this->adapterId = 'N/A';
		}

		// Domain of the adapter user/group
		$this->adapterDomain = $adapter->getDomain();

		if (empty($category))
		{
			$category = strtolower($adapter->getName());
		}

		parent::__construct($id, $message, $priority, $category, $date);

		// Creates a one liner for everything
		$this->full = JText::sprintf(
			'(%1$s::%2$s::%3$s) %4$s',
			$this->adapterType, $this->adapterDomain, $this->adapterId, $this->message
		);
	}
}
