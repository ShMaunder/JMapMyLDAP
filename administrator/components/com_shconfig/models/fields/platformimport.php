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

JFormHelper::loadFieldClass('checkboxes');

/**
 * Platform Import field for config.
 *
 * @package     Shmanic.Components
 * @subpackage  Shconfig
 * @since       2.0
 */
class JFormFieldPlatformImport extends JFormFieldCheckboxes
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $type = 'PlatformImport';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   2.0
	 */
	public function getOptions()
	{
		$files = scandir(JPATH_PLATFORM . '/shmanic/import');

		$options = array();

		foreach ($files as $file)
		{
			if (substr($file, -4, 4) == '.php')
			{
				$option = array('value' => str_replace('.php', '', $file), 'label' => str_replace('.php', '', $file));

				// Create a new option object based on the import files
				$tmp = JHtml::_(
					'select.option', (string) $option['value'], trim((string) $option['label']), 'value', 'text', 0
				);

				$tmp->checked = false;

				$options[] = $tmp;
			}
		}

		// Replace the value with a decoded json version
		$value = json_decode($this->value, true);
		$this->value = $value ? array_combine($value, $value) : array();

		reset($options);

		return $options;
	}
}
