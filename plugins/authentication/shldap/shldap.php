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

		if (empty($credentials['password']))
		{
			// Blank passwords not allowed to prevent anonymous binding
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12602');
			return;
		}

		// Check the Shmanic platform has been imported
		if (!$this->_checkPlatform())
		{
			// Failed to boot the platform
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12601');
			return false;
		}

		/*
		 * Attempt to authenticate with Ldap. This method will automatically detect
		 * the correct configuration (if multiple ones are specified) and return a
		 * SHLdap object. If this method returns false, then the authentication was
		 * unsuccessful.
		 */
		if ($ldap = SHLdapHelper::getClient(
			array(
				'username' => $credentials['username'],
				'password' => $credentials['password'],
				'authenticate' => SHLdapHelper::AUTH_USER
			)
		))
		{
			/*
			 * Successful authentication.
			 * Store the distinguished name of the user and the current
			 * Ldap instance for authorisation (that happens next).
			 */
			$response->set('dn', $ldap->getLastUserDN());
			$response->set('ldap', & $ldap);

			// Report back and say Goodbye!
			$response->status			= JAuthentication::STATUS_SUCCESS;
			$response->error_message 	= '';
			return true;
		}
		else
		{
			// No configurations could authenticate user
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::sprintf('PLG_AUTHENTICATION_SHLDAP_ERR_12604', $credentials['username']);
			return;
		}

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

		// Check if some other authentication system is dealing with this request
		if (!empty($response->type) && (strtoupper($response->type) !== 'LDAP'))
		{
			return $retResponse;
		}

		$response->type = 'LDAP';

		// Check if the DN are present from the onUserAuthenticate() method.
		$dn = $response->get('dn');

		/* If we aren't connected to LDAP yet then we can assume
		 * onUserAuthenticate() hasn't been executed beforehand.
		 *
		 * This might be the case when Sigle Sign On just needs to
		 * authorise a user with the Ldap server.
		 */
		if (!$ldap = $response->get('ldap', false))
		{

			// Check the Shmanic platform has been imported
			if (!$this->_checkPlatform())
			{
				// Failed to boot the platform
				$response->status = JAuthentication::STATUS_FAILURE;
				$response->error_message = JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12601');
				return false;
			}

			/*
			 * Attempt to authorise with Ldap. This method will automatically detect
			 * the correct configuration (if multiple ones are specified) and return a
			 * SHLdap object. If this method returns false, then the authorise was
			 * unsuccessful - basically the user was not found or configuration was
			 * bad.
			 */
			if (!$ldap = SHLdapHelper::getClient(
				array('username' => $response->username)
			))
			{
				// Failed to authoriser the user, probably not an Ldap user
				return;
			}

			/*
			 * Successful authorisation.
			 * Update the users distinguished name for laters.
			 */
			$dn = $ldap->getLastUserDN();

		}

		/*
		 * The Ldap client is no longer required and should be unset to free up
		 * some memory.
		 */
		unset($response->ldap);

		// Let's get the user attributes for this dn.
		$details = $ldap->getUserDetails($dn);
		if ($details === false)
		{
			// Error getting user attributes.
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12611');

			// Process a error log
			SHLog::add(JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12611'), 12611, JLog::ERROR, 'ldap');

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
			SHLog::add(JText::_('PLG_AUTHENTICATION_SHLDAP_ERR_12611'), 12611, JLog::ERROR, 'ldap');

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
		SHLog::add(JText::sprintf('PLG_AUTHENTICATION_SHLDAP_INFO_12612', $response->username), 12612, JLog::INFO, 'ldap');

		$retResponse->status = JAuthentication::STATUS_SUCCESS;

		// Close the Ldap connections and say goodbye.
		$ldap->close();
		unset($ldap);

		return $retResponse;
	}

	/**
	 * If the platform or ldap project has not been imported
	 * then import them now.
	 *
	 * @return  boolean  True on successful load or False if failed.
	 *
	 * @since   2.0
	 */
	private function _checkPlatform()
	{
		// Check if the Shmanic platform has already been imported
		if (!defined('SH_PLATFORM'))
		{
			$platform = JPATH_PLATFORM . '/shmanic/import.php';

			if (!file_exists($platform))
			{
				// Failed to find the Shmanic platform import
				return false;
			}

			// Shmanic Platform import
			if (!include_once $platform)
			{
				// Failed to import the Shmanic platform
				return false;
			}
		}

		if (!SHImport('ldap'))
		{
			// Failed to import the Ldap project
			return false;
		}

		// Everything imported successfully
		return true;
	}

}
