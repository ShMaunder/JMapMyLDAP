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

jimport('joomla.application.component.modeladmin');

/**
 * Host model class for Shldap.
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @since       2.0
 */
class ShldapModelHost extends JModelAdmin
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $text_prefix = 'COM_SHLDAP';

	protected $formData = array();

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
	public function getTable($type = 'Host', $prefix = 'ShldapTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form. [optional]
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not. [optional]
	 *
	 * @return  JForm  A JForm object on success, false on failure
	 *
	 * @since   2.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		if ($loadData && !empty($data))
		{
			// Due to Joomla limitations with injecting data in loadForm, we have to resort to this less than ideal way
			$this->formData = $data;
		}

		// Get the form.
		$form = $this->loadForm('com_shldap.host', 'host', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   2.0
	 */
	protected function loadFormData()
	{
		if (!empty($this->formData))
		{
			// Though it works its pretty nasty tbh, not the best way of all time
			return $this->formData;
		}

		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_shldap.edit.host.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Gets the SHLdap object from the Debug data.
	 *
	 * @param   JForm  $form  Form to process.
	 *
	 * @return  SHLdap
	 *
	 * @since   2.0
	 */
	public function getLdapObject($form = null)
	{
		if (is_null($form))
		{
			$form = $this->getForm();
		}

		$config = array();

		// Build the LDAP configuration array
		foreach ($form->getFieldset('host') as $field)
		{
			if (!$field->hidden)
			{
				$config[$field->fieldname] = $field->value;
			}
		}

		return new SHLdap($config);
	}

	/**
	 * Returns the debug parameters from the form.
	 *
	 * @param   JForm  $form  Form to process.
	 *
	 * @return  array  Debug data.
	 *
	 * @since   2.0
	 */
	public function getDebugParams($form = null)
	{
		if (is_null($form))
		{
			$form = $this->getForm();
		}

		return array(
			'full' => $form->getValue('debug_full'),
			'username' => $form->getValue('debug_username'),
			'password' => $form->getValue('debug_password')
		);
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
		// Initialise variables;
		$table = $this->getTable();
		$key = $table->getKeyName();
		$pk = (!empty($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');
		$isNew = true;

		// Unset some debug data
		unset($data['debug_full']);
		unset($data['debug_username']);
		unset($data['debug_password']);

		// Allow an exception to be thrown.
		try
		{
			// Load the row if saving an existing record.
			if ($pk > 0)
			{
				$table->load($pk);
				$isNew = false;
			}

			// Sets a default proxy encryption flag in the table
			$table->proxy_encryption = isset($table->proxy_encryption) ? $table->proxy_encryption : false;

			// Deal with the proxy encryption if the password has changed
			if (isset($data['proxy_encryption'])
				&& $data['proxy_encryption']
				&& (!$table->proxy_encryption || $table->proxy_password != $data['proxy_password']))
			{
				$crypt = SHFactory::getCrypt();

				$data['proxy_password'] = $crypt->encrypt($data['proxy_password']);
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return parent::save($data);
	}

	/**
	 * Method to set default in shconfig.
	 *
	 * @param   integer  $pk  The ID of the primary key to set default
	 *
	 * @return  mixed  False on failure or error, true on success, null if the $pk is empty (no items selected).
	 *
	 * @since   11.1
	 */
	public function setDefault($pk)
	{
		// Initialise variables.
		$table = $this->getTable();

		$table->reset();

		if ($this->canEditState($table) && $table->load($pk) && $this->checkout($pk))
		{
			$name = $table->name;

			$db = JFactory::getDbo();

			// We want to use the platform table here - show lets use the platform's component
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_shconfig/tables');

			$configTable = JTable::getInstance('Item', 'ShconfigTable', array());

			$configTable->load(array('name' => 'ldap:defaultconfig'));

			$configTable->bind(array('name' => 'ldap:defaultconfig', 'value' => $name));

			if ($configTable->check())
			{
				$configTable->store();
			}

			$this->checkin($pk);

			$this->cleanCache();

			return true;
		}

		return false;
	}
}
