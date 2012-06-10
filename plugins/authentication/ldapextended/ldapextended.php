<?php
/**
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @package     Shmanic.Plugin
 * @subpackage  Authentication.JMapMyLDAP
 * 
 * @copyright	Copyright (C) 2011 Shaun Maunder. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

//jimport('joomla.plugin.plugin');
//jimport('shmanic.log.ldaphelper');

/**
 * LDAP Authentication Plugin
 *
 * @package     Shmanic.Plugin
 * @subpackage  Authentication.JMapMyLDAP
 * @since       1.0
 */
class PlgAuthenticationLDAPExtended extends JPlugin
{
	/**
	 * Constructor
	 *
	 * @param  object  &$subject  The object to observe
	 * @param  array   $config    An array that holds the plugin configuration
	 * 
	 * @since  2.0
	 */
	function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();

	}

	/**
	 * This method handles the Ldap authentication and reports 
	 * back to the subject. 
	 *
	 * @param   array   $credentials  Array holding the user credentials
	 * @param   array   $options      Array of extra options
	 * @param   object  &$response    Authentication response object
	 * 
	 * @return  boolean  Authentication result
	 * 
	 * @since   1.0
	 */
	public function onUserAuthenticate($credentials, $options, &$response)
	{

		// add the loggers
		JLogLdapHelper::addLoggers();
		
		// If JLDAP2 fails to import then exit
		jimport('shmanic.client.jldap2');
		if(!class_exists('JLDAP2')) { 
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('JGLOBAL_AUTH_FAIL');
			JLogLdapHelper::addErrorEntry(JText::sprintf('PLG_AUTHENTICATION_JMAPMYLDAP_ERROR_MISSING_LIBRARY', 'JLDAP2'), __CLASS__, 10001);
			return false;
		}
		
		$response->type = 'LDAP';
		
		// Must have a password to deny anonymous binding
		if(empty($credentials['password'])) {
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('PLG_AUTHENTICATION_JMAPMYLDAP_PASS_BLANK');
			JLogLdapHelper::addErrorEntry(JText::_('JGLOBAL_AUTH_PASS_BLANK'), __CLASS__, 10002);
			return false;
		}
		
		$ldap = JLDAP2::getInstance($this->params);
		
		// Start the LDAP connection procedure
		if(!$result = $ldap->connect()) {
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('JGLOBAL_AUTH_FAIL');
			JLogLdapHelper::addErrorEntry(JText::_('JGLOBAL_AUTH_NO_CONNECT'), __CLASS__, 10003);
			return;
		}
		
		/* We will now get the authenticated user's dn.
		 * In this method we are also going to test the 
		 * dn against the password. Therefore, if any dn
		 * is returned, it is a successfully authenticated
		 * user.
		 */
		if(!$dn = $ldap->getUserDN($credentials['username'], $credentials['password'], true)) {
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('JGLOBAL_AUTH_INVALID_PASS');
			JLogLdapHelper::addErrorEntry(JText::_('JGLOBAL_AUTH_BIND_FAILED'), __CLASS__, 10004);
			$ldap->close();
			return;
		}
		
		/* Store the dn of the user for the authorisation of 
		 * the user (i.e. that part happens next).
		 */
		$response->set('dn', $dn);
		
		// Successful authentication, report back and say goodbye!
		$response->status			= JAuthentication::STATUS_SUCCESS;
		$response->error_message 	= '';

		return true;
		
	}
	
	/**
	* This method handles the Ldap authorisation and reports
	* back to the subject. Also this method is used for SSO.
	*
	* There is no custom logging in the authentication.
	*
	* @param  array   $response    Authentication response object from onUserAuthenticate()
	* @param  array   $options     Array of extra options
	*
	* @return  JAuthenticationResponse  Authentication response object
	* @since   2.0
	*/
	public function onUserAuthorisation($response, $options = array())
	{

		$retResponse = new JAuthenticationResponse();
		
		$response->type = 'LDAP';
		$ldap 	= JLDAP2::getInstance($this->params);
		$dn 	= $response->get('dn');
		
		/* If we aren't connected to LDAP yet then we can assume 
		 * onUserAuthenticate() hasn't been executed beforehand.
		 * Firstly, we need to connect to LDAP.
		 */
		if(!$ldap->isConnected()) {
			// Start the LDAP connection procedure
			if(!$result = $ldap->connect()) {
				$response->status = JAuthentication::STATUS_FAILURE;
				$response->error_message = JText::_('JGLOBAL_AUTH_FAIL');
				JLogLdapHelper::addErrorEntry(JText::_('JGLOBAL_AUTH_NO_CONNECT'), __CLASS__, 10003);
				return;
			}
		}
		
		/* If this is SSO, then we need to secondly, get the
		 * current DN of the user
		 */
		if(!$dn) {
			// Get the user DN using the connect username/password
			if(!$dn = $ldap->getUserDN($response->username, null, false)) {
				$response->status = JAuthentication::STATUS_FAILURE;
				$response->error_message = JText::_('JGLOBAL_AUTH_INVALID_PASS');
				JLogLdapHelper::addErrorEntry(JText::_('JGLOBAL_AUTH_BIND_FAILED'), __CLASS__, 10004);
				$ldap->close();
				return;
			}
		}
		
		
		/* Let's get the user attributes for this dn. */
		if(!$details = $ldap->getUserDetails($dn)) {
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('PLG_AUTHENTICATION_JMAPMYLDAP_ERROR_ATTRIBUTES_FAIL');
			JLogLdapHelper::addErrorEntry(JText::_('PLG_AUTHENTICATION_JMAPMYLDAP_ERROR_ATTRIBUTES_FAIL_WUSER', $credentials['username']), __CLASS__, 10005);
			$ldap->close();
			return false;
		}
		
		/* Set the required Joomla user fields with the Ldap
		 * user attributes.
		*/
		if(isset($details[$ldap->ldap_uid][0])) {
			$response->username 	= $details[$ldap->ldap_uid][0];
		}
			
		if(isset($details[$ldap->ldap_fullname][0])) {
			$response->fullname 	= $details[$ldap->ldap_fullname][0];
		}
			
		if(isset($details[$ldap->ldap_email][0])) {
			$response->email 		= $details[$ldap->ldap_email][0];
		}
		
		// Joomla password should always be blank TODO: review this for password plug-in
		$response->set('password_clear', ''); 
		
		/* store for the user plugin so we do not have
		* to requery everything with the ldap server.
		*
		* NOTE: This uses attributes and is hard coded!
		* Therefore when querying ldap stuff back, we must
		* use attributes - TODO: make this a constant!
		*/
		$response->set('attributes', $details);
		
		/* Everything appears to be a success and 
		 * therefore we shall log the user login then
		 * report back to the subject.
		 */
		JLogLdapHelper::addInfoEntry('User ' . $response->username . ' successfully logged in.', __CLASS__);
		$retResponse->status = JAuthentication::STATUS_SUCCESS;
		$ldap->close();
		
		return $retResponse;
		
	}
	
}
