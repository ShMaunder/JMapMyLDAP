<?php
/**
 * Import for JSSOMySite.
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

if (!defined('SHPATH_PLATFORM'))
{
	// Load the platform
	require_once JPATH_PLATFORM . '/shmanic/import.php';
}

if (!defined('SHSSO_VERSION'))
{
	// Define the JSSOMySite version [TODO: move to platform]
	define('SHSSO_VERSION', '2.0.0');
}

// Load the global SSO language file
JFactory::getLanguage()->load('shmanic_sso', JPATH_ROOT);

// Employ the event monitor for SSO
if (class_exists('SHSsoMonitor'))
{
	$dispatcher = JDispatcher::getInstance();

	$instance = new SHSsoMonitor(
		$dispatcher
	);

	if (method_exists($instance, 'onAfterInitialise'))
	{
		// This is during initialisation
		$instance->onAfterInitialise();
	}
}
