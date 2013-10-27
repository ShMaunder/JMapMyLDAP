<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Components
 * @subpackage  Shconfig
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('list');

/**
 * User Adapter field for config.
 *
 * @package     Shmanic.Components
 * @subpackage  Shconfig
 * @since       2.0
 */
class JFormFieldUserAdapter extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $type = 'UserAdapter';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   2.0
	 */
	public function getOptions()
	{
		$files = scandir(JPATH_PLATFORM . '/shmanic/user/adapters');

		$adapters = array();

		foreach ($files as $file)
		{
			if (substr($file, -4, 4) == '.php')
			{
				// Only show the name without the file extension
				$adapters[] = str_replace('.php', '', $file);
			}
		}

		return array_combine($adapters, $adapters);
	}
}
