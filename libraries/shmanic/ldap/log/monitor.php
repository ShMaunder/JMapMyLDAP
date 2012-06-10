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
 * A built in log monitor for debugging purposes only.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap.Log
 * @since       2.0
 */
class SHLdapLogMonitor extends JEvent
{

	protected $enabled = false;

	protected $level = null;

	public function __construct(&$subject, $config)
	{

		if (isset($config['enabled']))
		{
			$this->enabled = $config['enabled'];
		}

		if (isset($config['level']))
		{
			$this->level = $config['level'];
		}

		$this->setup();

		parent::__construct($subject);

	}

	protected function setup()
	{
		if (!$this->enabled)
		{
			return;
		}

		// Setup the new on-screen logger
		JLog::addLogger(
			array('logger' => 'messagequeue'),
			$this->level,
			array('ldap')
		);

	}

	public function onLogDebug($message)
	{
		JLog::add($message, JLog::DEBUG, 'ldap');
	}

	public function onError()
	{
		//JLog::add($message, JLog::ERROR, 'ldap');
	}

	public function onInformation()
	{
		//JLog::add($message, JLog::INFO, 'ldap');
	}

}
