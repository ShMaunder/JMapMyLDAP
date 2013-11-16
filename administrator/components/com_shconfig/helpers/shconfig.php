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
	 * @param   string  $lName  Active layout name.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public static function addSubmenu($vName, $lName = null)
	{
		$mods = self::enabledModules();

		JSubMenuHelper::addEntry(
			JText::_('COM_SHCONFIG_SUBMENU_SETTINGS_BASE'),
			'index.php?option=com_shconfig&task=settings.edit&layout=base',
			($vName == 'settings' && $lName == 'base')
		);

		foreach ($mods as $mod)
		{
			if ($mod === 'ldap')
			{
				JSubMenuHelper::addEntry(
					JText::_('COM_SHCONFIG_SUBMENU_SETTINGS_LDAP'),
					'index.php?option=com_shconfig&task=settings.edit&layout=ldap',
					($vName == 'settings' && $lName == 'ldap')
				);
			}
			elseif ($mod === 'sso')
			{
				JSubMenuHelper::addEntry(
					JText::_('COM_SHCONFIG_SUBMENU_SETTINGS_SSO'),
					'index.php?option=com_shconfig&task=settings.edit&layout=sso',
					($vName == 'settings' && $lName == 'sso')
				);
			}
		}

		JSubMenuHelper::addEntry(
			JText::_('COM_SHCONFIG_SUBMENU_DATABASE'),
			'index.php?option=com_shconfig&view=items',
			$vName == 'items'
		);
	}

	/**
	 * Retrieves the enabled platform modules as an array.
	 *
	 * @return   array  Array of modules or blank array if non enabled.
	 *
	 * @since    2.0
	 */
	public static function enabledModules()
	{
		$db = JFactory::getDbo();

		$imports = $db->setQuery(
			$db->getQuery(true)
				->select($db->quoteName('value'))
				->from($db->quoteName('#__sh_config'))
				->where($db->quoteName('name') . ' = ' . $db->quote('platform:import'))
		)->loadResult();

		if ($imports)
		{
			return json_decode($imports);
		}

		return array();
	}
}
