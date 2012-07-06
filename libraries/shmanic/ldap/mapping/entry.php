<?php

/**
 * Holds each Ldap entry with its associated Joomla group. This
 * class also contains methods for comparing entries.
 *
 * @package		Shmanic.Ldap
 * @subpackage	Mapping
 * @since		1.0
 */
class SHLdapMappingEntry extends JObject
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
	protected $dn 		= false;

	/**
	* Valid entry
	*
	* @var    boolean
	* @since  1.0
	*/
	protected $valid		= false;

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
			$explode = ldap_explode_dn($dn, 0);

			if (JArrayHelper::getValue($explode, 'count', false))
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
	* Return if a valid entry
	*
	* @return  boolean  Valid entry
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
	 * @param  JMapMyEntry  $parameter    A JMapMyEntry object to the group mapping list parameters
	 * @param  JMapMyEntry  &$ldapGroups  A JMapMyEntry object to the ldap user groups
	 *
	 * @return  Boolean  Returns if this parameter entry is in the ldap user group
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
				$explode = ldap_explode_dn($ldapGroup, 0);

				if (is_array($explode) && count($explode))
				{
					// We need to convert to lower because escape characters return with uppercase hex ascii codes
					$explode = array_map('strToLower', ldap_explode_dn($ldapGroup, 0));

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
	 * @param  Array  $source   The source dn (e.g. group mapping list parameter entry)
	 * @param  Array  $compare  The comparasion dn (e.g. ldap group)
	 *
	 * @return  Boolean  Returns the comparasion result
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