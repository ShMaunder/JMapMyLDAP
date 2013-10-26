<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Components
 * @subpackage  Shconfig
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Item view class for Shconfig.
 *
 * @package     Shmanic.Components
 * @subpackage  Shconfig
 * @since       2.0
 */
class ShconfigViewItem extends JViewLegacy
{
	protected $item = null;

	protected $form = null;

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
		$this->form		= $this->get('Form');
		$this->item		= $this->get('Item');
		$this->state	= $this->get('State');

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
		$isNew	= ($this->item->id == 0);

		JToolBarHelper::title(JText::_('COM_SHCONFIG_MANAGER_ITEM'), '');

		if (JFactory::getUser()->authorise('core.admin', 'com_shconfig'))
		{
			JToolBarHelper::apply('item.apply');
			JToolBarHelper::save('item.save');
			JToolBarHelper::save2new('item.save2new');

			JToolBarHelper::divider();
		}

		if ($isNew)
		{
			JToolBarHelper::cancel('item.cancel');
		}
		else
		{
			JToolBarHelper::cancel('item.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
