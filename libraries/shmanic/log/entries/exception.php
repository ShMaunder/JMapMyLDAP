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
 * Builds the PHP Exception log entry.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Log
 * @since       2.0
 */
class SHLogEntriesException extends SHLogEntriesId
{
	/**
	 * Internal Error ID.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	public $code = null;

	/**
	 * Trace as String.
	 *
	 * @var    string
	 * @since  2.0
	 */
	public $trace = null;

	/**
	 * File path of thrown exception.
	 *
	 * @var    string
	 * @since  2.0
	 */
	public $file = null;

	/**
	 * Line number of thrown exception.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	public $line = null;

	/**
	 * Exception generated toString.
	 *
	 * @var   string
	 * @since  2.0
	 */
	public $full = null;

	/**
	 * Constructor
	 *
	 * @param   Exception  $exception  The PHP Exception.
	 * @param   integer    $id         Optional ID of the error (not the exception error).
	 * @param   string     $priority   Message priority based on {$this->priorities}.
	 * @param   string     $category   Type of entry
	 * @param   string     $date       Date of entry (defaults to now if not specified or blank)
	 *
	 * @since   2.0
	 */
	public function __construct(Exception $exception, $id = 0, $priority = JLog::INFO, $category = '', $date = null)
	{
		// Uses the exceptions toString for the exceptions string
		$this->full = (string) $exception;

		// Internal Error ID of the exception
		$this->code = $exception->getCode();

		// Replace the ID if it is empty
		if ($id === 0)
		{
			$id = $this->code;
		}

		// Uses the translated internal Error ID message
		$message = $exception->getMessage();

		// Trace
		$this->trace = $exception->getTraceAsString();

		// File occurred
		$this->file = $exception->getFile();

		// Line occurred
		$this->line = $exception->getLine();

		parent::__construct($id, $message, $priority, $category, $date);
	}
}
