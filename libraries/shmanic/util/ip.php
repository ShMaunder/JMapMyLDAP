<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Util
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Provides basic IP address helper methods.
 * This class was adapted from http://www.php.net/manual/en/function.ip2long.php#102898.
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
	 * @param   string  $ip      String of IP address to check
	 * @param   array   $ranges  An array of IP range addresses
	 *
	 * @return  boolean  True means the ip is in the range
	 *
	 * @since   1.0
	 */
	public static function check($ip, $ranges)
	{
		if (!is_array($ranges) && !count($ranges))
		{
			return false;
		}

		foreach ($ranges as $range)
		{
			if (empty($range))
			{
				// This IP range contains nothing
				continue;
			}

			// Trim any whitespace for the range
			$range = trim($range);

			/*
			 * Detect the type of range this IP address is then
			 * execute the correct sub routine to identify whether
			 * the IP is in the range.
			 */
			if (strpos($range, '*'))
			{
				$result = self::check_wildcard($range, $ip);
			}
			elseif (strpos($range, '/'))
			{
				$result = self::_sub_checker_mask($range, $ip);
			}
			elseif (strpos($range, '-'))
			{
				$result = self::_sub_checker_section($range, $ip);
			}
			elseif (ip2long($range))
			{
				$result = self::_sub_checker_single($range, $ip);
			}

			if ($result)
			{
				// Match found
				return true;
			}
		}

		// No matches
		return false;
	}

	/**
	 * Single IP address check (i.e. 192.168.0.2).
	 *
	 * @param   string  $allowed  A single IP address
	 * @param   string  $ip       String of IP address to check
	 *
	 * @return  boolean  True means the ip is matching
	 *
	 * @since   1.0
	 */
	protected static function _sub_checker_single($allowed, $ip)
	{
		return (ip2long($allowed) == ip2long($ip));
	}

	/**
	 * Wildcard IP address check (i.e. 192.168.0.*).
	 *
	 * @param   string  $allowed  A wildcard IP address
	 * @param   string  $ip       IP address to check
	 *
	 * @return  boolean  True means the ip is matching
	 *
	 * @since   1.0
	 */
	protected static function check_wildcard($allowed, $ip)
	{
		$allowed_ip_arr = explode('.', $allowed);
		$ip_arr = explode('.', $ip);

		// For each dot inside the allowed ip address
		for ($i = 0; $i < count($allowed_ip_arr); ++$i)
		{
			// Look out for the occurence of the wildcard
			if ($allowed_ip_arr[$i] == '*')
			{
				return true;
			}

			// Check if the IP address matches up to this point
			if ($allowed_ip_arr[$i] != $ip_arr[$i])
			{
				return false;
			}
		}
	}

	/**
	 * Mask based IP address check (i.e. 192.168.0.0/24).
	 *
	 * @param   string  $allowed  A mask based IP address
	 * @param   string  $ip       IP address to check
	 *
	 * @return  boolean  True means the ip is matching
	 *
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
	 * @param   string  $allowed  A section based IP address
	 * @param   string  $ip       IP address to check
	 *
	 * @return  boolean  True means the ip is matching
	 *
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
