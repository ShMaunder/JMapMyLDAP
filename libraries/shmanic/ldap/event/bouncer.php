<?php
/**
 * @version     $Id:$
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @package     Shmanic.Ldap
 * @subpackage  Event
 * 
 * @copyright	Copyright (C) 2011 Shaun Maunder. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

//jimport('shmanic.client.jldap2');

/**
 * Handles all LDAP events.
 *
 * @package		Shmanic.Ldap
 * @subpackage	Event
 * @since		2.0
 */
class SHLdapEventBouncer extends JEvent
{

	protected $curUserLdap = false;

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The JDispatcher object to observe.
	 *
	 * @since  2.0
	 */
	public function __construct(&$subject)
	{
		// Check if the current user is Ldap authenticated
		if (SHLdapHelper::isUserLdap())
		{
			$this->curUserLdap = true;
		}

		parent::__construct($subject);
	}

	public function onAfterRoute()
	{
		
	}
	
	public function onAfterDispatch()
	{
		
	}
	
	public function onBeforeRender()
	{
		
	}
	
	public function onAfterRender()
	{
		
	}
	
	public function onBeforeCompileHead()
	{
		
	}
	
	/**
	* @param  string  $context  The context for the data
	* @param  int     $data     The user id
	* @param  object
	*
	* @return  boolean
	* @since   2.0
	*/
	public function onContentPrepareData($context, $data)
	{
		//$result = LdapEventHelper::triggerEvent('onLdapContentPrepareData', array($context, $data));
		
		//return $result;
	}
	
	/**
	* @param  JForm  $form  The form to be altered.
	* @param  array  $data  The associated data for the form.
	*
	* @return  boolean
	* @since   2.0
	*/
	public function onContentPrepareForm($form, $data)
	{
		//$result = LdapEventHelper::triggerEvent('onLdapContentPrepareForm', array($form, $data));
		
		//return $result;	
	}
	
	//public function onUserBeforeDelete() {}
	
	public function onUserAfterDelete($user, $success, $msg)
	{
		return;
		
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
		
		
		//$result = LdapEventHelper::triggerEvent('onLdapAfterDelete', array($user, $success, $msg));
	
		//return $result;
	}
	
	public function onUserBeforeSave($user, $isNew, $new)
	{
		//$result = LdapEventHelper::triggerEvent('onLdapBeforeSave', array($user, $isNew, $new));
		
		//return $result;
	}
	
	public function onUserAfterSave($user, $isNew, $success, $msg)
	{
		//$result = LdapEventHelper::triggerEvent('onLdapAfterSave', array($user, $isNew, $success, $msg));
	
		//return $result;
	}
	
	public function onUserLogin($user, $options = array())
	{
		return;
		
		// START OF LDAP DISPATCHER
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
		
		// END OF LDAP DISPATCHER
		
		$result = false;
		
		/* Before firing the onLdapLogin method, we must make sure
		 * the user has the attributes element. If not then it can
		 * be assumed that the JMapMyLDAP authentication wasn't used.
		 */
		if(!isset($user['attributes'])) {
			if($attributes = LdapUserHelper::getAttributes($user)) {
				$user['attributes'] = $attributes;	
			} else {
				//TODO: raise an error - check auth plugin parameter
				return false;
			}
		}
			
		$instance = LdapUserHelper::getUser($user, $options);
		
		/* Set a user parameter to distinguish the authentication
		* type even when this user is not logged in.
		*/
		LdapUserHelper::setUserLdap($instance);
		
		// Fire the ldap specific on login events
		$result = LdapEventHelper::triggerEvent('onLdapLogin', array(&$instance, $user, $options));
		
		if($result) {
			$instance->save();
		}
		
		return $result;
	}
	
	public function onUserLogout($user, $options = array())
	{
		return;
		$session = JFactory::getSession();
		$session->clear('authtype');
		
		$result = LdapEventHelper::triggerEvent('onLdapLogout', array($user, $options));
	
		return $result;
	}
	
	public function onUserLoginFailure($response)
	{
		//Never would get executed?
	}
	
	public function onUserLogoutFailure($parameters = array()) 
	{
		//Never would get executed?
	}
	

}
