<?php
/**
 * PHP Version 5.3
 *
 * @package    Shmanic.Scripts
 * @author     Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright  Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Installer script for pkg_autoupdater.
 * This package is only used to update all the packages through Joomla's
 * auto updater.
 *
 * @package  Shmanic.Scripts
 * @since    2.0
 */
class Pkg_ShautoupdaterInstallerScript
{
	/**
	 * Minimum PHP version to install this extension.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const MIN_PHP_VERSION = '5.3.0';

	/**
	 * Method to run before an install/update/uninstall method.
	 *
	 * @param   string  $type    Type of change (install, update or discover_install).
	 * @param   object  $parent  Object of class calling this method.
	 *
	 * @return  boolean  False to abort installation.
	 *
	 * @since   2.0
	 */
	public function preflight($type, $parent)
	{
		// Check the PHP version is at least at 5.3.0
		if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '<'))
		{
			JFactory::getApplication()->enqueueMessage(
				JText::sprintf('PKG_SHAUTOUPDATER_PREFLIGHT_PHP_VERSION', PHP_VERSION, self::MIN_PHP_VERSION),
				'error'
			);

			return false;
		}

		if ($type == 'install' || $type == 'update')
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query->select($db->quoteName('element'))
				->select($db->quoteName('name'))
				->select($db->quoteName('type'))
				->from($db->quoteName('#__extensions'));

			try
			{
				$list = $db->setQuery($query)->loadAssocList('name');

				/*
				 * Check all files that were packaged in this auto updater with that
				 * of currently installed Joomla extensions. If extension is installed
				 * then add to the update list.
				 */
				foreach ($parent->manifest->autoupdates->file as $file)
				{
					$type = (string) $file->attributes()->type;
					$name = (string) $file->attributes()->id;

					if (isset($list[$name]) && $list[$name]['type'] == $type)
					{
						// This extension is installed so add it to the update list
						$parent->manifest->files->addChild('file', (string) $file);
					}
				}

				return true;
			}
			catch (Exception $e)
			{
				JFactory::getApplication()->enqueueMessage(
					$e->getMessage(),
					'error'
				);
			}

			return false;
		}
	}

	/**
	 * Method to run after an install/update/uninstall method.
	 * Removes this autoupdator package from the Joomla database.
	 *
	 * @param   string  $type     Type of change (install, update or discover_install).
	 * @param   object  $parent   Object of class calling this method.
	 * @param   array   $results  Array of extension results.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function postflight($type, $parent, $results = array())
	{
		if ($type == 'install' || $type == 'update')
		{
			$db = JFactory::getDbo();

			$db->setQuery(
				$db->getQuery(true)
					->delete('#__extensions')
					->where($db->quoteName('name') . ' = ' . $db->quote('shautoupdater'))
			)
			->execute();
		}
	}
}
