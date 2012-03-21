<?php

defined('_JEXEC') or die;

jimport('joomla.application.component.view');

class LdapAdminViewSync extends JView
{
	protected $enabled;
	protected $items;
	protected $pagination;
	protected $state;
	protected $ldapExt = false;

	/**
	 * Display the view
	 *
	 * @since	1.6
	 */
	public function display($tpl = null)
	{

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
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
		JToolBarHelper::title(JText::sprintf('COM_LDAPADMIN_TITLE', JTEXT::_('COM_LDAPADMIN_SECTION_SYNC')));

		if ($canDo->get('core.admin')) { //this is our "options" button
			JToolBarHelper::preferences('com_ldapadmin');
			JToolBarHelper::divider();
		}
		JToolBarHelper::help('sync', true);
	}
	
}
