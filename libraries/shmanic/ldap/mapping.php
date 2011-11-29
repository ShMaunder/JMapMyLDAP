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
jimport('shmanic.client.jldap2');

/**
 * Holds each Ldap entry with its associated Joomla group. This
 * class also contains methods for comparing entries.
 *
 * @package		Shmanic.Ldap
 * @subpackage	Mapping
 * @since		1.0
 */
class MappingEntry extends JObject 
{
	
	/**
	* An array of RDNs to form the DN
	* 
	* @var    array
	* @since  1.0
	*/
	protected $rdn 		= array();
	
	/**
	* The original unaltered dn
	* 
	* @var    string
	* @since  1.0
	*/
	protected $dn 		= null;
	
	/**
	* Valid entry
	* 
	* @var    boolean
	* @since  1.0
	*/
	public $valid		= false;
	
	/**
	* Contains either ldap group memberships or joomla group id's 
	* depending on this instance
	* 
	* @var    array
	* @since  1.0
	*/
	protected $groups	= array();
	
	/**
	 * Class constructor.
	 *
	 * @param  string  $dn      The dn thats to hold the associated groups
	 * @param  array   $groups  The assocaited groups of the dn
	 *
	 * @since   1.0
	 */
	function __construct($dn = null, $groups = array()) 
	{ 
		$this->dn = 'INVALID'; //we just default to anything to ensure we've something later on
		
		$explode 		=  ldap_explode_dn($dn, 0);
		if(isset($explode['count']) && $explode['count']>0) {
			$this->rdn 		= array_map('strToLower', $explode); //break up the dn into an array and lowercase it
			$this->dn 		= $dn; //store the original dn string
			$this->groups 	= array_map('strToLower', $groups);
			$this->valid	= true;
		}

	}	
	
	/**
	 * Return the groups class variable
	 *
	 * @return  array  Array of groups
	 * @since   1.0
	 */
	public function getGroups() 
	{
		return $this->groups;
	}
	
	/**
	 * Return the rdn class variable
	 *
	 * @return  array  Array of RDNs to form the DN
	 * @since   1.0
	 */
	public function getRDN() 
	{
		return $this->rdn;
	}
	
	/**
	 * Return the dn class variable
	 *
	 * @return  string  The unaltered full dn
	 * @since   1.0
	 */
	public function getDN() 
	{
		return $this->dn;
	}
	
	/**
	 * Compares all the group mapping entries to all the ldap user
	 * groups and returns an array of JMapMyEntry parameters that
	 * match. 
	 *
	 * @param  Array        &$params      An array of JMapMyEntry's for the group mapping list
	 * @param  JMapMyEntry  &$ldapGroups  A JMapMyEntry object to the ldap user groups
	 * 
	 * @return  Array  An array of JMapMyEntry parameters that match
	 * @since   1.0
	 */
	public static function compareGroups(&$params, &$ldapGroups) 
	{
		$return = array();
		//compare an entire array of DNs in $entries
		foreach($params as $parameter) { //this is the set of group mapping that the user set
			if(self::compareGroup($parameter, $ldapGroups)) {
				$return[] = $parameter;
			}
		}
		
		return $return;
	}
	
	/**
	 * Compare the DN in the parameter against the groups in 
	 * the ldap groups. This is used to compare if one of the
	 * group mapping list dn entries matches any of the ldap user
	 * groups and if so returns true.
	 *
	 * @param  JMapMyEntry  $parameter    A JMapMyEntry object to the group mapping list parameters
	 * @param  JMapMyEntry  &$ldapGroups  A JMapMyEntry object to the ldap user groups
	 * 
	 * @return  Boolean  Returns if this parameter entry is in the ldap user group
	 * @since   1.0
	 */
	public static function compareGroup($parameter, &$ldapGroups) 
	{
		$matches 	= array();
		
		if($parameter->dn=='INVALID' || $ldapGroups->dn=='INVALID') {
			return false; //we only get here if our DN was invalid syntax
		}
		
		foreach($ldapGroups->groups as $ldapGroup) {
			//we need to convert to lower because escape characters return with uppercase hex ascii codes
			$explode = array_map('strToLower', ldap_explode_dn($ldapGroup,0));
			if(count($explode)) {
				if(self::compareDN($parameter->rdn, $explode)) {
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
	 * @param  Array  $source   The source dn (e.g. group mapping list parameter entry)
	 * @param  Array  $compare  The comparasion dn (e.g. ldap group)
	 * 
	 * @return  Boolean  Returns the comparasion result
	 * @since   1.0
	 */
	public static function compareDN($source, $compare) 
	{
		if(count($source)==0 || count($compare)==0 || $source['count']>$compare['count']) {
			return false;
		}
		
		/* lets start checking each RDN from left to right to see 
		 * if it matches. This would have to be changed if we 
		 * wanted to also check from right to left.
		 */
		for($i=0; $i<$source['count']; $i++) {
			if($source[$i]!=$compare[$i]) { 
				return false;
			}
		}

		return true;
	}
}

/**
 * A Ldap group mapping class to initiate and get group mappings.
 *
 * @package		Shmanic.Ldap
 * @subpackage	Mapping
 * @since		1.0
 */
class LdapMapping extends JObject 
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
	 * @param  array  $parameters  The LDAP Group Mapping parameters
	 *
	 * @since   1.0
	 */
	function __construct($parameters) 
	{	
		if($parameters instanceof JRegistry) {
			$parameters = $parameters->toArray();
		}
		
		parent::__construct($parameters);
		$this->validate();
		
		$lang = JFactory::getLanguage();
		$lang->load('lib_ldapmapping', JPATH_SITE); //for errors
		
	}
	
	/**
	 * Converts some of the parameters into a specific mask.
	 *
	 * @return  void
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
		foreach($tmp as $entry) {
			$entry = trim($entry);
			if(is_numeric($entry)) {
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
		foreach($tmp as $entry) {
			if($entry != "" && strrpos($entry, ':') > 0) {
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
	 * @since   2.0
	 */
	public function getAttributes()
	{
		$return = array();
		$return[] = $this->lookup_member;
		if($this->lookup_type == 'forward') {
			$return[] = $this->lookup_attribute;
		}
		
		return $return;
	}
	
	/**
	 * Commit the mapping depending on the parameters specified. This includes
	 * processing the group mapping list, adding joomla groups and removing
	 * joomla groups.
	 *
	 * @param  JUser   &$JUser       A JUser object for the joomla user to be processed
	 * @param  array   $attributes   An array of attributes from the source ldap user
	 *
	 * @return  boolean  Returns true on success
	 * @since   1.0
	 */
	public function doMap(&$JUser, $attributes) 
	{
		$JGroups 		= LdapMappingHelper::getJGroups(); //get all the joomla groups
		
		/* Process the map list parameter into validated entries. 
		 * Then ensure that there is atleast 1 valid entry to 
		 * proceed with the mapping process.
		 */
		$mapList		= $this->processList($JGroups);
		if(!count($mapList)) {
			$this->setError(JText::_('LIB_LDAPMAPPING_ERROR_NO_MAPPING_PARAMETERS'));
			return false;
		}
		
		/* Process the ldap attributes created from the source ldap
		 * user into mapping entries, then evaulate which groups
		 * are of interest when compared to the parameter list.
		 */
		if(isset($attributes['dn']) && isset($attributes[$this->lookup_attribute])) {
			$ldapUser = new MappingEntry($attributes['dn'], $attributes[$this->lookup_attribute]);
		} else {
			$this->setError(JText::_('LIB_LDAPMAPPING_ERROR_NO_GROUPS'));
			return false;
		}
		$mapList		= MappingEntry::compareGroups($mapList, $ldapUser);

		// Get the groups to be added to this user then add them.
		if($this->addition) {
			$toAdd = $this->getGroupsToAdd($JUser, $mapList);  
			foreach($toAdd as $group) {
				$result = LdapMappingHelper::addUserToGroup($JUser, $group);
				if(JError::isError($result)) $this->setError($result);

			}
		}
		
		// Get the groups to be removed from this user then remove them.
		if($this->removal != 'no') {
			$toRemove = $this->getGroupsToRemove($JUser, $mapList);
			foreach($toRemove as $group) 
				LdapMappingHelper::removeUserFromGroup($JUser, $group);
		}
		
		/* If we have no groups left in our user then we must add
		 * the public group otherwise Joomla won't save the changes
		 * to the database.
		 */
		if(!count($JUser->get('groups'))) {
			self::LdapMappingHelper($JUser, $this->publicid);
		}
		
		return true;
	}
	
	/**
	 * Process the group mapping list by splitting each entry from the
	 * format DN:1;2;3;* into a MappingEntry which then is added to a
	 * returned array.
	 *
	 * @param  array  $JGroups  An array of all the Joomla groups
	 *
	 * @return  array  An array of MappingEntry's 
	 * @since   1.0
	 */
	public function processList($JGroups) 
	{
		$list				= array();

		foreach ($this->list as $entry) {
			$entry				= trim($entry);
			$colonPosition 		= strrpos($entry, ':');
			$entryDN			= substr($entry, 0, $colonPosition);
			$entryGroups		= explode(',',substr($entry, $colonPosition+1)); //put joomla group id's for the ldap group dn in array
			
			$groups 	= array();
			foreach($entryGroups as $group) {
				$group = trim($group);
				if(is_numeric($group) && isset($JGroups[$group])) {
					$groups[] = $group;
				}
			}

			if(count($groups)>0 && strpos($entryDN, '=')>0) {
				$this->addManagedGroups($groups, $JGroups); //if there isn't one valid group then there will never ever be any managed groups
				$newEntry = new MappingEntry($entryDN, $groups);
				if($newEntry->valid) {
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
	 * @param  array  $groups        An array of joomla group IDs to be managed
	 * @param  array  $joomlaGroups  An array of all the Joomla groups
	 *
	 * @return  void
	 * @since   1.0
	 */
	public function addManagedGroups($groups, $joomlaGroups) 
	{
		$managed		= $this->removal;
		$unmanaged		= $this->unmanaged;

		if($managed == "yesdefault" && count($this->managed) == 0) { //lets just add them all
			foreach($joomlaGroups as $group) {
				if(!in_array($group['id'], $unmanaged)) $this->managed[] = ($group['id']); 
			}
		} elseif($managed == "yes") {
			foreach($groups as $group) { 
				if(!in_array($group, $unmanaged) && !in_array($group, $this->managed)) {
					$this->managed[] = $group; //this is a managed group
				}
			}
		}
	}
	
	/**
	 * Get the Joomla groups to remove.
	 *
	 * @param  JUser  $JUser    The JUser to remove groups from
	 * @param  array  $mapList  An array of matching group mapping entries
	 *
	 * @return  array  An array of Joomla IDs to remove
	 * @since   1.0
	 */
	public function getGroupsToRemove($JUser, $mapList) 
	{
		$removeGroups = array();
		
		foreach($JUser->groups as $jUserGroup) {
			if(in_array($jUserGroup, $this->managed)) { //check its in our managed pool
				$this->groupsToRemoveHelper($mapList, $removeGroups, $jUserGroup);
			}
		}
		
		return $removeGroups;
		
	}
	
	/**
	 * Before adding to the remove list, check its not already on
	 * the remove list and that its not in the mapping list.
	 *
	 * @param  array    $mapList        An array of matching group mapping entries
	 * @param  array    &$removeGroups  Groups to remove (byref)
	 * @param  integer  $jUserGroup     The Joomla group ID up for trial to be removed
	 *
	 * @return  void
	 * @since   1.0
	 */
	protected function groupsToRemoveHelper($mapList, &$removeGroups, $jUserGroup) 
	{
		if(in_array($jUserGroup, $removeGroups)) //check if we've already got this on our remove list
			return false;
			
		foreach($mapList as $item) {
			if(in_array($jUserGroup, $item->getGroups()))
				return false;
		}
		
		$removeGroups[] = $jUserGroup; //add it
		
	}
	
	/**
	 * Get the Joomla groups to add.
	 *
	 * @param  JUser  $JUser    The JUser to add groups to
	 * @param  array  $mapList  An array of matching group mapping entries
	 *
	 * @return  array  An array of Joomla IDs to add
	 * @since   1.0
	 */
	public function getGroupsToAdd($JUser, $mapList) 
	{
		$addGroups = array();
		
		foreach($mapList as $item) {
			foreach($item->getGroups() as $group) {
				$this->groupsToAddHelper($JUser, $addGroups, $group);
			}
		}
		
		return $addGroups;
	}
	
	/**
	 * Before adding to the add list, check its not already on
	 * the add list and that the user doesn't already have it.
	 *
	 * @param  JUser    $JUser       The JUser to add groups to
	 * @param  array    &$addGroups  Groups to add (byref)
	 * @param  integer  $paramGroup  The Joomla group ID up for trial to be added
	 *
	 * @return  void
	 * @since   1.0
	 */
	protected function groupsToAddHelper($JUser, &$addGroups, $paramGroup) 
	{
		if(in_array($paramGroup, $addGroups)) //check if we've already got this on our add list
			return false;
			
		if(in_array($paramGroup, $JUser->groups)) //check if the user already has this
			return false;
		
		$addGroups[] = $paramGroup; //add it
	}
	
}

/**
 * A Ldap group mapping helper for commiting groups to and from Joomla.
 *
 * @package		Shmanic.Ldap
 * @subpackage	Mapping
 * @since		2.0
 */
class LdapMappingHelper 
{
	/**
	 * Add a group to a Joomla user.
	 *
	 * @param  JUser    &$user     The JUser for the group addition
	 * @param  integer  $groupId   The Joomla group ID to add
	 *
	 * @return  mixed  JException on errror
	 * @since   1.0
	 */
	public static function addUserToGroup(&$user, $groupId) 
	{
		// Add the user to the group if necessary.
		if (!in_array($groupId, $user->groups)) {
			// Get the title of the group.
			$db	= JFactory::getDbo();
			$db->setQuery(
				'SELECT `title`' .
				' FROM `#__usergroups`' .
				' WHERE `id` = '. (int) $groupId
			);
			$title = $db->loadResult();

			// Check for a database error.
			if ($db->getErrorNum()) {
				return new JException($db->getErrorMsg());
			}

			// If the group does not exist, return an exception.
			if (!$title) {
				return new JException(JText::_('JLIB_USER_EXCEPTION_ACCESS_USERGROUP_INVALID'));
			}

			// Add the group data to the user object.
			$user->groups[$title] = $groupId;
		}

	}
	
	/**
	 * Remove a group from a Joomla user.
	 *
	 * @param  JUser    &$user     The JUser for the group removal
	 * @param  integer  $groupId   The Joomla group ID to remove
	 *
	 * @return  void
	 * @since   1.0
	 */
	public static function removeUserFromGroup(&$user, $groupId) 
	{
		// Remove the user from the group if necessary.
		$key = array_search($groupId, $user->groups);
		if ($key !== false) {
			// Remove the user from the group.
			unset($user->groups[$key]);
		}
		
	}
	
	/**
	 * Get all Joomla groups from the Joomla database.
	 *
	 * @return  array  An array of all the Joomla groups
	 * @since   1.0
	 */
	public static function getJGroups() 
	{
		$joomlaGroups 	= array();
		$db = JFactory::getDbo();
				
		//build a basic return of joomla group id's
		$query = $db->getQuery(true);
		$query->select('usrgrp.id, usrgrp.title')
			->from('#__usergroups AS usrgrp')
			->order('usrgrp.id');
		
			
		$db->setQuery($query);
		$joomlaGroups = $db->loadAssocList('id');

		return $joomlaGroups;
	}
	
}
