<?php

abstract class TestsHelper
{
	/**
	 * Read in the case XML file and parse it to an
	 * array in the form array(category=>case).
	 *
	 * @param string $file Path to the XML file
	 *
	 * @return array Array of cases
	 * @since  1.0
	 */
	public static function getLdapConfig($id, $file = null)
	{
		$result = array();

		$config = static::getConfigXml($id, $file);

		foreach($config as $key=>$value)
		{
			if (!is_array($value))
			{
				$result[$key] = (string) $value[0];
			}
		}

		return $result;
	}

	public static function getUserCreds($username = null, $file = null)
	{
		$config = static::getConfigXml(100);

		$user = array();

		if (is_null($username))
		{
			// Get a random username
			$users = $config->standard;
			$index = (rand(1, $users->count()) - 1);

			$user = $users[$index];
		}

		if (strtolower($username) === 'admin' || strtolower($username) === 'administrator')
		{
			$user = $config->admin;
		}
		else if (!is_null($username))
		{
			//$user = $config->normal->$username;
			$x = $config->xpath("standard[@username='$username']");

			if (isset($x[0]))
			{
				$user = $x[0];
			}
		}

		$result = array();

		// Save all xml user config attributes to an array
		foreach ($user->attributes() as $key => $value)
		{
			$result[(string) $key] = (string) $value;
		}

		return $result;
	}

	public static function getConfigXml($id, $file = null)
	{
		if (is_null($file))
		{
			$file = JPATH_TESTS . '/config.xml';
		}

		if (!is_file($file))
		{
			return false;
		}

		// Load the XML file
		$xml = \simplexml_load_file($file, 'SimpleXMLElement');

		// Get all the category tags ignoring anything else in the file
		$x = $xml->xpath("/configs/config[@id={$id}]");

		if (isset($x[0]))
		{
			// Config exists successfully
			return $x[0];
		}

		return array();
	}

}
