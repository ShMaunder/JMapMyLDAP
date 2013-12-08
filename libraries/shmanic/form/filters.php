<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Form
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Common set of static Form Filters.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Form
 * @since       2.0
 */
abstract class SHFormFilters
{
	/**
	 * Converts from newlines to json format.
	 *
	 * @param   string  $value  Raw input.
	 *
	 * @return  string  Json string
	 *
	 * @since   2.0
	 */
	public static function newline2json($value)
	{
		if (!empty($value))
		{
			return json_encode(preg_split('/\r\n|\n|\r/', $value));
		}

		return '';
	}
}
