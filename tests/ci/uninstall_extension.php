<?php
/**
 * PHP Version 5.3
 *
 * Extension uninstall for continuous integration.
 *
 * @package    Shmanic.CI
 * @author     Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright  Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// Make sure we're being called from the command line, not a web interface
(!array_key_exists('REQUEST_METHOD', $_SERVER)) or die;

define('_JEXEC', 1);

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load system defines
if (file_exists(dirname(dirname(dirname(__FILE__))) . '/defines.php'))
{
	require_once dirname(dirname(dirname(__FILE__))) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(dirname(dirname(__FILE__))));
	require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_BASE . '/includes/framework.php';

// Package installation section
if (isset($_SERVER['argv'][1]))
{
	$name = $_SERVER['argv'][1];

	echo "Attempting to uninstall ${name}\n";

	$db = JFactory::getDbo();
	$query = $db->getQuery(true);

	// Get all the enabled Ldap configurations from SQL
	$query->select('name')->select('type')->select('extension_id')
		->from($query->qn('#__extensions'))
		->where('name = ' . $query->quote($name), 'OR')
		->where('element = ' . $query->quote($name), 'OR');

	if ($row = $db->setQuery($query)->loadAssoc())
	{
		$installer = JInstaller::getInstance();

		if ($installer->uninstall($row['type'], $row['extension_id']))
		{
			echo "Successfully uninstalled extension\n";
		}
		else
		{
			echo "Error: Failed to uninstall extension\n";
			die(1);
		}
	}
	else
	{
		echo "Error: Cannot find extension\n";
		die(1);
	}
}
else
{
	echo "Error: No package to uninstall\n";
	die(1);
}
