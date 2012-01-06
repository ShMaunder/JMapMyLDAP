<?php

// No direct access.
defined('_JEXEC') or die;
jimport('joomla.application.component.controller');

class LdapAdminController extends JController
{
	/**
	 * @var		string	The default view.
	 * @since	1.6
	 */
	protected $default_view = 'dashboard';

	/**
	 * Method to display a view.
	 *
	 * @param	boolean			If true, the view output will be cached
	 * @param	array			An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return	JController		This object to support chaining.
	 * @since	1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		require_once JPATH_COMPONENT.'/helpers/ldapadmin.php';
		
		//LdapAdminHelper::getPlugins();

		// Load the submenu.
		LdapAdminHelper::addSubmenu(JRequest::getCmd('view', 'dashboard'));

		$view		= JRequest::getCmd('view', $this->default_view);
		$layout 	= JRequest::getCmd('layout', 'default');
		$id			= JRequest::getInt('id');

		// Check for edit form.
		/*if ($view == 'link' && $layout == 'edit' && !$this->checkEditId('com_jmapmyldap.edit.link', $id)) {
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(JRoute::_('index.php?option=com_jmapmyldap&view=' . $this->default_view, false));

			return false;
		}*/

		parent::display();
	}
}