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
 * @since       1.0
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
		
		// Bit nasty: allow it to be picked up later if need be
		LdapHelper::$auth_plugin = $this->params->get('auth_plugin', 'jmapmyldap');
	}
	
	public function onAfterInitialise() 
	{	
		if(class_exists('LdapEventHelper')) {
			$dispatcher = JDispatcher::getInstance();
			LdapEventHelper::loadPlugins('ldap');
			// Determine if the current session is a logged-in LDAP user
			if(LdapEventHelper::isLdapSession()) {
				LdapEventHelper::loadEvents($dispatcher);
			}
		}
	}
	

	public function onUserLogin($user, $options = array()) 
	{
		if($user['type'] == 'LDAP' && class_exists('LdapEventHelper')) {
			$dispatcher = JDispatcher::getInstance();
			$events = LdapEventHelper::loadEvents($dispatcher);
			$options['authplugin'] 		= $this->params->get('auth_plugin', 'jmapmyldap');
			$options['autoregister'] 	= $this->params->get('autoregister', true);
			return $events->onUserLogin($user, $options);
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
