<?php
/**
 * Bootstrap for JSSOMySite.
 *
 * PHP Version 5.3
 *
 * @package    Shmanic.Libraries
 * @author     Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright  Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

if (!defined('SHPATH_PLATFORM'))
{
	// Load the platform
	require_once JPATH_PLATFORM . '/shmanic/bootstrap.php';
}

if (!defined('SHSSO_VERSION'))
{
	// Define the JSSOMySite version [TODO: move to platform]
	define('SHSSO_VERSION', '2.0.0');
}

// Load the global SSO language file
JFactory::getLanguage()->load('shmanic_sso', JPATH_ROOT);

// Setup the SSO error logger - this should be used with SHLogEntriesId
JLog::addLogger(
	array(
		'logger' => 'formattedtext',
		'text_file' => 'sso.error.php',
		'text_entry_format' => '{DATETIME}	{ID}	{MESSAGE}'
	),
	JLog::ERROR,
	array('sso')
);

// Employ the event monitor for SSO
if (class_exists('SHSsoMonitor'))
{
	$instance = new SHSsoMonitor(
		JDispatcher::getInstance()
	);

	if (method_exists($instance, 'onAfterInitialise'))
	{
		// This is during initialisation
		$instance->onAfterInitialise();
	}
}
