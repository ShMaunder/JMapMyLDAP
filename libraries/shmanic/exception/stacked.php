<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Exception
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Exception that stacks other exceptions in the same exception.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Exception
 * @since       2.0
 */
class SHExceptionStacked extends Exception
{
	/**
	 * Stacked exceptions.
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $stacked = array();

	/**
	 * Constructor
	 *
	 * @param   string   $message  Error message.
	 * @param   integer  $code     Error ID.
	 * @param   array    $stacked  An array of exceptions.
	 *
	 * @since   2.0
	 */
	public function __construct($message = null, $code = 0, $stacked = array())
	{
		$this->stacked = $stacked;

		// Get the stacked exceptions and loop around each to parse them
		$stackedMsg = null;

		foreach ($this->stacked as $item)
		{
			if (!is_null($stackedMsg))
			{
				$stackedMsg .= '; ';
			}

			$stackedMsg .= $item->getCode() . ': ' . $item->getMessage();
		}

		// Combine the message in the form "MSG :: [STACKED ERRORS]"
		$message = "{$message} :: [{$stackedMsg}]";

		parent::__construct($message, $code, null);
	}

	/**
	 * Magic method to override the to string value for the exception.
	 *
	 * @return  string  Exception string.
	 *
	 * @since   2.0
	 */
	public function __toString()
	{
		// TODO: need to add the stacked exceptions here somehow
		return sprintf(
			'Exception %1$d message \'%2$s\' in %3$s:%4$d',
			$this->getCode(), $this->getMessage(),
			$this->getFile(), $this->getLine()
		);
	}

	/**
	 * Gets the stacked exceptions array.
	 *
	 * @return  array
	 *
	 * @since   2.0
	 */
	public function getStacked()
	{
		return $this->stacked;
	}
}
