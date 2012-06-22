<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Util
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Provides basic IP address helper methods.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Util
 * @since       2.0
 */
abstract class SHUtilIp
{
	/**
	 * Determine the type of each range entry in ranges. Then
	 * call the corresponding method. This method shall return
	 * true if any of the ranges match the ip.
	 *
	 * adapted from http://www.php.net/manual/en/function.ip2long.php#102898
	 *
	 * @param  $ip        string   String of IP address to check
	 * @param  $ranges    array    An array of IP range addresses
	 *
	 * @return  boolean  True means the ip is in the range
	 * @since   1.0
	 */
	public static function check($ip, $ranges)
	{
		if (!count($ranges) || $ranges[0]=='')
		{
			return false;
		}

		foreach ($ranges as $range)
		{
			$type = null;
			if(strpos($range, '*')) 		$type = 'wildcard';
			elseif(strpos($range, '/')) 	$type = 'mask';
			elseif(strpos($range, '-')) 	$type = 'section';
			elseif(ip2long($range)) 		$type = 'single';

			if ($type)
			{
				$sub_rst = call_user_func(array('self', '_sub_checker_' . $type), $range, $ip);

				if ($sub_rst)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Single IP address check (i.e. 192.168.0.2).
	 *
	 * adapted from http://www.php.net/manual/en/function.ip2long.php#102898
	 *
	 * @param  $allowed   string   A single IP address
	 * @param  $ip        string   String of IP address to check
	 *
	 * @return  boolean  True means the ip is matching
	 * @since   1.0
	 */
	protected static function _sub_checker_single($allowed, $ip)
	{
		return (ip2long($allowed) == ip2long($ip));
	}

	/**
	 * Wildcard IP address check (i.e. 192.168.0.*).
	 *
	 * adapted from http://www.php.net/manual/en/function.ip2long.php#102898
	 *
	 * @param  $allowed   string   A wildcard IP address
	 * @param  $ip        string   String of IP address to check
	 *
	 * @return  boolean  True means the ip is matching
	 * @since   1.0
	 */
	protected static function _sub_checker_wildcard($allowed, $ip)
	{
		$allowed_ip_arr = explode('.', $allowed);
		$ip_arr = explode('.', $ip);
		for ($i = 0;$i < count($allowed_ip_arr);$i++)
		{
			if ($allowed_ip_arr[$i] == '*')
			{
				return true;
			}

			if (false == ($allowed_ip_arr[$i] == $ip_arr[$i]))
			{
				return false;
			}
		}
	}

	/**
	 * Mask based IP address check (i.e. 192.168.0.0/24).
	 *
	 * adapted from http://www.php.net/manual/en/function.ip2long.php#102898
	 *
	 * @param  $allowed   string   A mask based IP address
	 * @param  $ip        string   String of IP address to check
	 *
	 * @return  boolean  True means the ip is matching
	 * @since   1.0
	 */
	protected static function _sub_checker_mask($allowed, $ip)
	{
		list($allowed_ip_ip, $allowed_ip_mask) = explode('/', $allowed);

		if ($allowed_ip_mask <= 0)
		{
			return false;
		}
		$ip_binary_string = sprintf("%032b", ip2long($ip));
		$net_binary_string = sprintf("%032b", ip2long($allowed_ip_ip));

		return (substr_compare($ip_binary_string, $net_binary_string, 0, $allowed_ip_mask) === 0);
	}

	/**
	 * Section based IP address check (i.e. 192.168.0.0-192.168.0.2).
	 *
	 * adapted from http://www.php.net/manual/en/function.ip2long.php#102898
	 *
	 * @param  $allowed   string   A section based IP address
	 * @param  $ip        string   String of IP address to check
	 *
	 * @return  boolean  True means the ip is matching
	 * @since   1.0
	 */
	protected static function _sub_checker_section($allowed, $ip)
	{
		list($begin, $end) = explode('-', $allowed);
		$begin = ip2long($begin);
		$end = ip2long($end);
		$ip = ip2long($ip);
		return ($ip >= $begin && $ip <= $end);
	}
}
