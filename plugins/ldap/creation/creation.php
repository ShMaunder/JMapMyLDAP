<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Plugin
 * @subpackage  Ldap.Creation
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.plugin.plugin');

/**
 * LDAP User Creation Plugin
 *
 * @package     Shmanic.Plugin
 * @subpackage  Ldap.Creation
 * @since       2.0
 */
class PlgLdapCreation extends JPlugin
{
	protected $templateName = null;

	protected $templateBase = null;

	protected $domain = null;

	protected $helper = null;

	/**
	 * This fields only populates after user creation.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $username = null;

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 *
	 * @since  2.0
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();

		$this->templateName = $this->params->get('template_name', 'default');
		$this->templateBase = $this->params->get('template_base', JPATH_PLUGINS . '/ldap/creation/templates');
		$this->domain = SHFactory::getConfig()->get('ldap.defaultconfig');
	}

	/**
	 * Asks whether any plugins can handle LDAP user creation.
	 *
	 * @return  boolean
	 *
	 * @since   2.0
	 */
	public function askUserCreation()
	{
		// Broadcast that it can create users
		return true;
	}

	/**
	 * Create the user to LDAP (before onUserBeforeSave).
	 *
	 * @param   array  $user  Populated LDAP attributes from the form.
	 *
	 * @return  boolean  Cancels the user creation to Joomla if False.
	 *
	 * @since   2.0
	 */
	public function onUserCreation($user)
	{
		try
		{
			$dn = null;
			$attributes = array();

			// Populate defaults for the mandatory
			$mandatory = array(
				'username' => SHUtilArrayhelper::getValue($user, 'username'),
				'password' => SHUtilArrayhelper::getValue($user, 'password_clear'),
				'email' => SHUtilArrayhelper::getValue($user, 'email'),
				'name' => SHUtilArrayhelper::getValue($user, 'name')
			);

			// Include the helper file only if it exists
			if ($this->helper = $this->_getHelperFile())
			{
				// Calculate the correct domain to insert user on
				if (method_exists($this->helper, 'getDomain'))
				{
					$this->domain = $this->helper->getDomain($user);
				}
			}

			$fields = $this->_getXMLFields();

			// Loops around everything in the template XML
			foreach ($fields as $key => $value)
			{
				// Convert the value to a string
				$stringValue = (string) $value;

				// Convert the key to a string
				$stringKey = (string) $key;

				$name = (string) $value->attributes()->name;

				if ($stringKey == 'dn')
				{
					$name = 'mandatory' . $stringKey;

					// The dn which isn't an array
					$attribute =& $dn;
				}
				elseif ($stringKey == 'username' || $stringKey == 'password' || $stringKey == 'email' || $stringKey == 'name')
				{
					$name = 'mandatory' . $stringKey;

					// The mandatory fields use something a bit different
					$attribute =& $mandatory[$stringKey];
				}
				else
				{
					// Standard multi-array attributes
					if (!isset($attributes[$name]))
					{
						$attributes[$name] = array();
					}

					$attribute =& $attributes[$name][];
				}

				// Get the value of the attributes using a variety of types
				switch ((string) $value->attributes()->type)
				{
					case 'form':
						$attribute = $user[$stringValue];
						break;

					case 'string':
						$attribute = $stringValue;
						break;

					case 'eval':
						$attribute = $this->_execEval($stringValue, $user);
						break;

					case 'helper':
						$method = 'get' . (string) $name;
						$attribute = $this->helper->{$method}($user);
						break;
				}
			}

			$credentials = array(
				'username' => $mandatory['username'],
				'password' => $mandatory['password'],
				'domain' => $this->domain,
				'dn' => $dn
			);

			// Kill any previous adapters for this user (though this plugin should be ordered first!!)
			SHFactory::$adapters[strtolower($user['username'])] = null;

			// Create an adapter and save core attributes
			$adapter = SHFactory::getUserAdapter($credentials, 'ldap', array('isNew' => true));

			// Add core Joomla fields
			$adapter->setAttributes(
				array(
					'username' => $mandatory['username'],
					'password' => $mandatory['password'],
					'fullname' => $mandatory['name'],
					'email' => $mandatory['email']
				)
			);

			// Add extra fields based from the template xml
			$adapter->setAttributes($attributes);

			// Create the LDAP user now
			SHLdapHelper::commitChanges($adapter, true, true);
			SHLog::add(JText::sprintf('PLG_LDAP_CREATION_INFO_12821', $mandatory['username']), 12821, JLog::INFO, 'ldap');

			$this->username = $mandatory['username'];

			/*
			 * Call onAfterCreation method in the helper which can be used to run
			 * external scripts (such as creating home directories) and/or adding
			 * groups to the new user.
			 *
			 * This method will be passed:
			 * - $user        Values directly from the user registration form.
			 * - $attributes  The attributes passed to the LDAP server for creation.
			 * - $adapter     The user adapter object.
			 */
			if ($this->helper && method_exists($this->helper, 'onAfterCreation'))
			{
				$this->helper->onAfterCreation($user, $attributes, $adapter);
			}

			return true;
		}
		catch (Exception $e)
		{
			SHLog::add($e, 12802, JLog::ERROR, 'ldap');

			return false;
		}
	}

	/**
	 * Method is called after user data is stored in the database.
	 *
	 * Deletes the user if they're new and Joomla user creation failed.
	 *
	 * @param   array    $user     Holds the new user data.
	 * @param   boolean  $isNew    True if a new user has been stored.
	 * @param   boolean  $success  True if user was successfully stored in the database.
	 * @param   string   $msg      An error message.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onUserAfterSave($user, $isNew, $success, $msg)
	{
		if ($isNew && !$success && $this->params->get('onfail_delete', false) && $this->username)
		{
			try
			{
				// Check the session to ensure this user was created successfully last time
				if (JFactory::getSession()->get('creation', null, 'ldap') == $this->username)
				{
					$adapter = SHFactory::getUserAdapter($this->username);
					$adapter->delete();
					SHLog::add(JTest::sprintf('PLG_LDAP_CREATION_INFO_12826', $this->username), 12826, JLog::INFO, 'ldap');
				}
			}
			catch (Exception $e)
			{
				SHLog::add($e, 12803, JLog::ERROR, 'ldap');
			}

			$this->username = null;
		}
	}

	/**
	 * Evaluates the eval string to get the correct ldap attribute.
	 * User attributes are also available.
	 *
	 * @param   string  $eval  Eval statement to be executed.
	 * @param   array   $form  Holds the new user attributes array (from the form).
	 *
	 * @return  string  LDAP attribute value.
	 *
	 * @since   2.0
	 */
	private function _execEval($eval, $form)
	{
		return eval($eval);
	}

	/**
	 * Instantiates the attribute creation helper file.
	 *
	 * @return  object|false  Helper class object or False on failure.
	 *
	 * @since   2.0
	 */
	private function _getHelperFile()
	{
		$file = $this->templateBase . '/' . $this->templateName . '.php';

		if (file_exists($file))
		{
			include_once $file;

			$class = "LdapCreation_{$this->templateName}";

			if (class_exists($class))
			{
				return new $class;
			}
		}

		// Failed to instantiate helper file
		return false;
	}

	/**
	 * Gets the XML for the creation template.
	 *
	 * @return  SimpleXMLElement  Required XML fields.
	 *
	 * @since   2.0
	 * @throws  RuntimeException
	 */
	private function _getXMLFields()
	{
		$file = $this->templateBase . '/' . $this->templateName . '.xml';

		if (!file_exists($file))
		{
			// XML file doesn't exist
			throw new RuntimeException(JText::sprintf('PLG_LDAP_CREATION_ERR_12811', $file), 12811);
		}

		// Disable libxml errors and allow to fetch error information as needed
		libxml_use_internal_errors(true);

		// Attempt to load the XML file.
		if ($xml = simplexml_load_file($file))
		{
			// Get only the required header - i.e. domain
			if ($xml = $xml->xpath("/templates/template[@domain='{$this->domain}']"))
			{
				return $xml[0];
			}

			// XML loaded correctly but could not load the path - could be the domain
			throw new RuntimeException(JText::sprintf('PLG_LDAP_CREATION_ERR_12813', $file, $this->domain), 12813);
		}

		// Something is invalid about the XML file
		throw new RuntimeException(JText::sprintf('PLG_LDAP_CREATION_ERR_12812', $file), 12812);
	}
}
