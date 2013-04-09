<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  User
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Interface class for an implementation of a user adapter.
 *
 * @package     Shmanic.Libraries
 * @subpackage  User
 * @since       2.0
 */
interface SHUserAdapter
{
	/**
	 * Class constructor.
	 *
	 * @param   array  $credentials  User credentials to use for this object.
	 * @param   mixed  $config       Configuration options for the driver library (e.g. ldap, xml).
	 * @param   array  $options      Extra options.
	 *
	 * @since   2.0
	 */
	public function __construct(array $credentials, $config = null, array $options = array());

	/**
	 * Get the drivers unique identifier of the user.
	 *
	 * @param   array  $authenticate  An array for storing user identifiable informatino such as username.
	 *
	 * @return  mixed  Unique identifier.
	 *
	 * @since   2.0
	 */
	public function getId($authenticate);

	/**
	 * Return specified user attributes from the source.
	 *
	 * @param   string|array  $input  Optional string or array of attributes to return.
	 * @param   boolean       $null   Include null or non existent values.
	 *
	 * @return  mixed  Ldap attribute results.
	 *
	 * @since   2.0
	 */
	public function getAttributes($input = null, $null = false);

	/**
	 * Return the users unique identifier for Joomla.
	 *
	 * @param   boolean  $key      If true returns the key of the UID instead of value.
	 * @param   mixed    $default  The default value.
	 *
	 * @return  mixed  Either the Key, Value or Default value.
	 *
	 * @since   2.0
	 */
	public function getUid($key = false, $default = null);

	/**
	 * Return the users full name for Joomla.
	 *
	 * @param   boolean  $key      If true returns the key of the UID instead of value.
	 * @param   mixed    $default  The default value.
	 *
	 * @return  mixed  Either the Key, Value or Default value.
	 *
	 * @since   2.0
	 */
	public function getFullname($key = false, $default = null);

	/**
	 * Return the users email for Joomla.
	 *
	 * @param   boolean  $key      If true returns the key of the UID instead of value.
	 * @param   mixed    $default  The default value.
	 *
	 * @return  mixed  Either the Key, Value or Default value.
	 *
	 * @since   2.0
	 */
	public function getEmail($key = false, $default = null);

	/**
	 * Updates the password of the user.
	 *
	 * @param   string  $new           New password.
	 * @param   string  $old           Current password.
	 * @param   string  $authenticate  Authenticate the old password before setting new.
	 *
	 * @return  boolean  True on success or False on error.
	 *
	 * @since   2.0
	 */
	public function updatePassword($new, $old = null, $authenticate = false);

	/**
	 * Sets new attributes for the user.
	 *
	 * @param   array  $attributes  An array of the new/changed attributes for the object.
	 *
	 * @return  boolean  True on success or False on error.
	 *
	 * @since   2.0
	 */
	public function setAttributes(array $attributes);
}
