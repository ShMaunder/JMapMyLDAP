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
 * Exception that also stores a username when a invalid user error occurs.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Exception
 * @since       2.0
 */
class SHExceptionInvaliduser extends Exception
{
	/**
	 * Affected username.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $username = null;

	/**
	 * Constructor
	 *
	 * @param   string   $message   Error message.
	 * @param   integer  $code      Error ID.
	 * @param   integer  $username  Username of user.
	 *
	 * @since   2.0
	 */
	public function __construct($message = null, $code = 0, $username = null)
	{
		$this->username = $username;

		// Convert the username now
		$message = str_replace('[username]', $this->username, $message);

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
		// Print with username string attached
		return sprintf(
			'Exception %1$d message \'%2$s\' for user \'%3$s\' in %3$s:%4$d',
			$this->getCode(), $this->getMessage(), $this->getUsername(),
			$this->getFile(), $this->getLine()
		);
	}

	/**
	 * Returns the affected username.
	 *
	 * @return  string
	 *
	 * @since   2.0
	 */
	public function getUsername()
	{
		return $this->username;
	}
}
