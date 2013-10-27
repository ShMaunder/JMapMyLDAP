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
 * Helper class for Shconfig.
 *
 * @package     Shmanic.Components
 * @subpackage  Shconfig
 * @since       2.0
 */
abstract class ShconfigHelper
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string  $vName  The name of the active view.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public static function addSubmenu($vName)
	{
		JSubMenuHelper::addEntry(
			JText::_('COM_SHCONFIG_SUBMENU_SETTINGS'),
			'index.php?option=com_shconfig&view=settings&task=settings.edit',
			$vName == 'settings'
		);

		JSubMenuHelper::addEntry(
			JText::_('COM_SHCONFIG_SUBMENU_DATABASE'),
			'index.php?option=com_shconfig&view=items',
			$vName == 'items'
		);
	}
}
