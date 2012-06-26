<?php
/**
 * PHP Version 5.3
 * 
 * @package     Shmanic.Libraries
 * @subpackage  Ldap.Log
 * @author      Shaun Maunder <shaun@shmanic.com>
 * 
 * @copyright   Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Builds the Ldap exception (SHLdapException) log entry.
 * 
 * @package     Shmanic.Libraries
 * @subpackage  Ldap.Log
 * @since       2.0
 */
class SHLdapLogEntryexception extends JLogEntry
{
	/**
	 * Internal Error ID.
	 * 
	 * @var    integer
	 * @since  2.0
	 */
	public $code = null;

	/**
	 * Ldap Error ID.
	 * 
	 * @var    integer
	 * @since  2.0
	 */
	public $ldapCode = null;

	/**
	 * Ldap Error Message.
	 * 
	 * @var    string
	 * @since  2.0
	 */
	public $ldapMessage = null;

	/**
	 * Backtrace as String.
	 * 
	 * @var    string
	 * @since  2.0
	 */
	public $backTrace = null;

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
	 * Exceptions generated toString.
	 * 
	 * @va r   string
	 * @since  2.0
	 */
	public $exceptionStr = null;

	/**
	 * Constructor
	 *
	 * @param   SHLdapException  $exception  The exception to base log entry on.
	 * @param   string           $priority   Message priority based on {$this->priorities}.
	 * @param   string           $category   Type of entry
	 *
	 * @since   2.0
	 */
	public function __construct(SHLdapException $exception, $priority = JLog::INFO, $category = 'ldap')
	{
		// Uses the exceptions toString for the exceptions string
		$this->exceptionStr = (string) $exception;

		// Internal Error ID of the exception
		$this->code = $exception->getCode();

		// Uses the translated internal Error ID message
		$message = $exception->getMessage();

		// Ldap Error ID
		$this->ldapCode = $exception->getLdapCode();

		// Ldap Error Description
		$this->ldapMessage = $exception->getLdapMessage();

		// Backtrace
		$this->backTrace = $exception->getBackTraceAsString();

		// File occurred
		$this->file = $exception->getFile();

		// Line occurred
		$this->line = $exception->getLine();

		parent::__construct($message, $priority, $category);
	}
}
