<?php
/**
 * @version     $Id:$
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @package     Shmanic.Plugin
 * @subpackage  System.LdapMapping
 *
 * @copyright	Copyright (C) 2011 Shaun Maunder. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * LDAP Group Mapping Plugin
 *
 * @package     Shmanic.Plugin
 * @subpackage  System.LdapMapping
 * @since       2.0
 */
class plgLdapMapping extends JPlugin
{
	/**
	* An object to a instance of LdapMapping
	*
	* @var    SHLdapMapping
	* @since  2.0
	*/
	protected $mapper = null;

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

		$this->mapper = new SHLdapMapping($this->params->toArray());
	}

	/**
	 * Called during a ldap login. If this method returns false,
	 * then the whole login is cancelled.
	 *
	 * Checks to ensure the onlogin parameter is true then calls
	 * the on sync method.
	 *
	 * @param   object  &$instance  A JUser object for the authenticating user
	 * @param   array   $user       The auth response including LDAP attributes
	 * @param   array   $options    Array holding options
	 *
	 * @return  boolean  False to cancel login
	 *
	 * @since   2.0
	 */
	public function onUserLogin(&$instance, $user, $options = array())
	{
		if ($this->params->get('onlogin'))
		{
			$this->onLdapSync($instance, $user, $options);
		}

		// Even if it did fail, we don't want to cancel the logon
		return true;
	}

	/**
	 * Called during a ldap synchronisation.
	 *
	 * Checks to ensure that required variables are set before
	 * calling the main do mapping library routine.
	 *
	 * @param   object  &$instance  A JUser object for the authenticating user
	 * @param   array   $user       The auth response including LDAP attributes
	 * @param   array   $options    Array holding options
	 *
	 * @return  boolean  True on success
	 *
	 * @since   2.0
	 */
	public function onLdapSync(&$instance, $user, $options = array())
	{
		if (isset($user[SHLdapHelper::ATTRIBUTE_KEY]))
		{
			if (!$this->mapper->doMap($instance, $user[SHLdapHelper::ATTRIBUTE_KEY]))
			{
				// Failed to commit mapping to user
				$this->_reportError($this->mapper->getError());
				return false;
			}
		}
		else
		{
			// Error: no user attributes to process
			$this->_reportError('No attributes to process');
			return false;
		}

		return true;
	}

	/**
	 * Called just before a user LDAP read to gather
	 * extra user ldap attributes required for this plugin.
	 *
	 * @param  JLDAP2  $ldap
	 * @param  array   $options
	 *
	 * @return  array  Array of attributes required for this plug-in
	 * @since   2.0
	 */
	public function onLdapBeforeRead(&$ldap, $options = array())
	{
		return $this->mapper->getAttributes($ldap, $options);
	}

	/**
	 * Called during an active LDAP connection after the
	 * initial user LDAP read for any extra object/attributes that
	 * were not returned from the initial LDAP read.
	 *
	 * @param  JLDAP2  $ldap
	 * @param  array   $attribute values of ldap read
	 * @param  array   $options (user=>JUser)
	 *
	 * @return  void
	 * @since   2.0
	 */
	public function onLdapAfterRead(&$ldap, &$details, $options = array())
	{
		$this->mapper->getData($ldap, $details, $options);
	}

	/**
	 * Reports an error to the screen and log. If debug mode is on
	 * then it displays the specific error on screen, if debug mode
	 * is off then it displays a generic error.
	 *
	 * @param  string  $exception  The error
	 *
	 * @return  void
	 * @since   1.0
	 */
	protected function _reportError($exception = null)
	{
		//TODO: An error routine...
	}

}
