<?php

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
