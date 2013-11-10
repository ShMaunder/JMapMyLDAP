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
	 * Cookie key to use for SSO status.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const COOKIE_STATUS_KEY = 'jmml_sso_status';

	const STATUS_BYPASS_DISABLE = -1;

	const STATUS_LOGOUT_DISABLE = -2;

	const STATUS_ENABLE = 1;

	/**
	 * Session key to use for SSO plug-in.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const SESSION_PLUGIN_KEY = 'sso_plugin';

	/**
	 * Set when cookie destory cannot happen in a single execution.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	protected static $statusNow = 0;

	/**
	 * When set to true, redirect after login.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected static $redirect = false;

	/**
	 * Returns whether SSO is allowed to perform actions in the current session.
	 *
	 * @return  integer  True if session is enabled or False if SSO disabled.
	 *
	 * @since   1.0
	 */
	public static function status()
	{
		if ((self::$statusNow != self::STATUS_ENABLE) && (self::$statusNow == self::STATUS_BYPASS_DISABLE
			|| (isset($_COOKIE[self::COOKIE_STATUS_KEY]) && $_COOKIE[self::COOKIE_STATUS_KEY] == self::STATUS_BYPASS_DISABLE)))
		{
			// Bypass is activated
			return self::STATUS_BYPASS_DISABLE;
		}

		if ((self::$statusNow != self::STATUS_ENABLE) && (self::$statusNow == self::STATUS_LOGOUT_DISABLE
			|| (isset($_COOKIE[self::COOKIE_STATUS_KEY]) && $_COOKIE[self::COOKIE_STATUS_KEY] == self::STATUS_LOGOUT_DISABLE)))
		{
			$config = SHFactory::getConfig();

			if (!$config->get('sso.ignoredisabled', false))
			{
				// Get the login tasks and check if username can be null to sso
				$tasks = json_decode($config->get('sso.logintasks', '[]'));
				$usernameField = $config->get('sso.checkusernull', true);

				// Check if the URL contains this key and the value assigned to it
				$input = new JInput;
				$task = $input->get('task', false);

				if ((!in_array($task, $tasks) || !JSession::checkToken()) || ($usernameField && $input->get('username', null)))
				{
					return self::STATUS_LOGOUT_DISABLE;
				}
			}

			self::$redirect = true;
			self::enable();
		}

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
		$config = JFactory::getConfig();
		$cookie_domain = $config->get('cookie_domain', '');
		$cookie_path = $config->get('cookie_path', '/');

		setcookie(self::COOKIE_STATUS_KEY, '', time() - 3600, $cookie_path, $cookie_domain);
		self::$statusNow = self::STATUS_ENABLE;
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
		// If this is a logout then we only want to set the cookie if bypass disable isn't enabled
		if ($bypass || !(isset($_COOKIE[self::COOKIE_STATUS_KEY]) && $_COOKIE[self::COOKIE_STATUS_KEY] == self::STATUS_BYPASS_DISABLE))
		{
			$config = JFactory::getConfig();
			$cookie_domain = $config->get('cookie_domain', '');
			$cookie_path = $config->get('cookie_path', '/');

			$value = $bypass ? self::STATUS_BYPASS_DISABLE : self::STATUS_LOGOUT_DISABLE;

			$time = (int) SHFactory::getConfig()->get('sso.cookietime', 3600);

			setcookie(self::COOKIE_STATUS_KEY, $value, time() + $time, $cookie_path, $cookie_domain);
			self::$statusNow = $value;
		}
	}

	/**
	 * Redirect internally when login success and task matches.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public static function redirect()
	{
		if (self::$redirect)
		{
			$input = new JInput;
			$return = $input->get('return', null, 'base64');

			if (empty($return) || !JUri::isInternal(base64_decode($return)))
			{
				$redirect = JURI::base();
			}
			else
			{
				$redirect = base64_decode($return);
			}

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
