<?php
/**
 * @version		$Id: redirect.php 20740 2011-02-17 10:28:57Z infograf768 $
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.plugin.helper');

/**
 * Redirect component helper.
 *
 * @package		Joomla.Administrator
 * @subpackage	com_redirect
 * @since		1.6
 */
class LdapAdminHelper
{
	public static $extension = 'com_ldapadmin';

	/**
	 * Configure the Linkbar.
	 *
	 * @param	string	The name of the active view.
	 */
	public static function addSubmenu($vName = 'dashboard')
	{
		JSubMenuHelper::addEntry(
			JText::_('COM_LDAPADMIN_SECTION_DASHBOARD'),
			'index.php?option=com_ldapadmin&view=dashboard',
			$vName == 'dashboard'
		);
		JSubMenuHelper::addEntry(
			JText::_('COM_LDAPADMIN_SECTION_SYNC'),
			'index.php?option=com_jmapmyldap&view=sync',
			$vName == 'sync'
		);
		
		
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return	JObject
	 */
	public static function getActions()
	{
		$user		= JFactory::getUser();
		$result		= new JObject;
		$assetName	= 'com_jmapmyldap';

		$actions = array(
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.state', 'core.delete'
		);

		foreach ($actions as $action) {
			$result->set($action,	$user->authorise($action, $assetName));
		}

		return $result;
	}

	/**
	 * Returns an array of standard published state filter options.
	 *
	 * @return	string			The HTML code for the select tag
	 */
	public static function publishedOptions()
	{
		// Build the active state filter options.
		$options	= array();
		$options[]	= JHtml::_('select.option', '*', 'JALL');
		$options[]	= JHtml::_('select.option', '1', 'JENABLED');
		$options[]	= JHtml::_('select.option', '0', 'JDISABLED');
		$options[]	= JHtml::_('select.option', '2', 'JARCHIVED');
		$options[]	= JHtml::_('select.option', '-2', 'JTRASHED');

		return $options;
	}

	/**
	 * Determines if the plugin specified in $name is enabled.
	 *
	 * @return	integer (-1 - extension doesn't exist | 0 - extension disabled | 1 - extension enabled)
	 */
	public static function isEnabled($type, $name)
	{
		$db = JFactory::getDbo();
		$db->setQuery(
			'SELECT enabled' .
			' FROM #__extensions' .
			' WHERE folder = '.$db->quote($type).
			'  AND element = '.$db->quote($name)
		);
		$result = $db->loadResult();
		
		if(is_null($result)) $result = -1;

		if ($error = $db->getErrorMsg()) {
			JError::raiseWarning(500, $error);
		}
		return $result;
	}
	
	public static function getPluginDetails($type, $name, $params = false) 
	{
		$result = array('name'=>$name, 'enabled'=>false, 'version'=>null, 'author'=>null, 'params'=>null, 'description'=>null, 'rawname'=>null);
		
		$db		= JFactory::getDbo();
		$query	= $db->getQuery(true);

		$select = $params ? 'enabled, manifest_cache, params' : 'enabled, manifest_cache';

		$query->select($select)
			->from('#__extensions')
			->where('type ='.$db->Quote('plugin'))
			->where('folder = ' . $db->quote($type))
			->where('element = ' . $db->quote($name))
			->order('ordering');
		
		$plugin = $db->setQuery($query)
			->loadRow();

		// Plugin doesn't exist
		if(is_null($plugin)) {
			$result['enabled'] = -1;
			return $result;
		}
			
		if ($error = $db->getErrorMsg()) {
			JError::raiseWarning(500, $error);
			return false;
		}
		
		// Plugin is enabled
		if(isset($plugin[0]) && $plugin[0]) {
			$result['enabled'] = true;
		}
		
		if(isset($plugin[1])) {
			$registry = new JRegistry();
			$registry->loadString($plugin[1]);
			
			$lang = JFactory::getLanguage();
			$lang->load($registry->get('name'), JPATH_ADMINISTRATOR);
			
			$result['rawname']		= $registry->get('name');
			$result['name'] 		= JText::_($registry->get('name'));
			$result['version'] 		= $registry->get('version');
			$result['author']		= $registry->get('author');
			$result['description']	= JText::_($registry->get('description'));
			
		}
		
		if($params && isset($plugin[2])) {
			$registry = new JRegistry();
			$registry->loadString($plugin[2]);
			$result['params'] = $registry->toArray();
		}
		
		return $result;
		
	}
	
	public static function getPlugins() 
	{
		//jimport('joomla.application.component.model');
		
		// TODO: sort out the hardcoded stuff...
		$plugins = array();
		
		// Import the authentication plugin (single)
		$plugins[] = self::getAuthPlugin();
		
		// Import the ldap dispatcher plugin (single)
		$plugins[] = self::getDispatchPlugin();
		
		// Import the LDAP plugins (one or more)
		$ldaps = self::getLdapPlugins();
		foreach($ldaps as $ldap) {
			$plugins[] = $ldap;
		}

		foreach($plugins as &$plugin) {
			$plugin['controller'] = null;
			
			// Does this controller exist
			if(JLoader::import($plugin['rawname'], JPATH_ADMINISTRATOR . '/components/com_ldapadmin/controllers')) {
				$plugin['controller'] = $plugin['rawname'];
			}

		}

		return $plugins;
	}
	
	public static function getAuthPlugin() 
	{
		$config = JComponentHelper::getParams('com_ldapadmin');	
		
		$name = $config->get('auth_plugin', 'jmapmyldap');
		
		return self::getPluginDetails('authentication', $name);
	}
	
	public static function getDispatchPlugin()
	{
		$config = JComponentHelper::getParams('com_ldapadmin');	
		
		$name = $config->get('dispatcher_plugin', 'ldapdispatcher');
		
		return self::getPluginDetails('system', $name);
	}
	
	public static function getLdapPlugins()
	{
		$return = array();
		
		$config = JComponentHelper::getParams('com_ldapadmin');	
		
		$type = $config->get('plugin_type', 'ldap');
		
		$db		= JFactory::getDbo();
		$query	= $db->getQuery(true);

		$query->select('element')
			->from('#__extensions')
			->where('type ='.$db->Quote('plugin'))
			->where('folder = ' . $db->quote($type))
			->order('ordering');
		
		$plugins = $db->setQuery($query)
			->loadColumn();
		
		foreach($plugins as $plugin) {
			$return[] = self::getPluginDetails($type, $plugin);
		}
		
		return $return;
	}
	
	/*
	 * Checks whether the PHP LDAP extension is enabled.
	 * 
	 * @return  boolean  True if PHP LDAP extension is included
	 * 
	 */
	public static function checkPhpLdap() 
	{
		return extension_loaded('ldap');
	}
	
	/*
	 * Get the ID of the plugin specified in type and name
	 * 
	 * @return  integer  ID of plugin
	 * 
	 */
	public static function getPluginId($type, $name) {
		$db = JFactory::getDbo();
		$db->setQuery(
			'SELECT extension_id' .
			' FROM #__extensions' .
			' WHERE folder = '.$db->quote($type).
			'  AND element = '.$db->quote($name)
		);
		$result = $db->loadResult();
		
		if(is_null($result)) $result = -1;

		if ($error = $db->getErrorMsg()) {
			JError::raiseWarning(500, $error);
		}
		return $result;
	}
	
}