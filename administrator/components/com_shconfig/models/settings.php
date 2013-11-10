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

jimport('joomla.application.component.modelform');

/**
 * Settings model class for Shconfig.
 *
 * @package     Shmanic.Components
 * @subpackage  Shconfig
 * @since       2.0
 */
class ShconfigModelSettings extends JModelForm
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $text_prefix = 'COM_SHCONFIG';

	/**
	 * Filters the platform import into json.
	 *
	 * @param   string  $value  Raw input.
	 *
	 * @return  string  Json string
	 *
	 * @since   2.0
	 */
	public static function filterImport($value)
	{
		if (!empty($value))
		{
			return json_encode($value);
		}

		return '';
	}

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
		$input = new JInput;
		$name = $input->get('layout', 'base', 'cmd');

		$form = $this->loadForm('com_shconfig.settings', $name, array('control' => 'jform', 'load_data' => $loadData));

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
		$data = JFactory::getApplication()->getUserState('com_shconfig.edit.settings.data', array());

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
			if ($name == 'platform:enable')
			{
				$db = JFactory::getDbo();

				$db->setQuery(
					$db->getQuery(true)
						->update($db->quoteName('#__extensions'))
						->set($db->quoteName('enabled') . ' = ' . $db->quote($value))
						->where($db->quoteName('name') . ' = ' . $db->quote('plg_system_shplatform'), 'AND')
						->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
				)->execute();

				continue;
			}

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

		// Clean the cache.
		$this->cleanCache();

		return true;
	}
}
