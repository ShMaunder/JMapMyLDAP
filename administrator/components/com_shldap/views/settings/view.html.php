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
 * Settings view class for Shldap.
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @since       2.0
 */
class ShldapViewSettings extends JViewLegacy
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
		$user	= JFactory::getUser();
		$canDo	= ComShldapHelper::getActions();

		JToolBarHelper::title(JText::_('COM_SHLDAP_SETTINGS_MANAGER'), '');

		if ($canDo->get('core.edit') || $canDo->get('core.admin'))
		{
			JToolBarHelper::apply('settings.apply');

				if ($canDo->get('core.admin'))
				{
					JToolBarHelper::preferences('com_shldap');
				}
		}
	}
}
