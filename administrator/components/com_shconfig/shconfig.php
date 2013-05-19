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

// Include dependancies
jimport('joomla.application.component.controller');

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_shconfig'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

// Register the helper class for this component
JLoader::register('ShconfigHelper', JPATH_COMPONENT . '/helpers/shconfig.php');

// Get the input class
$input = JFactory::getApplication()->input;

// Launch the controller.
$controller = JControllerLegacy::getInstance('Shconfig');
$controller->execute($input->get('task', 'display', 'cmd'));
$controller->redirect();
