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
 * LDAP creation helper file for Active Directory.
 *
 * @package     Shmanic.Examples
 * @subpackage  Ldap
 * @since       2.0
 */
final class LdapCreation_AD
{
	/**
	 * Returns the distinguished name.
	 *
	 * @param   array  $form  Registration form.
	 *
	 * @return  string  Distinguished name.
	 *
	 * @since   2.0
	 */
	public function getMandatoryDN($form)
	{
		$name = SHLdapHelper::escape($form['name'], true);

		return "CN={$name},OU=People,DC=shmanic,DC=net";
	}

	/**
	 * Returns the first name.
	 *
	 * @param   array  $form  Registration form.
	 *
	 * @return  string  Last name.
	 *
	 * @since   2.0
	 */
	public function getGivenName($form)
	{
		return $this->genFirstname($form['name']);
	}

	/**
	 * Returns the last name.
	 *
	 * @param   array  $form  Registration form.
	 *
	 * @return  string  Last name.
	 *
	 * @since   2.0
	 */
	public function getSn($form)
	{
		return $this->genLastname($form['name']);
	}

	/**
	 * Returns the correct userPrincipalName.
	 *
	 * @param   array  $form  Registration form.
	 *
	 * @return  string  userPrincipalName.
	 *
	 * @since   2.0
	 */
	public function getUserPrincipalName($form)
	{
		return $form['username'] . '@shmanic.net';
	}

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
	protected function genFirstname($name)
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
	protected function genLastname($name)
	{
		// Get the last name (if no space then return whole name)
		return substr($name, strrpos($name, ' ') + 1);
	}

	/**
	 * Method is called after the user is created in LDAP. This can be used to run external
	 * scripts (such as creating home directories) and/or adding groups to the new user.
	 *
	 * @param   array          $form        Values directly from the user registration form.
	 * @param   array          $attributes  The attributes passed to the LDAP server for creation.
	 * @param   SHUserAdapter  $adapter     The user adapter object.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onAfterCreation($form, $attributes, $adapter)
	{
	}
}
