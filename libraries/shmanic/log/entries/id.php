<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Log.Entries
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Builds a error log with an included ID.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Log.Entries
 * @since       2.0
 */
class SHLogEntriesId extends JLogEntry
{
	/**
	 * Internal ID code of entry.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	public $id = null;

	/**
	 * Constructor
	 *
	 * @param   integer  $id        Internal ID code of entry.
	 * @param   string   $message   The message to log.
	 * @param   string   $priority  Message priority based on {$this->priorities}.
	 * @param   string   $category  Type of entry.
	 * @param   string   $date      Date of entry (defaults to now if not specified or blank).
	 *
	 * @since   2.0
	 */
	public function __construct($id, $message, $priority = JLog::INFO, $category = '', $date = null)
	{
		// Internal ID code of this specific entry.
		$this->id = (int) $id;

		// Pass the remaining attributes to the parent class for processing.
		parent::__construct($message, $priority, $category, $date);
	}
}
