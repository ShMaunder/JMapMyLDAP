<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Form.Rules
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Form rule for testing the current password against ldap.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Form.Rules
 * @since       2.0
 */
class SHFormRuleLdappassword extends JFormRule
{
	/**
	 * Method to test the ldap password against the current ldap password
	 *
	 * @param   SimpleXMLElement  &$element  The SimpleXMLElement object representing the <field /> tag for the form field object.
	 * @param   mixed             $value     The form field value to validate.
	 * @param   string            $group     The field name group control value. This acts as as an array container for the field.
	 *                                       For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                       full field name would end up being "bar[foo]".
	 * @param   JRegistry         &$input    An optional JRegistry object with the entire data set to validate against the entire form.
	 * @param   object            &$form     The form object for which the field is being tested.
	 *
	 * @return  boolean  True if the value is valid, false otherwise.
	 *
	 * @since   11.1
	 * @throws  JException on invalid rule.
	 */
	public function test(&$element, $value, $group = null, &$input = null, &$form = null)
	{
		if (($form instanceof JForm) && ($form->getValue('id')))
		{
			try
			{
				// Gets the username from the user id
				$db = JFactory::getDbo();
				$query = $db->getQuery(true);

				$query->select($db->quoteName('username'))
					->from($db->quoteName('#__users'))
					->where($db->quoteName('id') . ' = ' . $db->quote((int) $form->getValue('id')));

				$db->setQuery($query)->execute();

				if ($username = $db->loadResult())
				{
					// Put username and password together for authenticating with Ldap
					$auth = array(
						'username' => $username,
						'password' => $value
					);

					// This is a valid username so lets check it against ldap
					return SHFactory::getUserAdapter($auth)->getId(true) ? true : false;
				}
			}
			catch (Exception $e)
			{
				// We ignore the exception for now
				return false;
			}
		}
	}
}
