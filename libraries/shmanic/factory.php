<?php

defined('JPATH_PLATFORM') or die;

jimport('joomla.event.dispatcher');

abstract class SHFactory
{

	/**
	 * Stores the active (i.e. successfully authenticated) LDAP instance
	 * 
	 * @var    JLDAP2
	 * @since  2.0
	 */
	public static $ldapClient = null;

	public static $dispatcher = array();
	
	// @return  JLDAP2
	public static function getLDAPClient($id = null, $config = null)
	{

	}

	/**
	 * 
	 * @param unknown_type $name
	 * 
	 * @return  JDispatcher
	 */
	public static function getDispatcher($name = 'SHFactory')
	{
		if (!isset(self::$dispatcher[$name]))
		{
			self::$dispatcher[$name] = new JDispatcher;
		}

		return self::$dispatcher[$name];
	}
}

