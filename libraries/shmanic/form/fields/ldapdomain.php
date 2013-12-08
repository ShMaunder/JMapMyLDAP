<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Form.Fields
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('list');

/**
 * Form field for retrieving LDAP configurations (aka Domains).
 *
 * @package     Shmanic.Libraries
 * @subpackage  Form.Fields
 * @since       2.0
 */
class SHFormFieldLdapdomain extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $type = 'Ldapdomain';

	/**
	 * Method to get the Ldap domains field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   2.0
	 */
	protected function getOptions()
	{
		// Initialize variables.
		$options = array();

		// Get the Ldap Domains
		$configs = SHLdapHelper::getConfigIDs();

		// Present the Ldap domains in the list
		foreach ($configs as $id => $config)
		{
			$options[] = JHtml::_('select.option', $config, $config, 'value', 'text');
		}

		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
