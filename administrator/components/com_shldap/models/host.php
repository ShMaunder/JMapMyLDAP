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
			'username' => $form->getValue('debug_username'),
			'password' => $form->getValue('debug_password')
		);
	}
}
