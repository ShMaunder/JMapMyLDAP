<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Util
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Basic parameter validation class primarily for configurations.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Util
 * @since       2.1
 */
class SHUtilValidate
{
	/**
	 * Array of callback rules for validation.
	 *
	 * @var    array
	 * @since  2.1
	 */
	private $rules = array();

	/**
	 * Gets a singleton instance.
	 *
	 * @return  SHUtilValidate
	 *
	 * @since   2.1
	 */
	public static function getInstance()
	{
		static $instance;

		$instance = empty($instance) ? new self : $instance;

		return $instance;
	}

	/**
	 * Registers a callback validation rule.
	 *
	 * @param   string  $callback  Callback definition.
	 *
	 * @return  void
	 *
	 * @since   2.1
	 */
	public function register($callback)
	{
		$this->rules[] = $callback;
	}

	/**
	 * Registers a callback validation rule for a adapter. Also checks
	 * the method exists in the adapter before registering.
	 *
	 * @param   string  $class  Adapter class name.
	 *
	 * @return  void
	 *
	 * @since   2.1
	 */
	public function registerAdapter($class)
	{
		if (method_exists($class, 'validate'))
		{
			$this->register("${class}::validate");
		}
	}

	/**
	 * Initiates the validation against all the callback rules.
	 *
	 * @param   JRegistry  &$config  Configuration or parameters to check.
	 *
	 * @return  void
	 *
	 * @since   2.1
	 */
	public function validate(&$config)
	{
		foreach ($this->rules as $rule)
		{
			// Validate the config against the callback rule
			call_user_func_array($rule, array(&$config));
		}
	}
}
