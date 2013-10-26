<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Hosts view class for Shldap.
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @since       2.0
 */
class ShldapViewHosts extends JViewLegacy
{
	protected $items = null;

	protected $pagination = null;

	protected $state = null;

	/**
	 * Method to display the view.
	 *
	 * @param   string  $tpl  A template file to load. [optional]
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @since   2.0
	 */
	public function display($tpl = null)
	{
		$this->items = $this->get('Items');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));

			return false;
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Method to configure the toolbar for this view.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	protected function addToolbar()
	{
		$state	= $this->get('State');

		JToolBarHelper::title(JText::_('COM_SHLDAP_HOSTS_MANAGER'), '');

		if (JFactory::getUser()->authorise('core.admin', 'com_shldap'))
		{
			JToolBarHelper::addNew('host.add');
			JToolBarHelper::editList('host.edit');

			JToolBarHelper::deleteList('', 'hosts.delete');
			JToolBarHelper::divider();

			JToolBarHelper::preferences('com_shldap');
		}
	}
}
