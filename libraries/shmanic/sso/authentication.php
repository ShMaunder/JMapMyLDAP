<?php
/**
 * @package     Shmanic
 * @subpackage  SSO
 * 
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @copyright   Copyright (C) 2011 Shaun Maunder. All rights reserved. 
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * 
 * --- Original based on JAuthTools ---
 * @url         http://joomlacode.org/gf/project/jauthtools
 * @author      Sam Moffatt <sam.moffatt@toowoombarc.qld.gov.au>
 * @author      Toowoomba Regional Council Information Management Department
 * @copyright   (C) 2008 Toowoomba Regional Council/Sam Moffatt 
 * ------------------------------------
 */

defined('_JEXEC') or die;

jimport('joomla.base.observable');
jimport('joomla.user.authentication');

/**
 * Provides a framework for SSO and login methods. The framework
 * is similar to the JAuthTools framework.
 * 
 * Former name was JSSOMySite.
 *
 * @package		Shmanic
 * @subpackage	SSO
 * @since		1.0
 */
class SSOAuthentication extends JObservable 
{
	
	protected $pluginGroup = null;
	
	/**
	 * Class constructor.
	 *
	 * @since   1.0
	 */
	public function __construct($pluginGroup = 'sso') 
	{
		JFactory::getLanguage()->load('lib_sso_core', JPATH_SITE);
		
		$this->pluginGroup = $pluginGroup;
		
		if(!JPluginHelper::importPlugin($this->pluginGroup)) {
			$this->_reportError(new JException(JText::_('LIB_SSOAUTHENTICATION_ERROR_IMPORT_PLUGINS')));
		}
	}

	/**
	 * Detect the remote SSO user by looping through all SSO
	 * plugins. Once a detection is found, it is put into
	 * the options parameter array and method is returned as
	 * true. Uses the same framework as JAuthTools SSO.
	 *
	 * @param  array  &$options  An array containing action and autoregister value (byref)
	 * 
	 * @return  Boolean  Return true if a remote user has been detected
	 * @since   1.0
	 */
	public function detect(&$options = array()) 
	{	
		$plugins = JPluginHelper::getPlugin($this->pluginGroup);
		
		foreach ($plugins as $plugin) {
			$name = $plugin->name;
			$className = 'plg' . $plugin->type . $name;
				
			if(class_exists($className)) { 
				$plugin = new $className($this, (array)$plugin);
			} else {
				$this->_reportError(new JException(JText::sprintf('LIB_SSOAUTHENTICATION_ERROR_PLUGIN_CLASS', $className, $name)));
				continue;
			}

			// we need to check the ip rule & list before attempting anything...
			$params = new JRegistry;
			//$params->loadJSON($plugin->params);
			$params->loadString($plugin->params);
			
			$myip = JRequest::getVar('REMOTE_ADDR', 0, 'server');
			
			$iplist = explode("\n", $params->get('ip_list', ''));
			$ipcheck = SSOHelper::doIPCheck($myip, $iplist, $params->get('ip_rule', 'allowall')=='allowall');

			if($ipcheck) {
				// Try to authenticate remote user
				$username = $plugin->detectRemoteUser();
				
				//if detection is successful then return true
				if (!is_null($username) && $username != '') { 
					$options['username'] = $username;
					$options['type'] = $name;
					$options['sso'] = true;
					return true;
				}
			}
		}
	}
	
	/**
	 * If a detection has been successful then it will try to
	 * authenticate with the onUserAuthorisation method
	 * in any of the authentication plugins. 
	 *
	 * @param  string  $username  String containing detected username
	 * @param  array   $options   An array containing action, autoregister and detection name
	 * 
	 * @return  mixed  Returns a JAuthenticationReponse on success, nothing on failure
	 * @since   1.0
	 */
	public function authorise($username, $options) 
	{
		JAuthentication::getInstance();
		$response = new JAuthenticationResponse();
		
		$response->username = $username;

		// We need to authorise our username to an authentication plugin
		$authorisations = JAuthentication::authorise($response, $options);
		
		foreach($authorisations as $authorisation) {
			
			if ($authorisation->status === JAuthentication::STATUS_SUCCESS) {
				// This username is authorised to use the system
				$response->status = JAuthentication::STATUS_SUCCESS;
				return $response;
			}
			
		}
		
		// No authorises found
		return false;

	}
	
	/**
	 * Attempts a SSO by calling the detection function, then
	 * authenticates the returned username and lastly calls the
	 * onUserLogin user plugin events.
	 *
	 * @param  array   $options   An array containing action and autoregister
	 * 
	 * @return  boolean  True on successful SSO login
	 * @since   1.0
	 */
	public function login($options = array()) 
	{
		// Get the sso username through detection plugins
		if(!$this->detect($options)) { 
			return false;
		}
		
		// Authorise the detected username
		if($response = $this->authorise($options['username'], $options)) {

			/* Username has been authorised. We can now proceed
			 * with standard Joomla logon by calling the user's
			 * onUserLogin event.
			 */
			JPluginHelper::importPlugin('user');

			$results = JFactory::getApplication()->triggerEvent('onUserLogin', array((array)$response, $options));
				
			if(!in_array(false, $results, true)) {
				// onUserLogin event success
				return true;
			}
					
			// Something failed within the onUserLogin event
			$this->_reportError(new JException(JText::sprintf('LIB_SSOAUTHENTICATION_ERROR_USER_PLUGIN', $options['username'])));
			return false;	
				
		}
 
		$this->_reportError(new JException(JText::sprintf('LIB_SSOAUTHENTICATION_ERROR_AUTHENTICATION', $options['username'])));
		return false;

	}
	
	/**
	 * Reports an error to the screen if debug mode is enabled.
	 * Will also report to the logger for administrators.
	 *
	 * @param  mixed  $exception  The authentication error can either be
	 *                              a string or a JException.
	 * 
	 * @return  string  Exception comment string
	 * @since   1.0
	 * 
	 * @deprecated  This is shit code. Lets use the new LDAP logging!
	 */
	protected function _reportError($exception = null) 
	{
		$comment = is_null($exception) ? JText::_('LIB_SSOAUTHENTICATION_ERROR_UNKNOWN') : $exception;
		
		$errorlog = array('status'=>'SSO Fail: ', 'comment'=>$comment);
		
		jimport('joomla.error.log');
		$log = JLog::getInstance();
		$log->addEntry($errorlog);
		
		if(JDEBUG) {
			JError::raiseWarning('SOME_ERROR_CODE', $comment); 
		}

		return $comment;
	}

}
