<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Plugin
 * @subpackage  Log.Ldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.plugin.plugin');

/**
 * LDAP logging plugin.
 *
 * @package     Shmanic.Plugin
 * @subpackage  Log.Ldap
 * @since       2.0
 */
class PlgShlogLdap extends JPlugin
{
	/**
	 * Logger for text based file log.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const LOGGER_FILE = 'formattedtext';

	/**
	 * Logger for on-screen logging.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const LOGGER_SCREEN = 'messagequeue';

	/**
	 * Fired on log initialiser.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onLogInitialise()
	{
		// This is the columns that the log files will use
		$fileFormat = str_replace('\t', "\t", $this->params->get('FILE_FORMAT', '{DATETIME}\t{ID}\t{MESSAGE}'));

		// Categories to log
		$categories = array('ldap', 'auth');

		/*
		 * Deals with the Information level logs.
		 */
		if ($this->params->get('enable_info', true))
		{
			// Setup a information file logger
			JLog::addLogger(
				array(
					'logger' => self::LOGGER_FILE,
					'text_file' => $this->params->get('log_name_info', 'ldap.info.php'),
					'text_entry_format' => $fileFormat
				),
				JLog::INFO,
				$categories
			);
		}

		/*
		 * Deals with the Debugging level logs (which includes all levels internally).
		 */
		if ($this->params->get('enable_debug', true))
		{
			// Setup a debugger file logger
			JLog::addLogger(
				array(
					'logger' => self::LOGGER_FILE,
					'text_file' => $this->params->get('log_name_debug', 'ldap.debug.php'),
					'text_entry_format' => $fileFormat
				),
				JLog::ALL,
				$categories
			);
		}

		/*
		 * Deals with the Error level logs.
		 */
		if ($this->params->get('enable_error', true))
		{
			// Setup a error file logger
			JLog::addLogger(
				array(
					'logger' => self::LOGGER_FILE,
					'text_file' => $this->params->get('log_name_error', 'ldap.error.php'),
					'text_entry_format' => $fileFormat
				),
				JLog::ERROR,
				$categories
			);

			if ($this->params->get('error_to_screen', true))
			{
				// Setup a error on-screen logger
				JLog::addLogger(
					array('logger' => self::LOGGER_SCREEN),
					JLog::ERROR,
					$categories
				);
			}
		}
	}
}
