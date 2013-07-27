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
 * HTML host view class for Shldap.
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @since       2.0
 */
class ShldapViewHost extends JViewLegacy
{
	protected $form = null;

	protected $id = null;

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
		$this->form = $this->get('Form');

		$this->id = $this->form->getValue('id');

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
		JFactory::getApplication()->input->set('hidemainmenu', true);
		$user	= JFactory::getUser();
		$canDo	= ComShldapHelper::getActions();

		JToolBarHelper::title(JText::_('COM_SHLDAP_HOST_MANAGER'), '');

		if ($canDo->get('core.edit') || $canDo->get('core.admin'))
		{
			// TODO: change test
			JToolBarHelper::custom('host.debug', 'extension', '', 'Test', false);

			JToolBarHelper::divider();

			JToolBarHelper::apply('host.apply');

			JToolBarHelper::divider();

			if ($this->id)
			{
				// Is Existing
				JToolBarHelper::cancel('host.cancel', 'JTOOLBAR_CLOSE');
			}
			else
			{
				// Is New
				JToolBarHelper::cancel('host.cancel');
			}
		}
	}
}
