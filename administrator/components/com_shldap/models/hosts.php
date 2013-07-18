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

jimport('joomla.event.dispatcher');

/**
 * Hosts model class for Shldap.
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @since       2.0
 */
class ShldapModelHosts extends JModelLegacy
{
	public function getItems()
	{
		try
		{
			$results = array();

			// Get all the Ldap config IDs and Names
			$ids = SHLdapHelper::getConfigIDs();

			foreach ($ids as $id => $name)
			{
				// Get this specific Ldap configuration based on name
				$config = SHLdapHelper::getConfig($name);

				$result = new stdClass;
				$result->id = $id;
				$result->name = $name;
				$result->host = $config->get('host');
				$result->port = $config->get('port');
				$result->attribute_uid = $config->get('ldap_uid');
				$result->attribute_email = $config->get('ldap_email');
				$result->attribute_name = $config->get('ldap_fullname');
				$result->users = $this->getCountDomain($result->id, $result->name);

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
					$db->quoteName('params') . ' LIKE ' . $db->quote("%\"{$authDomain}\":\"{$name}\"%")
				), 'OR'
			);

		return (int) $db->setQuery($query)->loadResult();
	}
}
