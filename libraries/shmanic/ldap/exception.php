<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * LDAP Exception class to hold LDAP error codes.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap
 * @since       2.0
 */
class SHLdapException extends RuntimeException
{
	/**
	 * URL to a help site with %1 being the Error ID and %2 being the version.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const HELP_SITE = 'http://docs.shmanic.com/ldap/?error_id=%1$s&version=%2$s';

	/**
	 * Records the parameter/argument for each method call within backtrace.
	 * This is disabled by default for security reasons (i.e. LDAP password disclosure).
	 *
	 * @var    boolean
	 * @since  2.0
	 */
	const PROVIDE_ARGS_BACKTRACE = false;

	/**
	 * Holds the Ldap error ID.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	protected $ldapCode = null;

	/**
	 * Holds the backtrace.
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $backTrace = array();

	/**
	 * Constructor
	 *
	 * @param   integer  $ldapCode  Ldap Error ID.
	 * @param   integer  $code      Error ID.
	 * @param   string   $message   Error message.
	 *
	 * @since   2.0
	 */
	public function __construct($ldapCode = null, $code = 0, $message = null)
	{
		$this->ldapCode = $ldapCode;

		self::PROVIDE_ARGS_BACKTRACE ?
			$this->backTrace = debug_backtrace(!DEBUG_BACKTRACE_IGNORE_ARGS) :
			$this->backTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

		parent::__construct($message, $code, null);

		if (!is_null($this->ldapCode))
		{
			// Update the message to include the Ldap error
			$this->message = sprintf(
				'%1$s (%2$d) %3$s.',
				$this->getMessage(), $this->getLdapCode(), $this->getLdapMessage()
			);
		}
	}

	/**
	 * Returns the Ldap Error ID.
	 *
	 * @return  integer  Ldap Error ID.
	 *
	 * @since   2.0
	 */
	final public function getLdapCode()
	{
		return $this->ldapCode;
	}

	/**
	 * Converts the Ldap Error ID into a readable message.
	 *
	 * @return  string  Error Message.
	 *
	 * @since   2.0
	 */
	final public function getLdapMessage()
	{
		if (!is_null($this->ldapCode))
		{
			// Convert ID to string
			return SHLdap::errorToString($this->ldapCode);
		}

		return null;
	}

	/**
	 * Gets the help link for this specific error.
	 *
	 * @return  string  URL to Help Site.
	 *
	 * @since   2.0
	 */
	final public function getHelpSite()
	{
		// Generate the help site
		return sprintf(self::HELP_SITE, $this->getCode(), SHLDAP_VERSION);
	}

	/**
	 * Return the backtrace.
	 *
	 * @return  array  Backtrace.
	 *
	 * @since   2.0
	 */
	final public function getBackTrace()
	{
		return $this->backTrace;
	}

	/**
	 * Converts the backtrace into a string then returns it.
	 *
	 * @return  string  Backtrace to string.
	 *
	 * @since   2.0
	 */
	final public function getBackTraceAsString()
	{
		$result = null;

		foreach ($this->backTrace as $a => $b)
		{
			$result .= "#{$a} ";

			foreach ($b as $c => $d)
			{
				$result .= "[{$c}] ";
				$result .= var_export($d, true);
				$result .= "\n";
			}

			$result .= "\n\n";
		}

		return $result;
	}
}
