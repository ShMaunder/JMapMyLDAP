<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * An Ldap group mapping class to initiate and get group mappings.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap
 * @since       2.0
 */
class SHLdapMapping extends JObject
{
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
	 * @var    string
	 * @since  1.0
	 */
	protected $removal = null;

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
	protected $publicid = 1;

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
	protected $dn_validate 	= true;

	/**
	 * Lookup type (reverse or forward)
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $lookup_type = null;

	/**
	 * Ldap attribute for the lookup (i.e. groupMembership, memberOf)
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $lookup_attribute = null;

	/**
	 * The user attribute to be used for group member lookup (i.e. dn, uid)
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $lookup_member = null;

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
	public $managed			= array();

	/**
	 * Class constructor.
	 *
	 * @param   array  $parameters  The LDAP Group Mapping parameters
	 *
	 * @since   1.0
	 */
	public function __construct($parameters)
	{
		$parameters = ($parameters instanceof JRegistry) ?
			$parameters->toArray() : $parameters;

		parent::__construct($parameters);

		$this->validate();

		// Load languages for errors
		$lang = JFactory::getLanguage();
		$lang->load('lib_ldap_mapping', JPATH_SITE);

	}

	/**
	 * Converts some of the parameters into a specific mask.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function validate()
	{
		/* Validate unmanaged groups parameter by splitting them into
		 * semi-colons, then removing white space and lastly check for
		 * a numeric value.
		 */
		$in = $this->unmanaged;
		$unmanaged = array();
		$tmp = explode(';', $in);

		foreach ($tmp as $entry)
		{
			if ($entry = (int) $entry)
			{
				$unmanaged[] = $entry;
			}
		}
		$this->set('unmanaged', $unmanaged);

		/**
		 * Validate the group mapping list parameter by splitting them into newlines,
		 * then ensuring that each entry contains a colon.
		 */
		$in = $this->list;
		$list = array();
		$tmp = explode("\n", $in);

		foreach ($tmp as $entry)
		{
			if (!empty($entry) && strrpos($entry, ':') > 0)
			{
				$list[] = $entry;
			}
		}
		$this->set('list', $list);

	}

	/**
	 * Get any extra data from LDAP that is not returned from the
	 * authentication read.
	 *
	 * @param   SHUserAdapter  $adapter   User adapter of LDAP user.
	 * @param   array          &$details  An array of LDAP attributes that have already been returned.
	 * @param   array          $options   An array of options.
	 *
	 * @return  boolean  Returns true on success.
	 *
	 * @since   2.0
	 */
	public function getData($adapter, &$details = array(), $options = array())
	{
		$attributes = array();
		$return		= array();
		$groups		= array();

		/* Firstly, we need to check if there are any initial groups discovered
		 * if a forward lookup is being used. If not then we need to find these
		 * initial groups first.
		 */
		if ($this->lookup_type == 'forward')
		{
			/*
			 * Attempt to do a forward lookup if the Ldap user group attributes are
			 * not present. Though in most cases, they should be present.
			 */
			if (!isset($details[$this->lookup_attribute]))
			{
				// We cannot get any more information if there is no source user DN
				if (is_null($adapter->getId(false)))
				{
					return false;
				}

				// Add to the user attribute request for an Ldap read
				$attributes[] = $this->lookup_attribute;
				$return = $attributes;

			}
			else
			{
				if (!count($details[$this->lookup_attribute]))
				{
					// There are no groups to process for this user (or the parameter was set incorrectly)
					return true;
				}

				// Yes we have groups already, we just need to check for recursion if required laters
				$groups = $details[$this->lookup_attribute];

			}
		}
		else
		{
			// Attempt to do a reverse lookup
			$return[] = $this->lookup_attribute;

			if (!isset($details[$this->lookup_member]) || is_null($this->lookup_member))
			{
				// We cannot get any more information if there is no source user DN
				if (is_null($adapter->getId(false)))
				{
					return false;
				}

				$attributes[] = $this->lookup_member;
			}
		}

		// Lets get our result ready
		$return = array_fill_keys($return, null);
		$result = null;

		if (count($attributes))
		{
			// Get our ldap user attributes and check we have a valid result
			$result	= $adapter->client->read($adapter->getId(false), null, $attributes);
			if ($result === false)
			{
				// No user attributes found on the read
				$this->setError(JText::_('LIB_LDAPMAPPING_ERROR_NO_ATTRIBUTES'));
				return false;
			}
		}

		if (!count($groups))
		{
			// Need to process first level user groups from the ldap result
			if ($this->lookup_type == 'forward')
			{
				// Forward lookup: all we need is the user group values
				$groups = $result->getAttribute(0, $this->lookup_attribute, array());
			}
			else
			{
				// Reverse lookup: have to find the groups with the user dn present
				$lookupValue = is_null($result) ? $details[$this->lookup_member][0] :
					$result->getValue(0, $this->lookup_member, 0);

				// Build the search filter for this
				$search = $this->lookup_attribute . '=' . $lookupValue;
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
				$outcome = array();

				if ($this->lookup_type == 'reverse')
				{
					// We need to override the lookup attribute for reverse recursion
					$adapter->client->getRecursiveGroups(
						$groups, $this->recursion_depth, $outcome, 'dn', $this->lookup_attribute
					);
				}
				else
				{
					$adapter->client->getRecursiveGroups(
						$groups, $this->recursion_depth, $outcome, $this->lookup_attribute, $this->dn_attribute
					);
				}

				// We need to merge them back together without duplicates
				$groups = array_unique(array_merge($groups, $outcome));
		}

		// Lets store the groups
		$groups = array_values($groups);
		$details[$this->lookup_attribute] = $groups;

		return true;
	}

	/**
	 * Get an array of the required attributes to be processed by
	 * the Ldap server only when getting the user.
	 *
	 * @return  array  An array of the attributes required from the Ldap server
	 *
	 * @since   2.0
	 */
	public function getAttributes()
	{
		$return = array($this->lookup_member);

		if ($this->lookup_type == 'forward')
		{
			$return[] = $this->lookup_attribute;
		}

		return $return;
	}

	/**
	 * Commit the mapping depending on the parameters specified. This includes
	 * processing the group mapping list, adding joomla groups and removing
	 * joomla groups.
	 *
	 * @param   JUser          &$user    A JUser object for the joomla user to be processed.
	 * @param   SHUserAdapter  $adapter  User adapter of LDAP user.
	 *
	 * @return  boolean  Returns true on success.
	 *
	 * @since   1.0
	 */
	public function doMap(JUser &$user, $adapter)
	{
		// Get the distinguished name of the user
		$dn = $adapter->getId(false);

		// Get all the Joomla user groups
		if (is_null($JUserGroups = SHLdapMappingHelper::getJUserGroups()))
		{
			$this->setError(JText::_('Failed to retrieve Joomla user groups'));
			return false;
		}

		/*
		 * Process the map list parameter into validated entries.
		 * Then ensure that there is atleast 1 valid entry to
		 * proceed with the mapping process.
		 */
		if (!count($mapList = $this->processList($JUserGroups)))
		{
			$this->setError(JText::_('LIB_LDAPMAPPING_ERROR_NO_MAPPING_PARAMETERS'));
			return false;
		}

		/*
		 * Process the ldap attributes created from the source ldap
		 * user into mapping entries, then evaulate which groups
		 * are of interest when compared to the parameter list.
		 */
		$userGroups = JArrayHelper::getValue($adapter->getAttributes($this->lookup_attribute), $this->lookup_attribute);
		if (is_array($userGroups) && count($userGroups))
		{
			$ldapUser = new SHLdapMappingEntry($dn, $userGroups, $this->dn_validate);
		}
		else
		{
			$this->setError(JText::_('LIB_LDAPMAPPING_ERROR_NO_GROUPS'));
			return false;
		}

		// Do the actual compare
		$mapList = SHLdapMappingEntry::compareGroups($mapList, $ldapUser);

		// Check if add groups are allowed
		if ($this->addition)
		{
			/*
			 * Find the groups that require adding then add them to the JUser
			 * instance. Any errors will results in the entire method failing.
			 */
			$toAdd = SHLdapMappingHelper::getGroupsToAdd($user, $mapList);

			foreach ($toAdd as $group)
			{
				$result = SHLdapMappingHelper::addUserToGroup($user, $group);

				if ($result instanceof Exception)
				{
					// An error has occurred while adding groups!
					$this->setError((string) $result);
					return false;
				}
			}
		}

		// Check if removal of groups are allowed by ensure the value isn't no
		if ($this->removal != 'no')
		{
			/*
			 * Find the groups that require removing then remove them from the
			 * JUser instance.
			 */
			$toRemove = SHLdapMappingHelper::getGroupsToRemove($user, $mapList, $this->managed);

			foreach ($toRemove as $group)
			{
				SHLdapMappingHelper::removeUserFromGroup($user, $group);
			}
		}

		/* If we have no groups left in our user then we must add
		 * the public group otherwise Joomla won't save the changes
		 * to the database.
		 */
		if (!count($user->get('groups')))
		{
			SHLdapMappingHelper::addUserToGroup($user, $this->publicid);
		}

		return true;
	}

	/**
	 * Process the group mapping list by splitting each entry from the
	 * format DN:1;2;3;* into a MappingEntry which then is added to a
	 * returned array.
	 *
	 * @param   array  $JUserGroups  Joomla user groups.
	 *
	 * @return  array[SHLdapMappingEntry]  Array of valid entries.
	 *
	 * @since   1.0
	 */
	public function processList(array $JUserGroups)
	{
		$list = array();

		// Loops around each mapping entry parameter
		foreach ($this->list as $entry)
		{
			// Remove any accidental whitespace from the entry
			$entry = trim($entry);

			// Find the right most (outside of the distinguished name) to split groups
			$colonPosition = strrpos($entry, ':');

			// Store distinguished name in a string and Joomla groups in an array
			$entryDN		= substr($entry, 0, $colonPosition);
			$entryGroups	= explode(',', substr($entry, $colonPosition + 1));

			$groups = array();

			/*
			 * Now we have our Joomla group IDs ($entryGroups) in an array,
			 * we must check if they are valid Joomla groups by comparing them
			 * against the ones from the database.
			 */
			foreach ($entryGroups as $group)
			{
				$group = (int) $group;

				if (isset($JUserGroups[$group]))
				{
					$groups[] = $group;
				}
			}

			// Save our validated groups back to the original variable
			$entryGroups = $groups;

			/*
			 * Add the entry to a new mapping entry object then check if it is
			 * valid. If so then we can assume this entry has no syntax errors.
			 */
			if (count($groups) > 0)
			{
				$newEntry = new SHLdapMappingEntry($entryDN, $entryGroups, $this->dn_validate);

				if ($newEntry->isValid())
				{
					$this->addManagedGroups($entryGroups, $JUserGroups);
					$list[] = $newEntry;
				}
			}

		}

		return $list;
	}

	/**
	 * Add a managed group to the managed class variable if the
	 * 'default all' option is not enabled. If the 'default all'
	 * option is enabled then add all the joomla groups to the
	 * variable (checks are done to ensure this is only done once).
	 *
	 * @param   array  $groups       An array of joomla group IDs to be managed
	 * @param   array  $JUserGroups  An array of all the Joomla groups
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function addManagedGroups($groups, $JUserGroups)
	{
		$managed		= $this->removal;
		$unmanaged		= $this->unmanaged;

		if ($managed == 'yesdefault' && count($this->managed) == 0)
		{
			// Yesdefault means we want to add all Joomla groups to the managed pool
			foreach ($JUserGroups as $group)
			{
				if (!in_array($group['id'], $unmanaged))
				{
					$this->managed[] = ($group['id']);
				}
			}
		}
		elseif ($managed == 'yes')
		{
			// Yes means we want to add only groups that are defined in the mapping list
			foreach ($groups as $group)
			{
				if (!in_array($group, $unmanaged) && !in_array($group, $this->managed))
				{
					// This is a managed group so lets add it
					$this->managed[] = $group;
				}
			}
		}
	}

	/**
	 * TODO: fix the migration
	 *
	 * Get an array of all the nested groups through the use of group recursion.
	 * This is required usually only for Active Directory, however there could
	 * be other LDAP platforms that cannot pick up nested groups.
	 *
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
	public function getRecursiveGroups($searchDNs, $depth, &$result, $attribute, $queryAttribute = null)
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
		$results = $this->search(null, $search, array($attribute));

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
			$this->getRecursiveGroups($next, $depth, $result, $attribute, $queryAttribute);
		}

		return $result;
	}

}
