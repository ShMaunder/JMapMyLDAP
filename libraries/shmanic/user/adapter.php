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
	 * @param   boolean  $authenticate  True to authenticate user with the driver source using the password supplied.
	 *
	 * @return  mixed  Unique identifier.
	 *
	 * @since   2.0
	 */
	public function getId($authenticate);

	/**
	 * Returns the type/name of this adapter.
	 *
	 * @param   string  $type  An optional string to compare against the adapter type.
	 *
	 * @return  string|false  Adapter type/name or False on non-matching parameter.
	 *
	 * @since   2.0
	 */
	public static function getType($type = null);

	/**
	 * Returns the domain or the configuration ID used for this specific user.
	 *
	 * @return  string  Domain or Configuration ID.
	 *
	 * @since   2.0
	 */
	public function getDomain();

	/**
	 * Return specified user attributes from the source.
	 *
	 * @param   string|array  $input    Optional string or array of attributes to return.
	 * @param   boolean       $null     Include null or non existent values.
	 * @param   boolean       $changes  Use the attribute changes (before change commit).
	 *
	 * @return  mixed  Ldap attribute results.
	 *
	 * @since   2.0
	 */
	public function getAttributes($input = null, $null = false, $changes = false);

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
	 * @param   boolean  $key      If true returns the key of the full name instead of value.
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
	 * @param   boolean  $key      If true returns the key of the Email instead of value.
	 * @param   mixed    $default  The default value.
	 *
	 * @return  mixed  Either the Key, Value or Default value.
	 *
	 * @since   2.0
	 */
	public function getEmail($key = false, $default = null);

	/**
	 * Return the users password for Joomla.
	 *
	 * @param   boolean  $key      If true returns the key of the password instead of value.
	 * @param   mixed    $default  The default value.
	 *
	 * @return  mixed  Either the Key, Value or Default value.
	 *
	 * @since   2.0
	 */
	public function getPassword($key = false, $default = null);

	/**
	 * Sets the users password.
	 *
	 * @param   string  $new           New password.
	 * @param   string  $old           Current password.
	 * @param   string  $authenticate  Authenticate the old password before setting new.
	 *
	 * @return  boolean  True on success or False on error.
	 *
	 * @since   2.0
	 */
	public function setPassword($new, $old = null, $authenticate = false);

	/**
	 * Updates the adapters stored password (this doesnt change anything in the driver source).
	 *
	 * @param   string  $password  New password to update.
	 * @param   array   $options   Optional array of options.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function updateCredential($password = null, $options = array());

	/**
	 * Sets new attributes for the user but doesnt commit to the driver.
	 *
	 * @param   array  $attributes  An array of the new/changed attributes for the object.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function setAttributes(array $attributes);

	/**
	 * Commits any changes to the user including attribute changes.
	 *
	 * @param   array  $options  Optional array of options.
	 *
	 * @return  SHAdapterResponseCommits  Stores all commit objects and status.
	 *
	 * @since   2.0
	 */
	public function commitChanges($options = array());

	/**
	 * Creates the user in the driver.
	 *
	 * @param   array  $options  Optional array of options.
	 *
	 * @return  SHAdapterResponseCommit  Stores the add commit object and status.
	 *
	 * @since   2.0
	 */
	public function create($options = array());

	/**
	 * Deletes the user in the driver.
	 *
	 * @param   array  $options  Optional array of options.
	 *
	 * @return  boolean  True on success or False on error.
	 *
	 * @since   2.0
	 */
	public function delete($options = array());
}
