<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

/**
 * Hosts model class for Shldap.
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @since       2.0
 */
class ShldapModelHosts extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JController
	 * @since   2.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'name', 'a.name',
				'enabled', 'a.enabled',
				'ordering', 'a.ordering'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Gets all the LDAP configurations.
	 *
	 * @return  array  Array of objects containing LDAP config information.
	 *
	 * @since   2.0
	 */
	public function getItems()
	{
		$store = $this->getStoreId();

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$config = SHFactory::getConfig();

		// Config managed default domain
		$default = $config->get('ldap.defaultconfig');

		// Config managed LDAP host source
		$source = (int) $config->get('ldap.config', SHLdapHelper::CONFIG_SQL);

		if ($source === SHLdapHelper::CONFIG_SQL)
		{
			parent::getItems();

			foreach ($this->cache[$store] as $row)
			{
				/*
				 * We need to mark the LDAP hosts that is default.
				 */
				$row->default = ($row->name == $default) ? true : false;

				/*
				 * Count the ID number of users in each LDAP host.
				 */
				$row->users = $this->getCountDomain($row->id, $row->name);

				/*
				 * Decode the paramters to get the host and port
				 */
				$decode = json_decode($row->params);
				$row->host = $decode->host;
				$row->port = $decode->port;
			}
		}
		else
		{
			try
			{
				// Get all the Ldap config IDs and Names
				$ids = SHLdapHelper::getConfigIDs();

				$this->cache[$store] = array();

				foreach ($ids as $id => $name)
				{
					// Get this specific Ldap configuration based on name
					$config = SHLdapHelper::getConfig($name);

					$result = new stdClass;
					$result->id = $id;
					$result->name = $name;
					$result->host = $config->get('host');
					$result->port = $config->get('port');
					$result->users = $this->getCountDomain($result->id, $result->name);

					// Lets add this config to our results pool
					$this->cache[$store][] = $result;
				}
			}
			catch (Exception $e)
			{
				// We need to look for a string instead of an array on error
				return $e->getMessage();
			}
		}

		return $this->cache[$store];
	}

	/**
	 * Returns whether the records can be edited. Currently,
	 * we can only edit if its SQL.
	 *
	 * @return  boolean  True if editable.
	 *
	 * @since   2.0
	 */
	public function getIsEditable()
	{
		$source = (int) SHFactory::getConfig()->get('ldap.config', SHLdapHelper::CONFIG_SQL);

		if ($source === SHLdapHelper::CONFIG_SQL)
		{
			return true;
		}

		return false;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication('administrator');

		// Load the filter state.
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$state = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $state);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_shldap');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('ordering', 'asc');
	}

	/**
	 * Counts the number of users on a specific adapter domain.
	 *
	 * @param   integer  $id    Domain ID.
	 * @param   string   $name  Domain name.
	 *
	 * @return  integer  Number of users.
	 *
	 * @since   2.0
	 */
	public function getCountDomain($id, $name)
	{
		$db = JFactory::getDbo();

		$authDomain = SHUserHelper::PARAM_AUTH_DOMAIN;

		$query = $db->getQuery(true)
			->select('count(*)')
			->from($db->quoteName('#__users'))
			->where(
				array(
					$db->quoteName('params') . ' LIKE ' . $db->quote("%\"{$authDomain}\":\"{$id}\"%"),
					$db->quoteName('params') . ' LIKE ' . $db->quote("%\"{$authDomain}\":{$id}%"),
					$db->quoteName('params') . ' LIKE ' . $db->quote("%\"{$authDomain}\":\"{$name}\"%")
				), 'OR'
			);

		return (int) $db->setQuery($query)->loadResult();
	}

	/**
	 * Method to get a JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  JDatabaseQuery   A JDatabaseQuery object to retrieve the data set.
	 *
	 * @since   2.0
	 */
	public function getListQuery()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$db->escape(
				$this->getState(
					'list.select',
					'a.*'
				)
			)
		);

		$query->from($db->quoteName(SHFactory::getConfig()->get('ldap.table', '#__sh_ldap_config')) . ' AS a');

		// Filter the items over the search string if set.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->quoteName('a.id') . ' = ' . $db->quote((int) substr($search, 3)));
			}
			else
			{
				// Note: * we use an escape so no quote required *
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where(
					'(' . $db->quoteName('name') . ' LIKE ' . $search . ')'
				);
			}
		}

		// Add the list ordering clause.
		$query->order($db->escape($this->getState('list.ordering', 'ordering')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));

		return $query;
	}
}
