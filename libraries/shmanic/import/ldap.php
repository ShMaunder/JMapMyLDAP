<?php
/**
 * Import for JMapMyLDAP.
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
	require_once JPATH_PLATFORM . '/shmanic/import.php';
}

if (!defined('SHLDAP_VERSION'))
{
	// Define the JMapMyLDAP version
	define('SHLDAP_VERSION', SHFactory::getConfig()->get('ldap.version'));
}

// Load the global Ldap language file
JFactory::getLanguage()->load('shmanic_ldap', JPATH_ROOT);

// Setup and get the Ldap dispatcher
$dispatcher = SHFactory::getDispatcher('ldap');

// Start the LDAP event monitor for core event triggers
if (class_exists('SHLdapEventMonitor'))
{
	new SHLdapEventMonitor($dispatcher);
}

// Import the Ldap group and use the ldap dispatcher
JPluginHelper::importPlugin('ldap', null, true, $dispatcher);

// Employ the event bouncer to control the global Joomla event triggers
if (class_exists('SHLdapEventBouncer'))
{
	$instance = new SHLdapEventBouncer(
		JDispatcher::getInstance()
	);

	if (method_exists($instance, 'onAfterInitialise'))
	{
		// This is during initialisation
		$instance->onAfterInitialise();
	}
}
