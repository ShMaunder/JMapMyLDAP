<?php
/**
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @package     System.LDAPDispatcher
 * @subpackage  User.JMapMyLDAP
 * 
 * @copyright	Copyright (C) 2011 Shaun Maunder. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');
jimport('shmanic.ldap.helper');
jimport('shmanic.client.jldap2');

/**
 * LDAP User Plugin
 *
 * @package     Shmanic.Plugin
 * @subpackage  System.LDAPDispatcher
 * @since       2.0
 */
class plgSystemLDAPDispatcher extends JPlugin 
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
		
		if(!class_exists('LdapEventHelper')) {
			JFactory::getApplication()->enqueueMessage('LDAP Dispatcher: there are missing LDAP libraries!', 'error');
		}
		
		if(!class_exists('LdapUserHelper')) {
			JFactory::getApplication()->enqueueMessage('LDAP Dispatcher: there are missing LDAP libraries!', 'error');
		}
		
	}
	
	public function onAfterInitialise() 
	{	

		LdapEventHelper::loadPlugins();
		
		/* Check if a user is currently logged in and 
		 * if so then attempt single sign on.
		 */
		if(!JFactory::getUser()->get('id')) { 
			$this->_attemptSSO();
			return;
		}
		
		/* Determine if the current user is a LDAP user and if so then
		* allow LDAP events to be fired.
		*/
		if(LdapUserHelper::isUserLdap()) {
			LdapEventHelper::loadEvents(
				JDispatcher::getInstance()
			);
		}

	}
	

	public function onUserLogin($user, $options = array()) 
	{ 
		if($user['type'] != 'LDAP') {
			return;
		}

		$events = LdapEventHelper::loadEvents(
			JDispatcher::getInstance()
		);
			
		// Autoregistration with optional override
		$autoRegister = LdapHelper::getGlobalParam('autoregister', true);
		if($autoRegister == '0' || $autoRegister == '1') {
			// inherited registration
			$options['autoregister'] = isset($options['autoregister']) ? $options['autoregister'] : $autoRegister;
		} else {
			// override registration
			$options['autoregister'] = ($autoRegister == 'override1') ? 1 : 0;
		}

		return $events->onUserLogin($user, $options);

	}
	
	/**
	* Method is called after user data is deleted from the database
	*
	* @param	array		$user		Holds the user data
	* @param	boolean		$success	True if user was succesfully stored in the database
	* @param	string		$msg		Message
	*/
	public function onUserAfterDelete($user, $success, $msg)
	{  
		if($params = JArrayHelper::getValue($user, 'params', 0, 'string')) {

			$reg = new JRegistry();
			$reg->loadString($params);

			/* This was an LDAP user so lets fire the LDAP specific
			 * on user deletion
			 */
			if($reg->get('authtype')=='LDAP') {
				
				$events = LdapEventHelper::loadEvents(
					JDispatcher::getInstance()
				);
				
				return $events->onUserAfterDelete($user, $success, $msg);
				
			}
		}
	}
	
	/**
	 * Method for attempting single sign on.
	 * 
	 * @return  void
	 * @since   2.0
	 */
	protected function _attemptSSO() 
	{
		// Check if SSO is disabled via the config parameter
		if(!LdapHelper::getGlobalParam('sso_enabled', false)) {
			return;
		}
		
		jimport('shmanic.sso.authentication');
		jimport('shmanic.sso.helper');
		
		// If the libraries are not installed, then no SSO for you!
		if(
			!class_exists('SSOHelper') ||
			!class_exists('SSOAuthentication')
		) return;
		
		if($urlBypass = LdapHelper::getGlobalParam('sso_url_bypass')) {
			$bypassValue = JFactory::getApplication()->input->get($urlBypass);
			if($bypassValue == 1) SSOHelper::disableSession();
			elseif($bypassValue == 0) SSOHelper::enableSession();
		}
		
		// Check if SSO is disabled via the session
		if(SSOHelper::isDisabled()) {
			return;
		}
		
		/* Lets check the IP rule is valid before we continue -
		 * if the IP rule is false then SSO is not allowed here.
		 */
		$myIP = JRequest::getVar('REMOTE_ADDR', 0, 'server');
		$ipList = explode("\n", LdapHelper::getGlobalParam('sso_ip_list'));
		$ipCheck = SSOHelper::doIPCheck($myIP, $ipList, LdapHelper::getGlobalParam('sso_ip_rule', 'allowall')=='allowall');
		
		if(!$ipCheck) return; // this ip isn't allowed
		
		$options = array();
		$options['action'] = 'core.login.site';
		
		/*
		 * We are going to check if we are in backend.
		 * If so then we need to check if sso is allowed
		 * to execute on the backend.
		 * 
		 */
		if(JFactory::getApplication()->isAdmin()) {
			if(!LdapHelper::getGlobalParam('sso_backend', 0)) return;
			$options['action'] = 'core.login.admin';
		}
			
		$options['autoregister'] = LdapHelper::getGlobalParam('sso_auto_register', false);
			
		$sso = new SSOAuthentication(
			LdapHelper::getGlobalParam('sso_plugin_type', 'sso')
		);
		$sso->login($options);
			
	
	}
	
}
