<?php

defined('_JEXEC') or die;

jimport('joomla.application.controller');


class LdapAdminControllerload extends JController
{
	
	/* 
	 * Display the default view for this plugin
	 */
	public function display()
	{ 
		require_once JPATH_COMPONENT.'/helpers/ldapadmin.php';
		
		/*JRequest::setVar('view', 'ldap_auth');
		JRequest::setVar('layout', 'default');
		
		parent::display();*/
	}
	
	public function plugin() 
	{
		JLoader::import('joomla.application.component.model');
		//JLoader::import('plugin', JPATH_SITE . '/plugins/authentication/jmapmyldap/admin' );
		
		//$pluginController = JController::getInstance('Controller', array('base_path'=>'/plugins/authentication/jmapmyldap/admin'));
		
		$pluginController = $this->addViewPath(JPATH_SITE . '/plugins/authentication/jmapmyldap/admin/views/some_shit');
		
		$pluginController->getView('some_shit');
		
		
		//$pluginController = JController::getInstance('Controller', 'LdapAdminController');
		
		print_r($pluginController);
		
		die();
		//$pluginModel = JModel::getInstance('plugin', 'PluginsModel' );
		//$pluginModel->setState('id');
		
		//return $pluginModel;
		
		
	}
	
}