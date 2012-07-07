<?php

/**
 * A Ldap group mapping helper for commiting groups to and from Joomla.
 *
 * @package		Shmanic.Ldap
 * @subpackage	Mapping
 * @since		2.0
 */
abstract class SHLdapMappingHelper
{
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
				->where($query->qn('id') . '=' . $query->q($groupId));

			$db->setQuery($query);

			try
			{
				$title = $db->loadResult();
			}
			catch (Exception $e)
			{
				return $e;
			}

			// If the group does not exist, return an exception.
			if (!$title)
			{
				return new Exception(JText::_('JLIB_USER_EXCEPTION_ACCESS_USERGROUP_INVALID'));
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

	/**
	 * Get all Joomla user groups from the database.
	 *
	 * @return  array|null  Joomla user groups or Null on failure.
	 *
	 * @since   1.0
	 */
	public static function getJUserGroups()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Build SQL to return both group IDs and Names/Titles
		$query->select('usrgrp.id, usrgrp.title')
			->from('#__usergroups AS usrgrp')
			->order('usrgrp.id');

		$db->setQuery($query);
		$groups = $db->loadAssocList('id');

		return $groups;
	}

	/**
	 * Get the Joomla groups to add.
	 *
	 * @param   JUser  $user     Specify user for adding groups.
	 * @param   array  $mapList  An array of matching group mapping entries.
	 *
	 * @return  array  An array of Joomla IDs to add.
	 *
	 * @since   1.0
	 */
	public static function getGroupsToAdd(JUser $user, array $mapList)
	{
		$addGroups = array();

		foreach ($mapList as $item)
		{
			foreach ($item->getGroups() as $group)
			{
				// Check if we've already got this on our add list
				if (!in_array($group, $addGroups))
				{
					// Check if the user already has this
					if (!in_array($group, $user->groups))
					{
						// Yes we want to add this group
						$addGroups[] = $group;
					}
				}
			}
		}

		return $addGroups;
	}

	/**
	 * Get the Joomla groups to remove.
	 *
	 * @param   JUser  $user     The JUser to remove groups from.
	 * @param   array  $mapList  An array of matching group mapping entries.
	 * @param   array  $managed  An array of managed groups.
	 *
	 * @return  array  Joomla IDs to remove.
	 *
	 * @since   1.0
	 */
	public static function getGroupsToRemove(JUser $user, array $mapList, array $managed)
	{
		$removeGroups = array();

		foreach ($user->groups as $JUserGroup)
		{
			// Check its in our managed pool
			if (in_array($JUserGroup, $managed))
			{
				// Check if we've already got this on our remove list
				if (!in_array($JUserGroup, $removeGroups))
				{
					foreach ($mapList as $item)
					{
						// Check that this user is not suppose to have this mapping
						if (in_array($JUserGroup, $item->getGroups()))
						{
							continue 2;
						}
					}

					// Yes we want to remove this group
					$removeGroups[] = $JUserGroup;
				}
			}
		}

		return $removeGroups;
	}

}
