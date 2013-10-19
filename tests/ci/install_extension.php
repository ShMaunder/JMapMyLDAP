<?php
/**
 * PHP Version 5.3
 *
 * Extension installation for continuous integration.
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
if (isset($_SERVER['argv'][1]) && file_exists($_SERVER['argv'][1]))
{
	$package = $_SERVER['argv'][1];

	echo "Attempting to unpack and install ${package}\n";

	if ($unpack = JInstallerHelper::unpack($package))
	{
		if (JInstaller::getInstance()->install($unpack['extractdir']))
		{
			echo "Successfully installed extension\n";
		}
		else
		{
			echo "Error: Failed to install extension\n";
		}


		JFolder::delete($unpack['extractdir']);
	}
	else
	{
		echo "Error: Failed to unpack\n";
		die(1);
	}
}
else
{
	echo "Error: No package to install\n";
	die(1);
}
