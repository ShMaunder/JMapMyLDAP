<?php
/**
 * @version     $Id:$
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @package     Shmanic.Ldap
 * @subpackage  Mapping
 *
 * @copyright	Copyright (C) 2011 Shaun Maunder. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.version');

/**
 * A Ldap group mapping class to initiate and get group mappings.
 *
 * @package		Shmanic.Ldap
 * @subpackage	Mapping
 * @since		1.0
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
	 * @param  JLDAP2  &$ldap     An active JLDAP2 object connected to LDAP server
	 * @param  array   &$details  An array of LDAP attributes that have already been returned
	 * @param  array   $options   An array of options. The dn element must be set.
	 *
	 * @return  boolean  Returns true on success
	 * @since   2.0
	 */
	public function getData(&$ldap, &$details = array(), $options = array())
	{

		$attributes = array();
		$return		= array();
		$groups		= array();

		/* Firstly, we need to check if there are any initial groups discovered
		 * if a forward lookup is being used. If not then we need to find these
		 * initial groups first.
		 */
		if($this->lookup_type == 'forward') {

			// Forward Lookup
			if(!isset($details[$this->lookup_attribute])) {
				// Need to attempt to do a forward lookup

				// We cannot get any more information if there is no user dn
				if(!isset($options['dn']) || is_null($options['dn'])) return true;

				$attributes[] = $this->lookup_attribute;
				$return = $attributes;


			} else {
				if(count($details[$this->lookup_attribute])) {
					// Yes we have groups already, we just need to check for recursion later on
					$groups = $details[$this->lookup_attribute];
				} else {
					// There are no groups
					return true;
				}
			}

		} else {
			// Reverse Lookup
			$return[] = $this->lookup_attribute;

			if(!isset($details[$this->lookup_member]) || is_null($this->lookup_member)) {
				// We cannot get any more information if there is no user dn
				if(!isset($options['dn']) || is_null($options['dn'])) return true;

				$attributes[] = $this->lookup_member;
			}
		}


		$return = array_fill_keys($return, null); //lets get our result ready
		$result = null;
		if(count($attributes)) {
			//get our ldap user attributes and check we have a valid result
			$result	= new JLDAPResult($ldap->read($options['dn'], null, $attributes));
			if(is_null($result->getValue(0,'dn',0))) {
				$this->setError(JText::_('LIB_LDAPMAPPING_ERROR_NO_ATTRIBUTES')); //TODO: language file
				return true;
			}
		}

		if(!count($groups)) {
			//need to process first level user groups from the ldap result
			if($this->lookup_type == 'forward') {
				$groups		= $result->getAttribute(0, $this->lookup_attribute, true);
			} else {
				$lookupValue = is_null($result) ? $details[$this->lookup_member] :
					$result->getValue(0, $this->lookup_member, 0);

				$search = $this->lookup_attribute . '=' . $lookupValue;
				$search = JLDAPHelper::buildFilter(array($search));
				$reverse = new JLDAPResult($ldap->search(null, $search, array('dn')));
				for($i=0;$i<$reverse->countEntries();$i++) {
					$groups[] = $reverse->getValue($i,'dn',0); //extract the group from the result
				}
			}
		}

		//need to process the recursion using the initial groups as a basis
		if($this->recursion && count($groups)) {
				$outcome 	= array();

				if($this->lookup_type == 'reverse') { //we need to override the lookup attribute for reverse recursion

					$ldap->getRecursiveGroups($groups,
						$this->recursion_depth, $outcome,
						'dn', $this->lookup_attribute);

				} else {

					$ldap->getRecursiveGroups($groups,
						$this->recursion_depth, $outcome,
						$this->lookup_attribute,
						$this->dn_attribute);
				}

				// we need to merge them back together without duplicates
				$groups = array_unique(array_merge($groups, $outcome));
		}

		//lets store the groups
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
	 * @param   JUser  &$user       A JUser object for the joomla user to be processed.
	 * @param   array  $attributes  An array of attributes from the source ldap user.
	 *
	 * @return  boolean  Returns true on success.
	 *
	 * @since   1.0
	 */
	public function doMap(JUser &$user, $attributes)
	{
		// Convert the distinguished name if required
		$attributes['dn'] = is_array($attributes['dn']) ?
			JArrayHelper::getValue($attributes['dn'], 0) : $attributes['dn'];

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
		if (isset($attributes[$this->lookup_attribute]))
		{
			$ldapUser = new SHLdapMappingEntry($attributes['dn'], $attributes[$this->lookup_attribute], $this->dn_validate);
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
		elseif($managed == 'yes')
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

}
