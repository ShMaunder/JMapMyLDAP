<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap.Event
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * A global LDAP event monitor.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap.Event
 * @since       2.0
 */
class SHLdapEventMonitor extends JEvent
{

	public function onAfterInitialise()
	{
		SHLog::add(__METHOD__ . ' called.', 0, JLog::DEBUG, 'ldap');
	}

	public function onUserBeforeDelete($user)
	{
		SHLog::add(__METHOD__ . ' called.', 0, JLog::DEBUG, 'ldap');
	}

	public function onUserAfterDelete($user, $success, $msg)
	{
		SHLog::add(__METHOD__ . ' called.', 0, JLog::DEBUG, 'ldap');
	}

	public function onUserBeforeSave($user, $isNew, $new)
	{
		SHLog::add(__METHOD__ . ' called.', 0, JLog::DEBUG, 'ldap');
	}

	public function onUserAfterSave($user, $isNew, $success, $msg)
	{
		SHLog::add(__METHOD__ . ' called.', 0, JLog::DEBUG, 'ldap');
	}

	public function onUserLogin($user, $options = array())
	{
		/**
		 * Set a user parameter to distinguish the authentication type even
		 * when the user is not logged in.
		 */
		SHLdapHelper::setUserLdap($user);

		SHLog::add(__METHOD__ . ' called.', 0, JLog::DEBUG, 'ldap');
	}

	public function onUserLogout($user, $options = array())
	{
		SHLog::add(__METHOD__ . ' called.', 0, JLog::DEBUG, 'ldap');
	}

	public function onUserLoginFailure($response)
	{
		SHLog::add(__METHOD__ . ' called.', 0, JLog::DEBUG, 'ldap');
	}
}
