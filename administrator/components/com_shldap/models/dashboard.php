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
jimport('joomla.event.dispatcher');

/**
 * Dashboard model class for Shldap.
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @since       2.0
 */
class ShldapModelDashboard extends JModelList
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
				'extension_id', 'a.extension_id',
				'name', 'a.name',
				'enabled', 'a.enabled',
				'folder', 'a.folder',
				'manifest_cache', 'a.manifest_cache',
				'checked_out_time', 'a.checked_out_time',
				'checked_out', 'a.checked_out_time'
			);
		}

		parent::__construct($config);
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

		// Load the parameters.
		$params = JComponentHelper::getParams('com_shldap');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.name', 'asc');

		// No Pagination (i.e. ALL)
		$this->setState('list.start', 0);
		$this->state->set('list.limit', 0);
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

		$query->from($db->quoteName('#__extensions') . ' AS a');

		$query->where($db->quoteName('a.type') . ' = ' . $db->quote('plugin'))
			->where(
				'(' . $db->quoteName('a.folder') . ' = ' . $db->quote('ldap') .
					' OR ' . $db->quoteName('a.name') . ' = ' . $db->quote('plg_authentication_shadapter') .
					' OR ' . $db->quoteName('a.name') . ' = ' . $db->quote('plg_system_shplatform') . ')'
			);

		// Filter the items over the search string if set.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'extension_id:') === 0)
			{
				$query->where($db->quoteName('a.extension_id') . ' = ' . $db->quote((int) substr($search, 3)));
			}
			else
			{
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where(
					'(' . $db->quoteName('a.name') . ' LIKE ' . $search .
					' OR ' . $db->quoteName('a.folder') . ' LIKE ' . $search . ')'
				);
			}
		}

		// Add the list ordering clause.
		$query->order($db->escape($this->getState('list.ordering', 'a.name')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));

		return $query;
	}

	/**
	 * Gets all the LDAP configs and attempts to bind with each.
	 * This is presented on the dashboard.
	 *
	 * @return  array  Array of objects containing LDAP config information.
	 *
	 * @since   2.0
	 */
	public function getBinds()
	{
		try
		{
			$results = array();

			// Get all the Ldap config IDs and Names
			$ids = SHLdapHelper::getConfigIDs();

			foreach ($ids as $name)
			{
				// Get this specific Ldap configuration based on name
				$config = SHLdapHelper::getConfig($name);

				$result = new stdClass;
				$result->name = $name;
				$result->host = $config->get('host');
				$result->port = $config->get('port');
				$result->connect = false;

				$ldap = new SHLdap($config);

				// Need to process the ldap formatting for the host configuration ready for a fsockopen
				$processed = str_replace(array('ldap://', 'ldaps://'), '', $config->get('host'));

				if ($pos = strpos($processed, chr(32)))
				{
					$processed = substr($processed, 0, $pos);
				}

				// Check if we can open a socket to the LDAP server:port to check the connection
				if (@fsockopen($processed, $config->get('port')))
				{
					$result->connect = true;
				}

				// Attempt to connect and bind and record the result
				if ($ldap->connect())
				{
					if ($ldap->proxyBind())
					{
						$result->bind = true;
					}
				}

				// Lets add this config to our results pool
				$results[] = $result;
			}

			return $results;
		}
		catch (Exception $e)
		{
			// We need to look for a string instead of an array on error
			return $e->getMessage();
		}
	}
}
