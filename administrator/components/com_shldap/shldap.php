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

// Include dependancies
jimport('joomla.application.component.controller');

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_shldap'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

// Register the helper class for this component
JLoader::register('ComShldapHelper', JPATH_COMPONENT . '/helpers/shldap.php');

// Check if the Shmanic platform has already been imported
if (!defined('SHPATH_PLATFORM'))
{
	// Shmanic Platform import
	if (!file_exists(JPATH_PLATFORM . '/shmanic/import.php'))
	{
		JError::raiseError(500, JText::_('COM_SHLDAP_PLATFORM_MISSING'));

		return false;
	}

	require_once JPATH_PLATFORM . '/shmanic/import.php';
	SHImport('ldap');
}

// Get the input class
$input = JFactory::getApplication()->input;

// Launch the controller.
$controller = JControllerLegacy::getInstance('Shldap');
$controller->execute($input->get('task', 'display', 'cmd'));
$controller->redirect();
