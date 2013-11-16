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
 * Attempts to match a user based on the supplied global server variable.
 *
 * @package     Shmanic.Plugin
 * @subpackage  SSO
 * @since       2.0
 */
class PlgSSOHTTP extends JPlugin
{
	/**
	 * This method checks if a value for remote user is present inside
	 * the $_SERVER array. If so then replace any domain related stuff
	 * to get the username and return it.
	 *
	 * @return  mixed  Username of detected user or False.
	 *
	 * @since   1.0
	 */
	public function detectRemoteUser()
	{
		/*
		 * When legacy flag is true, it ensures compatibility with JSSOMySite 1.x by
		 * only returning a string username or false can be returned. This also means
		 * keeping compatibility with Joomla 1.6.
		 * When it is set to False, it can return an array and compatible with Joomla 2.5.
		 */
		$legacy = $this->params->get('use_legacy', false);

		// Get the array key of $_SERVER where the user can be located
		$serverKey = strtoupper($this->params->get('userkey', 'REMOTE_USER'));

		// Get the $_SERVER key and ensure its lowercase and doesn't filter
		if ($legacy)
		{
			// Get the $_SERVER value which should contain the SSO username
			$remoteUser = JRequest::getVar($serverKey, null, 'server', 'string', JREQUEST_ALLOWRAW);
		}
		else
		{
			// Get the $_SERVER value which should contain the SSO username
			$input = new JInput($_SERVER);
			$remoteUser = $input->get($serverKey, null, 'USERNAME');
			unset($input);
		}

		// Ensures the returned user is lowercased
		$remoteUser = strtolower($remoteUser);

		// Get a username replacement parameter in lowercase and split by semi-colons
		$replace_set = explode(';', strtolower($this->params->get('username_replacement', '')));

		foreach ($replace_set as $replacement)
		{
			$remoteUser = str_replace(trim($replacement), '', $remoteUser);
		}

		// Returns the username
		return $remoteUser;
	}

	/**
	 * Attempts to clear HTTP SSO header.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function logoutRemoteUser()
	{
		if ($header = $this->params->get('clearHeader', false))
		{
			// Probably the best way to clear is by doing a 'Location: nobody@mysite.com'
			header($header);
		}
	}
}
