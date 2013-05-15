<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Plugin
 * @subpackage  Ldap.Deletion
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.plugin.plugin');

/**
 * LDAP User Deletion Plugin.
 *
 * @package     Shmanic.Plugin
 * @subpackage  Ldap.Deletion
 * @since       2.0
 */
class PlgLdapDeletion extends JPlugin
{
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
	 * Deletes the user from LDAP if the deletion in the Joomla database was successful.
	 *
	 * Method is called after user data is deleted from the database.
	 *
	 * @param   array    $user     Holds the user data.
	 * @param   boolean  $success  True if user was successfully deleted from the database.
	 * @param   string   $msg      An error message.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onUserAfterDelete($user, $success, $msg)
	{
		if ($success)
		{
			try
			{
				$username = $user['username'];
				SHLog::add(JText::sprintf('PLG_LDAP_DELETION_DEBUG_12905', $username), 12905, JLog::DEBUG, 'ldap');

				// Pick up the user and delete it using the User Adapter
				$adapter = SHFactory::getUserAdapter($username);
				$adapter->delete();

				SHLog::add(JText::sprintf('PLG_LDAP_DELETION_INFO_12908', $username), 12908, JLog::INFO, 'ldap');
			}
			catch (Exception $e)
			{
				SHLog::add($e, 12901, JLog::ERROR, 'ldap');
			}
		}
	}
}
