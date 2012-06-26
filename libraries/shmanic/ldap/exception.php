<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 * 
 * @copyright   Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
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
class SHLdapException extends Exception
{
	const HELP_SITE = 'http://docs.shmanic.com/ldap/?error_id=%1$s&version=%2$s';

	const PROVIDE_ARGS_BACKTRACE = false;

	protected $ldapCode = null;

	protected $backTrace = array();

	public function __construct($ldapCode = null, $code = 0, $message = null)
	{
		$this->ldapCode = $ldapCode;

		self::PROVIDE_ARGS_BACKTRACE ?
			$this->backTrace = debug_backtrace(!DEBUG_BACKTRACE_IGNORE_ARGS) :
			$this->backTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

		parent::__construct($message, $code, null);
	}

	public function __toString()
	{
		if (is_null($this->ldapCode))
		{
			// Print without Ldap errors
			return sprintf(
				'Exception %1$d message \'%2$s\' in %3$s:%4$d',
				$this->getCode(), $this->getMessage(),
				$this->getFile(), $this->getLine()
			);
		}
		else
		{
			// Print to include Ldap errors
			return sprintf(
				'Exception %1$d message \'%2$s\' in %3$s:%4$d with Ldap error %5$d (%6$s).',
				$this->getCode(), $this->getMessage(),
				$this->getFile(), $this->getLine(),
				$this->getLdapCode(), $this->getLdapMessage()
			);
		}
	}

	final public function getLdapCode()
	{
		return $this->ldapCode;
	}

	final public function getLdapMessage()
	{
		if (!is_null($this->ldapCode))
		{
			// TODO: fix this
			return ldap_err2str($this->ldapCode);
		}

		return null;
	}

	final public function getHelpSite()
	{
		// Generate the help site
		return sprintf(self::HELP_SITE, $this->getCode(), SHLDAP_VERSION);
	}

	final public function getBackTrace()
	{
		return $this->backTrace;
	}

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
		return nl2br($result);
	}
}
