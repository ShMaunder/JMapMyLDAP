<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  User
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * A user helper class.
 *
 * @package     Shmanic.Libraries
 * @subpackage  User
 * @since       2.0
 */
abstract class SHUserHelper
{
	/**
	 * This method returns a user object. If options['autoregister'] is true,
	 * and if the user doesn't exist, then it'll be created.
	 *
	 * @param   array  $user     Holds the user data.
	 * @param   array  $options  Array holding options (remember, autoregister, group).
	 *
	 * @return  JUser|False  A JUser object containing the user or False on error.
	 *
	 * @since   1.0
	 */
	public static function getUser(array $user, $options = array())
	{
		$instance = JUser::getInstance();

		// Check if the user already exists in the database
		if ($id = intval(JUserHelper::getUserId($user['username'])))
		{
			$instance->load($id);
			return $instance;
		}

		jimport('joomla.application.component.helper');
		$config	= JComponentHelper::getParams('com_users', true);

		if ($config === false)
		{
			// Check if there is a default set in the SHConfig
			$config = SHFactory::getConfig();
			$defaultUserGroup = $config->get('user.defaultgroup', 2);
		}
		else
		{
			// Respect Joomla's default user group
			$defaultUserGroup = $config->get('new_usertype', 2);
		}

		// Setup the user fields for this new user
		$instance->set('id', 0);
		$instance->set('name', $user['fullname']);
		$instance->set('username', $user['username']);
		$instance->set('password_clear', $user['password_clear']);
		$instance->set('email', $user['email']);
		$instance->set('usertype', 'depreciated');
		$instance->set('groups', array($defaultUserGroup));

		// If autoregister is set, register the user
		$autoregister = isset($options['autoregister']) ? $options['autoregister'] : true;

		if ($autoregister)
		{
			if (!$instance->save())
			{
				// Failed to save the user to the database
				SHLog::add(
					JText::sprintf('LIB_SHUSERHELPER_ERR_10501', $user['username'], $instance->getError()), 10501, JLog::ERROR
				);
				return false;
			}
		}
		else
		{
			// We don't want to proceed if autoregister is not enabled
			SHLog::add(JText::sprintf('LIB_SHUSERHELPER_ERR_10502', $user['username']), 10502, JLog::ERROR);
			return false;
		}

		return $instance;
	}

	/**
	 * Get all Joomla user groups from the database.
	 *
	 * @return  array  Joomla user groups
	 *
	 * @since   1.0
	 */
	public static function getJUserGroups()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Build SQL to return both group IDs and Names/Titles
		$query->select('id')
			->from('#__usergroups')
			->order('id');

		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Add a group to a Joomla user.
	 *
	 * @param   JUser    &$user    User for group addition.
	 * @param   integer  $groupId  Joomla group ID.
	 *
	 * @return  mixed  Exception on errror
	 *
	 * @since   1.0
	 */
	public static function addUserToGroup(JUser &$user, $groupId)
	{
		$groupId = (int) $groupId;

		// Add the user to the group if necessary.
		if (!in_array($groupId, $user->groups))
		{
			// Get the title of the group.
			$db	= JFactory::getDbo();
			$query = $db->getQuery(true);

			$query->select('title')
				->from('#__usergroups')
				->where($query->quoteName('id') . '=' . $query->quote($groupId));

			$db->setQuery($query);

			$title = $db->loadResult();

			// If the group does not exist, throw an exception.
			if (!$title)
			{
				throw new Exception(JText::_('JLIB_USER_EXCEPTION_ACCESS_USERGROUP_INVALID'));
			}

			// Add the group data to the user object.
			$user->groups[$title] = $groupId;
		}

		return true;
	}

	/**
	 * Remove a group from a Joomla user.
	 *
	 * @param   JUser    &$user    The JUser for the group removal
	 * @param   integer  $groupId  The Joomla group ID to remove
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public static function removeUserFromGroup(JUser &$user, $groupId)
	{
		// Remove the user from the group if necessary.
		$key = array_search((int) $groupId, $user->groups);

		if ($key !== false)
		{
			// Remove the user from the group.
			unset($user->groups[$key]);
		}
	}
}
