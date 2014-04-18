<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  SSO
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * An SSO helper class.
 *
 * @package     Shmanic.Libraries
 * @subpackage  SSO
 * @since       1.0
 */
abstract class SHSsoHelper
{
	/**
	 * IP rule defaults to deny.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	const RULE_DENY_ALL = 0;

	/**
	 * IP rule defaults to true.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	const RULE_ALLOW_ALL = 1;

	/**
	 * Session key to use for SSO status.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const SESSION_STATUS_KEY = 'sso_status';

	/**
	 * Used to specify if the SSO is disabled due to behaviour setting.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	const STATUS_BEHAVIOUR_DISABLED = 3;

	/**
	 * Session value for SSO disabled via manual bypass.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	const STATUS_BYPASS_DISABLE = 2;

	/**
	 * Session value for SSO disabled via logout.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	const STATUS_LOGOUT_DISABLE = 1;

	/**
	 * Session value for SSO enabled.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	const STATUS_ENABLE = 0;

	/**
	 * Session key to use for SSO plug-in.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const SESSION_PLUGIN_KEY = 'sso_plugin';

	/**
	 * Returns whether SSO is allowed to perform actions in the current session.
	 *
	 * @return  integer  True if session is enabled or False if SSO disabled.
	 *
	 * @since   1.0
	 */
	public static function status()
	{
		$config = SHFactory::getConfig();

		$behaviour = (int) $config->get('sso.behaviour', 1);

		$status = JFactory::getSession()->get(self::SESSION_STATUS_KEY, false);

		if ($status === false)
		{
			$status = self::STATUS_ENABLE;
		}

		$status = (int) $status;

		if ($status === self::STATUS_BYPASS_DISABLE)
		{
			if ($behaviour !== 1)
			{
				// Manual bypass is activated
				return self::STATUS_BYPASS_DISABLE;
			}
		}
		elseif ($behaviour === 2 || $behaviour === 0)
		{
			$formLogin = true;

			// Get the login tasks and check if username can be null to sso
			$tasks = json_decode($config->get('sso.logintasks', '[]'));
			$usernameField = $config->get('sso.checkusernull', true);

			// Check if the URL contains this key and the value assigned to it
			$input = new JInput;
			$task = $input->get('task', false);

			if ((!in_array($task, $tasks) || !JSession::checkToken()) || ($usernameField && $input->get('username', null)))
			{
				$formLogin = false;
			}

			if ($status === self::STATUS_LOGOUT_DISABLE)
			{
				// Logout bypass is activated
				if (!$formLogin)
				{
					return self::STATUS_LOGOUT_DISABLE;
				}
			}
			elseif ($status === self::STATUS_ENABLE)
			{
				if ($behaviour === 0 && !$formLogin)
				{
					return self::STATUS_BEHAVIOUR_DISABLED;
				}
			}
		}

		// Default to SSO enabled
		return self::STATUS_ENABLE;
	}

	/**
	 * Enable SSO for this browser.
	 *
	 * @param   string  $bypass  True if user bypass requested.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public static function enable($bypass = false)
	{
		$session = JFactory::getSession();

		if (!$bypass)
		{
			if ($session->get(self::SESSION_STATUS_KEY, false) == self::STATUS_BYPASS_DISABLE)
			{
				// We dont allow re-enable of manual bypass without the specific variable
				return;
			}
		}

		$session->clear(self::SESSION_STATUS_KEY);
	}

	/**
	 * Disables SSO for this browser.
	 *
	 * @param   string  $bypass  True if user bypass requested.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public static function disable($bypass = false)
	{
		$session = JFactory::getSession();

		// If this is a logout then we only want to set the session if bypass disable isn't enabled
		if ($bypass || $session->get(self::SESSION_STATUS_KEY, false) != self::STATUS_BYPASS_DISABLE)
		{
			JFactory::getSession()->set(self::SESSION_STATUS_KEY, $bypass ? self::STATUS_BYPASS_DISABLE : self::STATUS_LOGOUT_DISABLE);
		}
	}

	/**
	 * Redirects if a return is present and is an internal Joomla URL
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public static function redirect()
	{
		$input = new JInput;
		$return = $input->get('return', null, 'base64');

		if (!empty($return) && JUri::isInternal(base64_decode($return)))
		{
			$redirect = base64_decode($return);

			$app = JFactory::getApplication();
			$app->redirect($redirect);
		}
	}

	/**
	 * Do a IP range address check. There are four different
	 * types of ranges; single; wildcard; mask; section.
	 *
	 * @param   string   $ip      String of IP address to check.
	 * @param   array    $ranges  An array of IP range addresses.
	 * @param   boolean  $rule    IP mode (i.e. allow all except...).
	 *
	 * @return  boolean  True if IP is in range or False if not.
	 *
	 * @since   1.0
	 */
	public static function doIPCheck($ip, $ranges, $rule)
	{
		if (is_numeric($rule))
		{
			$rule = (int) $rule;

			if ($rule === 1)
			{
				$rule = self::RULE_ALLOW_ALL;
			}
			else
			{
				$rule = self::RULE_DENY_ALL;
			}
		}
		elseif (is_string($rule))
		{
			// Legacy rule conversion
			if (strtolower(trim($rule)) == 'allowall')
			{
				$rule = self::RULE_ALLOW_ALL;
			}
			else
			{
				$rule = self::RULE_DENY_ALL;
			}
		}

		// Check if the IP was in any of the ranges
		$return = SHUtilIp::check($ip, $ranges);

		return ($rule === self::RULE_ALLOW_ALL ? !$return : $return);
	}
}
