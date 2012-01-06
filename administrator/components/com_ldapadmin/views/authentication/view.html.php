<?php

defined('_JEXEC') or die;

jimport('joomla.application.component.view');


class LdapAdminViewauthentication extends JView
{
	protected $enabled;
	protected $items;
	protected $pagination;
	protected $state;
	protected $form;
	protected $item;
	

	/**
	 * Display the view
	 *
	 * @since	1.6
	 */
	public function display($tpl = null)
	{ 
		//$this->enabled		= JMapMyLDAPHelper::isEnabled();
		//$this->items		= $this->get('Items');
		//$this->pagination	= $this->get('Pagination');
		//$this->state		= $this->get('State');
		//$pluginModel = $this->getPluginModel();
		
		$id = LdapAdminHelper::getPluginId('authentication', 'jmapmyldap');
		if(!$id) return false; //TODO: needs to display an error here
		
		//$this->item		= $pluginModel->getItem($id);
		
		//print_r($this->item); die();
		
		//$this->form		= $pluginModel->getForm();
		
		
		
		
		//print_r($this->form); die();
		//$pluginModel->getItem(10004); 
		//$pluginModel->getForm(10004);
		//$pluginModel
		//$this->set('Errors', 'lala');
		// Check for errors.
		if (count($errors = $this->get('Errors'))) { echo 'test';
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}


		
		parent::display($tpl);
		$this->addToolbar();
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since	1.6
	 */
	protected function addToolbar()
	{
		$state	= $this->get('State');
		$canDo	= LdapAdminHelper::getActions();

		//JToolBarHelper::title(JText::_('COM_LDAPADMIN_VIEW_DASHBOARD'), 'LDAP Admin');
		JToolBarHelper::title(JText::sprintf('COM_LDAPADMIN_TITLE', JTEXT::_('COM_LDAPADMIN_SECTION_AUTHENTICATION')));

		if ($canDo->get('core.admin')) { //this is our "options" button
			JToolBarHelper::preferences('com_ldapadmin');
			JToolBarHelper::divider();
		}
		JToolBarHelper::help('authentication', true);
	}
	
	protected function getPluginModel()
	{
		JLoader::import('joomla.application.component.model');
		JLoader::import('plugin', JPATH_ADMINISTRATOR . '/components/com_plugins/models' );
		
		$pluginModel = JModel::getInstance('plugin', 'PluginsModel' );
		//$pluginModel->setState('id');
		
		return $pluginModel;
		
	}

}
