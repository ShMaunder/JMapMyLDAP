<?php

	public function onAfterInitialise() 
	{	
		
		return;
		
		/* Check if a user is currently logged in and 
		 * if so then attempt single sign on.
		 */
		if(!JFactory::getUser()->get('id')) { 
			$this->_attemptSSO();
			return;
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