<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Form.Fields
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('textarea');

/**
 * Json to text area field.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Form.Fields
 * @since       2.0
 */
class SHFormFieldJsontextarea extends JFormFieldTextarea
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $type = 'Jsontextarea';

	/**
	 * Method to get the textarea field input markup.
	 * Use the rows and columns attributes to specify the dimensions of the area.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   2.0
	 */
	public function getInput()
	{
		if ($this->value)
		{
			$value = json_decode($this->value);

			$this->value = implode("\n", $value);
		}

		return parent::getInput();
	}
}
