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
 * Platform Enable field for config.
 *
 * @package     Shmanic.Components
 * @subpackage  Shconfig
 * @since       2.0
 */
class JFormFieldPlatformEnable extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $type = 'PlatformEnable';

	/**
	 * Method to get the radio button field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   2.0
	 */
	public function getInput()
	{
		$this->value = JPluginHelper::isEnabled('system', 'shplatform') ? 1 : 0;

		return parent::getInput();
	}
}
