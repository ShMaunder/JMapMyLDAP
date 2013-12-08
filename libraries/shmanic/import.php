<?php
/**
 * Import for Shmanic Platform. Loads the Autoloader and Factory.
 *
 * PHP Version 5.3
 *
 * @package    Shmanic.Libraries
 * @author     Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright  Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

// Load the global platform language file
JFactory::getLanguage()->load('shmanic_platform', JPATH_ROOT);

// Platform directory location
if (!defined('SHPATH_PLATFORM'))
{
	define('SHPATH_PLATFORM', JPATH_PLATFORM . '/shmanic');
}

if (!class_exists('SHLoader'))
{
	// Include the autoloader
	require_once SHPATH_PLATFORM . '/loader.php';
}

// Register the autoloader for all shmanic libraries
SHLoader::setup();

if (!class_exists('SHFactory'))
{
	// Manually include the factory
	require_once SHPATH_PLATFORM . '/factory.php';
}

// Register the JForm field classes
if (file_exists(SHPATH_PLATFORM . '/form/fields'))
{
	JForm::addFieldPath(SHPATH_PLATFORM . '/form/fields');
}

// Register the JForm rule classes
if (file_exists(SHPATH_PLATFORM . '/form/rules'))
{
	JForm::addRulePath(SHPATH_PLATFORM . '/form/rules');
}

// We need to check for the legacy folder which indicates Joomla 3+ / Platform
if (file_exists(JPATH_PLATFORM . '/legacy'))
{
	// Resolve some legacy classes without importing all legacy classes
	JLoader::register('JDispatcher', JPATH_PLATFORM . '/legacy/dispatcher/dispatcher.php');
	JLoader::register('JApplication', JPATH_PLATFORM . '/legacy/application/application.php');
	JLoader::register('JApplicationHelper', JPATH_PLATFORM . '/legacy/application/helper.php');
	JLoader::register('JComponentHelper', JPATH_PLATFORM . '/legacy/component/helper.php');
}
else
{
	// Register JComponentHelper in case it is required later
	JLoader::register('JComponentHelper', JPATH_PLATFORM . '/joomla/application/component/helper.php');
}
