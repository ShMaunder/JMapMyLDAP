<?php

defined('_JEXEC') or die;

jimport('joomla.application.controller');


class LdapAdminControllerplg_authentication_jmapmyldap extends JController
{
	
	public function ajax()
	{
		
		//lets get the ajax running babe
		if (!JRequest::getCmd('view')) {
			JRequest::setVar('view', 'xml'); // Probably should always be xml
		}
		
		JRequest::setVar('layout', 'authentication');

		parent::display();
	}
	
	/* 
	 * Display the default ldap_auth view for this plugin.
	 */
	public function display()
	{ 
		require_once JPATH_COMPONENT.'/helpers/ldapadmin.php';
		
		// Load the submenu.
		LdapAdminHelper::addSubmenu(JRequest::getCmd('view', ''));
		
		JRequest::setVar('view', 'authentication');
		JRequest::setVar('layout', 'default');
		
		parent::display();
	}
	
}