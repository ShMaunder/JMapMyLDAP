<?php

defined('JPATH_PLATFORM') or die;

abstract class TCorePlugin
{
	/**
	 * Class constructor.
	 *
	 * @param string $params Parameters to be saved to the class properties
	 *
	 * @since 1.0
	 */
	function __construct($params = array())
	{
		/* Loop around all variables and assign any parameters
		 * that have a class variable to the matching name.
		 */
		$properties = get_class_vars(get_class($this));
		foreach (array_keys($properties) as $property) {
			if (isset($params[$property])) {
				$this->$property = $params[$property];
			}
		}
	}
	
}