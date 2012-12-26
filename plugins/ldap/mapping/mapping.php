<?php
/**
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @package     Shmanic.Plugin
 * @subpackage  System.LdapMapping
 *
 * @copyright	Copyright (C) 2011 Shaun Maunder. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * LDAP Group Mapping Plugin.
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
	 * @param   object  &$subject  The object to observe.
	 * @param   array   $config    An array that holds the plugin configuration.
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
	 * Called during an ldap login.
	 *
	 * Checks to ensure the onlogin parameter is true then calls
	 * the on sync method.
	 *
	 * @param   JUser  &$instance  A JUser object for the authenticating user.
	 * @param   array  $options    Array holding options.
	 *
	 * @return  boolean  False to cancel login
	 *
	 * @since   2.0
	 */
	public function onUserLogin(&$instance, $options = array())
	{
		if ($this->params->get('onlogin'))
		{
			$this->onLdapSync($instance, $options);
		}

		// Even if it did fail, we don't want to cancel the logon
		return true;
	}

	/**
	 * Called during a ldap synchronisation.
	 *
	 * Checks to ensure that required variables are set before calling the main
	 * do mapping library routine.
	 *
	 * @param   JUser  &$instance  A JUser object for the authenticating user.
	 * @param   array  $options    Array holding options.
	 *
	 * @return  boolean  True on success
	 *
	 * @since   2.0
	 */
	public function onLdapSync(&$instance, $options = array())
	{
		// Gather the user adapter
		$username = $instance->username;
		$adapter = SHFactory::getUserAdapter($username);

		if (!$this->mapper->doMap($instance, $adapter))
		{
			// Failed to commit mapping to user
			//$this->_reportError($this->mapper->getError());
			return false;
		}

		return true;
	}

	/**
	 * Called before a user LDAP read to gather extra user ldap attribute keys
	 * required for this plugin to function correctly.
	 *
	 * @param   SHUserAdapter  $adapter  The current user adapter.
	 * @param   array          $options  Array holding options.
	 *
	 * @return  array  Array of attributes
	 *
	 * @since   2.0
	 */
	public function onLdapBeforeRead($adapter, $options = array())
	{
		return $this->mapper->getAttributes($adapter, $options);
	}

	/**
	 * Called after a user LDAP read to gather extra ldap attribute values that
	 * were not included in the initial read.
	 *
	 * @param   SHUserAdapter  $adapter      The current user adapter.
	 * @param   array          &$attributes  Discovered User Ldap attribute keys=>values.
	 * @param   array          $options      Array holding options.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onLdapAfterRead($adapter, &$attributes, $options = array())
	{
		$this->mapper->getData($adapter, $attributes, $options);
	}
}
