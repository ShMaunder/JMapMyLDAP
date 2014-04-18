<?php
/**
 * Import for JMapMyLDAP.
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

if (!defined('SHLDAP_VERSION'))
{
	// Define the JMapMyLDAP version
	define('SHLDAP_VERSION', SHFactory::getConfig()->get('ldap.version'));
}

// Load the global Ldap language file
JFactory::getLanguage()->load('shmanic_ldap', JPATH_ROOT);

// Push the reqcert setting if defined
if ($reqcert = (int) SHFactory::getConfig()->get('ldap.reqcert', 0))
{
	if ($reqcert === 1)
	{
		putenv('LDAPTLS_REQCERT=never');
	}
	elseif ($reqcert === 2)
	{
		putenv('LDAPTLS_REQCERT=allow');
	}
	elseif ($reqcert === 3)
	{
		putenv('LDAPTLS_REQCERT=try');
	}
	elseif ($reqcert === 4)
	{
		putenv('LDAPTLS_REQCERT=hard');
	}
}

// Setup and get the Ldap dispatcher
$dispatcher = SHFactory::getDispatcher('ldap');

// Start the LDAP event debugger only if global jdebug is switched on
if (defined('JDEBUG') && JDEBUG && class_exists('SHLdapEventDebug'))
{
	new SHLdapEventDebug($dispatcher);
}

// Import the Ldap group and use the ldap dispatcher
JPluginHelper::importPlugin('ldap', null, true, $dispatcher);

// Employ the event bouncer to control the global Joomla event triggers
if (class_exists('SHLdapEventBouncer'))
{
	$dispatcher = JDispatcher::getInstance();

	$instance = new SHLdapEventBouncer(
		$dispatcher
	);
}
