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
	 * Session key to use.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const SESSION_KEY = 'nosso';

	/**
	 * Returns whether SSO is disabled for the current session.
	 *
	 * @return  boolean  True if session is disabled or False if SSO allowed.
	 *
	 * @since   1.0
	 */
	public static function isDisabled()
	{
		$session = JFactory::getSession();

		return $session->get(self::SESSION_KEY, false);
	}

	/**
	 * Enable SSO for this session.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public static function enableSession()
	{
		$session = JFactory::getSession();
		$session->clear(self::SESSION_KEY);
	}

	/**
	 * Disables SSO for this session.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public static function disableSession()
	{
		$session = JFactory::getSession();
		$session->set(self::SESSION_KEY, 1);
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
