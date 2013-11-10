<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Components
 * @subpackage  Shconfig
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

/**
 * Base controller class for Shconfig.
 *
 * @package     Shmanic.Components
 * @subpackage  Shconfig
 * @since       2.0
 */
class ShconfigController extends JControllerLegacy
{
	/**
	 * The default view.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $default_view = 'items';

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return	JController  A JController object to support chaining.
	 *
	 * @since	2.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		// Get the document object.
		$document = JFactory::getDocument();

		// Get the input class
		$input = JFactory::getApplication()->input;

		// Set the default view name and format from the Request.
		$vName	 = $input->get('view', 'default', 'cmd');
		$vFormat = $document->getType();
		$lName	 = $input->get('layout', 'default', 'cmd');
		$id		 = $input->get('id', null, 'cmd');

		if ($vName == 'default')
		{
			$input->set('view', 'settings');
			$input->set('layout', 'base');
			$lName = $input->get('layout', 'default', 'cmd');
			$vName = 'settings';
		}

		// Check for edit form.
		if ($vName == 'item' && $lName == 'edit' && !$this->checkEditId('com_shconfig.edit.item', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(JRoute::_('index.php?option=com_shconfig&view=items', false));

			return false;
		}

		// Add the submenu
		ShconfigHelper::addSubmenu($vName, $lName);

		parent::display($cachable, $urlparams);

		return $this;
	}
}
