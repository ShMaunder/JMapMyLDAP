<?php

abstract class SHUserHelper
{
	/**
	 * This method returns a user object. If options['autoregister'] is true,
	 * and if the user doesn't exist, then it'll be created.
	 *
	 * @param   array  $user     Holds the user data.
	 * @param   array  $options  Array holding options (remember, autoregister, group).
	 *
	 * @return  JUser|False  A JUser object containing the user or False on error.
	 *
	 * @since   1.0
	 */
	public static function &getUser(array $user, $options = array())
	{
		$instance = JUser::getInstance();

		// Check if the user already exists in the database
		if ($id = intval(JUserHelper::getUserId($user['username'])))
		{
			$instance->load($id);
			return $instance;
		}

		jimport('joomla.application.component.helper');
		$config	= JComponentHelper::getParams('com_users', true);

		if ($config === false)
		{
			// Check if there is a default set in the SHConfig
			$config = SHFactory::getConfig();
			$defaultUserGroup = $config->get('user.defaultgroup', 2);
		}
		else
		{
			// Respect Joomla's default user group
			$defaultUserGroup = $config->get('new_usertype', 2);
		}

		// Setup the user fields for this new user
		$instance->set('id', 0);
		$instance->set('name', $user['fullname']);
		$instance->set('username', $user['username']);
		$instance->set('password_clear', $user['password_clear']);
		$instance->set('email', $user['email']);
		$instance->set('usertype', 'depreciated');
		$instance->set('groups', array($defaultUserGroup));

		// If autoregister is set, register the user
		$autoregister = isset($options['autoregister']) ? $options['autoregister'] : true;

		if ($autoregister)
		{
			if (!$instance->save())
			{
				// Failed to save the user to the database
				SHLog::add(JText::sprintf('LIB_SHLDAP_ERR_10501', $instance->getError()), 10501, JLog::ERROR);
				return false;
			}
		}
		else
		{
			// We don't want to proceed if autoregister is not enabled
			SHLog::add(JText::_('LIB_SHLDAP_ERR_10502'), 10502, JLog::ERROR);
			return false;
		}

		return $instance;
	}

}
