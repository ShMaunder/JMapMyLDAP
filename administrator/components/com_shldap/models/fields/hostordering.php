<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('ordering');

/**
 * Hostordering form field for Shldap.
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @since       2.0
 */
class JFormFieldHostordering extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since   1.6
	 */
	protected $type = 'Hostordering';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string	The field input markup.
	 *
	 * @since   1.6
	 */
	protected function getInput()
	{
		$html = array();
		$attr = '';

		// Initialize some field attributes.
		$attr .= $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';
		$attr .= ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
		$attr .= $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';

		// Initialize JavaScript field attributes.
		$attr .= $this->element['onchange'] ? ' onchange="' . (string) $this->element['onchange'] . '"' : '';

		// Get some field values from the form.
		$id = (int) $this->form->getValue('id');
		$db = JFactory::getDbo();

		$table = SHFactory::getConfig()->get('ldap:table', '#__sh_ldap_config');

		$query = $db->getQuery(true);

		// Build the query for the ordering list
		$query->select('ordering AS value')->select('name AS text')->select($db->quoteName('id'))
			->from($db->quoteName($table))
			->order($db->quoteName('ordering'));

		if ((string) $this->element['readonly'] == 'true')
		{
			// Create a read-only list (no name) with a hidden input to store the value.
			$html[] = JHtml::_('list.ordering', '', (string) $query, trim($attr), $this->value, $id ? 0 : 1);
			$html[] = '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '"/>';
		}
		else
		{
			// Create a regular list.
			$html[] = JHtml::_('list.ordering', $this->name, (string) $query, trim($attr), $this->value, $id ? 0 : 1);
		}

		return implode($html);
	}
}
