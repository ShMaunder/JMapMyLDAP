<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Plugins
 * @subpackage  Authentication
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * A generic user adapter authentication plugin specifically for SHAdapters.
 *
 * @package     Shmanic.Plugins
 * @subpackage  Authentication
 * @since       2.0
 */
class PlgAuthenticationSHAdapter extends JPlugin
{
	/**
	 * Default authentication type.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const AUTH_TYPE = 'SHADAPTER';

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
	}

	/**
	 * This method handles the user adapter authentication and reports
	 * back to the subject.
	 *
	 * @param   array   $credentials  Array holding the user credentials
	 * @param   array   $options      Array of extra options
	 * @param   object  &$response    Authentication response object
	 *
	 * @return  boolean  Authentication result
	 *
	 * @since   2.0
	 */
	public function onUserAuthenticate($credentials, $options, &$response)
	{
		$response->type = self::AUTH_TYPE;

		if (empty($credentials['password']))
		{
			// Blank passwords not allowed to prevent anonymous binding
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('PLG_AUTHENTICATION_SHADAPTER_ERR_12602');

			return;
		}

		// Check the Shmanic platform has been imported
		if (!$this->_checkPlatform())
		{
			// Failed to import the platform
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('PLG_AUTHENTICATION_SHADAPTER_ERR_12601');

			return false;
		}

		// Check if a Domain is present which represents a configuration ID
		if ($domain = SHUtilArrayhelper::getValue($options, 'domain', null, 'cmd'))
		{
			// Valid configuration ID (normally for SSO)
			$credentials['domain'] = $domain;
		}
		else
		{
			// See if we can get the domain directly from the input
			if ($domain = JFactory::getApplication()->input->get('login_domain', null, 'cmd'))
			{
				$credentials['domain'] = $domain;
			}
		}

		/*
		 * Attempt to authenticate with user adapter. This method will automatically detect
		 * the correct configuration (if multiple ones are specified) and return a
		 * SHUserAdapter* object. If the getid returns empty or it throws an error then
		 * authentication was unsuccessful.
		 */
		try
		{
			// Setup new user adapter
			$adapter = SHFactory::getUserAdapter($credentials);

			// Get the authenticating user dn
			$id = $adapter->getId(true);

			// Get the required attributes (this gets core attributes + plugin based)
			if (!empty($id) && $attributes = $adapter->getAttributes())
			{
				// Report back with success
				$response->status			= JAuthentication::STATUS_SUCCESS;
				$response->error_message 	= '';

				return true;
			}

			// Unable to find user or attributes missing (an error should get thrown already)
			throw new Exception(JText::_('JGLOBAL_AUTH_NO_USER'), 999);
		}
		catch (Exception $e)
		{
			// Configuration or authentication failure
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('JGLOBAL_AUTH_NO_USER');

			// Process a error log even if it could be a simple incorrect user
			SHLog::add($e, 12621, JLog::ERROR, 'auth');

			return;
		}
	}

	/**
	 * This method handles the user adapter authorisation and reports
	 * back to the subject. This method is also used for single sign on.
	 *
	 * There is no custom logging in the authentication.
	 *
	 * @param   array  $response  Authentication response object from onUserAuthenticate()
	 * @param   array  $options   Array of extra options
	 *
	 * @return  JAuthenticationResponse  Authentication response object
	 *
	 * @since   2.0
	 */
	public function onUserAuthorisation($response, $options = array())
	{
		// Create a new authentication response
		$retResponse = new JAuthenticationResponse;

		// Check if some other authentication system is dealing with this request
		if (!empty($response->type) && (strtoupper($response->type) !== self::AUTH_TYPE))
		{
			return $retResponse;
		}

		// Check the Shmanic platform has been imported
		if (!$this->_checkPlatform())
		{
			// Failed to import the platform
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('PLG_AUTHENTICATION_SHADAPTER_ERR_12601');

			return false;
		}

		$response->type = self::AUTH_TYPE;

		/*
		 * Attempt to authorise with User Adapter. This method will automatically detect
		 * the correct configuration (if multiple ones are specified) and return a
		 * SHUserAdapter object. If this method returns false, then the authorise was
		 * unsuccessful - basically the user was not found or configuration was
		 * bad.
		 */
		try
		{
			// Setup user adapter injecting the domain from SSO if specified
			$credentials = array('username' => $response->username);

			if (isset($options['domain']))
			{
				$credentials['domain'] = $options['domain'];
			}

			$adapter = SHFactory::getUserAdapter($credentials);

			// Get the authorising user dn
			$id = $adapter->getId(false);
		}
		catch (Exception $e)
		{
			// Configuration or authorisation failure
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('JGLOBAL_AUTH_NO_USER');

			return;
		}

		try
		{
			// Let's get the user attributes
			$attributes = $adapter->getAttributes();

			if (!is_array($attributes) || !count($attributes))
			{
				// No attributes therefore error
				throw new Exception(JText::_('PLG_AUTHENTICATION_SHADAPTER_ERR_12611'), 12611);
			}
		}
		catch (Exception $e)
		{
			// Error getting user attributes.
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('PLG_AUTHENTICATION_SHADAPTER_ERR_12611');

			// Process a error log
			SHLog::add($e, 12622, JLog::ERROR, 'auth');

			return false;
		}

		// Set the required Joomla specific user fields with the returned User Adapter Attributes
		$response->username 	= $adapter->getUid();
		$response->fullname 	= $adapter->getFullname();
		$response->email 		= $adapter->getEmail();

		// The adapter type needs to be set before returning the response
		$response->type 		= $adapter->getType();

		if (SHFactory::getConfig()->get('user.nullpassword'))
		{
			// Do not store password in Joomla database
			$response->password_clear = '';
		}

		/*
		 * Everything appears to be a success and therefore we shall log the user login
		 * then report back to the subject.
		 */
		SHLog::add(JText::sprintf('PLG_AUTHENTICATION_SHADAPTER_INFO_12612', $response->username), 12612, JLog::INFO, 'auth');

		$retResponse->status = JAuthentication::STATUS_SUCCESS;

		unset($adapter);

		return $retResponse;
	}

	/**
	 * Imports the SHPlatform if not already loaded.
	 *
	 * @return  boolean  True on successful load.
	 *
	 * @since   2.0
	 */
	private function _checkPlatform()
	{
		// Check if the SHPlatform has already been imported
		if (!defined('SHPATH_PLATFORM'))
		{
			$platform = JPATH_PLATFORM . '/shmanic/import.php';

			if (!file_exists($platform))
			{
				// Failed to find the SHPlatform import
				return false;
			}

			// SHPlatform import
			if (!include_once $platform)
			{
				// Failed to import the SHPlatform
				return false;
			}
		}

		// Everything imported successfully
		return true;
	}
}
