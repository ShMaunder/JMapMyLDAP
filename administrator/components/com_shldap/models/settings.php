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

jimport('joomla.application.component.modelform');

/**
 * Settings model class for Shldap.
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @since       2.0
 */
class ShldapModelSettings extends JModelForm
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $text_prefix = 'COM_SHLDAP';

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission for the component.
	 *
	 * @since   2.0
	 */
	protected function canDelete($record)
	{
		$user = JFactory::getUser();

		return $user->authorise('core.admin', 'com_shconfig');
	}

	/**
	 * Returns a JTable object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate. [optional]
	 * @param   string  $prefix  A prefix for the table class name. [optional]
	 * @param   array   $config  Configuration array for model. [optional]
	 *
	 * @return  JTable  A database object
	 *
	 * @since   2.0
	 */
	public function getTable($type = 'Item', $prefix = 'ShconfigTable', $config = array())
	{
		// We want to use the platform table here - show lets use the platform's component
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_shconfig/tables');

		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form. [optional]
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not. [optional]
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 *
	 * @since   2.0
	 */
	public function getForm($data = array(), $loadData = false)
	{
		$form = $this->loadForm('com_shldap.settings', 'settings', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		$this->bindFormData($form);

		return $form;
	}

	/**
	 * Method to inject data into the form.
	 *
	 * @param   JForm  $form  Form headers without data.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	protected function bindFormData($form)
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_shldap.edit.settings.data', array());

		if (empty($data))
		{
			// We need to get the items from the database
			$db = $this->getDbo();
			$query = $db->getQuery(true);

			// Select the required fields from the table.
			$query->select($db->quoteName('name'))->select($db->quoteName('value'))
				->from($db->quoteName('#__sh_config'));

			// Build the where clause for specifying only required settings from the table
			$whereClause = array();

			foreach ($form->getFieldset() as $field)
			{
				$whereClause[] = $db->quoteName('name') . ' = ' . $db->quote($field->fieldname);
			}

			$query->where($whereClause, 'OR');
			$data = $db->setQuery($query)->loadAssocList('name', 'value');
		}

		$form->bind($data);
	}

		/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since   11.1
	 */
	public function save($data)
	{
		foreach ($data as $name => $value)
		{
			$isNew = false;

			$table = $this->getTable();

			if ($table->load(array('name' => $name)) === false)
			{
				$isNew = true;
			}

			if (!$table->bind(array('name' => $name, 'value' => $value)) || !$table->check() || !$table->store())
			{
				// Error binding, checking or saving
				$this->setError($table->getError());

				return false;
			}
		}

		// Check the enable LDAP
		if (isset($data['ldap:include']))
		{
			$db = JFactory::getDbo();
			$result = $db->setQuery(
				$db->getQuery(true)
					->select($db->quoteName('value'))
					->from($db->quoteName('#__sh_config'))
					->where($db->quoteName('name') . ' = ' . $db->quote('platform:import'))
			)->loadResult();

			$isNew = is_null($result);

			if (!$result)
			{
				// The import value is blank in the database
				$result = '{}';
			}

			$decoded = array_values((array) json_decode($result));

			$change = false;

			if ($data['ldap:include'])
			{
				if (!in_array('ldap', $decoded))
				{
					// Lets include the import
					$decoded[] = 'ldap';
					$change = true;
				}
			}
			else
			{
				if (($pos = array_search('ldap', $decoded)) !== false)
				{
					// Lets remove the import
					unset($decoded[$pos]);
					$change = true;
				}
			}

			if ($change)
			{
				$encoded = json_encode(array_values($decoded));

				// Add the encoded value back to the database
				if ($isNew)
				{
					// Do an insert as its new
					$db->setQuery(
						$db->getQuery(true)
							->insert($db->quoteName('#__sh_config'))
							->columns(array($db->quoteName('name'), $db->quoteName('value')))
							->values($db->quote('platform:import') . ', ' . $db->quote($encoded))
					)->execute();
				}
				else
				{
					// Do an update as it currently exists
					$db->setQuery(
						$db->getQuery(true)
							->update($db->quoteName('#__sh_config'))
							->set($db->quoteName('value') . ' = ' . $db->quote($encoded))
							->where($db->quoteName('name') . ' = ' . $db->quote('platform:import'))
					)->execute();
				}
			}
		}

		// Clean the cache.
		$this->cleanCache();

		return true;
	}
}
