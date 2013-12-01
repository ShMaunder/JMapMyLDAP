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

jimport('joomla.application.component.controlleradmin');

/**
 * Hosts controller class for Shldap.
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @since       2.0
 */
class ShldapControllerHosts extends JControllerAdmin
{
	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  object  The model.
	 *
	 * @since   2.0
	 */
	public function getModel($name = 'Host', $prefix = 'ShldapModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Removes an item.
	 *
	 * Overrides JControllerAdmin::delete to check the core.admin permission.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function delete()
	{
		if (!JFactory::getUser()->authorise('core.admin', $this->option))
		{
			JError::raiseError(500, JText::_('JERROR_ALERTNOAUTHOR'));
			jexit();
		}

		return parent::delete();
	}

	/**
	 * Task for setting default LDAP configuraiton.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.0
	 */
	public function setDefault()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$input = JFactory::getApplication()->input;

		$ids = $input->get('cid', null, 'array');

		if (count($ids) !== 1)
		{
			// Cannot have more than one default
			$message = JText::_('COM_SHLDAP_HOSTS_DEFAULT_SINGLE_ROW_ONLY');
			$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false), $message, 'error');

			return false;
		}

		$model = $this->getModel();
		$return = $model->setDefault($ids[0]);

		if ($return === false)
		{
			// Default failed.
			$message = JText::sprintf('COM_SHLDAP_HOSTS_DEFAULT_FAILED', $model->getError());
			$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false), $message, 'error');

			return false;
		}
		else
		{
			// Default succeeded.
			$message = JText::_('COM_SHLDAP_HOSTS_DEFAULT_SUCCESS');
			$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false), $message);

			return true;
		}
	}
}
