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

	protected $includeHelper = null;

	protected $domain = null;

	protected $helper = null;

	protected $usernameKey = 'username';

	protected $passwordKey = 'password_clear';

	protected $emailKey = 'email';

	protected $nameKey = 'name';

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
		$this->includeHelper = $this->params->get('include_helper', false);
		$this->domain = $this->params->get('default_domain', null);
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
	 * Method is called before user data is stored in the database.
	 *
	 * Setups the Adapter in isNew mode and sets some attributes based on template.
	 *
	 * @param   array    $user   Holds the old user data.
	 * @param   boolean  $isNew  True if a new user is stored.
	 * @param   array    $new    Holds the new user data.
	 *
	 * @return  boolean  Cancels the save if False.
	 *
	 * @since   2.0
	 */
	public function onUserBeforeSave($user, $isNew, $new)
	{
		if (!$isNew)
		{
			// Plugin doesnt care about existing users
			return;
		}

		// Grab the XML for defining base attributes
		try
		{
			// Kill any previous adapters for this user (though this plugin should be ordered first!!)
			SHFactory::$adapters[$new['username']] = null;

			$dn = null;
			$attributes = array();

			// Include the helper file only if it exists
			if ($this->includeHelper)
			{
				if ($this->helper = $this->_getHelperFile())
				{
					// Calculate the correct domain to insert user on
					if (method_exists($this->helper, 'getDomain'))
					{
						$this->domain = $this->helper->getDomain($new);
					}
				}
			}

			$fields = $this->_getXMLFields();

			if ($fields->getAttribute('usernameKey'))
			{
				$this->usernameKey = $fields->getAttribute('usernameKey');
			}

			if ($fields->getAttribute('passwordKey'))
			{
				$this->passwordKey = $fields->getAttribute('passwordKey');
			}

			if ($fields->getAttribute('emailKey'))
			{
				$this->emailKey = $fields->getAttribute('emailKey');
			}

			if ($fields->getAttribute('nameKey'))
			{
				$this->nameKey = $fields->getAttribute('nameKey');
			}

			foreach ($fields as $key => $value)
			{
				if ($key == 'dn')
				{
					// The dn which isn't an array
					$attribute =& $dn;
				}
				else
				{
					// Standard multi-array attributes
					$name = $value->getAttribute('name');

					if (!isset($attributes[$name]))
					{
						$attributes[$name] = array();
					}

					$attribute =& $attributes[$name][];
				}

				// Get the value of the dn/attribute using a variety of types
				switch ($value->getAttribute('type'))
				{
					case 'string':
						$attribute = (string) $value;
						break;

					case 'eval':
						$attribute = $this->_execEval((string) $value, $new);
						break;
				}

			}

			$credentials = array(
				'username' => $new[$this->usernameKey],
				'password' => $new[$this->passwordKey],
				'domain' => $this->domain,
				'dn' => $dn
			);

			// Create an adapter and save core attributes
			$adapter = SHFactory::getUserAdapter($credentials, null, array('isNew' => true));

			// Add core Joomla fields
			$adapter->setAttributes(
				array(
					'username' => $new[$this->usernameKey],
					'password' => $new[$this->passwordKey],
					'fullname' => $new[$this->nameKey],
					'email' => $new[$this->emailKey]
				)
			);

			// Add extra fields based from the template xml
			$adapter->setAttributes($attributes);

			return true;
		}
		catch (Exception $e)
		{
			SHLog::add($e, 12801, JLog::ERROR, 'ldap');
			return false;
		}
	}

	/**
	 * Create the user to LDAP (after onUserBeforeCreate but before onUserAfterCreate).
	 *
	 * @param   array  $user  Populated LDAP attributes.
	 *
	 * @return  boolean  Cancels the user creation to Joomla if False.
	 *
	 * @since   2.0
	 */
	public function onUserCreation($user)
	{
		try
		{
			$username = $user[$this->usernameKey];
			$adapter = SHFactory::getUserAdapter($username);
			$adapter->create();

			SHLog::add(JText::sprintf('PLG_LDAP_CREATION_INFO_12821', $username), 12821, JLog::INFO, 'ldap');
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
		if ($isNew && !$success && $this->params->get('onfail_delete', false))
		{
			try
			{
				$username = $user[$this->usernameKey];
				$adapter = SHFactory::getUserAdapter($username);
				$adapter->delete();
				SHLog::add(JTest::sprintf('PLG_LDAP_CREATION_INFO_12826', $username), 12826, JLog::INFO, 'ldap');
			}
			catch (Exception $e)
			{
				SHLog::add($e, 12803, JLog::ERROR, 'ldap');
			}
		}
	}

	/**
	 * Evaluates the eval string to get the correct ldap attribute.
	 * User attributes are also available.
	 *
	 * @param   string  $eval  Eval statement to be executed.
	 * @param   array   $user  Holds the new user attributes array.
	 *
	 * @return  string  LDAP attribute value.
	 *
	 * @since   2.0
	 */
	private function _execEval($eval, $user)
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
	* @return  XMLElement  Required XML fields.
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

		// Attempt to load the XML file.
		if ($xml = JFactory::getXML($file, true))
		{
			// Get only the required header - i.e. domain
			if ($xml = $xml->xpath("/templates/template[@domain='{$this->domain}']"))
			{
				return $xml[0];
			}
		}

		// Something is invalid about the XML file
		throw new RuntimeException(JText::sprintf('PLG_LDAP_CREATION_ERR_12812', $file), 12812);
	}
}
