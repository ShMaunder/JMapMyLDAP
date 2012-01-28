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
		
	}
	
	public function onAfterInitialise() 
	{	
		/* Determine if the current user is a LDAP user and if so then
		 * allow LDAP events to be fired.
		 */
		if(class_exists('LdapEventHelper')) {
			LdapEventHelper::loadPlugins('ldap');
			if(LdapEventHelper::isLdapSession()) {
				LdapEventHelper::loadEvents(
					JDispatcher::getInstance()
				);
			}
		}
	}
	

	public function onUserLogin($user, $options = array()) 
	{
		if($user['type'] == 'LDAP' && class_exists('LdapEventHelper')) {

			$events = LdapEventHelper::loadEvents(
				JDispatcher::getInstance()
			);
			//$options['authplugin'] 		= $this->params->get('auth_plugin', 'jmapmyldap');
			//$options['autoregister'] 	= $this->params->get('autoregister', true);
			//$options['authplugin'] = LdapHelper::getGlobalParam('auth_plugin', 'jmapmyldap');
			
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
	 * Reports an error to the screen and log. If debug mode is on 
	 * then it displays the specific error on screen, if debug mode 
	 * is off then it displays a generic error.
	 *
	 * @param  JException  $exception  The authentication error
	 * 
	 * @return  JError  Error based on comment from exception
	 * @since   1.0
	 * 
	 * @deprecated We now use the new 11.3 JLogging
	 */
	protected function _reportError($exception = null) 
	{
		/*
		* The mapping was not successful therefore
		* we should report what happened to the logger
		* for admin inspection and user should be informed
		* all is not well.
		*/
		$comment = is_null($exception) ? JText::_('PLG_JMAPMYLDAP_ERROR_UNKNOWN') : $exception;
		
		$errorlog = array('status'=>'JMapMyLDAP Fail: ', 'comment'=>$comment);
		
		jimport('joomla.error.log');
		$log = JLog::getInstance();
		$log->addEntry($errorlog);
		
		if(JDEBUG) {
			return JERROR::raiseWarning('SOME_ERROR_CODE', $comment);
		}
		
		return JERROR::raiseWarning('SOME_ERROR_CODE', JText::_('PLG_JMAPMYLDAP_ERROR_GENERAL'));
		
	}

	
}
