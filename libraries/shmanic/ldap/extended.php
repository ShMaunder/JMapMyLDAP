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
 * Provides common pre-defined queries that can be used to retrieve data from
 * the LDAP client. This class extends the funtionality of the client.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap
 * @since       2.0
 */
class SHLdapExtended extends SHLdap
{
	/**
	 * Default filter when one is not specified
	 *
	 * @var    string
	 * @since  2.0
	 */
	const DEFAULT_FILTER = '(objectclass=*)';

	/**
	 * The placeholder for username replacement.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const USERNAME_REPLACE = '[username]';

	/**
	 * Use the result object in place of an array when returning data results.
	 *
	 * @var    boolean
	 * @since  2.0
	 */
	const USE_RESULT_OBJECT = true;

	/**
	 * If true then use search to find user
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $use_search = false;

	/**
	 * Base DN to use for searching (e.g. dc=acme,dc=local / o=company)
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $base_dn = null;

	/**
	 * User DN or Filter Query (e.g. (sAMAccountName=[username]) / cn=[username],dc=acme,dc=local)
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $user_qry = null;

	/**
	 * Constructor
	 *
	 * @param   object  $configObj  An object of configuration variables
	 *
	 * @since   2.0
	 */
	public function __construct($configObj = null)
	{
		parent::__construct($configObj);

		// For front-end error translation
		JFactory::getLanguage()->load('lib_jldap2', JPATH_BASE);
	}

	/**
	 * Inform any listening loggers of the debug message.
	 * 
	 * @param   string  $message  String to push to stack
	 * 
	 * @return  void
	 * 
	 * @since  2.0
	 */
	public function addDebug($message)
	{
		// Add the debug message to any listening loggers
		SHFactory::getDispatcher('ldap')->trigger(
			'onLogDebug', array($message)
		);

		parent::addDebug($message);
	}

	/**
	 * (non-PHPdoc)
	 * @see SHLdapClient::search()
	 */
	public function search($dn = null, $filter = null, $attributes = array())
	{
		if (is_null($dn))
		{
			// Use the base distinguished name in place of null value
			$dn = $this->base_dn;
		}

		if (is_null($filter))
		{
			// Use the default filter in place of null value
			$filter = self::DEFAULT_FILTER;
		}

		$result = parent::search($dn, $filter, $attributes);

		if (!self::USE_RESULT_OBJECT || $result === false)
		{
			/*
			 * Not using the special result object OR the operation failed,
			 * therefore lets return now.
			 */
			return $result;
		}

		return new SHLdapResult($result);
	}

	/**
	 * (non-PHPdoc)
	 * @see SHLdapClient::read()
	 */
	public function read($dn = null, $filter = null, $attributes = array())
	{
		if (is_null($dn))
		{
			// Use the base distinguished name in place of null value
			$dn = $this->base_dn;
		}

		if (is_null($filter))
		{
			// Use the default filter in place of null value
			$filter = self::DEFAULT_FILTER;
		}

		$result = parent::read($dn, $filter, $attributes);

		if (!self::USE_RESULT_OBJECT || $result === false)
		{
			/*
			 * Not using the special result object OR the operation failed,
			 * therefore lets return now.
			 */
			return $result;
		}

		return new SHLdapResult($result);
	}

	/**
	 * Get a user's dn with optional bind authentication.
	 *
	 * @param  string   $username      Authenticating username
	 * @param  string   $password      Authenticating password
	 * @param  boolean  $authenticate  Attempt to authenticate the user (i.e.
	 *                   bind the user with the password supplied)
	 *
	 * @return  string|false  On success shall return a string containing users DN, otherwise
	 *                  returns a JException to indicate an error.
	 * @since   1.0
	 */
	public function getUserDN($username = null, $password = null, $authenticate = false)
	{

		if (empty($this->user_qry))
		{
			// No user query specified, cannot proceed
			$this->setError(new SHLdapException(null, 10301, JText::_('LIB_SHLDAPEXTENDED_ERR_10301')));
			return false;
		}

		$replaced = str_replace(self::USERNAME_REPLACE, $username, $this->user_qry);

		$this->addDebug(
			"Attempt to retrieve user distinguished name using '{$replaced}' " .
			($this->use_search ? ' with search.' : ' with direct bind.')
		);

		// Get a array of distinguished names from either the search or direct bind methods.
		$DNs = $this->use_search ? $this->getUserDnBySearch($username) : $this->getUserDnDirectly($username);

		if ($DNs === false)
		{
			// An error occurred during distinguished name retrieval (error already set)
			return false;
		}

		if (!is_array($DNs) || !count($DNs))
		{
			// Cannot find the specified username
			$this->setError(new SHLdapException(null, 10302, JText::sprintf('LIB_SHLDAPEXTENDED_ERR_10302', $username)));
			return false;
		}

		// Check if we have to authenticate the distinguished name with a password
		if ($authenticate)
		{
			// Attempt to bind each distinguished name with the specified password then return it
			foreach ($DNs as $dn)
			{
				if ($this->bind($dn, $password))
				{
					// Successfully binded with this distinguished name
					$this->addDebug("Successfully authenticated {$username} with distinguished name {$dn}.");
					return $dn;
				}
			}

			if ($this->use_search)
			{
				// User found, but was unable to bind with the supplied password
				$this->setError(new SHLdapException(null, 10303, JText::sprintf('LIB_SHLDAPEXTENDED_ERR_10303', $username)));
			}
			else
			{
				// Unable to bind directly to the given distinguished name parameters
				$this->setError(new SHLdapException(null, 10304, JText::sprintf('LIB_SHLDAPEXTENDED_ERR_10304', $username)));
			}

			return false;
		}
		else
		{

			$result = false;

			if ($this->use_search)
			{
				/* We can be sure the distinguished name(s) exists in the Ldap
				 * directory. However, we cannot be sure if the correct
				 * distinguished name is returned for the specified user without
				 * authenticating. Therefore, we have to assume the first (and
				 * hopefully only) distinguished name is correct.
				 * If the correct configuration has been given and the Ldap
				 * directory is well organised, this will always be correct.
				 */
				$result = $DNs[0];
			}
			else
			{
				/* Unlike searching, binding directly means we cannot be sure
				 * if the distinguished name(s) exists in the Ldap directory.
				 * Therefore, lets attempt to bind with a proxy user, then Ldap
				 * read each distinguished name's entity to check if it exists.
				 * If binding with the proxy user fails, then we have no option
				 * but to assume the first distinguished name exists.
				 */
				if ($this->proxyBind())
				{
					foreach ($DNs as $dn)
					{
						// Check if the distinguished name entity exists
						if (count($this->read($dn, null, array('dn'))))
						{
							// It exists so we assume this is the correct distinguished name.
							$result = $dn;
							break;
						}
					}

					if ($result === false)
					{
						// Failed to find any of the distinguished name(s) in the Ldap directory.
						$this->setError(new SHLdapException(null, 10305, JText::sprintf('LIB_SHLDAPEXTENDED_ERR_10305', $username)));
					}
				}
				else
				{
					// Unable to check Ldap directory, so have to assume the first is correct
					$result = $DNs[0];
				}
			}

			$this->addDebug("Using distinguished name {$result} for user {$username}.");
			return $result;
		}
	}

	/**
	 * Get a user's dn by attempting to search for it in the directory.
	 * 
	 * this method uses the query as a filter to find where the user is located in the directory
	 *
	 * @param  string  $username  Authenticating username
	 *
	 * @return  mixed  On success shall return an array containing DNs, otherwise
	 *                  returns a JException to indicate an error.
	 * @since   1.0
	 */
	public function getUserDnBySearch($username)
	{
		// Fixes special usernames and provides simple protection against ldap injections
		$username 	= SHLdapHelper::escape($username);
		$search 	= str_replace(self::USERNAME_REPLACE, $username, $this->user_qry);


		// Basic check for LDAP filter (i.e. brackets). Could still be a distinguished name.
		if (!preg_match('/\((.)*\)/', $search))
		{
			$search = "({$search})";
		}

		if (empty($this->base_dn))
		{
			// No base distinguished name specified, cannot proceed.
			$this->setError(new SHLdapException(null, 10321, JText::_('LIB_SHLDAPEXTENDED_ERR_10321')));
			return false;
		}

		// Bind using the proxy user so the user can be found in the Ldap directory.
		if (!$this->proxyBind())
		{
			// Failed to bind with proxy user
			$this->setError(new SHLdapException(null, 10322, JText::_('LIB_SHLDAPEXTENDED_ERR_10322')));
			return false;
		}

		// Search the directory for the user
		$result = $this->search(null, $search, array($this->ldap_uid));

		if ($result === false)
		{
			// An Ldap error occurred whilst trying to lookup the user
			return false;
		}

		$return 	= array();
		$count 		= $result->countEntries();

		// Store the distinguished name for each user found
		for ($i = 0; $i < $count; ++$i)
		{
			$return[] = $result->getValue($i, 'dn', 0);
		}

		return $return;
	}

	/**
	 * Get a user's dn by attempting to replace the username keyword
	 * in the query, then using the result as the user dn.
	 *
	 * @param  string  $username  Authenticating username
	 *
	 * @return  mixed  On success shall return an array containing DNs, otherwise
	 *                  returns a JException to indicate an error.
	 * @since   1.0
	 */
	public function getUserDnDirectly($username)
	{
		//this method uses the query directly to bind as a user (the query must be a dn)
		$return 	= array();
		$username 	= LdapHelper::escape($username, true); //fixes special usernames and provides protection against dn ldap injections
		$search 	= str_replace(self::USERNAME_REPLACE, $username, $this->user_qry);
		$DNs 		= explode(';', $search);
	
		/*
		 * A very basic check to make sure we aren't using a filter.
		* No DN starts and ends with brackets.
		*/
		if(preg_match('/\((.)*\)/', $search)) {
			JLogLdapHelper::addErrorEntry(JText::sprintf('LIB_JLDAP2_ERROR_VALIDATION_DN', $this->getErrorMsg()), __CLASS__);
			return false;
		}
	
		foreach($DNs as $dn) { //we need to find the correct dn from the semi-colon'd delimited list specified
			$dn = trim($dn);
			if($dn) {
				$return[] = $dn;
			}
		}
	
		return $return;
	}
	
	/**
	 * Get an array of user detail attributes for the user. By default this method will return the
	 * mapping uid, fullname and email fields. It will also try to get all the attributes required
	 * for group mapping, though this is optional.
	 * 
	 * 
	 * TODO: MOVE THIS OUT. ITS NOTHING TO DO WITH LDAPCLIENT OR LDAPEXTENDED
	 * 
	 *
	 * @param  string  $dn       The dn of the user
	 * @param  array   $options  An optional array of options (
	 *   string  $lookupType   => Type of group lookup to perform on user (i.e. forward or reverse)
	 *   string  $lookupKey    => Group attribute for group lookup (i.e. groupMembership or member)
	 *   string  $lookupMember => User attribute to use when searching for group membership (i.e. dn or uid)
	 *   string  $dnAttribute  => Set the recursive forward lookup attribute (i.e. distinguishedName)
	 *   string  $recurseDepth => Define the max depth for recursion (i.e. set to 0 for unlimited OR set to null for no recursion)
	 *   array   $extras       => Any extra user attributes to get
	 * )
	 * @return  mixed  On success shall return an array of attributes, otherwise
	 *                   returns a JException to indicate an error.
	 * @since   1.0
	 */
	public function getUserDetails($dn, $attributes = array())
	{
	
		/*
		 * Lets call for any LDAP plug-ins that require any
		* extra attributes.
		*/
		$dispatcher = JDispatcher::getInstance();
		$extras = $dispatcher->trigger('onLdapBeforeRead', array(&$this, array('dn'=>$dn,'source'=>'getuser')));
	
		foreach($extras as $extra) {
			$attributes = array_merge($attributes, $extra);
		}
	
		$attributes[] = $this->ldap_fullname;
		$attributes[] = $this->ldap_uid;
	
		if(strpos($this->ldap_email, self::USERNAME_REPLACE)===false) { //check for a 'fake' email
			$attributes[] = $this->ldap_email;
		}
	
		$attributes = array_values(array_unique($attributes));
	
		$return = array_fill_keys($attributes, null);
	
		//get our ldap user attributes and check we have a valid result
		$result	= new JLDAPResult($this->read($dn, null, $attributes));
		if(is_null($result->getValue(0,'dn',0))) {
			JLogLdapHelper::addErrorEntry(JText::_('LIB_JLDAP2_ERROR_USER_ATTRIBUTE_FAIL'), __CLASS__);
			return false;
		}
	
	
		//lets store everything we've done
		foreach($return as $attribute=>$rubbish) {
			$return[$attribute] = $result->getAttribute(0, $attribute);
		}
	
		//if we used a fake email, then lets insert it
		if(strpos($this->ldap_email, self::USERNAME_REPLACE)!==false) { //check for a 'fake' email
			$email = str_replace(self::USERNAME_REPLACE, $return[$this->ldap_uid][0], $this->ldap_email);
			$return[$this->ldap_email] = array($email);
		}
	
		$result = $dispatcher->trigger('onLdapAfterRead', array(&$this, &$return, array('dn'=>$dn,'source'=>'getuser')));
		if(in_array(false, $result, true)) {
	
			JLogLdapHelper::addErrorEntry(JText::_('Cancelled Login'), __CLASS__); //TODO: put in a language file
			return false;
		}
	
		return $return;
	
	}
	
	
	/**
	 * Get an array of all the nested groups through the use of group recursion.
	 * This is required usually only for Active Directory, however there could
	 * be other LDAP platforms that cannot pick up nested groups.
	 *
	 * @param  array   $searchDNs       Initial user groups (or ones that have already been discovered)
	 * @param  string  $depth           How far to search down until it should give up (0 means unlimited)
	 * @param  array   &$result         Holds the result of every alliteration (this is a byreference)
	 * @param  string  $attribute       The LDAP attribute to store after each ldap search
	 * @param  string  $queryAttribute  The LDAP filter attribute to query
	 *
	 * @return  array  All user groups including the initial user groups
	 * @since   1.0
	 */
	public function getRecursiveGroups($searchDNs, $depth = 0, &$result, $attribute, $queryAttribute = null)
	{
		//recurse through the search dn
		$depth--;
		$search			= null;
		$next			= array();
		$filters		= array();
	
		if(!isset($searchDNs)) {
			return $result;
		}
	
		foreach($searchDNs as $dn)
			$filters[] = 	$queryAttribute . '=' . $dn;
	
		if(!count($filters)) {
			return $result;
		}
	
		$search = LdapHelper::buildFilter($filters, '|'); //build a filter using the OR operator
		$results = new JLDAPResult($this->search(null, $search, array($attribute)));
	
		$entryCount = $results->countEntries();
		for($i=0; $i<$entryCount; $i++) { //will process each record that the searched container found
	
			$dn = $results->getValue($i, 'dn', 0);
	
			if(!in_array($dn, $result)) { //has this container already been processed previously
				$result[] = $dn;
				$valueCount = $results->countValues($i, $attribute);
				for($j=0; $j<$valueCount; $j++) {
					$value = $results->getValue($i, $attribute, $j);
					$next[] = $value; //we want to process this object
				}
			}
		}
	
		/*
		 * Only start the recursion when we have something to process
		* next otherwise we would loop forever.
		*/
		if(count($next) && $depth!=0) {
			$this->getRecursiveGroups($next, $depth, $result, $attribute, $queryAttribute);
		}
	
		return $result;
	}
	
}
