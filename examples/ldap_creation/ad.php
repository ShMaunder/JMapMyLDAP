<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Examples
 * @subpackage  Ldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * LDAP creation helper file.
 *
 * @package     Shmanic.Examples
 * @subpackage  Ldap
 * @since       2.0
 */
final class LdapCreation_AD
{
	/**
	 * Splits the full name using a space and returns the first section.
	 * If no space is detected, then the whole name is returned.
	 *
	 * @param   string  $name  Full name.
	 *
	 * @return  string  First name.
	 *
	 * @since   2.0
	 */
	public function getFirstname($name)
	{
		if ($pos = strrpos($name, ' '))
		{
			// Space detected therefore return first section.
			return substr($name, 0, $pos);
		}

		return $name;
	}

	/**
	 * Splits the full name using a space and returns the last section.
	 * If no space is detected, then the whole name is returned.
	 *
	 * @param   string  $name  Full name.
	 *
	 * @return  string  Last name.
	 *
	 * @since   2.0
	 */
	public function getLastname($name)
	{
		// Get the last name (if no space then return whole name)
		return substr($name, strrpos($name, ' ') + 1);
	}
}
