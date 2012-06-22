<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Plugins
 * @subpackage  Authentication
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * An LDAP authentication plugin specifically for SHPlatform and SHLdap.
 *
 * @package     Shmanic.Plugins
 * @subpackage  Authentication
 * @since       2.0
 */
class PlgAuthenticationSHLdap extends JPlugin
{
	/**
	 * Temporary constant to clear password. This must be reviewed!
	 *
	 * @var    boolean
	 * @since  2.0
	 */
	const CLEAR_PASSWORD = true;

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
	 * This method handles the Ldap authentication and reports
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

		$response->type = 'LDAP';

		if (!$this->_checkPlatform())
		{
			// Failed to boot the platform
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12601');
			return;
		}

		if (empty($credentials['password']))
		{
			// Blank passwords not allowed to prevent anonymous binding
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12602');
			return;
		}

		// Retrieves all the SQL Ldap configuration hosts
		$configs = SHLdapHelper::getConfigIDs();

		if (!is_array($configs))
		{
			// No Ldap configuration host results found
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12603');

			// Process a error log
			SHLdapHelper::triggerEvent(
				'onError',
				array(12603, JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12603'))
			);

			return;
		}

		// Loop around each host configuration
		foreach (array_keys($configs) as $id)
		{

			// Attempt to instantiate an Ldap extended object with the configuration at record ID
			if ($ldap = SHLdapHelper::getClient($id))
			{

				// Start the LDAP connection procedure
				if ($ldap->connect() !== true)
				{
					// Failed to connect
					$response->status = JAuthentication::STATUS_FAILURE;
					$response->error_message = JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12605');

					$exception = $ldap->getError(null, false);
					if ($exception instanceof SHLdapException)
					{
						// Processes an exception log
						SHLdapHelper::triggerEvent(
							'onException',
							array($exception, 12605, JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12605'))
						);
					}
					else
					{
						// Process a error log
						SHLdapHelper::triggerEvent(
							'onError',
							array(12605, JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12605'))
						);
					}

					// Unset this Ldap client and try the next configuration
					unset($ldap);
					continue;
				}

				/* We will now get the authenticated user's dn.
				 * In this method we are also going to test the
				 * dn against the password. Therefore, if any dn
				 * is returned, it is a successfully authenticated
				 * user.
				 */
				if (!$dn = $ldap->getUserDN($credentials['username'], $credentials['password'], true))
				{
					// Failed to get users Ldap distinguished name
					$response->status = JAuthentication::STATUS_FAILURE;
					$response->error_message = JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12606');

					$exception = $ldap->getError(null, false);
					if ($exception instanceof SHLdapException)
					{
						// Processes an exception log
						SHLdapHelper::triggerEvent(
							'onException',
							array($exception, 12606, JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12606'))
						);
					}
					else
					{
						// Process a error log
						SHLdapHelper::triggerEvent(
							'onError',
							array(12606, JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12606'))
						);
					}

					// Unset this Ldap client and try the next configuration
					$ldap->close();
					unset($ldap);
					continue;
				}

				/* Store the distinguished name of the user and the current
				 * Ldap instance for authorisation (that happens next).
				 */
				$response->set('dn', $dn);
				$response->set('ldap', & $ldap);

				// Successful authentication, report back and say goodbye!
				$response->status			= JAuthentication::STATUS_SUCCESS;
				$response->error_message 	= '';

				return;

			}

		}

		// No configurations could authenticate user
		$response->status = JAuthentication::STATUS_FAILURE;
		$response->error_message = JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12604');

	}

	/**
	* This method handles the Ldap authorisation and reports
	* back to the subject. Also this method is used for SSO.
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

		$response->type = 'LDAP';

		// Check if the DN are present from the onUserAuthenticate() method.
		$dn = $response->get('dn');

		/* If we aren't connected to LDAP yet then we can assume
		 * onUserAuthenticate() hasn't been executed beforehand.
		 * Firstly, we need to connect to LDAP.
		 */
		if ($ldap = $response->get('ldap', false))
		{
			// Must unset the reference to free up a little memory
			unset($response->ldap);
		}
		else
		{
			// TODO: Implement a Ldap connection (this would be for SSO)
			die();
		}

		// TODO: Finish implementation of the DN

		/* If this is SSO, then we need to secondly, get the
		 * current DN of the user
		 */
		if (empty($dn))
		{
			// Get the user DN using the connect username/password
			if(!$dn = $ldap->getUserDN($response->username, null, false)) {
				$response->status = JAuthentication::STATUS_FAILURE;
				$response->error_message = JText::_('JGLOBAL_AUTH_INVALID_PASS');
				//JLogLdapHelper::addErrorEntry(JText::_('JGLOBAL_AUTH_BIND_FAILED'), __CLASS__, 10004);
				$ldap->close();
				return;
			}
		}



		// Let's get the user attributes for this dn.
		$details = $ldap->getUserDetails($dn);
		if ($details === false)
		{
			// Error getting user attributes.
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12611');


			$exception = $ldap->getError(null, false);
			if ($exception instanceof SHLdapException)
			{
				// Processes an exception log
				SHLdapHelper::triggerEvent(
					'onException',
					array($exception, 12611, JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12611'))
				);
			}
			else
			{
				// Process a error log
				SHLdapHelper::triggerEvent(
					'onError',
					array(12611, JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12611'))
				);
			}

			$ldap->close();
			unset($ldap);
			return false;
		}

		if (!is_array($details) || !count($details))
		{
			// No attributes therefore error
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12611');

			// Process a error log
			SHLdapHelper::triggerEvent(
				'onError',
				array(12611, JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12611'))
			);

			$ldap->close();
			unset($ldap);
			return false;
		}

		/*
		 * Set the required Joomla specific user fields with the returned Ldap
		 * user attributes.
		 */
		if (isset($details[$ldap->getUid()][0]))
		{
			$response->username 	= $details[$ldap->getUid()][0];
		}

		if (isset($details[$ldap->getFullname()][0]))
		{
			$response->fullname 	= $details[$ldap->getFullname()][0];
		}

		if (isset($details[$ldap->getEmail()][0]))
		{
			$response->email 		= $details[$ldap->getEmail()][0];
		}

		if (self::CLEAR_PASSWORD)
		{
			// Do not store password in Joomla database  TODO: review this for password plug-in
			$response->set('password_clear', '');
		}

		/*
		 * Store the User Ldap attributes for use in other plug-ins. This saves
		 * having to re-query with the Ldap server.
		 */
		$response->set(SHLdapHelper::ATTRIBUTE_KEY, $details);

		/*
		 * Everything appears to be a success and therefore we shall log the user login
		 * information then report back to the subject.
		 */
		SHLdapHelper::triggerEvent(
			'onInformation',
			array(12612, JText::sprintf('PLG_AUTHENTICATION_SHLDAP_INFO_12612', $response->username))
		);

		$retResponse->status = JAuthentication::STATUS_SUCCESS;

		// Close the Ldap connections and say goodbye.
		$ldap->close();
		unset($ldap);

		return $retResponse;
	}

	/**
	 * If the platform or ldap project has not been loaded via the bootstraps
	 * then load them now.
	 *
	 * @return  boolean  True on successful load or False if failed.
	 *
	 * @since   2.0
	 */
	private function _checkPlatform()
	{
		// Check if the Shmanic platform has already been booted
		if (!defined('SH_PLATFORM'))
		{
			$platform = JPATH_PLATFORM . '/shmanic/bootstrap.php';

			if (!file_exists($platform))
			{
				// Failed to find the Shmanic platform bootstrap
				return false;
			}

			// Shmanic Platform Boot
			if (!include_once $platform)
			{
				// Failed to boot the Shmanic platform
				return false;
			}
		}

		if (!SHBoot('ldap'))
		{
			// Failed to boot the Ldap project
			return false;
		}

		// Everything booted successfully
		return true;
	}

}
