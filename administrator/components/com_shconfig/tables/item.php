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

/**
 * Item Table class for Shconfig.
 *
 * @package     Shmanic.Components
 * @subpackage  Shconfig
 * @since       2.0
 */
class ShconfigTableItem extends JTable
{
	/**
	 * Constructor
	 *
	 * @param   object  &$db  Database object
	 *
	 * @since   2.0
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__sh_config', 'id', $db);
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
		$this->value = trim($this->value);

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
					->from($db->quoteName('#__sh_config'))
					->where($db->quoteName('name') . ' = ' . $db->quote($this->name))
		)->loadResult();

		if ($result && $result != (int) $this->id)
		{
			$this->setError('Duplicate name or key');

			return false;
		}

		return true;
	}
}
