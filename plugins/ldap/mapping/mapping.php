<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Plugin
 * @subpackage  Ldap.Mapping
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.plugin.plugin');

/**
 * LDAP Group Mapping Plugin
 *
 * @package     Shmanic.Plugin
 * @subpackage  Ldap.Mapping
 * @since       2.0
 */
class PlgLdapMapping extends JPlugin
{
	const LOOKUP_FORWARD = 0;
	const LOOKUP_REVERSE = 1;

	const NO = 0;
	const YES = 1;
	const YESDEFAULT = 2;

	/**
	 * Allow groups to sync back to Ldap.
	 *
	 * @var    boolean
	 * @since  2.0
	 */
	protected $sync_groups = false;

	/**
	 * Allow joomla group additions to users
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $addition = false;

	/**
	 * Allow joomla group removal from users and default state
	 * of groups
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $removal = self::YESDEFAULT;

	/**
	 * Unmanaged joomla group IDs seperated by a semi-colon
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $unmanaged = null;

	/**
	 * Public group ID
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $public_id = 1;

	/**
	 * Holds the entries for the group mapping list
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $list = array();

	/**
	 * When set to true each DN is processed with ldap_explode_dn()
	 *
	 * @var    boolean
	 * @since  2.0
	 */
	protected $dn_validate = true;

	/**
	 * Lookup type (reverse or forward)
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $lookup_type = self::LOOKUP_FORWARD;

	/**
	 * Ldap attribute for the lookup (i.e. groupMembership, memberOf)
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $memberof_attribute = null;

	/**
	 * The group attribute that holds members (i.e. member).
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $member_attribute = null;

	/**
	 * The user attribute to be used for group member lookup (i.e. dn, uid)
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $member_dn = null;

	/**
	 * Use recursion
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $recursion = false;

	/**
	 * The dn attribute key name
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $dn_attribute = null;

	/**
	 * Max depth for recursion
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $recursion_depth = null;

	/**
	 * Holds an array of managed joomla IDs
	 *
	 * @var    array
	 * @since  1.0
	 */
	public $managed = array();

	/**
	 * Holds an array of SHLdapMappingEntry.
	 *
	 * @var    array[SHLdapMappingEntry]
	 * @since  2.0
	 */
	public $entries = array();

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe.
	 * @param   array   $config    An array that holds the plugin configuration.
	 *
	 * @since  2.0
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	/**
	 * Method is called before user data is stored in the database.
	 *
	 * @param   array    $user   Holds the old user data.
	 * @param   boolean  $isNew  True if a new user is stored.
	 * @param   array    $new    Holds the new user data.
	 *
	 * @return  boolean  Cancels the save if False.
	 *
	 * @since   2.0
	 */
	public function onUserBeforeSave($user, $isNew, $new)
	{
		if (!$this->doSetup() || $isNew)
		{
			return;
		}

		try
		{
			// Check that if any groups have changed for the user
			if ($this->sync_groups && isset($new['groups']) && isset($user['groups']))
			{
				// Get the difference in the Joomla user groups from old and new
				$addedGroups = array_diff($new['groups'], $user['groups']);
				$removedGroups = array_diff($user['groups'], $new['groups']);

				$this->doGroupSync($user['username'], $addedGroups, $removedGroups);
			}
		}
		catch (Exception $e)
		{
			SHLog::add($e, 12022, JLog::ERROR, 'ldap');
		}
	}

	/**
	 * Method is called after user data is stored in the database.
	 *
	 * @param   array    $user     Holds the new user data.
	 * @param   boolean  $isNew    True if a new user has been stored.
	 * @param   boolean  $success  True if user was successfully stored in the database.
	 * @param   string   $msg      An error message.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onUserAfterSave($user, $isNew, $success, $msg)
	{
		if (!$this->doSetup())
		{
			return;
		}

		try
		{
			if ($isNew && $success && isset($user['groups']))
			{
				$this->doGroupSync($user['username'], $user['groups']);
			}
		}
		catch (Exception $e)
		{
			SHLog::add($e, 12023, JLog::ERROR, 'ldap');
		}
	}

	/**
	 * Adds and Removes LDAP groups to and from a LDAP user.
	 * This is based on the mapping list which maps Group DNs to
	 * Joomla group IDs.
	 *
	 * @param   string  $username      Username of the Ldap user.
	 * @param   array   $addGroups     Array of Joomla group IDs to add.
	 * @param   array   $removeGroups  Array of Joomla group IDs to remove.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 * @throws  Exception
	 */
	protected function doGroupSync($username, $addGroups, $removeGroups = array())
	{
		// Only allow Managed groups to be added or removed from Ldap
		$addedGroups = array_intersect($addGroups, $this->managed);
		$removedGroups = array_intersect($removeGroups, $this->managed);

		// Check if a group has changed
		if (count($addedGroups) || count($removedGroups))
		{
			$groupsToAdd = array();
			$groupsToRemove = array();

			// Loop each DN in the list
			foreach ($this->list as $dn => $groups)
			{
				// Loop each JGroup in this specific list
				foreach ($groups as $group)
				{
					if (in_array($group, $addedGroups))
					{
						// Add this Group to the user
						$groupsToAdd[] = $dn;
					}

					if (in_array($group, $removedGroups))
					{
						// Remove this Group from the user
						$groupsToRemove[] = $dn;
					}
				}
			}

			// Check whether we have any syncing to do
			if (count($groupsToAdd) || count($groupsToRemove))
			{
				$attribute = $this->member_attribute;

				$adapter = SHFactory::getUserAdapter($username);
				$adapter->getId(false);

				$tmp = $adapter->getAttributes($this->member_dn);

				if (isset($tmp[$this->member_dn][0]) && !empty($tmp[$this->member_dn][0]))
				{
					// This should contain the User DN
					$memberDn = $tmp[$this->member_dn][0];

					// We are going to be dealing with LDAP directly right now
					$ldap = $adapter->client;
					$ldap->proxyBind();

					if ($this->addition)
					{
						foreach ($groupsToAdd as $group)
						{
							try
							{
								// Get the current assigned users for this group
								$groupDetails = $ldap->read($group, null, array($attribute));

								$users = $groupDetails->getAttribute(0, $attribute);

								// Check to ensure the group is not empty or user is already part of it
								if (($groupDetails->countValues(0, $attribute) === 0)
									|| (array_search($memberDn, $users) === false))
								{
									$users[] = $memberDn;
									$ldap->replaceAttributes($group, array($attribute => $users));
									SHLog::add(JText::sprintf('PLG_LDAP_MAPPING_INFO_12038', $username, $group), 12038, JLog::INFO, 'ldap');
								}
							}
							catch (Exception $e)
							{
								// An error occurred trying to add the group
								SHLog::add(JText::sprintf('PLG_LDAP_MAPPING_ERR_12034', $group, $username), 12034, JLog::ERROR, 'ldap');
								SHLog::add($e, 12034, JLog::ERROR, 'ldap');
							}
						}
					}

					if ($this->removal)
					{
						foreach ($groupsToRemove as $group)
						{
							try
							{
								// Get the current assigned users for this group
								$users = $ldap->read($group, null, array($attribute))->getAttribute(0, $attribute);

								// Index of the array at which the value exists;
								$key = array_search($memberDn, $users);

								// Check to ensure the attribute exists
								if ($key !== false)
								{
									// Remove it and reorder as its a LDAP modify operation
									unset($users[$key]);
									$users = array_values($users);

									$ldap->replaceAttributes($group, array($attribute => $users));
									SHLog::add(JText::sprintf('PLG_LDAP_MAPPING_INFO_12039', $username, $group), 12039, JLog::INFO, 'ldap');
								}
							}
							catch (Exception $e)
							{
								// An error occurred trying to remove the group
								SHLog::add(JText::sprintf('PLG_LDAP_MAPPING_ERR_12036', $group, $username), 12036, JLog::ERROR, 'ldap');
								SHLog::add($e, 12036, JLog::ERROR, 'ldap');
							}
						}
					}
				}
				else
				{
					// Invalid member_dn parameter
					SHLog::add(JText::sprintf('PLG_LDAP_MAPPING_ERR_12031', $username), 12031, JLog::ERROR, 'ldap');
				}
			}
		}
	}

	/**
	 * Called during an ldap login.
	 *
	 * Checks to ensure the onlogin parameter is true then calls the on sync method.
	 *
	 * @param   JUser  &$instance  A JUser object for the authenticating user.
	 * @param   array  $options    Array holding options.
	 *
	 * @return  boolean  False to cancel login
	 *
	 * @since   2.0
	 */
	public function onUserLogin(&$instance, $options = array())
	{
		if ($this->params->get('onlogin'))
		{
			$result = $this->onLdapSync($instance, $options);

			if (!$this->params->get('abort_login') && $result === false)
			{
				// Abort login is disabled and the plugin failed - but lets not cancel login
				return;
			}

			return $result;
		}
	}

	/**
	 * Called during a ldap synchronisation.
	 *
	 * Checks to ensure that required variables are set before calling the main
	 * do mapping library routine.
	 *
	 * @param   JUser  &$instance  A JUser object for the authenticating user.
	 * @param   array  $options    Array holding options.
	 *
	 * @return  boolean  True on success
	 *
	 * @since   2.0
	 */
	public function onLdapSync(&$instance, $options = array())
	{
		if (!$this->doSetup())
		{
			return;
		}

		try
		{
			// Gather the user adapter
			$username = $instance->username;
			$adapter = SHFactory::getUserAdapter($username);

			// Get the distinguished name of the user
			$dn = $adapter->getId(false);

			/*
			 * Process the ldap attributes created from the source ldap
			 * user into mapping entries, then evaulate which groups
			 * are of interest when compared to the parameter list.
			 */
			$attribute = $this->lookup_type === self::LOOKUP_FORWARD ? $this->memberof_attribute : $this->member_attribute;
			$attributes = $adapter->getAttributes($attribute);
			$userGroups = SHUtilArrayhelper::getValue(
				$attributes, $attribute
			);

			if (!(is_array($userGroups) && count($userGroups)))
			{
				// No groups to process
				SHLog::add(JText::sprintf('PLG_LDAP_MAPPING_DEBUG_12008', $username), 12008, JLog::DEBUG, 'ldap');

				return;
			}

			$ldapUser = new SHLdapMappingEntry($dn, $userGroups, $this->dn_validate);

			// Do the actual compare
			$mapList = SHLdapMappingEntry::compareGroups($this->entries, $ldapUser);

			$changes = false;

			// Check if add groups are allowed
			if ($this->addition)
			{
				/*
				 * Find the groups that require adding then add them to the JUser
				 * instance. Any errors will results in the entire method failing.
				 */
				$toAdd = self::getGroupsToAdd($instance, $mapList);

				foreach ($toAdd as $group)
				{
					SHLog::add(JText::sprintf('PLG_LDAP_MAPPING_DEBUG_12011', $group, $username), 12011, JLog::DEBUG, 'ldap');
					SHUserHelper::addUserToGroup($instance, $group);
					$changes = true;
				}
			}

			// Check if removal of groups are allowed
			if ($this->removal !== self::NO)
			{
				/*
				 * Find the groups that require removing then remove them from the
				 * JUser instance.
				 */
				$toRemove = self::getGroupsToRemove($instance, $mapList, $this->managed);

				foreach ($toRemove as $group)
				{
					SHLog::add(JText::sprintf('PLG_LDAP_MAPPING_DEBUG_12013', $group, $username), 12013, JLog::DEBUG, 'ldap');
					SHUserHelper::removeUserFromGroup($instance, $group);
					$changes = true;
				}
			}

			/* If we have no groups left in our user then we must add
			 * the public group otherwise Joomla won't save the changes
			 * to the database.
			 */
			if (!count($instance->get('groups')))
			{
				SHUserHelper::addUserToGroup($instance, $this->public_id);
				$changes = true;
			}

			if ($changes)
			{
				return true;
			}

			return;
		}
		catch (Exception $e)
		{
			SHLog::add($e, 12021, JLog::ERROR, 'ldap');

			return false;
		}
	}

	/**
	 * Method to initialise all the properties based on the parameters
	 * specified in the plugin.
	 *
	 * @return  boolean  True on valid entries in the mapping list.
	 *
	 * @since   2.0
	 */
	protected function doSetup()
	{
		static $done = null;

		if (is_null($done))
		{
			// Assign class properties based on parameters from the plugin
			$this->sync_groups = (bool) $this->params->get('sync_groups', false);
			$this->addition = (bool) $this->params->get('addition', true);
			$this->removal = (int) $this->params->get('removal', self::YESDEFAULT);
			$this->unmanaged = array_map('intval', explode(';', $this->params->get('unmanaged')));
			$this->public_id = (int) $this->params->get('public_id');

			$this->dn_validate = $this->params->get('dn_validate', 1);

			$this->lookup_type = (int) $this->params->get('lookup_type', self::LOOKUP_FORWARD);
			$this->memberof_attribute = $this->params->get('memberof_attribute');
			$this->member_attribute = $this->params->get('member_attribute', 'member');
			$this->member_dn = $this->params->get('member_dn', 'dn');

			$this->recursion = (bool) $this->params->get('recursion', false);
			$this->dn_attribute = $this->params->get('dn_attribute', 'distinguishedName');
			$this->recursion_depth = (int) $this->params->get('recursion_depth', 0);

			$this->entries = array();
			$this->list = array();
			$list = preg_split('/\r\n|\n|\r/', $this->params->get('list'));

			// Loops around each mapping entry parameter
			foreach ($list as $item)
			{
				// Remove any accidental whitespace from the entry
				$item = trim($item);

				// Find the right most (outside of the distinguished name) to split groups
				if ($pos = strrpos($item, ':'))
				{
					// Store distinguished name in a string and Joomla groups in an array
					$entryDn = trim(substr($item, 0, $pos));

					if ($entryGroups = array_map('intval', explode(',', substr($item, $pos + 1))))
					{
						// Store as a parameter for validation later
						$this->list[$entryDn] = $entryGroups;
					}
				}
			}

			// Get all the Joomla user groups
			$JUserGroups = SHUserHelper::getJUserGroups();
			$JUserGroupsKey = array_fill_keys($JUserGroups, '');

			/*
			 * Process the map list parameter into validated entries.
			 * Then ensure that there is atleast 1 valid entry to
			 * proceed with the mapping process.
			 */
			foreach ($this->list as $dn => $groups)
			{
				foreach ($groups as $key => $group)
				{
					if (!isset($JUserGroupsKey[$group]))
					{
						// This isn't a valid Joomla group
						unset($this->list[$dn][$key]);
						continue;
					}
				}

				if (empty($this->list[$dn]))
				{
					// This DN doesn't have any valid Joomla groups
					unset($this->list[$dn]);
					continue;
				}

				/*
				 * Add the entry to a new mapping entry object then check if it is
				 * valid. If so then we can assume this entry has no syntax errors.
				 */
				$entry = new SHLdapMappingEntry($dn, $this->list[$dn], $this->dn_validate);

				if ($entry->isValid())
				{
					// Add as a valid entry
					$this->entries[] = $entry;

					// Add as a managed group
					if ($this->removal === self::YES)
					{
						/*
						 * Yes means we want to add only groups that are defined in the mapping list.
						 * Looping around dn group parameter list, ensuring its not already there and not in unmanaged.
						 */
						$this->managed = array_merge(
							$this->managed,
							array_diff(array_diff($this->list[$dn], $this->unmanaged), $this->managed)
						);
					}
				}
			}

			if ($this->removal === self::YESDEFAULT)
			{
				// Yesdefault means we want to add all Joomla groups to the managed pool
				$this->managed = array_diff($JUserGroups, $this->unmanaged);
			}

			$done = true;

			if (!count($this->entries))
			{
				// No valid entries here
				SHLog::add(JText::_('PLG_LDAP_MAPPING_DEBUG_12006'), 12006, JLog::DEBUG, 'ldap');
				$done = false;
			}
		}

		return $done;
	}

	/**
	 * Called before a user LDAP read to gather extra user ldap attribute keys
	 * required for this plugin to function correctly.
	 *
	 * @param   SHUserAdapter  $adapter  The current user adapter.
	 * @param   array          $options  Array holding options.
	 *
	 * @return  array  Array of attributes
	 *
	 * @since   2.0
	 */
	public function onLdapBeforeRead($adapter, $options = array())
	{
		if (!$this->doSetup())
		{
			return;
		}

		// Make sure we get the value used to query groups if required
		$return = array($this->member_dn);

		// We can only process forward requests as the groups are stored in the user object
		if ($this->lookup_type === self::LOOKUP_FORWARD)
		{
			$return[] = $this->memberof_attribute;
		}

		return $return;
	}

	/**
	 * Called after a user LDAP read to gather extra ldap attribute values that
	 * were not included in the initial read.
	 *
	 * @param   SHUserAdapter  $adapter      The current user adapter.
	 * @param   array          &$attributes  Discovered User Ldap attribute keys=>values.
	 * @param   array          $options      Array holding options.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onLdapAfterRead($adapter, &$attributes, $options = array())
	{
		if (!$this->doSetup())
		{
			return;
		}

		$details	= array();
		$return		= array();
		$groups		= array();

		/* Firstly, we need to check if there are any initial groups discovered
		 * if a forward lookup is being used. If not then we need to find these
		 * initial groups first.
		 */
		if ($this->lookup_type === self::LOOKUP_FORWARD)
		{
			/*
			 * Attempt to do a forward lookup if the Ldap user group attributes are
			 * not present. Though in most cases, they should be present.
			 */
			if (!isset($attributes[$this->memberof_attribute]))
			{
				// We cannot get any more information if there is no source user DN
				if (is_null($adapter->getId(false)))
				{
					return false;
				}

				// Add to the user attribute request for an Ldap read
				$details[] = $this->memberof_attribute;
				$return = $details;
			}
			else
			{
				if (!count($attributes[$this->memberof_attribute]))
				{
					// There are no groups to process for this user (or the parameter was set incorrectly)
					return true;
				}

				// Yes we have groups already, we just need to check for recursion if required laters
				$groups = $attributes[$this->memberof_attribute];
			}
		}
		else
		{
			// Attempt to do a reverse lookup
			$return[] = $this->member_attribute;

			// The following will only execute if the attributes doesnt have what is required for reverse lookup
			if (!isset($attributes[$this->member_dn]) || is_null($this->member_dn))
			{
				// We cannot get any more information if there is no source user DN
				if (is_null($adapter->getId(false)))
				{
					return false;
				}

				// This will indicate another ldap read later
				$details[] = $this->member_dn;
			}
		}

		// Lets get our result ready
		$return = array_fill_keys($return, null);
		$result = null;

		if (count($details))
		{
			// Get our ldap user attributes and check we have a valid result
			$result	= $adapter->client->read($adapter->getId(false), null, $details);
		}

		if (!count($groups))
		{
			// Need to process first level user groups from the ldap result
			if ($this->lookup_type === self::LOOKUP_FORWARD)
			{
				// Forward lookup: all we need is the user group values
				$groups = $result->getAttribute(0, $this->memberof_attribute, array());
			}
			else
			{
				// Reverse lookup: have to find the groups with the user dn present
				$lookupValue = is_null($result) ? $attributes[$this->member_dn][0] :
					$result->getValue(0, $this->member_dn, 0);

				// Build the search filter for this
				$search = $this->member_attribute . '=' . $lookupValue;
				$search = SHLDAPHelper::buildFilter(array($search));

				// Find all the groups that have this user present as a member
				if (($reverse = $adapter->client->search(null, $search, array('dn'))) !== false)
				{
					for ($i = 0; $i < $reverse->countEntries(); ++$i)
					{
						// Extract the group distinguished name from the result
						$groups[] = $reverse->getDN($i);
					}
				}
			}
		}

		// Need to process the recursion using the initial groups as a basis
		if ($this->recursion && count($groups))
		{
			// Check if the Adapter is LDAP based
			if ($adapter::getType('LDAP'))
			{
				$outcome = array();

				if ($this->lookup_type === self::LOOKUP_REVERSE)
				{
					// We need to override the lookup attribute for reverse recursion
					self::getRecursiveGroups(
						$adapter->client, $groups, $this->recursion_depth, $outcome, 'dn', $this->member_attribute
					);
				}
				else
				{
					self::getRecursiveGroups(
						$adapter->client, $groups, $this->recursion_depth, $outcome, $this->memberof_attribute, $this->dn_attribute
					);
				}

				// We need to merge them back together without duplicates
				$groups = array_unique(array_merge($groups, $outcome));
			}
		}

		// Lets store the groups
		$groups = array_values($groups);
		$attributes[$this->lookup_type === self::LOOKUP_FORWARD ? $this->memberof_attribute : $this->member_attribute] = $groups;

		return true;
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

	/**
	 * Get an array of all the nested groups through the use of group recursion.
	 * This is required usually only for Active Directory, however there could
	 * be other LDAP platforms that cannot pick up nested groups.
	 *
	 * @param   shldap  &$ldap           Reference to the LDAP object.
	 * @param   array   $searchDNs       Initial user groups (or ones that have already been discovered).
	 * @param   string  $depth           How far to search down until it should give up (0 means unlimited).
	 * @param   array   &$result         Holds the result of every alliteration (by reference).
	 * @param   string  $attribute       The LDAP attribute to store after each ldap search.
	 * @param   string  $queryAttribute  The LDAP filter attribute to query.
	 *
	 * @return  array  All user groups including the initial user groups.
	 *
	 * @since   1.0
	 * @throws  SHLdapException
	 */
	public static function getRecursiveGroups(SHLdap &$ldap, $searchDNs, $depth, &$result, $attribute, $queryAttribute = null)
	{
		$search		= null;
		$next		= array();
		$filters	= array();

		// As this is recursive, we want to be able to specify a optional depth
		--$depth;

		if (!isset($searchDNs))
		{
			return $result;
		}

		foreach ($searchDNs as $dn)
		{
			// Build one or more partial filters from the DN user groups
			$filters[] = $queryAttribute . '=' . $dn;
		}

		if (!count($filters))
		{
			// If there is no filter to process then we are finished
			return $result;
		}

		// Build the full filter using the OR operator
		$search = SHLdapHelper::buildFilter($filters, '|');

		// Search for any groups that also contain the groups we have in the filter
		$results = $ldap->search(null, $search, array($attribute));

		// Lets process each group that was found
		$entryCount = $results->countEntries();

		for ($i = 0; $i < $entryCount; ++$i)
		{
			$dn = $results->getDN($i);

			// We don't want to re-process a group that was processed previously
			if (!in_array($dn, $result))
			{
				$result[] = $dn;

				// Check if there are more groups we should process from the groups just discovered
				$valueCount = $results->countValues($i, $attribute);

				for ($j = 0; $j < $valueCount; ++$j)
				{
					// We want to process this object
					$value = $results->getValue($i, $attribute, $j);
					$next[] = $value;
				}
			}
		}

		/*
		 * Only start the recursion when we have something to process next
		 * otherwise, we would loop forever.
		 */
		if (count($next) && $depth != 0)
		{
			self::getRecursiveGroups($ldap, $next, $depth, $result, $attribute, $queryAttribute);
		}

		return $result;
	}
}

/**
 * Holds each Ldap entry with its associated Joomla group. This
 * class also contains methods for comparing entries.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap.Mapping
 * @since       2.0
 */
class SHLdapMappingEntry extends JObject
{
	/**
	 * An array of RDNs to form the DN
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $rdn = array();

	/**
	 * The original unaltered dn
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $dn = false;

	/**
	 * Valid entry
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $valid = false;

	/**
	 * Contains either ldap group memberships or joomla group id's
	 * depending on this instance
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $groups = array();

	/**
	 * Class constructor.
	 *
	 * @param   string   $dn          The dn thats to hold the associated groups
	 * @param   array    $groups      The assocaited groups of the dn
	 * @param   boolean  $validateDN  When set to true, the DN is processed with ldap_explode_dn() else a simple string comparison is used
	 *
	 * @since   1.0
	 */
	public function __construct($dn, $groups = array(), $validateDN = true)
	{
		if (empty($dn) || !is_string($dn))
		{
			return;
		}

		if ($validateDN)
		{
			/*
			 * Breaks up the DN into RDNs - in this plugin, this
			 * method is only used when validateDN is true.
			 */
			$explode = SHLdap::explodeDn($dn, 0);

			if (SHUtilArrayhelper::getValue($explode, 'count', false))
			{
				// Break up the distinguished name into an array and lowercase it
				$this->rdn = array_map('strToLower', $explode);
			}
			else
			{
				// Invalid distinguished name
				return;
			}
		}

		$this->dn 		= $dn;
		$this->groups 	= array_map('strToLower', $groups);
		$this->valid	= true;
	}

	/**
	 * Return the groups class variable.
	 *
	 * @return  array  Array of groups.
	 *
	 * @since   1.0
	 */
	public function getGroups()
	{
		return $this->groups;
	}

	/**
	 * Return the RDN class variable.
	 *
	 * @return  array  RDNs to form the DN.
	 *
	 * @since   1.0
	 */
	public function getRDN()
	{
		return $this->rdn;
	}

	/**
	 * Return the DN class variable.
	 *
	 * @return  string  Unaltered full DN.
	 *
	 * @since   1.0
	 */
	public function getDN()
	{
		return $this->dn;
	}

	/**
	 * Return whether this entry is valid.
	 *
	 * @return  boolean  Valid entry.
	 *
	 * @since   2.0
	 */
	public function isValid()
	{
		return $this->valid;
	}

	/**
	 * Compares all the group mapping entries to all the ldap user
	 * groups and returns an array of JMapMyEntry parameters that
	 * match.
	 *
	 * @param   Array               &$params      An array of JMapMyEntry's for the group mapping list
	 * @param   SHLdapMappingEntry  &$ldapGroups  A JMapMyEntry object to the ldap user groups
	 *
	 * @return  Array  An array of JMapMyEntry parameters that match
	 *
	 * @since   1.0
	 */
	public static function compareGroups(array &$params, self &$ldapGroups)
	{
		$result = array();

		// Compare an entire array of DNs in $entries
		foreach ($params as $parameter)
		{
			// This is the set of group mapping that the user set
			if (self::compareGroup($parameter, $ldapGroups))
			{
				$result[] = $parameter;
			}
		}

		return $result;
	}

	/**
	 * Compare the DN in the parameter against the groups in
	 * the ldap groups. This is used to compare if one of the
	 * group mapping list dn entries matches any of the ldap user
	 * groups and if so returns true.
	 *
	 * @param   self  $parameter    Group mapping list parameters.
	 * @param   self  &$ldapGroups  Ldap user groups.
	 *
	 * @return  Boolean  True on parameter entry is in the ldap user group.
	 *
	 * @since   1.0
	 */
	public static function compareGroup(self $parameter, self &$ldapGroups)
	{
		$matches = array();

		if ($parameter->dn === false || $ldapGroups->dn === false)
		{
			// Distinguished Name has invalid syntax
			return false;
		}

		foreach ($ldapGroups->groups as $ldapGroup)
		{
			/*
			 * If there is currently no RDNs (i.e. non validated DN)
			 * then we will use a simple string comparison.
			 */
			if (count($parameter->rdn))
			{
				$explode = SHLdap::explodeDn($ldapGroup, 0);

				if (is_array($explode) && count($explode))
				{
					// We need to convert to lower because escape characters return with uppercase hex ascii codes
					$explode = array_map('strToLower', SHLdap::explodeDn($ldapGroup, 0));

					if (self::compareValidatedDN($parameter->rdn, $explode))
					{
						return true;
					}
				}
			}
			else
			{
				// Simple string comparison instead of the validated DN method
				if (strToLower(trim($ldapGroup)) == strToLower(trim($parameter->dn)))
				{
					return true;
				}
			}
		}
	}

	/**
	 * Compare a exploded DN array to another DN array to see if
	 * it matches. Source is suppose to be a parameter whereas
	 * compare is suppose to be a ldap group. This is used to
	 * compare if a dn in the group mapping list matches a dn
	 * from the Ldap directory.
	 *
	 * @param   Array  $source   The source dn (e.g. group mapping list parameter entry).
	 * @param   Array  $compare  The comparasion dn (e.g. ldap group).
	 *
	 * @return  Boolean  Returns the comparasion result.
	 *
	 * @since   1.0
	 */
	public static function compareValidatedDN($source, $compare)
	{
		if (count($source) == 0 || count($compare) == 0 || $source['count'] > $compare['count'])
		{
			return false;
		}

		/* lets start checking each RDN from left to right to see
		 * if it matches. This would have to be changed if we
		 * wanted to also check from right to left.
		 */
		for ($i = 0; $i < $source['count']; ++$i)
		{
			if ($source[$i] != $compare[$i])
			{
				return false;
			}
		}

		return true;
	}
}
