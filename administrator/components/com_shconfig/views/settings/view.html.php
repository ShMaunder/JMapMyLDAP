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
 * Settings view class for Shldap.
 *
 * @package     Shmanic.Components
 * @subpackage  Shconfig
 * @since       2.0
 */
class ShconfigViewSettings extends JViewLegacy
{
	protected $form = null;

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

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));

			return false;
		}

		$this->addToolbar($this->getLayout());
		parent::display($tpl);
	}

	/**
	 * Method to configure the toolbar for this view.
	 *
	 * @param   string  $lName  Layout name.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	protected function addToolbar($lName = 'base')
	{
		$user	= JFactory::getUser();

		JToolBarHelper::title(JText::sprintf('COM_SHCONFIG_SETTINGS_MANAGER', ucfirst($lName)), '');

		if (JFactory::getUser()->authorise('core.admin', 'com_shconfig'))
		{
			JToolBarHelper::apply('settings.apply');
			JToolBarHelper::divider();
			JToolBarHelper::preferences('com_shconfig');
		}
	}
}
