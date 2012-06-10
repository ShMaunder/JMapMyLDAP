<?php
/**
 * Bootstrap for JMapMyLDAP.
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

if (!defined('SHLDAP_VERSION'))
{
	// Define the JMapMyLDAP version [TODO: make nicer]
	define('SHLDAP_VERSION', '2.0.0');
}

// Setup and get the Ldap dispatcher
$dispatcher = SHFactory::getDispatcher('ldap');

// Setup the global debug Ldap log monitor if it exists
if (class_exists('SHLdapLogMonitor'))
{
	new SHLdapLogMonitor(
		$dispatcher,
		array('enabled' => true, 'level' => JLog::ALL)
	);
}

// Import the Ldap group and use the ldap dispatcher
JPluginHelper::importPlugin('ldap', null, true, $dispatcher);

// Import the LdapLog group and use the ldap dispatcher
JPluginHelper::importPlugin('ldaplog', null, true, $dispatcher);

// Employ the event bouncer to control the global Joomla event triggers
if (class_exists('SHLdapEventBouncer'))
{
	new SHLdapEventBouncer(
		JDispatcher::getInstance()
	);
}

// Call the after boot trigger
$dispatcher->trigger('onAfterBoot');
