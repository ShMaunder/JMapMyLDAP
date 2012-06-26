<?php

// Prevent against direct access of PHP files
define('_JEXEC', 1);

/* For this project, we will assume the sapi is always command
 * line based though this should be extended so other methods
 * such as a web browser can initiate these tests.
 */

// Fix magic quotes.
@ini_set('magic_quotes_runtime', 0);

// Maximise error reporting.
@ini_set('zend.ze1_compatibility_mode', '0');
error_reporting(E_ALL & ~E_STRICT);
ini_set('display_errors', 1);

/*
 * Ensure that required path constants are defined.  These can be overridden within the phpunit.xml file
 * if you chose to create a custom version of that file.
 */
if (!defined('JPATH_TESTS'))
{
	define('JPATH_TESTS', realpath(__DIR__));
}
if (!defined('JPATH_PLATFORM'))
{
	define('JPATH_PLATFORM', realpath(dirname(JPATH_TESTS) . '/libraries'));
}
if (!defined('JPATH_BASE'))
{
	define('JPATH_BASE', realpath(JPATH_TESTS . '/tmp'));
}
if (!defined('JPATH_ROOT'))
{
	define('JPATH_ROOT', realpath(JPATH_BASE));
}
if (!defined('JPATH_CACHE'))
{
	define('JPATH_CACHE', JPATH_BASE . '/cache');
}
if (!defined('JPATH_CONFIGURATION'))
{
	define('JPATH_CONFIGURATION', JPATH_BASE);
}
if (!defined('JPATH_MANIFESTS'))
{
	define('JPATH_MANIFESTS', JPATH_BASE . '/manifests');
}
if (!defined('JPATH_PLUGINS'))
{
	define('JPATH_PLUGINS', JPATH_BASE . '/plugins');
}
if (!defined('JPATH_THEMES'))
{
	define('JPATH_THEMES', JPATH_BASE . '/themes');
}

// Import the platform.
require_once JPATH_PLATFORM . '/import.php';

// Import & Register the autoloader
require_once JPATH_TESTS . '/loader.php';
TLoader::setup();

// Setup the configuration
jimport('joomla.application.component.helper');
JFactory::getConfig(JPATH_ROOT . '/configuration.php');
