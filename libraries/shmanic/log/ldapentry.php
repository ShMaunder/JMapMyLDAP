<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Log
 *
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.utilities.date');
jimport('shmanic.client.jldap2');

/**
 * Joomla! Log Entry class
 *
 * This class is designed to hold log entries for either writing to an engine, or for
 * supported engines, retrieving lists and building in memory (PHP based) search operations.
 *
 * @package     Joomla.Platform
 * @subpackage  Log
 * @since       11.1
 */
class JLogEntryLdapEntry extends JLogEntry
{
	
	public $ldap = null;
	
	public $class = null;

	/**
	 * Constructor
	 *
	 * @param   string  $message   The message to log.
	 * @param   string  $priority  Message priority based on {$this->priorities}.
	 * @param   string  $category  Type of entry
	 * @param   string  $date      Date of entry (defaults to now if not specified or blank)
	 *
	 * @since   11.1
	 */
	public function __construct($message, $class, $priority = JLog::INFO, $date = null)
	{
		if(substr($message, -1) != '.') {
			$message .= '.';
		}
		
		$this->class = $class;
		
		$ldap = JLDAP2::getInstance();
		$this->ldap = $ldap->getError();
		
		if(!$this->ldap) $this->ldap = 'N/A';
		if(substr($this->ldap, -1) != '.') {
			$this->ldap .= '.';
		}
		
		parent::__construct($message, $priority, 'ldap', $date);
	}
}
