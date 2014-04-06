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
 * SHAdapter response class for a single commit.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Adapter.Response
 * @since       2.1
 */
class SHAdapterResponseCommit
{
	public $operation;

	public $message;

	public $status;

	public $exception;

	/**
	 * Class constructor.
	 *
	 * @param   string     $operation  The commit operation name (e.g. add, replace, delete).
	 * @param   string     $message    Describe the changes made in the commit.
	 * @param   integer    $status     @see JLog constants.
	 * @param   Exception  $exception  The exception object if something went wrong.
	 *
	 * @since   2.1
	 */
	public function __construct($operation, $message, $status = JLog::INFO, $exception = null)
	{
		$this->operation = $operation;
		$this->message = $message;
		$this->status = $status;
		$this->exception = $exception;
	}

	/**
	 * Composes a human readable summary of the operation and message.
	 *
	 * @return   string
	 *
	 * @since    2.1
	 */
	public function getSummary()
	{
		return JText::sprintf(
			'LIB_SHADAPTERRESPONSE_INFO_10801',
			$this->operation,
			$this->message
		);
	}
}
