<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  SSO
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * A built in SSO event monitor.
 *
 * @package     Shmanic.Libraries
 * @subpackage  SSO
 * @since       2.0
 */
class SHSsoMonitor extends JEvent
{
	/**
	 * Called on application initialisation.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onAfterInitialise()
	{
		if (!JFactory::getUser()->get('id'))
		{
			// There is no current user logged in so attempt SSO
			$this->_attemptSSO();
			return;
		}
	}

	/**
	 * Method for attempting single sign on.
	 *
	 * @return  boolean  True on successful SSO or False on failure.
	 *
	 * @since   2.0
	 */
	protected function _attemptSSO()
	{
		// Check the required SSO libraries exist
		if (!(class_exists('SHSsoHelper') && class_exists('SHSso')))
		{
			// Error: classes missing
			SHLog::add(JText::_('LIB_SHSSOMONITOR_ERR_15001'), 15001, JLog::ERROR, 'sso');
			return;
		}

		$config = SHFactory::getConfig();

		// Get the bypass key
		$bypass = $config->get('sso.bypasskey');

		// Check if URL bypassing is enabled
		if ($config->get('sso.urlbypass', false))
		{
			// Check if the URL contains this key and the value assigned to it
			$input = new JInput;
			$value = $input->get($bypass, false);

			// Check whether the url has been set
			if ($value !== false)
			{
				if ($value == 0)
				{
					// Enable the SSO in the session
					SHSsoHelper::enableSession();
				}
				elseif ($value == 1)
				{
					// Disable the SSO in the session
					SHSsoHelper::disableSession();
					return;
				}
			}
		}

		// Check if SSO is disabled via the session
		if (SHSsoHelper::isDisabled())
		{
			// It is disabled so do not continue
			return;
		}

		/*
		 * Lets check the IP rule is valid before we continue -
		 * if the IP rule is false then SSO is not allowed here.
		 */
		jimport('joomla.application.input');
		$input = new JInput($_SERVER);

		// Get the IP address of this client
		$myIp = $input->get('REMOTE_ADDR', false, 'string');

		// Get a list of the IP addresses specific to the specified rule
		$ipList = json_decode($config->get('sso.iplist'));

		// Get the rule value
		$ipRule = $config->get('sso.iprule', SHSsoHelper::RULE_ALLOW_ALL);

		if (!SHSsoHelper::doIPCheck($myIp, $ipList, $ipRule))
		{
			// This IP isn't allowed
			return;
		}

		/*
		 * We are going to check if we are in backend.
		 * If so then we need to check if sso is allowed
		 * to execute on the backend.
		 */
		if (JFactory::getApplication()->isAdmin())
		{
			if (!$config->get('sso.backend', false))
			{
				// Not allowed to SSO on backend
				return;
			}
		}

		// Instantiate the main SSO library for detection & authentication
		$sso = new SHSso(
			$config->get('sso.plugintype', 'sso')
		);

		// Attempt the login
		return $sso->login();
	}
}
