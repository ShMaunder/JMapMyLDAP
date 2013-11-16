<?php
/**
 * PHP Version 5.3
 *
 * ============== Original based on JAuthTools ===============
 * http://joomlacode.org/gf/project/jauthtools
 * Sam Moffatt <sam.moffatt@toowoombarc.qld.gov.au>
 * Toowoomba Regional Council Information Management Department
 * (C) 2008 Toowoomba Regional Council/Sam Moffatt
 * ============================================================
 *
 * @package     Shmanic.Plugin
 * @subpackage  SSO
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.plugin.plugin');

/**
 * Attempts to match a user based on their network address attribute (IP Address).
 *
 * @package     Shmanic.Plugin
 * @subpackage  SSO
 * @since       2.0
 */
class PlgSSOEDirLDAP extends JPlugin
{
	/**
	 * Gets the IP address of the client machine, translates it to a compatiable
	 * eDirectory netadress and queries it against the LDAP server using a filter.
	 *
	 * @return  mixed  Username of detected user or False.
	 *
	 * @since   1.0
	 */
	public function detectRemoteUser()
	{
		// Import languages for frontend errors
		$this->loadLanguage();

		/*
		 * When legacy flag is true, it ensures compatibility with JSSOMySite 1.x by
		 * only returning a string username or false can be returned. This also means
		 * keeping compatibility with Joomla 1.6.
		 * When it is set to False, it can return an array and compatible with Joomla 2.5.
		 */
		$legacy = $this->params->get('use_legacy', false);

		if ($legacy)
		{
			// Use legacy way of getting paramters
			$authParams = new JRegistry;

			$authName = $this->params->get('auth_plugin', 'jmapmyldap');
			$authPlugin = JPluginHelper::getPlugin('authentication', $authName);
			$authParams->loadString($authPlugin->params);

			$ldapUid = $authParams->get('ldap_uid', 'uid');

			// Attempt to load up a LDAP instance using the legacy method
			jimport('shmanic.jldap2');
			$ldap = new JLDAP2($authParams);

			// Lets try to bind using proxy user
			if (!$ldap->connect() || !$ldap->bind($ldap->connect_username, $ldap->connect_password))
			{
				JError::raiseWarning('SOME_ERROR_CODE', JText::_('PLG_EDIR_ERROR_LDAP_BIND'));

				return;
			}

			// Get IP of client machine
			$myip = JRequest::getVar('REMOTE_ADDR', 0, 'server');

			// Convert this to some net address thing that edir likes
			$na = JLDAPHelper::ipToNetAddress($myip);

			// Find the network address and return the uid for it
			$filter = "(networkAddress=$na)";

			$dn = $authParams->get('base_dn');

			// Do the LDAP filter search now
			$result = new JLDAPResult($ldap->search($dn, $filter, array($ldapUid)));
			$ldap->close();
		}
		else
		{
			try
			{
				// We will only check the first LDAP config
				$ldap = SHLdap::getInstance();
				$ldap->proxyBind();

				$ldapUid = $ldap->getUid;

				// Get the IP address of this client and convert to netaddress for LDAP searching
				$input = new JInput($_SERVER);
				$myIp = $input->get('REMOTE_ADDR', false, 'string');

				$na = SHLdapHelper::ipToNetAddress($myIp);

				$result = $ldap->search(null, "(networkAddress={$na})", array($ldapUid));
			}
			catch (Exception $e)
			{
				SHLog::add($e, 16010, JLog::ERROR, 'sso');

				return;
			}
		}

		if ($value = $result->getValue(0, $ldapuid, 0))
		{
			// Username was found logged in on this client machine
			return $value;
		}
	}
}
