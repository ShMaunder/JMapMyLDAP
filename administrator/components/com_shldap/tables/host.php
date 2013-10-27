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

/**
 * Host Table class for Shldap.
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @since       2.0
 */
class ShldapTableHost extends JTable
{
	protected $table = null;

	/**
	* Constructor
	*
	* @param   object  &$db  Database object
	*
	* @since   2.0
	*/
	public function __construct(&$db)
	{
		$this->table = SHFactory::getConfig()->get('ldap.table', '#__sh_ldap_config');

		parent::__construct($this->table, 'id', $db);
	}

	/**
	 * Method to perform sanity checks on the JTable instance properties to ensure
	 * they are safe to store in the database.  Child classes should override this
	 * method to make sure the data they are storing in the database is safe and
	 * as expected before storage.
	 *
	 * @return  boolean  True if the instance is sane and able to be stored in the database.
	 *
	 * @since   2.0
	 */
	public function check()
	{
		$this->name = trim($this->name);

		if (empty($this->name))
		{
			$this->setError('A name or key field is required.');

			return false;
		}

		$db = $this->getDbo();

		// Check for an existing record with the same name
		$result = (int) $db->setQuery(
				$db->getQuery(true)
					->select($db->quoteName('id'))
					->from($db->quoteName($this->table))
					->where($db->quoteName('name') . ' = ' . $db->quote($this->name))
		)->loadResult();

		if ($result && $result != (int) $this->id)
		{
			$this->setError('Duplicate name or key');

			return false;
		}

		return true;
	}

	/**
	 * Method to bind an associative array or object to the JTable instance.This
	 * method only binds properties that are publicly accessible and optionally
	 * takes an array of properties to ignore when binding.
	 *
	 * @param   mixed  $src     An associative array or object to bind to the JTable instance.
	 * @param   mixed  $ignore  An optional array or space separated list of properties to ignore while binding.
	 *
	 * @return  boolean  True on success.
	 *
	 * @link    http://docs.joomla.org/JTable/bind
	 * @since   11.1
	 */
	public function bind($src, $ignore = array())
	{
		// If the source value is an object, get its accessible properties.
		if (is_object($src))
		{
			$src = get_object_vars($src);
		}

		/*
		 * Due to the SQL table not being great, we will hack it so params
		 * are seen as different columns and then merged back on the set.
		 */
		if (isset($src['params']))
		{
			$params = json_decode($src['params']);

			foreach ($params as $k => $v)
			{
				$this->$k = $v;
			}
		}
		else
		{
			// Unset some debug data
			unset($src['debug_username']);
			unset($src['debug_password']);

			$fields = $this->getFields();

			// Ensure this table has the params column
			if (isset($fields['params']))
			{
				$fields['table'] = $this->table;

				// Find the params from the source data
				$params = array_diff_key($src, $fields);

				// Remove the params from the source data
				$src = array_diff($src, $params);

				// Deal with the proxy encryption at table level (little nasty but it works)
				if (isset($params['proxy_encryption'])
					&& $params['proxy_encryption']
					&& (!$this->proxy_encryption || $this->proxy_password != $params['proxy_password']))
				{
					$crypt = SHFactory::getCrypt();

					$params['proxy_password'] = $crypt->encrypt($params['proxy_password']);
				}

				// Add the params back in as a JSON'd object
				$src['params'] = json_encode($params);

				// Remove params from this object as well
				foreach (array_keys($this->getProperties()) as $f)
				{
					if (!in_array($f, array_keys($fields)))
					{
						unset($this->$f);
					}
				}
			}
		}

		return parent::bind($src, $ignore);
	}
}
