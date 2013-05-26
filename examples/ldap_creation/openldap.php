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
final class LdapCreation_Openldap
{
	/**
	 * The name/key to use in the #__sh_config table for storing UID number.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const UID_NAME = 'ldap:uid';

	/**
	 * The starting UID number if one doesnt exist in the table.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const UID_DEFAULT = 1001;

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

	/**
	 * Gets the current UID number from the database table and returns it before incrementing
	 * the number in the database.
	 *
	 * @return  integer  The UID to use.
	 *
	 * @since   2.0
	 */
	public function getUidNumber()
	{
		// Get a new UID number and increment it in the table
		$db = JFactory::getDbo();

		$uid = $db->setQuery(
			$db->getQuery(true)
				->select($db->quoteName('value'))
				->from($db->quoteName('#__sh_config'))
				->where($db->quoteName('name') . ' = ' . $db->quote(self::UID_NAME))
		)->loadResult();

		if ($uid)
		{
			// Increment the UID
			$db->setQuery(
				$db->getQuery(true)
					->update($db->quoteName('#__sh_config'))
					->set(array($db->quoteName('value') . ' = ' . $db->quoteName('value') . ' + 1' ))
					->where($db->quoteName('name') . ' = ' . $db->quote(self::UID_NAME))
			)->loadResult();
		}
		else
		{
			// Insert the UID from the default+1
			$db->setQuery(
				$db->getQuery(true)
					->insert($db->quoteName('#__sh_config'))
					->columns(array($db->quoteName('name'), $db->quoteName('value')))
					->values($db->quote(self::UID_NAME) . ', ' . $db->quote(self::UID_DEFAULT + 1))
			)->loadResult();

			$uid = UID_DEFAULT;
		}

		return $uid;
	}
}
