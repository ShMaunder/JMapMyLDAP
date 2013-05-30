<?php
/**
 * PHP Version 5.3
 *
 * ============== Original based on JAuthTools ===============
 * http://joomlacode.org/gf/project/jauthtools
 * Sam Moffatt <sam.moffatt@toowoombarc.qld.gov.au>
 * Toowoomba Regional Council Information Management Department
 * (C) 2008 Toowoomba Regional Council/Sam Moffatt
 * ============================================================
 *
 * @package     Shmanic.Libraries
 * @subpackage  SSO
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.user.authentication');
JLoader::register('JAuthenticationResponse', JPATH_PLATFORM . '/joomla/user/authentication.php');

/**
 * Provides a small pluggable framework for Single Sign On. This class
 * was orginally forked from JAuthTools.
 *
 * @package     Shmanic.Libraries
 * @subpackage  SSO
 * @since       2.0
 */
class SHSso extends JDispatcher
{
	/**
	 * Name of the method for SSO user detection.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const DETECT_METHOD_NAME = 'detectRemoteUser';

	/**
	 * Constructor
	 *
	 * @param   string  $group  Plugin type for SSO.
	 *
	 * @since   2.0
	 */
	public function __construct($group = 'sso')
	{
		// Attempt to import the plugins using this class instance as a dispatcher
		JPluginHelper::importPlugin($group, null, true, & $this);
	}

	/**
	 * Detect the remote SSO user by looping through all SSO
	 * plugins. Once a detection is found, it is put into
	 * the options parameter array and method is returned as
	 * true. Uses the same plug-in group as JAuthTools SSO.
	 *
	 * @return  Array|False  Array containing username on success or False on failure.
	 *
	 * @since   1.0
	 */
	public function detect()
	{
		$args = array();

		// Event to trigger for detection
		$event = strtolower(self::DETECT_METHOD_NAME);

		// Check if any plugins are attached to the event.
		if (!isset($this->_methods[$event]) || empty($this->_methods[$event]))
		{
			// No Plugins Associated To Event
			return false;
		}

		// Loop through all plugins having a method matching our event
		foreach ($this->_methods[$event] as $key)
		{
			// Check if the plugin is present.
			if (!isset($this->_observers[$key]))
			{
				continue;
			}

			// Check if parameters exist for this observer/plugin
			if (property_exists($this->_observers[$key], 'params'))
			{
				$params = $this->_observers[$key]->params;

				// Get the rule and list from the plug-in parameters
				$ipRule = $params->get('ip_rule', false);
				$ipList = $params->get('ip_list', false);

				// Check that both the rule and list have been set
				if ($ipRule !== false && $ipList !== false)
				{
					// Get the IP address of this client
					jimport('joomla.application.input');
					$input = new JInput($_SERVER);
					$myIp = $input->get('REMOTE_ADDR', false, 'string');

					// Split the list into newline entries
					$ranges = preg_split('/\r\n|\n|\r/', $ipList);

					if (!SHSsoHelper::doIPCheck($myIp, $ranges, $ipRule))
					{
						// IP address denies this plug-in from executing
						continue;
					}
				}
			}

			// Fire the event for an object based observer.
			if (is_object($this->_observers[$key]))
			{
				$args['event'] = $event;
				$value = $this->_observers[$key]->update($args);
			}

			// Fire the event for a function based observer.
			elseif (is_array($this->_observers[$key]))
			{
				$value = call_user_func_array($this->_observers[$key]['handler'], $args);
			}

			if (isset($value) && $value)
			{
				// Check if the detection has been successful for this plug-in
				if (is_string($value) || (is_array($value) && isset($value['username'])))
				{
					if (is_string($value))
					{
						// Convert the string to an array
						$value = array('username' => $value);
					}

					// Store the detection plug-in name
					$value['sso'] = get_class($this->_observers[$key]);

					// We have a detection result
					return $value;
				}
				else
				{
					// Error: invalid plug-in response
					SHLog::add(JText::sprintf('LIB_SHSSO_ERR_15061', get_class($this->_observers[$key])), 15061, JLog::ERROR, 'sso');

					// Try another plug-in.
					continue;
				}
			}
		}

		// No detection result found.
		return false;
	}

	/**
	 * If a detection has been successful then it will try to
	 * authenticate with the onUserAuthorisation method
	 * in any of the authentication plugins.
	 *
	 * @param   string  $username  String containing detected username.
	 * @param   array   $options   An array containing action, autoregister and detection name.
	 *
	 * @return  JAuthenticationResponse  Response from the authorise.
	 *
	 * @since   1.0
	 */
	public function authorise($username, $options)
	{
		$response = new JAuthenticationResponse;

		$response->username = $username;

		// Check for user attributes and set them into the authentication response if they exist
		if (isset($options['attributes']))
		{
			if (isset($options['attributes']['email']))
			{
				$response->email = $options['attributes']['email'];
			}

			if (isset($options['attributes']['fullname']))
			{
				$response->fullname = $options['attributes']['fullname'];
			}
		}

		// Import the authentication and user plug-ins in case they havent already
		// J! Pull Request: https://github.com/joomla/joomla-platform/pull/1305
		JPluginHelper::importPlugin('user');
		JPluginHelper::importPlugin('authentication');

		// We need to authorise our username to an authentication plugin
		$authorisations = JAuthentication::authorise($response, $options);

		foreach ($authorisations as $authorisation)
		{
			if ($authorisation->status === JAuthentication::STATUS_SUCCESS)
			{
				// This username is authorised to use the system
				$response->status = JAuthentication::STATUS_SUCCESS;

				return $response;
			}
		}

		// No authorises found
		$response->status = JAuthentication::STATUS_FAILURE;

		return $response;
	}

	/**
	 * Uses the Joomla! database to authorise. This can be used if
	 * there are no authentication plug-ins available to authorise the
	 * SSO user.
	 *
	 * @param   string  $username  String containing detected username.
	 * @param   array   $options   An array containing action, autoregister and detection name.
	 *
	 * @return  JAuthenticationResponse    Response from the Joomla authorise.
	 *
	 * @since   2.0
	 */
	public function jAuthorise($username, $options)
	{
		$response = new JAuthenticationResponse;

		$response->username = $username;

		// Check for user attributes and set them into the authentication response if they exist
		if (isset($options['attributes']))
		{
			if (isset($options['attributes']['email']))
			{
				$response->email = $options['attributes']['email'];
			}

			if (isset($options['attributes']['fullname']))
			{
				$response->fullname = $options['attributes']['fullname'];
			}
		}

		$response->type = 'Joomla';

		// Initialise variables.
		$conditions = '';

		// Get a database object
		$db		= JFactory::getDbo();
		$query	= $db->getQuery(true);

		$query->select($db->quoteName('id'))
			->select($db->quoteName('block'))
			->from($db->quoteName('#__users'))
			->where($db->quoteName('username') . ' = ' . $db->quote($username));

		$db->setQuery($query);
		$result = $db->loadObject();

		// Check if the user exists in the database
		if ($result)
		{
			// Check if the user has been blocked
			if ($result->block)
			{
				$response->status = JAuthentication::STATUS_DENIED;
			}
			else
			{
				// Set the email and fullname to the current one if one doesnt exist
				$user = JUser::getInstance($result->id);
				$response->email = empty($response->email) ? $user->email : $response->email;
				$response->fullname = empty($response->fullname) ? $user->name : $response->fullname;

				// Set the user defined languages
				if (JFactory::getApplication()->isAdmin())
				{
					$response->language = $user->getParam('admin_language');
				}
				else
				{
					$response->language = $user->getParam('language');
				}

				// User exists in database and isnt blocked
				$response->status = JAuthentication::STATUS_SUCCESS;
				$response->error_message = '';
			}
		}
		else
		{
			// User doesn't exist and is unknown
			$response->status = JAuthentication::STATUS_UNKNOWN;
		}

		return $response;
	}

	/**
	 * Attempts to login a user via Single Sign On.
	 * Only if a username is detected can a login been attempted.
	 *
	 * @param   array  $options  An optional array of options to override config settings.
	 *
	 * @return  boolean  True on successful login or False on fail.
	 *
	 * @since   1.0
	 */
	public function login($options = array())
	{
		$config = SHFactory::getConfig();

		// Get the SSO username and optional details from the plug-ins
		if (!$detection = $this->detect())
		{
			return false;
		}

		// Set the action if its currently unset
		if (!isset($options['action']))
		{
			$options['action'] = JFactory::getApplication()->isAdmin() ?
				'core.login.admin' : 'core.login.site';
		}

		// Set the autoregister if its currently unset
		if (!isset($options['autoregister']))
		{
			$options['autoregister'] = $config->get('sso.autoregister', false);
		}

		// Set the doauthorise if its currently unset
		if (!isset($options['doauthorise']))
		{
			$options['doauthorise'] = $config->get('sso.doauthorise', 0);
		}

		// Determine if the detection has extra attributes attached
		if (is_array($detection))
		{
			$username = $detection['username'];

			// Check if do authorised is based on the plug-in
			if ((int) $options['doauthorise'] === 0)
			{
				if (isset($detection['doauthorise']))
				{
					// Set the do authorised to the plug-in option
					$options['doauthorise'] = ((boolean) $detection['doauthorise']) ? 1 : -1;
				}
				else
				{
					// Default the doauthorise to true
					$options['doauthorise'] = 1;
				}
			}

			// Check for a domain
			if (isset($detection['domain']))
			{
				$options['domain'] = $detection['domain'];
			}

			// Check for any extra user attributes gathered from SSO
			if (isset($detection['attributes']))
			{
				$options['attributes'] = $detection['attributes'];
			}
		}

		/*
		 * Authorising will call on onUserAuthorise() to attempt
		 * to authorise the detected SSO user. If this
		 * is disabled, then it will attempt to authorise with the
		 * Joomla database. If autoregister is turned on then
		 * it'll attempt to create the user in the Joomla database.
		 */
		if ($options['doauthorise'] !== -1)
		{
			// Do authentication plug-in authorisation
			$response = $this->authorise($username, $options);
		}
		else
		{
			// Do Joomla database authorisation
			$response = $this->jAuthorise($username, $options);
		}

		// Check the response status for invalid status'
		if (!((JAuthentication::STATUS_SUCCESS + JAuthentication::STATUS_UNKNOWN) & $response->status))
		{
			// We can only process success and unknown status'
			return false;
		}
		elseif (($response->status === JAuthentication::STATUS_UNKNOWN) && !$options['autoregister'])
		{
			// The user is unknown and there is no autoregister - fail.
			return false;
		}
		elseif (empty($response->email))
		{
			// There is not email set for this user - fail.
			return false;
		}

		/*
		 * Username has been authorised. We can now proceed with the
		 * standard Joomla log-on by calling the onUserLogin event.
		 */
		JPluginHelper::importPlugin('user');

		// Fixes an undefined notice from the Joomla user plug-in
		$response->def('password_clear');

		$results = JFactory::getApplication()->triggerEvent(
			'onUserLogin',
			array((array) $response,
			$options)
		);

		// Check if any of the events failed
		if (in_array(false, $results, true))
		{
			return false;
		}

		// Everything successful - user should be logged on.
		return true;
	}
}
